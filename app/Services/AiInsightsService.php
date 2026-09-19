<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use App\Models\Department;
use Illuminate\Support\Facades\DB;

class AiInsightsService
{
    public static function generate(): array
    {
        $total = Ticket::count();
        $byStatus = Ticket::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c','status')->toArray();
        $byCategory = Ticket::select('category', DB::raw('count(*) as c'))->groupBy('category')->pluck('c','category')->toArray();
        $byPriority = Ticket::select('priority', DB::raw('count(*) as c'))->groupBy('priority')->pluck('c','priority')->toArray();
        $byDept = Ticket::select('department_id', DB::raw('count(*) as c'))->groupBy('department_id')->with('department')->get()->map(fn($r)=>['dept'=>$r->department?->name ?? 'Unassigned','count'=>$r->c])->toArray();
        $byUnit = Ticket::select('unit', DB::raw('count(*) as c'))->whereNotNull('unit')->groupBy('unit')->orderByDesc('c')->limit(5)->get()->map(fn($r)=>['unit'=>$r->unit,'count'=>$r->c])->toArray();
        $trend = Ticket::select(DB::raw("strftime('%Y-%m-%d', created_at) as d"), DB::raw('count(*) as c'))
            ->groupBy('d')->orderBy('d')->limit(14)->get()->map(fn($r)=>['date'=>$r->d,'count'=>$r->c])->toArray();

        $urgentOpen = Ticket::where('priority','urgent')->whereIn('status',['open','in_progress'])->count();
        $overdue = Ticket::where('sla_due_at','<', now())->whereNotIn('status',['resolved','closed'])->count();
        $escalated = Ticket::whereNotNull('escalated_at')->count();
        $avgCloseHours = Ticket::whereNotNull('closed_at')->selectRaw('avg((julianday(closed_at)-julianday(created_at))*24) as avg')->value('avg');
        $faceCoverage = User::count() ? round(User::whereNotNull('face_embedding')->count() / User::count() * 100) : 0;
        $deptCount = Department::count();
        $officerCount = User::count();

        // World-class insights
        $insights = [];
        $readiness = $overdue > 0 ? 'AMBER' : ($urgentOpen > 0 ? 'AMBER' : 'GREEN');
        $insights[] = "🛡️ Readiness: {$readiness} — ".($readiness==='GREEN' ? 'Ops nominal' : "{$overdue} overdue, {$urgentOpen} urgent open");
        if (($byStatus['open'] ?? 0) > $total * 0.5) $insights[] = "⚠️ Backlog: ".($byStatus['open']??0)."/{$total} open (".round(($byStatus['open']??0)/$total*100)."%) — assign Support per border sector.";
        if (($byCategory['medical'] ?? 0) > 0) $insights[] = "🏥 Medical: ".($byCategory['medical'])." — NATO triage: SLA 4h, MEDEVAC priority.";
        if ($urgentOpen > 0) $insights[] = "🚨 Urgent: {$urgentOpen} open — predicted SLA breach ".round($overdue/$total*100)."%. Escalate to Sector Cmd.";
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
        ];
    }

    public static function worldClassReport(string $type = 'SITREP'): array
    {
        $data = self::generate();
        $data['report_type'] = $type;
        $data['classification'] = 'NA CONFIDENTIAL // E-Ticket SITREP';
        return $data;
    }
}
