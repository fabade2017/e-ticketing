<?php

use App\Modules\Supply\Http\Controllers\SupplyApprovalController;
use App\Modules\Supply\Http\Controllers\SupplyController;
use Illuminate\Support\Facades\Route;

Route::middleware(['permission:use supply', 'hospital'])
    ->prefix('supply')
    ->name('supply.')
    ->group(function (): void {
        Route::get('/', [SupplyController::class, 'index'])->name('index');
        Route::get('/approvals', [SupplyApprovalController::class, 'index'])
            ->middleware('permission:approve stock adjustments')
            ->name('approvals.index');
        Route::get('/items/{item}', [SupplyController::class, 'showItem'])->name('items.show');
        Route::post('/items', [SupplyController::class, 'storeItem'])->name('items.store');
        Route::post('/receive', [SupplyController::class, 'receive'])
            ->middleware('permission:receive stock')
            ->name('receive');
        Route::post('/issue', [SupplyController::class, 'issue'])
            ->middleware('permission:issue stock')
            ->name('issue');
        Route::post('/reconcile', [SupplyController::class, 'reconcile'])
            ->middleware('permission:receive stock')
            ->name('reconcile');
        Route::post('/approvals/{approval}/approve', [SupplyApprovalController::class, 'approve'])
            ->middleware('permission:approve stock adjustments')
            ->name('approvals.approve');
        Route::post('/approvals/{approval}/reject', [SupplyApprovalController::class, 'reject'])
            ->middleware('permission:approve stock adjustments')
            ->name('approvals.reject');
    });
