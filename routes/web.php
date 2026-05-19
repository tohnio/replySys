<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FinanceiroController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Financeiro
Route::prefix('financeiro')->name('financeiro.')->group(function () {
    Route::get('/dashboard', [FinanceiroController::class, 'dashboard'])->name('dashboard');
    Route::get('/despesas', [FinanceiroController::class, 'despesas'])->name('despesas');
    Route::post('/despesas', [FinanceiroController::class, 'storeDespesa'])->name('despesas.store');
    Route::patch('/despesas/{despesa}/pagar', [FinanceiroController::class, 'pagarDespesa'])->name('despesas.pagar');
    Route::delete('/despesas/{despesa}', [FinanceiroController::class, 'destroyDespesa'])->name('despesas.destroy');
});