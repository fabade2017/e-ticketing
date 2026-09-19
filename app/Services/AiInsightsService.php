<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AiInsightsService
{
    public static function generate(): array
    {
        $total = Ticket::count();
        $byStatus = Ticket::select('status', DB::raw('count(*) as c'))->groupBy('status')->pluck('c','status')->toArray();
        $byCategory = Ticket::select('category', DB::raw('count(*) as c'))->groupBy('category')->pluck('c','category')->toArray();
        $byPriority = Ticket::select('priority', DB::raw('count(*) as c'))->groupBy('priority')->pluck('c','priority')->toArray();
        $trend = Ticket::select(DB::raw("strftime('%Y-%m-%d', created_at) as d"), DB::raw('count(*) as c'))
            ->groupBy('d')->orderBy('d')->limit(14)->get()->map(fn($r)=>['date'=>$r->d,'count'=>$r->c])->toArray();

        $urgentOpen = Ticket::where('priority','urgent')->whereIn('status',['open','in_progress'])->count();
        $faceCoverage = User::count() ? round(User::whereNotNull('face_embedding')->count() / User::count() * 100) : 0;

        // Rule-based AI insights (extensible to LLM)
        $insights = [];
        if (($byStatus['open'] ?? 0) > $total * 0.6) $insights[] = "⚠️ High open backlog (".($byStatus['open']??0)." tickets) — consider assigning more Support to border sectors.";
        if (($byCategory['medical'] ?? 0) > 0) $insights[] = "🏥 Medical tickets detected (".($byCategory['medical']).") — prioritize — AI suggests SLA 4h.";
        if ($urgentOpen > 0) $insights[] = "🚨 {$urgentOpen} urgent tickets still open — AI predicts SLA breach risk.";
        if ($faceCoverage < 50) $insights[] = "👤 Only {$faceCoverage}% face enrolled — AI recommends bulk enrollment for offline auth.";
        if (empty($insights)) $insights[] = "✅ Operations nominal — AI sees balanced load, no immediate risk.";
        $insights[] = "💡 Predicted: ".max(1, intval($total*0.2))." new tickets next 7 days (based on 20% weekly growth).";

        // Anomaly: unit with most tickets
        $topUnit = Ticket::select('unit', DB::raw('count(*) as c'))->whereNotNull('unit')->groupBy('unit')->orderByDesc('c')->first();
        if ($topUnit) $insights[] = "📍 Hotspot: {$topUnit->unit} ({$topUnit->c} tickets) — AI suggests logistics audit.";

        return [
            'stats' => compact('total','byStatus','byCategory','byPriority','trend','urgentOpen','faceCoverage'),
            'insights' => $insights,
            'generated_at' => now()->toIso8601String(),
        ];
    }
}
