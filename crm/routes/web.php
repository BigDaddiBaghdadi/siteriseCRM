<?php

use App\Http\Controllers\Admin\AuditJobController;
use App\Http\Controllers\Admin\AuditReviewController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LeadController;
use Illuminate\Support\Facades\Route;

$prefix = config('app.route_prefix');
$adminPrefix = $prefix ? "{$prefix}/admin" : 'admin';

Route::redirect($prefix ?: '/', "/{$adminPrefix}");

Route::prefix($adminPrefix)->name('admin.')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');

    Route::resource('leads', LeadController::class);
    Route::post('leads/{lead}/queue-audit', [LeadController::class, 'queueAudit'])
        ->name('leads.queue-audit');

    Route::get('audit-jobs', [AuditJobController::class, 'index'])
        ->name('audit-jobs.index');
    Route::post('audit-jobs/{auditJob}/retry', [AuditJobController::class, 'retry'])
        ->name('audit-jobs.retry');

    Route::get('audits', [AuditReviewController::class, 'index'])
        ->name('audits.index');
    Route::get('audits/{audit}', [AuditReviewController::class, 'show'])
        ->name('audits.show');
    Route::post('audits/{audit}/approve', [AuditReviewController::class, 'approve'])
        ->name('audits.approve');
    Route::post('audits/{audit}/reject', [AuditReviewController::class, 'reject'])
        ->name('audits.reject');
});
