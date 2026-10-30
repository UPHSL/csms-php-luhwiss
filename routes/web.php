<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResidentController;
use App\Http\Controllers\ServiceRequestController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index']);

Route::get('/health', [HealthController::class, 'index']);

Route::get('/residents', [ResidentController::class, 'index'])->name('residents.index');
Route::get('/residents/create', [ResidentController::class, 'create'])->name('residents.create');
Route::post('/residents', [ResidentController::class, 'store'])->name('residents.store');
Route::get('/residents/{resident}/edit', [ResidentController::class, 'edit'])
    ->whereNumber('resident')->name('residents.edit');
Route::put('/residents/{resident}', [ResidentController::class, 'update'])
    ->whereNumber('resident')->name('residents.update');
Route::post('/residents/{resident}/deactivate', [ResidentController::class, 'deactivate'])
    ->whereNumber('resident')->name('residents.deactivate');

Route::get('/service-requests', [ServiceRequestController::class, 'index'])->name('service-requests.index');
Route::get('/service-requests/create', [ServiceRequestController::class, 'create'])->name('service-requests.create');
Route::post('/service-requests', [ServiceRequestController::class, 'store'])->name('service-requests.store');
Route::get('/service-requests/{serviceRequest}', [ServiceRequestController::class, 'show'])
    ->whereNumber('serviceRequest')->name('service-requests.show');
Route::post('/service-requests/{serviceRequest}/start', [ServiceRequestController::class, 'start'])
    ->whereNumber('serviceRequest')->name('service-requests.start');
Route::post('/service-requests/{serviceRequest}/complete', [ServiceRequestController::class, 'complete'])
    ->whereNumber('serviceRequest')->name('service-requests.complete');
Route::post('/service-requests/{serviceRequest}/cancel', [ServiceRequestController::class, 'cancel'])
    ->whereNumber('serviceRequest')->name('service-requests.cancel');
