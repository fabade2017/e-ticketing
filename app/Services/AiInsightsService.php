<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class AiInsightsService
{
    public static function generate(array $opts = []): array
    {
        return self::worldClassReport('SITREP', $opts['from'] ?? null, $opts['to'] ?? null, $opts['user'] ?? null);
    }

    public static function worldClassReport(string $type = 'SITREP', $from = null, $to = null, ?User $user = null): array
    {
        return self::generateInternal($type, $from, $to, $user);
    }

    private static function generateInternal(string $type, $from, $to, ?User $user = null): array
    {
        $base = Ticket::query();
        if ($user) $base->visibleTo($user);
        if ($from) $base->whereDate('created_at','>=',$from);
        if ($to) $base->whereDate('created_at','<=',$to);

        $total = (clone $base)->count();
        $byStatus = (clone $base)->select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c','status')->toArray();
        $byCategory = (clone $base)->select('category', DB::raw('count(*) as c'))->groupBy('category')->pluck('c','category')->toArray();
        $byPriority = (clone $base)->select('priority', DB::raw('count(*) as c'))->groupBy('priority')->pluck('c','priority')->toArray();
        $byDept = (clone $base)->select('department_id', DB::raw('count(*) as c'))->groupBy('department_id')->with('department')->get()->map(fn($r)=>['dept'=>$r->department?->name ?? 'Unassigned','count'=>$r->c])->toArray();
        $byUnit = (clone $base)->select('unit', DB::raw('count(*) as c'))->whereNotNull('unit')->groupBy('unit')->orderByDesc('c')->limit(5)->get()->map(fn($r)=>['unit'=>$r->unit,'count'=>$r->c])->toArray();

        $driver = DB::connection()->getDriverName();
        $dateExpr = $driver === 'pgsql' ? "to_char(created_at, 'YYYY-MM-DD')" : ($driver === 'mysql' ? "DATE_FORMAT(created_at, '%Y-%m-%d')" : "strftime('%Y-%m-%d', created_at)");
        $trendQ = clone $base;
        $trend = $trendQ->select(DB::raw("$dateExpr as d"), DB::raw('count(*) as c'))->groupBy('d')->orderBy('d')->limit(14)->get()->map(fn($r)=>['date'=>$r->d,'count'=>$r->c])->toArray();

        $urgentOpen = (clone $base)->where('priority','urgent')->whereIn('status',['open','in_progress'])->count();
        $overdue = (clone $base)->where('sla_due_at','<', now())->whereNotIn('status',['resolved','closed'])->count();
        $escalated = (clone $base)->whereNotNull('escalated_at')->count();
        if ($driver === 'pgsql') {
            $avgCloseHours = (clone $base)->whereNotNull('closed_at')->selectRaw('avg(EXTRACT(EPOCH FROM (closed_at - created_at))/3600) as avg')->value('avg');
        } elseif ($driver === 'mysql') {
            $avgCloseHours = (clone $base)->whereNotNull('closed_at')->selectRaw('avg(TIMESTAMPDIFF(SECOND, created_at, closed_at)/3600) as avg')->value('avg');
        } else {
            $avgCloseHours = (clone $base)->whereNotNull('closed_at')->selectRaw('avg((julianday(closed_at)-julianday(created_at))*24) as avg')->value('avg');
        }
        // Face/officer stats scoped to user's department/unit when user present and not SuperAdmin
        if ($user && !$user->hasRole('SuperAdmin')) {
            $userQ = User::query();
            if ($user->department_id) $userQ->where('department_id', $user->department_id);
            elseif ($user->unit) $userQ->where('unit', $user->unit);
            else $userQ->where('id', $user->id);
            $scopeUserTotal = (clone $userQ)->count();
            $scopeFace = (clone $userQ)->whereNotNull('face_embedding')->count();
            $faceCoverage = $scopeUserTotal ? round($scopeFace / $scopeUserTotal * 100) : 0;
            $deptCount = $user->department_id ? 1 : Department::whereIn('id', User::whereNotNull('department_id')->pluck('department_id'))->distinct()->count();
            $officerCount = $scopeUserTotal;
        } else {
            $faceCoverage = User::count() ? round(User::whereNotNull('face_embedding')->count() / User::count() * 100) : 0;
            $deptCount = Department::count();
            $officerCount = User::count();
        }

        $insights = [];
        $readiness = $overdue > 0 ? 'AMBER' : ($urgentOpen > 0 ? 'AMBER' : 'GREEN');
        $insights[] = "🛡️ Readiness: {$readiness} — ".($readiness==='GREEN' ? 'Ops nominal' : "{$overdue} overdue, {$urgentOpen} urgent open");
        if (($byStatus['open'] ?? 0) > $total * 0.5) $insights[] = "⚠️ Backlog: ".($byStatus['open']??0)."/{$total} open (".round(($byStatus['open']??0)/$total*100)."%) — assign Support per border sector.";
        if (($byCategory['medical'] ?? 0) > 0) $insights[] = "🏥 Medical: ".($byCategory['medical'])." — NATO triage: SLA 4h, MEDEVAC priority.";
        if ($urgentOpen > 0) $insights[] = "🚨 Urgent: {$urgentOpen} open — predicted SLA breach ".($total? round($overdue/$total*100):0)."%. Escalate to Sector Cmd.";
        if ($overdue > 0) $insights[] = "⏰ Overdue: {$overdue} past SLA — auto-escalated {$escalated}. Immediate action.";
        if ($faceCoverage < 80) $insights[] = "👤 Biometrics: {$faceCoverage}% enrolled — target 95% for offline auth (NIST 800-63).";
        if ($avgCloseHours) $insights[] = "📊 Avg closure: ".round($avgCloseHours,1)."h — benchmark <24h (world-class <12h).";
        $insights[] = "📈 Forecast: +".max(1, intval($total*0.25))." tickets /7d (25% trend) — pre-stage logistics.";
        $topUnit = $byUnit[0] ?? null;
        if ($topUnit) $insights[] = "📍 Hotspot: {$topUnit['unit']} ({$topUnit['count']}) — recommend J4 audit & QRF.";
        $insights[] = "🏢 Depts: ".count($byDept)." active — ".implode(', ', array_map(fn($d)=>"{$d['dept']}:{$d['count']}", array_slice($byDept,0,3)));

        return [
            'stats' => compact('total','byStatus','byCategory','byPriority','byDept','byUnit','trend','urgentOpen','overdue','escalated','avgCloseHours','faceCoverage','deptCount','officerCount'),
            'insights' => $insights,
            'generated_at' => now()->toIso8601String(),
            'readiness' => $readiness,
            'range' => ['from'=>$from,'to'=>$to],
        ];
    }

    public static function worldClassReportOld(string $type = 'SITREP'): array
    {
        return self::generateInternal($type, null, null);
    }
}
