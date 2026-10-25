<?php

use App\Http\Controllers\HealthController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ResidentController;
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
