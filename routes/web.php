<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\FaceAuthController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\TicketController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});
Route::get('/help', fn() => Inertia::render('Help'))->name('help');
Route::get('/about', fn() => Inertia::render('About'))->name('about');
Route::get('/docs', fn() => Inertia::render('Docs'))->name('docs');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/face/verify', [FaceAuthController::class, 'verify'])->name('face.verify');

Route::get('/kb', [\App\Http\Controllers\KbController::class, 'index'])->name('kb.index')->middleware('auth');
Route::post('/kb', [\App\Http\Controllers\KbController::class, 'store'])->name('kb.store')->middleware(['auth','role:SuperAdmin|Admin']);
Route::get('/officers', [\App\Http\Controllers\OfficerController::class, 'index'])->name('officers')->middleware(['auth','role:SuperAdmin|Admin|Support']);
Route::get('/ai/insights', [\App\Http\Controllers\AiController::class, 'insights'])->name('ai.insights')->middleware(['auth','role:SuperAdmin|Admin|Support']);
Route::post('/ai/ask', [\App\Http\Controllers\AiController::class, 'ask'])->name('ai.ask')->middleware(['auth','role:SuperAdmin|Admin|Support']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::get('/friends', [\App\Http\Controllers\FriendController::class, 'index'])->name('friends.index');
    Route::post('/friends/request', [\App\Http\Controllers\FriendController::class, 'sendColleague'])->name('friends.request');
    Route::post('/friends/group-join', [\App\Http\Controllers\FriendController::class, 'sendGroupJoin'])->name('friends.groupJoin');
    Route::post('/friends/requests/{friendRequest}/accept', [\App\Http\Controllers\FriendController::class, 'accept'])->name('friends.accept');
    Route::post('/friends/requests/{friendRequest}/reject', [\App\Http\Controllers\FriendController::class, 'reject'])->name('friends.reject');

    Route::get('/chat/unread', [\App\Http\Controllers\ChatController::class, 'unread'])->name('chat.unread');
    Route::get('/chat', [\App\Http\Controllers\ChatController::class, 'index'])->name('chat.index');
    Route::post('/chat/direct/{user}', [\App\Http\Controllers\ChatController::class, 'direct'])->name('chat.direct');
    Route::post('/chat/groups', [\App\Http\Controllers\ChatController::class, 'storeGroup'])->name('chat.groups.store');
    Route::post('/chat/groups/{group}/members', [\App\Http\Controllers\ChatController::class, 'addMembers'])->name('chat.groups.members.add');
    Route::delete('/chat/groups/{group}/members/{member}', [\App\Http\Controllers\ChatController::class, 'removeMember'])->name('chat.groups.members.remove');
    Route::post('/chat/groups/{group}/messages', [\App\Http\Controllers\ChatController::class, 'storeMessage'])->name('chat.messages.store');
    Route::get('/chat/groups/{group}/messages', [\App\Http\Controllers\ChatController::class, 'messages'])->name('chat.messages');

    Route::get('/admin/departments/users/search', [\App\Http\Controllers\DepartmentController::class, 'searchUsers'])->name('admin.departments.search')->middleware('role:SuperAdmin|Admin|Support');

    Route::resource('tickets', TicketController::class);

    // Face enrollment (SuperAdmin/Admin)
    Route::post('/face/enroll', [FaceAuthController::class, 'enroll'])->name('face.enroll');

    // SuperAdmin-only
    Route::middleware('role:SuperAdmin')->group(function () {
        Route::get('/admin/users', [AdminController::class, 'users'])->name('admin.users');
        Route::post('/admin/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('admin.users.role');
        Route::get('/admin/settings', [AdminController::class, 'settings'])->name('admin.settings');
        Route::post('/admin/settings/face', [FaceAuthController::class, 'settings'])->name('admin.settings.face');

        Route::get('/admin/users/bulk', [BulkUploadController::class, 'index'])->name('admin.users.bulk');
        Route::post('/admin/users/bulk', [BulkUploadController::class, 'store'])->name('admin.users.bulk.store');
        Route::get('/admin/users/bulk/template', [BulkUploadController::class, 'template'])->name('admin.users.bulk.template');

        Route::get('/admin/roles', [RolePermissionController::class, 'index'])->name('admin.roles');
        Route::post('/admin/roles', [RolePermissionController::class, 'storeRole'])->name('admin.roles.store');
        Route::put('/admin/roles/{role}', [RolePermissionController::class, 'updateRole'])->name('admin.roles.update');
        Route::delete('/admin/roles/{role}', [RolePermissionController::class, 'destroyRole'])->name('admin.roles.destroy');
        Route::post('/admin/permissions', [RolePermissionController::class, 'storePermission'])->name('admin.permissions.store');
        Route::post('/admin/users/assign-role', [RolePermissionController::class, 'assignRole'])->name('admin.users.assignRole');
        Route::post('/admin/users/assign-permission', [RolePermissionController::class, 'assignPermission'])->name('admin.users.assignPermission');

        Route::get('/admin/branding', [BrandingController::class, 'index'])->name('admin.branding');
        Route::post('/admin/branding', [BrandingController::class, 'update'])->name('admin.branding.update');

        Route::get('/admin/departments', [\App\Http\Controllers\DepartmentController::class, 'index'])->name('admin.departments');
        Route::post('/admin/departments', [\App\Http\Controllers\DepartmentController::class, 'store'])->name('admin.departments.store');
        Route::put('/admin/departments/{department}', [\App\Http\Controllers\DepartmentController::class, 'update'])->name('admin.departments.update');
        Route::delete('/admin/departments/{department}', [\App\Http\Controllers\DepartmentController::class, 'destroy'])->name('admin.departments.destroy');
        Route::post('/admin/departments/assign', [\App\Http\Controllers\DepartmentController::class, 'assignUser'])->name('admin.departments.assign');

        Route::get('/admin/users/crud', [\App\Http\Controllers\UserCrudController::class, 'index'])->name('admin.users.crud');
        Route::post('/admin/users/crud', [\App\Http\Controllers\UserCrudController::class, 'store'])->name('admin.users.crud.store');
        Route::put('/admin/users/crud/{user}', [\App\Http\Controllers\UserCrudController::class, 'update'])->name('admin.users.crud.update');
        Route::delete('/admin/users/crud/{user}', [\App\Http\Controllers\UserCrudController::class, 'destroy'])->name('admin.users.crud.destroy');
    });
});
