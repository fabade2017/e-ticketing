<?php

namespace App\Http\Controllers;

use App\Services\AiInsightsService;
use App\Models\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $from = $request->input('from');
        $to = $request->input('to');
        $drill = $request->input('drill');
        $user = $request->user();
        $scopeParam = $request->input('scope');
        $isAll = $scopeParam === 'all' && $user->hasAnyRole(['SuperAdmin','Admin','Support']);
        if ($user->hasRole('SuperAdmin') && !$request->has('scope')) $isAll = true;
        $scopedUser = $isAll ? null : $user;
        $data = AiInsightsService::worldClassReport($request->input('type','SITREP'), $from, $to, $scopedUser);
        $adhoc = $this->adhocStats($from, $to, $scopedUser);
        $drillTickets = null;
        $drillLabel = null;
        if ($drill) {
            [$label, $tickets] = $this->drillThrough($drill, $from, $to, $scopedUser);
            $drillLabel = $label;
            $drillTickets = $tickets;
        }
        $narrative = null;
        $key = config('ai.key');
        if ($key) {
            try {
                $prompt = "Generate WORLD-CLASS Army SITREP for Nigerian Army E-Ticket. Data: ".json_encode($data['stats']).". Readiness {$data['readiness']}. Adhoc range ".($from ?: 'all')." to ".($to ?: 'now').". Format: 1. Classification & DTG, 2. Executive Summary, 3. Situation (metrics table), 4. Analysis (hotspots, SLA, biometrics), 5. Forecast, 6. Commander's Recommendations. Markdown, 350 words.";
                $res = Http::withToken($key)->timeout(20)->post(config('ai.groq_url'), [
                    'model'=>config('ai.model','groq/compound-mini'),
                    'messages'=>[['role'=>'system','content'=>'You are NATO J2/J4 SITREP writer. World-class, concise.'],['role'=>'user','content'=>$prompt]],
                    'temperature'=>0.35,
                ]);
                if ($res->successful()) $narrative = $res->json('choices.0.message.content');
            } catch (\Throwable $e) {}
        }
        $scopeLabel = $isAll ? 'all' : ($user->hasRole('SuperAdmin') ? 'scoped' : ($user->department_id ? 'department' : ($user->unit ? 'unit' : 'personal')));
        if (!$isAll && $user->hasRole('Soldier') && !$user->hasAnyRole(['SuperAdmin','Admin','Support'])) $scopeLabel = 'personal';
        return Inertia::render('Reports/Index', [
            'report'=>$data,
            'narrative'=>$narrative,
            'canGenerate'=> (bool)$key,
            'adhoc'=>$adhoc,
            'filters'=> $request->only(['from','to','drill','type','scope']),
            'drillLabel'=>$drillLabel,
            'drillTickets'=>$drillTickets,
            'scope'=>$scopeLabel,
            'isAll'=>$isAll,
            'canSeeAll'=> $user->hasAnyRole(['SuperAdmin','Admin','Support']),
        ]);
    }

    private function adhocStats($from, $to, ?\App\Models\User $user = null): array
    {
        $q = Ticket::query();
        if ($user) $q->visibleTo($user);
        if ($from) $q->whereDate('created_at','>=',$from);
        if ($to) $q->whereDate('created_at','<=',$to);
        $base = clone $q;
        return [
            'total' => (clone $q)->count(),
            'byStatus' => (clone $base)->selectRaw('status, count(*) as c')->groupBy('status')->pluck('c','status')->toArray(),
            'byCategory' => (clone $base)->selectRaw('category, count(*) as c')->groupBy('category')->pluck('c','category')->toArray(),
            'byPriority' => (clone $base)->selectRaw('priority, count(*) as c')->groupBy('priority')->pluck('c','priority')->toArray(),
            'byDept' => (clone $base)->selectRaw('department_id, count(*) as c')->groupBy('department_id')->get()->map(fn($r)=>['dept'=>\App\Models\Department::find($r->department_id)?->name ?? 'Unassigned','dept_id'=>$r->department_id,'count'=>$r->c])->toArray(),
            'overdue' => (clone $base)->where('sla_due_at','<', now())->whereNotIn('status',['resolved','closed'])->count(),
            'urgentOpen' => (clone $base)->where('priority','urgent')->whereIn('status',['open','in_progress'])->count(),
            'escalated' => (clone $base)->whereNotNull('escalated_at')->count(),
        ];
    }

    private function drillThrough(string $drill, $from, $to, ?\App\Models\User $user = null): array
    {
        $q = Ticket::with(['user:id,name,rank,service_number','department:id,name'])->latest();
        if ($user) $q->visibleTo($user);
        if ($from) $q->whereDate('created_at','>=',$from);
        if ($to) $q->whereDate('created_at','<=',$to);
        $label = $drill;
        if (str_starts_with($drill, 'status:')) {
            $v = substr($drill, 7); $q->where('status',$v); $label = "Status = $v";
        } elseif (str_starts_with($drill, 'category:')) {
            $v = substr($drill, 9); $q->where('category',$v); $label = "Category = $v";
        } elseif (str_starts_with($drill, 'priority:')) {
            $v = substr($drill, 9); $q->where('priority',$v); $label = "Priority = $v";
        } elseif (str_starts_with($drill, 'dept:')) {
            $v = substr($drill, 5); $q->where('department_id',$v); $label = "Department = ".(\App\Models\Department::find($v)?->name ?? $v);
        } elseif (str_starts_with($drill, 'unit:')) {
            $v = substr($drill, 5); $q->where('unit',$v); $label = "Unit = $v";
        } elseif ($drill === 'overdue') {
            $q->where('sla_due_at','<', now())->whereNotIn('status',['resolved','closed']); $label = "Overdue SLA";
        } elseif ($drill === 'escalated') {
            $q->whereNotNull('escalated_at'); $label = "Escalated";
        } elseif ($drill === 'urgentOpen') {
            $q->where('priority','urgent')->whereIn('status',['open','in_progress']); $label = "Urgent Open";
        }
        $tickets = $q->limit(50)->get();
        return [$label, $tickets];
    }

    public function download(Request $request)
    {
        $user = $request->user();
        $isAll = $request->input('scope') === 'all' && $user->hasAnyRole(['SuperAdmin','Admin','Support']);
        if ($user->hasRole('SuperAdmin') && !$request->has('scope')) $isAll = true;
        $scopedUser = $isAll ? null : $user;
        $data = AiInsightsService::worldClassReport($request->input('type','SITREP'), $request->input('from'), $request->input('to'), $scopedUser);
        $content = "NA E-TICKET WORLD-CLASS SITREP\nGenerated: {$data['generated_at']}\nRange: ".($request->input('from')?:'all')." to ".($request->input('to')?:'now')."\nReadiness: {$data['readiness']}\n\nSTATS:\n".json_encode($data['stats'], JSON_PRETTY_PRINT)."\n\nINSIGHTS:\n".implode("\n", $data['insights']);
        return response($content, 200, ['Content-Type'=>'text/plain','Content-Disposition'=>'attachment; filename="NA_SITREP_'.now()->format('Ymd_His').'.txt"']);
    }
}
