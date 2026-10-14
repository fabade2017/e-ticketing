<?php

namespace App\Http\Controllers;

use App\Models\SystemSetting;
use App\Models\User;
use App\Services\FaceVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FaceAuthController extends Controller
{
    public function __construct(protected FaceVerificationService $faceVerifier) {}

    public function enroll(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'embedding' => 'required|array|size:128',
            'embedding.*' => 'numeric',
            'image' => 'nullable|string',
        ]);

        $this->authorizeEnroll();

        $user = User::findOrFail($request->user_id);
        $user->update([
            'face_embedding' => $request->embedding,
            'face_enrolled_at' => now(),
        ]);

        $imageBytes = $this->decodeImage($request->input('image'));
        if ($imageBytes !== null) {
            $this->faceVerifier->storeReferenceImage($user, $imageBytes);
        }

        return back()->with('success', "Face enrolled for {$user->name}");
    }

    public function verify(Request $request)
    {
        $request->validate([
            'service_number' => 'required|string',
            'embedding' => 'required|array|size:128',
            'image' => 'nullable|string',
        ]);

        if (SystemSetting::getValue('face_auth_enabled', '1') !== '1') {
            return response()->json(['message' => 'Face authentication is disabled by SuperAdmin'], 403);
        }

        $user = User::where('service_number', $request->service_number)->first();
        if (! $user || ! $user->face_embedding) {
            return response()->json(['message' => 'No face enrolled for this service number', 'mode' => $this->faceVerifier->mode()], 404);
        }

        if (! $user->is_active) {
            return response()->json(['message' => 'Account inactive'], 403);
        }

        $result = $this->faceVerifier->verify($user, $request->embedding, $this->decodeImage($request->input('image')));

        if ($result['matched']) {
            Auth::login($user);
            $request->session()->regenerate();

            return response()->json([
                'message' => 'Face verified',
                'similarity' => $result['similarity'],
                'mode' => $result['mode'],
                'cloud_used' => $result['cloud_used'],
                'redirect' => route('dashboard'),
            ]);
        }

        return response()->json([
            'message' => 'Face verification failed',
            'similarity' => $result['similarity'],
            'mode' => $result['mode'],
            'cloud_used' => $result['cloud_used'],
        ], 401);
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

        return back()->with('success', 'Face auth settings updated. Mode: '.$request->face_auth_mode);
    }

    protected function decodeImage(?string $image): ?string
    {
        if (empty($image)) {
            return null;
        }

        if (str_contains($image, ',')) {
            $image = substr($image, strpos($image, ',') + 1);
        }

        $decoded = base64_decode($image, true);

        return $decoded === false ? null : $decoded;
    }

    private function authorizeToggle(): void
    {
        if (! auth()->user()?->hasAnyRole(['SuperAdmin'])) {
            abort(403, 'Only SuperAdmin can toggle face auth mode');
        }
    }

    private function authorizeEnroll(): void
    {
        if (! auth()->user()?->hasAnyRole(['SuperAdmin', 'Admin'])) {
            abort(403, 'Only SuperAdmin or Admin can enroll faces');
        }
    }
}
