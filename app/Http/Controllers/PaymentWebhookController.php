<?php

namespace App\Http\Controllers;

use App\Models\Player;
use App\Models\Tenant;
use App\Services\Ledger\LedgerService;
use App\Services\Operators\OperatorManager;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    public function __construct(
        protected LedgerService $ledgerService,
        protected OperatorManager $operatorManager,
        protected WhatsAppManager $whatsAppManager
    ) {}

    /**
     * Handle incoming payment notification webhook (Paystack, Flutterwave, Monnify DVA).
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('[PaymentWebhook] Ingress payload received', $request->all());

        $event = $request->input('event') ?? $request->input('event_type') ?? 'charge.success';

        // Extract payment metadata
        $data = $request->input('data', $request->all());
        $reference = $data['reference'] ?? $data['tx_ref'] ?? 'TXN_' . uniqid();
        $rawAmount = $data['amount'] ?? 0;
        
        // Most gateways report in minor units (kobo/cents)
        $amount = is_numeric($rawAmount) && $rawAmount > 1000 ? $rawAmount / 100 : (float)$rawAmount;
        if ($amount <= 0) {
            $amount = 1000.00; // fallback default
        }

        // Phone resolution from customer object or metadata
        $customerPhone = $data['customer']['phone'] 
            ?? $data['customer']['phone_number'] 
            ?? $data['metadata']['phone'] 
            ?? null;

        if (!$customerPhone) {
            Log::warning('[PaymentWebhook] No customer phone found in payment payload', $data);
            return response()->json(['status' => 'ignored_no_phone'], 200);
        }

        $cleanPhone = preg_replace('/[^0-9]/', '', $customerPhone);
        if (str_starts_with($cleanPhone, '0')) {
            $cleanPhone = '234' . substr($cleanPhone, 1);
        }

        // Match tenant
        $tenant = Tenant::first();
        if (!$tenant) {
            return response()->json(['status' => 'tenant_not_found'], 404);
        }

        // Find or create player
        $player = Player::firstOrCreate(
            ['tenant_id' => $tenant->id, 'phone' => $cleanPhone],
            [
                'name' => $data['customer']['first_name'] ?? 'Player ' . substr($cleanPhone, -4),
                'pin_hash' => bcrypt('1234'),
                'last_active_at' => now(),
            ]
        );

        // Credit operator wallet
        $operator = $this->operatorManager->driver($tenant);
        $operator->creditWallet($cleanPhone, $amount, $reference);
        $wallet = $operator->checkWalletBalance($cleanPhone);
        $newBal = number_format($wallet['balance'], 2);

        // Send instant WhatsApp receipt to player
        try {
            $gateway = $this->whatsAppManager->driver($tenant);
            $receiptMsg = "✅ *Deposit Confirmed!*\n"
                . "━━━━━━━━━━━━━━━━\n"
                . "💰 Amount: *{$tenant->currency} " . number_format($amount, 2) . "*\n"
                . "💳 Ref: *{$reference}*\n"
                . "💼 New Balance: *{$tenant->currency} {$newBal}*\n"
                . "━━━━━━━━━━━━━━━━\n\n"
                . "Your wallet is funded! Reply *1* to view today's lotto games. 🍀";

            $gateway->sendTextMessage($cleanPhone, $receiptMsg, $tenant->whatsapp_number);
        } catch (\Throwable $e) {
            Log::error('[PaymentWebhook] Failed to dispatch WhatsApp receipt', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Wallet credited successfully',
            'phone' => $cleanPhone,
            'amount' => $amount,
            'balance' => $wallet['balance'],
        ], 200);
    }
}

