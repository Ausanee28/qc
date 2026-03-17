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

Route::middleware(['auth'])->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class , 'index'])->name('dashboard');

    // Receive Job
    Route::get('/receive-job', [ReceiveJobController::class , 'create'])->name('receive-job.create');
    Route::post('/receive-job', [ReceiveJobController::class , 'store'])->middleware('throttle:15,1')->name('receive-job.store');
    Route::put('/receive-job/{id}', [ReceiveJobController::class, 'update'])->middleware('throttle:15,1')->name('receive-job.update');
    Route::delete('/receive-job/{id}', [ReceiveJobController::class, 'destroy'])->name('receive-job.destroy');
    Route::patch('/receive-job/{id}/restore', [ReceiveJobController::class, 'restore'])->name('receive-job.restore');
    Route::patch('/receive-job/{id}/close', [ReceiveJobController::class, 'close'])->name('receive-job.close');
    Route::patch('/receive-job/{id}/reopen', [ReceiveJobController::class, 'reopen'])->name('receive-job.reopen');

    // Execute Test
    Route::get('/execute-test', [ExecuteTestController::class , 'create'])->name('execute-test.create');
    Route::post('/execute-test', [ExecuteTestController::class , 'store'])->middleware('throttle:15,1')->name('execute-test.store');
    Route::put('/execute-test/{id}', [ExecuteTestController::class, 'update'])->middleware('throttle:15,1')->name('execute-test.update');
    Route::delete('/execute-test/{id}', [ExecuteTestController::class, 'destroy'])->name('execute-test.destroy');
    Route::patch('/execute-test/{id}/restore', [ExecuteTestController::class, 'restore'])->name('execute-test.restore');

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

    // Master Data
    Route::prefix('master-data')->middleware('admin')->name('master-data.')->group(function () {
        Route::resource('departments', \App\Http\Controllers\DepartmentController::class)->except(['create', 'show', 'edit']);
        Route::resource('equipments', \App\Http\Controllers\EquipmentController::class)->except(['create', 'show', 'edit']);
        Route::resource('test-methods', \App\Http\Controllers\TestMethodController::class)->except(['create', 'show', 'edit']);
        Route::resource('users', \App\Http\Controllers\UserController::class)->except(['create', 'show', 'edit']);
        Route::resource('external-users', \App\Http\Controllers\ExternalUserController::class)->except(['create', 'show', 'edit']);
    });
});

require __DIR__ . '/auth.php';
