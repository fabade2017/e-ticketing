<?php

use App\Modules\Maintenance\Http\Controllers\MaintenanceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['permission:use maintenance', 'hospital'])
    ->prefix('maintenance')
    ->name('maintenance.')
    ->group(function (): void {
        Route::get('/', [MaintenanceController::class, 'index'])->name('index');
        Route::get('/create', [MaintenanceController::class, 'create'])->name('create');
        Route::post('/', [MaintenanceController::class, 'store'])->name('store');
        Route::post('/tickets/{ticket}/open', [MaintenanceController::class, 'fromTicket'])->name('from-ticket');
        Route::get('/{workOrder}', [MaintenanceController::class, 'show'])
            ->whereNumber('workOrder')
            ->name('show');
        Route::post('/{workOrder}/assign', [MaintenanceController::class, 'assign'])
            ->middleware('permission:approve work orders')
            ->whereNumber('workOrder')
            ->name('assign');
        Route::post('/{workOrder}/complete', [MaintenanceController::class, 'complete'])
            ->whereNumber('workOrder')
            ->name('complete');
        Route::post('/{workOrder}/parts', [MaintenanceController::class, 'issueParts'])
            ->middleware('permission:issue stock')
            ->whereNumber('workOrder')
            ->name('parts.store');
    });
