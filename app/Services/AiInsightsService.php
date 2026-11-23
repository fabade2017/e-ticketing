<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use App\Shared\Reporting\SuiteInsights;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AiInsightsService
{
    public static function generate(array $opts = []): array
    {
        $reportUser = array_key_exists('report_user', $opts) ? $opts['report_user'] : ($opts['user'] ?? null);
        $report = self::worldClassReport('SITREP', $opts['from'] ?? null, $opts['to'] ?? null, $reportUser);
        $user = $opts['user'] ?? null;
        $report['suite'] = $user instanceof User
            ? self::suiteSummary($user, (bool) ($opts['include_all'] ?? false))
            : [];

        return $report;
    }

    public static function suiteSummary(User $user, bool $includeAll): array
    {
        return SuiteInsights::build($user, $includeAll);
    }

    public static function worldClassReport(string $type = 'SITREP', $from = null, $to = null, ?User $user = null): array
    {
        return self::generateInternal($type, $from, $to, $user);
    }

    private static function generateInternal(string $type, $from, $to, ?User $user = null): array
    {
        $base = Ticket::query();
        if ($user) {
            $base->visibleTo($user);
        }
        if ($from) {
            $base->where('created_at', '>=', Carbon::parse($from)->startOfDay());
        }
        if ($to) {
            $base->where('created_at', '<=', Carbon::parse($to)->endOfDay());
        }

        $driver = DB::connection()->getDriverName();
        $avgCloseExpression = match ($driver) {
            'pgsql' => 'EXTRACT(EPOCH FROM (closed_at - created_at))/3600',
            'mysql' => 'TIMESTAMPDIFF(SECOND, created_at, closed_at)/3600',
            default => '(julianday(closed_at)-julianday(created_at))*24',
        };
        $dateExpression = match ($driver) {
            'pgsql' => "to_char(created_at, 'YYYY-MM-DD')",
            'mysql' => "DATE_FORMAT(created_at, '%Y-%m-%d')",
            default => "strftime('%Y-%m-%d', created_at)",
        };
        $now = now()->toDateTimeString();

        $summary = (clone $base)->toBase()
            ->selectRaw(
                "count(*) as total,
                count(case when priority = 'urgent' and status in ('open', 'in_progress') then 1 end) as urgent_open,
                count(case when sla_due_at < ? and status not in ('resolved', 'closed') then 1 end) as overdue,
                count(case when escalated_at is not null then 1 end) as escalated,
                avg(case when closed_at is not null then {$avgCloseExpression} end) as avg_close_hours",
                [$now]
            )
            ->first();

        $total = (int) $summary->total;
        $urgentOpen = (int) $summary->urgent_open;
        $overdue = (int) $summary->overdue;
        $escalated = (int) $summary->escalated;
        $avgCloseHours = $summary->avg_close_hours === null ? null : (float) $summary->avg_close_hours;

        $grouped = (clone $base)->toBase()
            ->selectRaw('status, category, priority, department_id, unit, count(*) as c')
            ->groupBy('status', 'category', 'priority', 'department_id', 'unit')
            ->get();

        $byStatus = [];
        $byCategory = [];
        $byPriority = [];
        $departmentCounts = [];
        $unitCounts = [];

        foreach ($grouped as $row) {
            $count = (int) $row->c;
            $status = (string) $row->status;
            $category = (string) $row->category;
            $priority = (string) $row->priority;
            $byStatus[$status] = ($byStatus[$status] ?? 0) + $count;
            $byCategory[$category] = ($byCategory[$category] ?? 0) + $count;
            $byPriority[$priority] = ($byPriority[$priority] ?? 0) + $count;

            if ($row->department_id !== null) {
                $departmentId = (int) $row->department_id;
                $departmentCounts[$departmentId] = ($departmentCounts[$departmentId] ?? 0) + $count;
            }
            if ($row->unit !== null && $row->unit !== '') {
                $unit = (string) $row->unit;
                $unitCounts[$unit] = ($unitCounts[$unit] ?? 0) + $count;
            }
        }

        $departmentNames = Department::whereIn('id', array_keys($departmentCounts))->pluck('name', 'id');
        $byDept = collect($departmentCounts)
            ->map(fn (int $count, int $id): array => ['dept' => $departmentNames[$id] ?? 'Unassigned', 'count' => $count])
            ->sortByDesc('count')
            ->values()
            ->all();
        arsort($unitCounts);
        $byUnit = collect($unitCounts)
            ->take(5)
            ->map(fn (int $count, string $unit): array => ['unit' => $unit, 'count' => $count])
            ->values()
            ->all();

        $trend = (clone $base)->toBase()
            ->selectRaw("{$dateExpression} as d, count(*) as c")
            ->groupBy('d')
            ->orderBy('d')
            ->limit(14)
            ->get()
            ->map(fn ($row): array => ['date' => $row->d, 'count' => (int) $row->c])
            ->all();

        $userQuery = User::query();
        $scopedToRequester = $user && ! $user->hasRole('SuperAdmin');
        if ($scopedToRequester) {
            if ($user->department_id) {
                $userQuery->where('department_id', $user->department_id);
            } elseif ($user->unit) {
                $userQuery->where('unit', $user->unit);
            } else {
                $userQuery->where('id', $user->id);
            }
        }

        $userSummary = (clone $userQuery)->toBase()
            ->selectRaw('count(*) as total, count(case when face_embedding is not null then 1 end) as face')
            ->first();
        $officerCount = (int) $userSummary->total;
        $faceCoverage = $officerCount ? round(((int) $userSummary->face / $officerCount) * 100) : 0;

        if ($scopedToRequester && $user->department_id) {
            $deptCount = 1;
        } elseif ($scopedToRequester) {
            $deptCount = Department::whereIn('id', User::whereNotNull('department_id')->select('department_id'))->distinct()->count();
        } else {
            $deptCount = Department::count();
        }

        $insights = [];
        $readiness = $overdue > 0 ? 'AMBER' : ($urgentOpen > 0 ? 'AMBER' : 'GREEN');
        $insights[] = "🛡️ Readiness: {$readiness} — ".($readiness === 'GREEN' ? 'Ops nominal' : "{$overdue} overdue, {$urgentOpen} urgent open");
        $openCount = $byStatus['open'] ?? 0;
        if ($total > 0 && $openCount > $total * 0.5) {
            $insights[] = '⚠️ Backlog: '.$openCount."/{$total} open (".round(($openCount / $total) * 100).'%) — assign Support per border sector.';
        }
        if (($byCategory['medical'] ?? 0) > 0) {
            $insights[] = '🏥 Medical: '.($byCategory['medical']).' — NATO triage: SLA 4h, MEDEVAC priority.';
        }
        if ($urgentOpen > 0) {
            $insights[] = "🚨 Urgent: {$urgentOpen} open — predicted SLA breach ".($total ? round($overdue / $total * 100) : 0).'%. Escalate to Sector Cmd.';
        }
        if ($overdue > 0) {
            $insights[] = "⏰ Overdue: {$overdue} past SLA — auto-escalated {$escalated}. Immediate action.";
        }
        if ($faceCoverage < 80) {
            $insights[] = "👤 Biometrics: {$faceCoverage}% enrolled — target 95% for offline auth (NIST 800-63).";
        }
        if ($avgCloseHours) {
            $insights[] = '📊 Avg closure: '.round($avgCloseHours, 1).'h — benchmark <24h (world-class <12h).';
        }
        $insights[] = '📈 Forecast: +'.max(1, intval($total * 0.25)).' tickets /7d (25% trend) — pre-stage logistics.';
        $topUnit = $byUnit[0] ?? null;
        if ($topUnit) {
            $insights[] = "📍 Hotspot: {$topUnit['unit']} ({$topUnit['count']}) — recommend J4 audit & QRF.";
        }
        $insights[] = '🏢 Depts: '.count($byDept).' active — '.implode(', ', array_map(fn ($d) => "{$d['dept']}:{$d['count']}", array_slice($byDept, 0, 3)));

        return [
            'stats' => compact('total', 'byStatus', 'byCategory', 'byPriority', 'byDept', 'byUnit', 'trend', 'urgentOpen', 'overdue', 'escalated', 'avgCloseHours', 'faceCoverage', 'deptCount', 'officerCount'),
            'insights' => $insights,
            'generated_at' => now()->toIso8601String(),
            'readiness' => $readiness,
            'range' => ['from' => $from, 'to' => $to],
        ];
    }

    public static function worldClassReportOld(string $type = 'SITREP'): array
    {
        return self::generateInternal($type, null, null);
    }
}
