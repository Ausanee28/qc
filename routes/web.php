<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReceiveJobController;
use App\Http\Controllers\ExecuteTestController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\PerformanceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');

    // Receive Job
    Route::get('/receive-job', [ReceiveJobController::class , 'create'])->name('receive-job.create');
    Route::post('/receive-job', [ReceiveJobController::class , 'store'])->middleware('throttle:15,1')->name('receive-job.store');

    // Execute Test
    Route::get('/execute-test', [ExecuteTestController::class , 'create'])->name('execute-test.create');
    Route::post('/execute-test', [ExecuteTestController::class , 'store'])->middleware('throttle:15,1')->name('execute-test.store');

    // Report
    Route::get('/report', [ReportController::class , 'index'])->name('report.index');
    Route::get('/report/export', [ReportController::class , 'export'])->name('report.export');

    // Certificates
    Route::get('/certificates', [CertificateController::class , 'index'])->name('certificates.index');
    Route::get('/certificates/{id}/pdf', [CertificateController::class , 'downloadPdf'])->name('certificates.pdf');

    // Performance
    Route::get('/performance', [PerformanceController::class , 'index'])->name('performance.index');

    // Profile
    Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class , 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class , 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
