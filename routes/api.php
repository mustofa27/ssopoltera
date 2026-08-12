<?php

use App\Http\Controllers\LecturerController;
use App\Http\Controllers\ReferenceDataController;
use Illuminate\Support\Facades\Route;

Route::middleware('app.key')->group(function () {
    Route::get('/lecturers', [LecturerController::class, 'index'])->name('api.lecturers');
    Route::get('/departments', [ReferenceDataController::class, 'departments'])->name('api.departments');
    Route::get('/program-studies', [ReferenceDataController::class, 'programStudies'])->name('api.program-studies');
});
