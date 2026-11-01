<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PlayerPortalController;
use App\Http\Controllers\SettlementStatementController;
use Illuminate\Support\Facades\Route;

// Public Player Kiosk & WhatsApp Play Portal
Route::get('/play', [PlayerPortalController::class, 'index'])->name('play.portal');
Route::post('/play/register', [PlayerPortalController::class, 'register'])->name('play.register');
Route::post('/play/chat', [PlayerPortalController::class, 'chat'])->name('play.chat');
Route::post('/play/fund', [PlayerPortalController::class, 'fund'])->name('play.fund');

// Authentication Routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Two-Factor Authentication (2FA) Challenge
Route::get('/two-factor-challenge', [AuthController::class, 'showTwoFactorChallenge'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [AuthController::class, 'verifyTwoFactor'])->name('two-factor.verify');
Route::post('/two-factor-challenge/resend', [AuthController::class, 'resendTwoFactor'])->name('two-factor.resend');

// Password Reset Flow
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Protected Dashboard Routes
Route::middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // Simulation & Live Testing APIs
    Route::post('/api/dashboard/simulate-bet', [DashboardController::class, 'simulateBet'])->name('dashboard.simulate-bet');
    Route::post('/api/dashboard/simulate-deposit', [DashboardController::class, 'simulateDeposit'])->name('dashboard.simulate-deposit');
    Route::post('/api/dashboard/toggle-2fa', [DashboardController::class, 'toggle2FA'])->name('dashboard.toggle-2fa');

    // Management Actions
    Route::post('/dashboard/create-tenant', [DashboardController::class, 'createTenant'])->name('dashboard.create-tenant');
    Route::post('/dashboard/invite-rep', [DashboardController::class, 'inviteRep'])->name('dashboard.invite-rep');

    // Settlement & Reconciliation Invoicing
    Route::get('/dashboard/export-settlement-csv', [SettlementStatementController::class, 'exportCsv'])->name('dashboard.export-csv');
    Route::get('/dashboard/settlement-invoice', [SettlementStatementController::class, 'printInvoice'])->name('dashboard.invoice');
});
