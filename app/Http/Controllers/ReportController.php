<?php

namespace App\Http\Controllers;

use App\Services\AiInsightsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $data = AiInsightsService::worldClassReport($request->input('type','SITREP'));
        // Try Groq to generate narrative
        $narrative = null;
        $key = config('ai.key');
        if ($key) {
            try {
                $prompt = "Generate WORLD-CLASS Army SITREP for Nigerian Army E-Ticket. Data: ".json_encode($data['stats']).". Readiness {$data['readiness']}. Format: 1. Classification & DTG, 2. Executive Summary, 3. Situation (metrics table), 4. Analysis (hotspots, SLA, biometrics), 5. Forecast, 6. Commander's Recommendations (world-class standard). Markdown, 350 words.";
                $res = Http::withToken($key)->timeout(20)->post(config('ai.groq_url'), [
                    'model'=>config('ai.model','groq/compound-mini'),
                    'messages'=>[['role'=>'system','content'=>'You are NATO J2/J4 SITREP writer. World-class, concise.'],['role'=>'user','content'=>$prompt]],
                    'temperature'=>0.35,
                ]);
                if ($res->successful()) $narrative = $res->json('choices.0.message.content');
            } catch (\Throwable $e) {}
        }
        return Inertia::render('Reports/Index', [
            'report'=>$data,
            'narrative'=>$narrative,
            'canGenerate'=> (bool)$key,
        ]);
    }

    public function download(Request $request)
    {
        $data = AiInsightsService::worldClassReport();
        $content = "NA E-TICKET WORLD-CLASS SITREP\nGenerated: {$data['generated_at']}\nReadiness: {$data['readiness']}\n\nSTATS:\n".json_encode($data['stats'], JSON_PRETTY_PRINT)."\n\nINSIGHTS:\n".implode("\n", $data['insights']);
        return response($content, 200, ['Content-Type'=>'text/plain','Content-Disposition'=>'attachment; filename="NA_SITREP_'.now()->format('Ymd_His').'.txt"']);
    }
}
