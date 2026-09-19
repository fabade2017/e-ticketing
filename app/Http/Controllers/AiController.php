<?php

namespace App\Http\Controllers;

use App\Services\AiInsightsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AiController extends Controller
{
    public function insights()
    {
        return response()->json(AiInsightsService::generate());
    }

    public function ask(Request $request)
    {
        $request->validate(['q' => 'required|string|max:500']);
        $q = $request->q;
        $data = AiInsightsService::generate();

        // Try Groq LLM if key present — world-class Army prompt
        $key = config('ai.key');
        if ($key) {
            try {
                $prompt = "You are NA E-Ticket AI — World-Class Army (NATO APP-6, J4 logistics, medical triage) Advisor for Nigerian Army. Stats: ".json_encode($data['stats']).". Readiness: {$data['readiness']}. Insights: ".implode(' | ', $data['insights']).". User question: {$q}.\n\nRespond WORLD-CLASS ORGANISED with: 1) Executive Summary (1-2 lines, readiness GREEN/AMBER/RED), 2) Key Metrics (table-like bullets: total/open/urgent/overdue/avg close/face%), 3) Operational Impact (hotspots, dept load, SLA risk), 4) Action Checklist (numbered, owner: J1/J4/Med/Cmd, SLA, priority), 5) Predictive Forecast & Risks, 6) Recommendation for World-Class Standard (NIST, NATO). Use markdown headings, bold, bullets. Max 280 words, concise, authoritative.";
                $res = Http::withToken($key)->timeout(20)->post(config('ai.groq_url'), [
                    'model' => config('ai.model', 'groq/compound-mini'),
                    'messages' => [
                        ['role'=>'system','content'=>'You are a World-Class Army (NATO, JCS, NIST 800-63) ticketing advisor. Always respond ORGANISED with headings: Executive Summary, Key Metrics, Operational Impact, Action Checklist, Forecast & Risks, World-Class Recommendation. Use markdown bullets and bold. Be authoritative and concise.'],
                        ['role'=>'user','content'=>$prompt],
                    ],
                    'temperature'=>0.35,
                ]);
                if ($res->successful()) {
                    $answer = $res->json('choices.0.message.content') ?? $res->json('choices.0.text');
                    if ($answer) return response()->json(['answer'=>trim($answer).' (Groq '.config('ai.model').')', 'insights'=>$data['insights'], 'provider'=>'groq']);
                }
            } catch (\Throwable $e) {
                // fall back to rule-based
            }
        }

        // Fallback rule-based
        $ql = strtolower($q);
        $answer = "I analyzed {$data['stats']['total']} tickets. ";
        if (str_contains($ql, 'urgent') || str_contains($ql, 'priority')) $answer .= "Urgent open: {$data['stats']['urgentOpen']}. Recommendation: triage urgent first. ";
        if (str_contains($ql, 'face')) $answer .= "Face coverage: {$data['stats']['faceCoverage']}%. ";
        if (str_contains($ql, 'trend') || str_contains($ql, 'predict')) $answer .= implode(' ', array_slice($data['insights'], -2));
        if (strlen($answer) < 80) $answer .= implode(' ', $data['insights']);
        $answer .= $key ? " (Groq fallback — check key/model)" : " (Local AI — rule-based, Groq ready)";
        return response()->json(['answer' => $answer, 'insights' => $data['insights'], 'provider'=>'local']);
    }
}
