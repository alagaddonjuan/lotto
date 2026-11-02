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

// Open Self-Registration is strictly disabled in institutional mode
// Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
// Route::post('/register', [AuthController::class, 'register'])->name('register.post');

// Two-Factor Authentication (2FA) Challenge (Login)
Route::get('/two-factor-challenge', [AuthController::class, 'showTwoFactorChallenge'])->name('two-factor.challenge');
Route::post('/two-factor-challenge', [AuthController::class, 'verifyTwoFactor'])->name('two-factor.verify');
Route::post('/two-factor-challenge/resend', [AuthController::class, 'resendTwoFactor'])->name('two-factor.resend');

// Mandatory 2FA Configuration (Required for All Staff)
Route::middleware(['auth'])->group(function () {
    Route::get('/two-factor-setup', [AuthController::class, 'showSetupTwoFactor'])->name('two-factor.setup');
    Route::post('/two-factor-setup', [AuthController::class, 'enableTwoFactor'])->name('two-factor.setup.post');
});

// Signed Staff Invitation Routes
Route::get('/invitations/accept/{token}', [\App\Http\Controllers\Auth\OperatorInviteController::class, 'showAcceptForm'])->name('invitations.accept');
Route::post('/invitations/accept/{token}', [\App\Http\Controllers\Auth\OperatorInviteController::class, 'accept'])->name('invitations.accept.post');

// Password Reset Flow
Route::get('/forgot-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
Route::post('/forgot-password', [AuthController::class, 'sendResetLink'])->name('password.email');
Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update');

// Protected Clearinghouse Dashboard Routes (Auth + Mandatory 2FA)
Route::middleware(['auth', 'two-factor.mandatory'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('home');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/api/dashboard/stats', [DashboardController::class, 'stats'])->name('dashboard.stats');

    // Operator Staff Cryptographic Invitations
    Route::post('/dashboard/invitations', [\App\Http\Controllers\Auth\OperatorInviteController::class, 'invite'])->name('dashboard.invitations.create');
    Route::post('/dashboard/invite-rep', [\App\Http\Controllers\Auth\OperatorInviteController::class, 'invite'])->name('dashboard.invite-rep');

    // Simulation & Live Testing APIs
    Route::post('/api/dashboard/simulate-bet', [DashboardController::class, 'simulateBet'])->name('dashboard.simulate-bet');
    Route::post('/api/dashboard/simulate-deposit', [DashboardController::class, 'simulateDeposit'])->name('dashboard.simulate-deposit');
    Route::post('/api/dashboard/toggle-2fa', [DashboardController::class, 'toggle2FA'])->name('dashboard.toggle-2fa');

    // Management Actions
    Route::post('/dashboard/create-tenant', [DashboardController::class, 'createTenant'])->name('dashboard.create-tenant');

    // Draw Lifecycle & Maker-Checker Operations
    Route::get('/dashboard/draws', [\App\Http\Controllers\Dashboard\DrawManagementController::class, 'index'])->name('dashboard.draws.index');
    Route::post('/dashboard/draws', [\App\Http\Controllers\Dashboard\DrawManagementController::class, 'store'])->name('dashboard.draws.store');
    Route::post('/dashboard/draws/{draw}/enter-results', [\App\Http\Controllers\Dashboard\DrawManagementController::class, 'enterResults'])->name('dashboard.draws.enter-results');
    Route::post('/dashboard/draws/{draw}/approve-results', [\App\Http\Controllers\Dashboard\DrawManagementController::class, 'approveResults'])->name('dashboard.draws.approve-results');

    // Settlement & Reconciliation Invoicing
    Route::get('/dashboard/export-settlement-csv', [SettlementStatementController::class, 'exportCsv'])->name('dashboard.export-csv');
    Route::get('/dashboard/settlement-invoice', [SettlementStatementController::class, 'printInvoice'])->name('dashboard.invoice');
});
