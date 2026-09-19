<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class FaceAuthController extends Controller
{
    public function enroll(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'embedding' => 'required|array|size:128',
            'embedding.*' => 'numeric',
        ]);

        $this->authorizeToggle();

        $user = User::findOrFail($request->user_id);
        $user->update([
            'face_embedding' => $request->embedding,
            'face_enrolled_at' => now(),
        ]);

        return back()->with('success', "Face enrolled for {$user->name}");
    }

    public function verify(Request $request)
    {
        $request->validate([
            'service_number' => 'required|string',
            'embedding' => 'required|array|size:128',
        ]);

        $mode = SystemSetting::getValue('face_auth_mode', config('face_auth.mode', 'local'));
        $threshold = (float) config('face_auth.threshold', 0.6);

        $user = User::where('service_number', $request->service_number)->first();
        if (!$user || !$user->face_embedding) {
            return response()->json(['message' => 'No face enrolled for this service number', 'mode' => $mode], 404);
        }

        if ($mode === 'cloud') {
            // Delegate to cloud provider — stub for AWS/Azure. In production, call Rekognition/Azure Face here.
            // Example: $result = $this->cloudVerify($user, $request->embedding);
            // For now, fallback to local cosine similarity to keep toggle functional without credentials.
        }

        $similarity = $this->cosineSimilarity($user->face_embedding, $request->embedding);

        if ($similarity >= $threshold) {
            Auth::login($user);
            $request->session()->regenerate();
            return response()->json(['message' => 'Face verified', 'similarity' => $similarity, 'mode' => $mode, 'redirect' => route('dashboard')]);
        }

        return response()->json(['message' => 'Face verification failed', 'similarity' => $similarity, 'mode' => $mode], 401);
    }

    public function settings(Request $request)
    {
        $this->authorizeToggle();

        $request->validate([
            'face_auth_mode' => 'required|in:local,cloud',
            'face_auth_enabled' => 'required|boolean',
            'cloud_provider' => 'nullable|in:aws,azure',
        ]);

        SystemSetting::setValue('face_auth_mode', $request->face_auth_mode);
        SystemSetting::setValue('face_auth_enabled', $request->face_auth_enabled ? '1' : '0');
        if ($request->cloud_provider) {
            SystemSetting::setValue('face_cloud_provider', $request->cloud_provider);
        }

        return back()->with('success', 'Face auth settings updated. Mode: ' . $request->face_auth_mode);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0; $normA = 0; $normB = 0;
        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }
        $den = sqrt($normA) * sqrt($normB);
        return $den == 0 ? 0 : $dot / $den;
    }

    private function authorizeToggle(): void
    {
        if (!auth()->user()?->hasRole('SuperAdmin')) {
            abort(403, 'Only SuperAdmin can toggle face auth mode');
        }
    }
}
