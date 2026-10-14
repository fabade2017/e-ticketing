<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AiController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BrandingController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FaceAuthController;
use App\Http\Controllers\FriendController;
use App\Http\Controllers\KbController;
use App\Http\Controllers\OfficerController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\RolePermissionController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserCrudController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
});
Route::get('/help', fn () => Inertia::render('Help'))->name('help');
Route::get('/about', fn () => Inertia::render('About'))->name('about');
Route::get('/docs', fn () => Inertia::render('Docs'))->name('docs');
Route::get('/proposal', fn () => Inertia::render('ProposalCoverage'))->name('proposal');
Route::get('/download', fn () => Inertia::render('DownloadApp'))->name('download');

Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/face/verify', [FaceAuthController::class, 'verify'])->name('face.verify');

Route::get('/kb', [KbController::class, 'index'])->name('kb.index')->middleware('auth');
Route::post('/kb', [KbController::class, 'store'])->name('kb.store')->middleware(['auth', 'role:SuperAdmin|Admin']);
Route::get('/reports', [ReportController::class, 'index'])->name('reports.index')->middleware(['auth', 'role:SuperAdmin|Admin|Support']);
Route::get('/reports/download', [ReportController::class, 'download'])->name('reports.download')->middleware(['auth', 'role:SuperAdmin|Admin|Support']);
Route::get('/officers', [OfficerController::class, 'index'])->name('officers')->middleware(['auth', 'role:SuperAdmin|Admin|Support']);
Route::get('/ai/insights', [AiController::class, 'insights'])->name('ai.insights')->middleware(['auth', 'role:SuperAdmin|Admin|Support']);
Route::post('/ai/ask', [AiController::class, 'ask'])->name('ai.ask')->middleware(['auth', 'role:SuperAdmin|Admin|Support']);

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    Route::get('/dashboard', [AdminController::class, 'dashboard'])->name('dashboard');

    Route::middleware('permission:use friends')->group(function () {
        Route::get('/friends', [FriendController::class, 'index'])->name('friends.index');
        Route::post('/friends/request', [FriendController::class, 'sendColleague'])->name('friends.request');
        Route::post('/friends/group-join', [FriendController::class, 'sendGroupJoin'])->name('friends.groupJoin');
        Route::post('/friends/requests/{friendRequest}/accept', [FriendController::class, 'accept'])->name('friends.accept');
        Route::post('/friends/requests/{friendRequest}/reject', [FriendController::class, 'reject'])->name('friends.reject');
    });

    Route::middleware('permission:use chat')->group(function () {
        Route::get('/chat/unread', [ChatController::class, 'unread'])->name('chat.unread');
        Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
        Route::post('/chat/direct/{user}', [ChatController::class, 'direct'])->name('chat.direct');
        Route::post('/chat/groups', [ChatController::class, 'storeGroup'])->name('chat.groups.store');
        Route::post('/chat/groups/{group}/members', [ChatController::class, 'addMembers'])->name('chat.groups.members.add');
        Route::delete('/chat/groups/{group}/members/{member}', [ChatController::class, 'removeMember'])->name('chat.groups.members.remove');
        Route::post('/chat/groups/{group}/messages', [ChatController::class, 'storeMessage'])->name('chat.messages.store');
        Route::get('/chat/groups/{group}/messages', [ChatController::class, 'messages'])->name('chat.messages');
    });

    Route::get('/admin/departments/users/search', [DepartmentController::class, 'searchUsers'])->name('admin.departments.search')->middleware('role:SuperAdmin|Admin|Support');

    Route::resource('tickets', TicketController::class);

    // Face enrollment (SuperAdmin/Admin)
    Route::post('/face/enroll', [FaceAuthController::class, 'enroll'])->name('face.enroll')->middleware('role:SuperAdmin|Admin');

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

        Route::get('/admin/departments', [DepartmentController::class, 'index'])->name('admin.departments');
        Route::post('/admin/departments', [DepartmentController::class, 'store'])->name('admin.departments.store');
        Route::put('/admin/departments/{department}', [DepartmentController::class, 'update'])->name('admin.departments.update');
        Route::delete('/admin/departments/{department}', [DepartmentController::class, 'destroy'])->name('admin.departments.destroy');
        Route::post('/admin/departments/assign', [DepartmentController::class, 'assignUser'])->name('admin.departments.assign');

        Route::get('/admin/users/crud', [UserCrudController::class, 'index'])->name('admin.users.crud');
        Route::post('/admin/users/crud', [UserCrudController::class, 'store'])->name('admin.users.crud.store');
        Route::put('/admin/users/crud/{user}', [UserCrudController::class, 'update'])->name('admin.users.crud.update');
        Route::delete('/admin/users/crud/{user}', [UserCrudController::class, 'destroy'])->name('admin.users.crud.destroy');
    });
});
