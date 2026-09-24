<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LeadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [LeadController::class, 'create'])->name('home');
Route::post('/leads', [LeadController::class, 'store'])->name('leads.store');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/leads/{lead}', [LeadController::class, 'show'])->whereNumber('lead')->name('leads.show');
Route::get('/leads/{lead}/edit', [LeadController::class, 'edit'])->whereNumber('lead')->name('leads.edit');
Route::put('/leads/{lead}', [LeadController::class, 'update'])->whereNumber('lead')->name('leads.update');
Route::post('/leads/{lead}/notes', [LeadController::class, 'storeNote'])->whereNumber('lead')->name('leads.notes.store');
Route::patch('/leads/{lead}/status', [LeadController::class, 'updateStatus'])->whereNumber('lead')->name('leads.status.update');
