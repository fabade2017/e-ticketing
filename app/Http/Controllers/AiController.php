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

        // Try Groq LLM if key present
        $key = config('ai.key');
        if ($key) {
            try {
                $prompt = "You are NA E-Ticket AI for Nigerian Army. Stats: ".json_encode($data['stats']).". Insights: ".implode(' | ', $data['insights']).". User question: {$q}.\n\nRespond ORGANISED with: 1) Summary (1 line), 2) Key Stats (bullet list), 3) Action Checklist (numbered, owner & SLA), 4) Risks & Next Steps (bullets). Use clear headings, bullets, concise military tone. Max 180 words.";
                $res = Http::withToken($key)->timeout(15)->post(config('ai.groq_url'), [
                    'model' => config('ai.model', 'groq/compound-mini'),
                    'messages' => [
                        ['role'=>'system','content'=>'You are a helpful military ticketing assistant. Always respond ORGANISED with headings: Summary, Key Stats, Action Checklist, Risks & Next Steps. Use markdown bullets and bold. Be concise.'],
                        ['role'=>'user','content'=>$prompt],
                    ],
                    'temperature'=>0.3,
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
