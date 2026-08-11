<?php

use App\Http\Controllers\LecturerController;
use Illuminate\Support\Facades\Route;

Route::middleware('app.key')->group(function () {
    Route::get('/lecturers', [LecturerController::class, 'index'])->name('api.lecturers');
});
