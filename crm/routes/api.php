<?php

use App\Http\Controllers\Api\WorkerDiscoveryController;
use App\Http\Controllers\Api\WorkerJobController;
use Illuminate\Support\Facades\Route;

$prefix = config('app.route_prefix');
$workerPrefix = $prefix ? "{$prefix}/worker" : 'worker';

Route::prefix($workerPrefix)->group(function (): void {
    Route::get('/discovery-jobs/next', [WorkerDiscoveryController::class, 'next']);
    Route::post('/discovery-jobs/{leadDiscoveryJob}/result', [WorkerDiscoveryController::class, 'result']);
    Route::post('/discovery-jobs/{leadDiscoveryJob}/fail', [WorkerDiscoveryController::class, 'fail']);

    Route::get('/jobs/next', [WorkerJobController::class, 'next']);
    Route::post('/jobs/{auditJob}/result', [WorkerJobController::class, 'result']);
    Route::post('/jobs/{auditJob}/fail', [WorkerJobController::class, 'fail']);
});
