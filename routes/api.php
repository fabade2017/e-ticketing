<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\ChatController;
use App\Http\Controllers\Api\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes - Soldier Mobile App
|--------------------------------------------------------------------------
| Sanctum token-based auth. Use header: Authorization: Bearer <token>
| All /api/* routes are stateless (no CSRF, no session cookies needed)
*/

// Public - soldier login
Route::post('/login', [AuthController::class, 'login'])->name('api.login');
Route::post('/face/verify', [AuthController::class, 'faceVerify'])->name('api.face.verify');

// Protected - require Bearer token
Route::middleware('auth:sanctum')->group(function () {
    // Auth
    Route::get('/user', [AuthController::class, 'me'])->name('api.user');
    Route::post('/logout', [AuthController::class, 'logout'])->name('api.logout');
    Route::post('/logout-all', [AuthController::class, 'logoutAll'])->name('api.logoutAll');

    // Tickets - core soldier flow
    Route::get('/tickets/stats', [TicketController::class, 'stats'])->name('api.tickets.stats');
    Route::get('/tickets', [TicketController::class, 'index'])->name('api.tickets.index');
    Route::post('/tickets', [TicketController::class, 'store'])->name('api.tickets.store');
    Route::get('/tickets/{ticket}', [TicketController::class, 'show'])->name('api.tickets.show');
    Route::patch('/tickets/{ticket}', [TicketController::class, 'update'])->name('api.tickets.update'); // privileged only (checked in controller)

    // Chat - minimal for soldiers (view groups, messages)
    Route::get('/chat/groups', [ChatController::class, 'groups'])->name('api.chat.groups');
    Route::get('/chat/unread', [ChatController::class, 'unread'])->name('api.chat.unread');
    Route::get('/chat/groups/{group}/messages', [ChatController::class, 'messages'])->name('api.chat.messages');
    Route::post('/chat/groups/{group}/messages', [ChatController::class, 'sendMessage'])->name('api.chat.messages.store');

    // Meta - for mobile dropdowns / offline validation
    Route::get('/meta', function (Request $request) {
        return response()->json([
            'categories' => ['welfare','logistics','medical','equipment','operations','other'],
            'priorities' => ['low','medium','high','urgent'],
            'statuses' => ['open','in_progress','resolved','closed'],
            'sla_hours' => \App\Models\Ticket::SLA_HOURS,
            'departments' => \App\Models\Department::select('id','name')->get(),
            'states' => array_keys(\App\Models\Ticket::distinct()->pluck('state','state')->toArray()) ?: [], // fallback
        ]);
    })->name('api.meta');
});
