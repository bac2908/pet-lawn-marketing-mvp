<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeadController::class, 'create'])->name('home');
Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
