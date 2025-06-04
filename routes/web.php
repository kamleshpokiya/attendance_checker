<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;

Route::get('/', [AttendanceController::class, 'index'])->name('attendance');
Route::post('/check-attendance', [AttendanceController::class, 'check_attendance'])->name('check.attendance');
