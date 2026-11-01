<?php

use App\Http\Controllers\MetaWebhookController;
use App\Http\Controllers\PaymentWebhookController;
use App\Http\Controllers\TwilioWebhookController;
use Illuminate\Support\Facades\Route;

// Twilio WhatsApp Ingress
Route::post('/webhook/twilio', [TwilioWebhookController::class, 'handle'])->name('webhook.twilio');

// Meta Cloud API Webhook Ingress (Verification & Incoming)
Route::get('/webhook/meta', [MetaWebhookController::class, 'verify'])->name('webhook.meta.verify');
Route::post('/webhook/meta', [MetaWebhookController::class, 'handle'])->name('webhook.meta.handle');

// Dedicated Virtual Account (DVA) & Payment Gateways (Paystack / Monnify / Flutterwave)
Route::post('/webhook/payment', [PaymentWebhookController::class, 'handle'])->name('webhook.payment');

// Health Check
Route::get('/ping', fn () => response()->json(['status' => 'ok', 'app' => 'Q4I Lotto Middleware', 'timestamp' => now()]));
