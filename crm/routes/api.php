<?php

use App\Http\Controllers\Api\WorkerJobController;
use Illuminate\Support\Facades\Route;

Route::prefix('worker')->group(function (): void {
    Route::get('/jobs/next', [WorkerJobController::class, 'next']);
    Route::post('/jobs/{auditJob}/result', [WorkerJobController::class, 'result']);
    Route::post('/jobs/{auditJob}/fail', [WorkerJobController::class, 'fail']);
});

