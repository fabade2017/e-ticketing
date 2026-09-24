<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    /**
     * Soldier mobile login - returns Sanctum token
     * POST /api/login
     */
    public function login(Request $request)
    {
        $request->merge([
            'email' => $request->email ?: null,
            'service_number' => $request->service_number ?: null,
        ]);

        $request->validate([
            'service_number' => 'required_without:email|nullable|string',
            'email' => 'required_without:service_number|nullable|email',
            'password' => 'required|string',
            'device_name' => 'nullable|string|max:100',
        ]);

        $field = $request->email ? 'email' : 'service_number';
        $credentials = [$field => $request->$field, 'password' => $request->password, 'is_active' => true];

        if (!Auth::attempt($credentials)) {
            return response()->json(['message' => 'Invalid credentials or inactive account'], 401);
        }

        $user = Auth::user();
        $user->load(['roles', 'department']);

        // Revoke old tokens for same device_name if provided to avoid accumulation
        $device = $request->input('device_name', 'mobile');
        // Optional: delete old tokens with same name
        // $user->tokens()->where('name', $device)->delete();

        $token = $user->createToken($device)->plainTextToken;

        return response()->json([
            'message' => 'Login successful',
            'token' => $token,
            'token_type' => 'Bearer',
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * Face verify for mobile - returns token on success
     * POST /api/face/verify
     */
    public function faceVerify(Request $request)
    {
        $request->validate([
            'service_number' => 'required|string',
            'embedding' => 'required|array|size:128',
            'embedding.*' => 'numeric',
            'device_name' => 'nullable|string|max:100',
        ]);

        $mode = SystemSetting::getValue('face_auth_mode', config('face_auth.mode', 'local'));
        $threshold = (float) config('face_auth.threshold', 0.6);

        $user = User::where('service_number', $request->service_number)->first();
        if (!$user || !$user->face_embedding) {
            return response()->json(['message' => 'No face enrolled for this service number', 'mode' => $mode], 404);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'Account inactive'], 403);
        }

        $similarity = $this->cosineSimilarity($user->face_embedding, $request->embedding);

        if ($similarity >= $threshold) {
            $user->load(['roles', 'department']);
            $device = $request->input('device_name', 'mobile-face');
            $token = $user->createToken($device)->plainTextToken;

            return response()->json([
                'message' => 'Face verified',
                'similarity' => $similarity,
                'mode' => $mode,
                'token' => $token,
                'token_type' => 'Bearer',
                'user' => $this->userPayload($user),
            ]);
        }

        return response()->json(['message' => 'Face verification failed', 'similarity' => $similarity, 'mode' => $mode], 401);
    }

    /**
     * GET /api/user
     */
    public function me(Request $request)
    {
        $user = $request->user()->load(['roles', 'department']);
        return response()->json($this->userPayload($user));
    }

    /**
     * POST /api/logout
     */
    public function logout(Request $request)
    {
        // Revoke current token only
        $request->user()->currentAccessToken()->delete();
        return response()->json(['message' => 'Logged out']);
    }

    /**
     * POST /api/logout-all
     */
    public function logoutAll(Request $request)
    {
        $request->user()->tokens()->delete();
        return response()->json(['message' => 'All sessions revoked']);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'service_number' => $user->service_number,
            'rank' => $user->rank,
            'unit' => $user->unit,
            'phone' => $user->phone,
            'is_active' => $user->is_active,
            'face_enrolled' => $user->hasFaceEnrolled(),
            'department' => $user->department ? $user->department->only(['id', 'name']) : null,
            'roles' => $user->getRoleNames()->toArray(),
            'permissions' => $user->getAllPermissions()->pluck('name')->toArray(),
        ];
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        $dot = 0;
        $normA = 0;
        $normB = 0;
        for ($i = 0; $i < count($a); $i++) {
            $dot += $a[$i] * $b[$i];
            $normA += $a[$i] ** 2;
            $normB += $b[$i] ** 2;
        }
        $den = sqrt($normA) * sqrt($normB);
        return $den == 0 ? 0 : $dot / $den;
    }
}
