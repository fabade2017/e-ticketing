<?php

namespace App\Http\Controllers;

use App\Services\AiInsightsService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function insights()
    {
        return response()->json(AiInsightsService::generate());
    }

    public function ask(Request $request)
    {
        $request->validate(['q' => 'required|string|max:500']);
        $q = strtolower($request->q);
        $data = AiInsightsService::generate();
        $answer = "I analyzed {$data['stats']['total']} tickets. ";
        if (str_contains($q, 'urgent') || str_contains($q, 'priority')) $answer .= "Urgent open: {$data['stats']['urgentOpen']}. Recommendation: triage urgent first. ";
        if (str_contains($q, 'face')) $answer .= "Face coverage: {$data['stats']['faceCoverage']}%. ";
        if (str_contains($q, 'trend') || str_contains($q, 'predict')) $answer .= implode(' ', array_slice($data['insights'], -2));
        if (strlen($answer) < 80) $answer .= implode(' ', $data['insights']);
        $answer .= " (Local AI — rule-based, extensible to LLM via config/ai.php).";
        return response()->json(['answer' => $answer, 'insights' => $data['insights']]);
    }
}
