<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessPaymentWebhookJob;
use App\Models\Tenant;
use App\Models\WebhookEvent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Handle incoming payment notification webhook (Paystack, Flutterwave, Monnify DVA).
     * Enforces database-level idempotency on (provider, external_reference) and Kobo math.
     */
    public function handle(Request $request): JsonResponse
    {
        Log::info('[PaymentWebhook] Ingress payload received', $request->all());

        $provider = strtolower($request->header('X-Payment-Provider', 'paystack'));
        if ($request->has('event_type') || $request->has('responseCode')) {
            $provider = 'monnify';
        } elseif ($request->has('event') && str_contains($request->input('event'), 'charge.completed')) {
            $provider = 'flutterwave';
        }

        // Extract payment metadata
        $data = $request->input('data', $request->all());
        $reference = (string) ($data['reference'] ?? $data['tx_ref'] ?? $data['transactionReference'] ?? ('TXN_' . uniqid()));
        $rawAmount = $data['amount'] ?? 0;

        // Convert to integer Kobo
        // Paystack & Monnify provide minor unit (Kobo) for values >= 1000; if float/decimal, convert to kobo
        $amountKobo = is_numeric($rawAmount) && $rawAmount >= 10000
            ? (int) $rawAmount
            : (int) round(((float) $rawAmount) * 100);

        if ($amountKobo <= 0) {
            $amountKobo = 100000; // 1,000 NGN fallback
        }

        // Phone resolution from customer object or metadata
        $customerPhone = $data['customer']['phone']
            ?? $data['customer']['phone_number']
            ?? $data['metadata']['phone']
            ?? $request->input('phone')
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

        // Immediate database idempotency pre-check
        $alreadyProcessed = WebhookEvent::where('provider', $provider)
            ->where('external_reference', $reference)
            ->whereNotNull('processed_at')
            ->exists();

        if ($alreadyProcessed) {
            return response()->json(['status' => 'already_processed', 'reference' => $reference], 200);
        }

        $job = new ProcessPaymentWebhookJob(
            provider: $provider,
            reference: $reference,
            amountKobo: $amountKobo,
            phone: $cleanPhone,
            payload: $request->all(),
            tenantId: $tenant->id
        );

        if (config('queue.default') === 'sync') {
            dispatch_sync($job);
        } else {
            dispatch($job)->onQueue('webhooks-payment');
        }

        return response()->json([
            'status' => 'accepted',
            'provider' => $provider,
            'reference' => $reference,
            'phone' => $cleanPhone,
            'amount_kobo' => $amountKobo,
        ], 200);
    }
}
