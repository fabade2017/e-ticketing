<?php

use App\Modules\Care\Http\Controllers\CareAppointmentController;
use App\Modules\Care\Http\Controllers\CareBillingController;
use App\Modules\Care\Http\Controllers\CareClinicalController;
use App\Modules\Care\Http\Controllers\CareDutyCertificateController;
use App\Modules\Care\Http\Controllers\CareHospitalController;
use App\Modules\Care\Http\Controllers\CarePortalController;
use App\Modules\Care\Http\Controllers\CareReferralController;
use App\Modules\Care\Http\Controllers\CareRegistrationController;
use App\Modules\Care\Http\Controllers\CareReportController;
use App\Modules\Care\Http\Controllers\CareWorkspaceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['permission:use care workspace', 'hospital'])
    ->prefix('care')
    ->name('care.')
    ->group(function (): void {
        Route::get('/', [CareWorkspaceController::class, 'dashboard'])->name('dashboard');
        Route::post('hospital/select', [CareHospitalController::class, 'select'])->name('hospital.select');

        Route::get('registrations', [CareRegistrationController::class, 'index'])->name('registrations.index');
        Route::post('registrations/{registration}/review', [CareRegistrationController::class, 'review'])
            ->middleware('permission:approve registrations')
            ->name('registrations.review');
        Route::post('sponsors/{sponsor}/approve', [CareRegistrationController::class, 'approveSponsor'])
            ->middleware('permission:approve registrations')
            ->name('sponsors.approve');

        Route::get('appointments', [CareAppointmentController::class, 'index'])->name('appointments.index');
        Route::post('appointments/{appointment}/confirm', [CareAppointmentController::class, 'confirm'])
            ->middleware('permission:approve registrations')
            ->name('appointments.confirm');

        Route::get('visits', [CareClinicalController::class, 'visits'])->name('visits.index');
        Route::post('visits', [CareClinicalController::class, 'storeVisit'])->name('visits.store');
        Route::get('visits/{visit}', [CareClinicalController::class, 'showVisit'])->name('visits.show');
        Route::post('visits/{visit}/complete', [CareClinicalController::class, 'completeVisit'])->name('visits.complete');
        Route::post('visits/{visit}/summary', [CareClinicalController::class, 'storeSummary'])
            ->middleware('permission:publish consultation summaries')
            ->name('visits.summary');
        Route::post('visits/{visit}/vitals', [CareClinicalController::class, 'storeVitals'])->name('vitals.store');
        Route::post('visits/{visit}/diagnoses', [CareClinicalController::class, 'storeDiagnosis'])->name('diagnoses.store');
        Route::post('visits/{visit}/lab-orders', [CareClinicalController::class, 'orderLab'])->name('lab-orders.store');
        Route::post('visits/{visit}/prescriptions', [CareClinicalController::class, 'prescribe'])->name('prescriptions.store');

        Route::get('lab', [CareClinicalController::class, 'lab'])->name('lab.index');
        Route::post('lab-orders/{order}/results', [CareClinicalController::class, 'storeLabResult'])
            ->middleware('permission:publish lab results')
            ->name('lab-results.store');
        Route::post('lab-results/{result}/publish', [CareClinicalController::class, 'publishLabResult'])
            ->middleware('permission:publish lab results')
            ->name('lab-results.publish');
        Route::post('lab-results/{result}/images', [CareClinicalController::class, 'uploadImage'])
            ->middleware('permission:upload lab images')
            ->name('lab-results.images');

        Route::get('admissions', [CareClinicalController::class, 'admissions'])
            ->middleware('permission:manage admissions')
            ->name('admissions.index');
        Route::post('admissions', [CareClinicalController::class, 'admit'])
            ->middleware('permission:manage admissions')
            ->name('admissions.store');
        Route::post('admissions/{admission}/discharge', [CareClinicalController::class, 'discharge'])
            ->middleware('permission:manage admissions')
            ->name('admissions.discharge');

        Route::get('referrals', [CareReferralController::class, 'index'])
            ->middleware('permission:manage referrals')
            ->name('referrals.index');
        Route::post('referrals', [CareReferralController::class, 'store'])
            ->middleware('permission:manage referrals')
            ->name('referrals.store');
        Route::post('referrals/{referral}/accept', [CareReferralController::class, 'accept'])
            ->middleware('permission:manage referrals')
            ->name('referrals.accept');

        Route::get('invoices', [CareBillingController::class, 'invoices'])->name('invoices.index');
        Route::post('invoices', [CareBillingController::class, 'storeInvoice'])->name('invoices.store');
        Route::post('invoices/{invoice}/payments', [CareBillingController::class, 'storePayment'])->name('payments.store');
        Route::post('payments/{payment}/verify', [CareBillingController::class, 'verifyPayment'])
            ->middleware('permission:verify payments')
            ->name('payments.verify');

        Route::get('duty-certificates', [CareDutyCertificateController::class, 'index'])->name('duty-certificates.index');
        Route::post('duty-certificates', [CareDutyCertificateController::class, 'store'])
            ->middleware('permission:certify duty status')
            ->name('duty-certificates.store');
        Route::post('duty-certificates/{certificate}/administrative', [CareDutyCertificateController::class, 'administrative'])
            ->middleware('permission:maintain duty records')
            ->name('duty-certificates.administrative');

        Route::get('reports', [CareReportController::class, 'index'])
            ->middleware('permission:view reports')
            ->name('reports.index');
        Route::post('reports', [CareReportController::class, 'generate'])
            ->middleware('permission:view reports')
            ->name('reports.generate');
        Route::get('reports/{report}/csv', [CareReportController::class, 'download'])
            ->middleware('permission:view reports')
            ->name('reports.csv');
    });

Route::middleware(['permission:use care portal'])
    ->prefix('portal')
    ->name('portal.')
    ->group(function (): void {
        Route::get('/', [CarePortalController::class, 'dashboard'])->name('dashboard');
        Route::get('registrations', [CarePortalController::class, 'registrations'])->name('registrations');
        Route::post('registrations', [CarePortalController::class, 'storeRegistration'])->name('registrations.store');
        Route::get('appointments', [CarePortalController::class, 'appointments'])->name('appointments');
        Route::post('appointments', [CarePortalController::class, 'storeAppointment'])->name('appointments.store');
        Route::get('invoices', [CarePortalController::class, 'invoices'])->name('invoices');
        Route::post('invoices/{invoice}/payments', [CareBillingController::class, 'storePayment'])->name('payments.store');
        Route::get('results', [CarePortalController::class, 'results'])->name('results');
    });
