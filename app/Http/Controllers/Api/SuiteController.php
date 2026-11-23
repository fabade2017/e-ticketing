<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Shared\Navigation\SuiteNavigation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SuiteController extends Controller
{
    public function modules(Request $request, SuiteNavigation $navigation): JsonResponse
    {
        return response()->json([
            'modules' => $navigation->modulesFor($request->user()),
        ]);
    }

    public function rooms(Request $request, SuiteNavigation $navigation): JsonResponse
    {
        return response()->json([
            'rooms' => $navigation->roomsFor($request->user()),
        ]);
    }

    public function notifications(Request $request): JsonResponse
    {
        $notifications = $request->user()
            ->suiteNotifications()
            ->unread()
            ->latest()
            ->limit(20)
            ->get(['id', 'module', 'title', 'url', 'created_at'])
            ->map(fn ($notification): array => [
                'id' => $notification->id,
                'module' => $notification->module,
                'title' => $notification->title,
                'url' => $notification->url,
                'created_at' => $notification->created_at?->toIso8601String(),
            ]);

        return response()->json([
            'notifications' => $notifications,
        ]);
    }
}
