<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\Bot\LottoConversationEngine;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class MetaWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppManager $whatsAppManager,
        protected LottoConversationEngine $engine
    ) {}

    /**
     * Meta Webhook Verification Handshake (GET).
     */
    public function verify(Request $request): Response
    {
        $mode = $request->query('hub_mode') ?? $request->input('hub_mode');
        $token = $request->query('hub_verify_token') ?? $request->input('hub_verify_token');
        $challenge = $request->query('hub_challenge') ?? $request->input('hub_challenge');

        $verifyToken = config('services.meta.verify_token', 'q4i_lotto_meta_secret');
        $tenantToken = Tenant::where('is_active', true)->first()?->driver_config['verify_token'] ?? null;

        if ($mode === 'subscribe' && ($token === $verifyToken || ($tenantToken && $token === $tenantToken))) {
            Log::info('[MetaWebhook] Verification challenge succeeded.');
            return response((string) $challenge, 200)->header('Content-Type', 'text/plain');
        }

        Log::warning('[MetaWebhook] Verification challenge failed.', ['mode' => $mode, 'token' => $token]);
        return response('Forbidden', 403);
    }

    /**
     * Handle incoming Meta WhatsApp messages (POST).
     */
    public function handle(Request $request): JsonResponse
    {
        $displayPhoneNumber = preg_replace('/[^0-9]/', '', (string) $request->input('entry.0.changes.0.value.metadata.display_phone_number', ''));
        $phoneNumberId = (string) $request->input('entry.0.changes.0.value.metadata.phone_number_id', '');

        // Resolve tenant by display phone number, phone_number_id in driver_config, or active fallback
        $tenant = null;
        if (!empty($displayPhoneNumber)) {
            $tenant = Tenant::where('is_active', true)
                ->where(function ($q) use ($displayPhoneNumber) {
                    $q->where('whatsapp_number', $displayPhoneNumber)
                      ->orWhere('whatsapp_number', "+{$displayPhoneNumber}");
                })
                ->first();
        }

        if (!$tenant && !empty($phoneNumberId)) {
            $tenant = Tenant::where('is_active', true)
                ->get()
                ->first(fn (Tenant $t) => ($t->driver_config['phone_number_id'] ?? null) === $phoneNumberId);
        }

        if (!$tenant) {
            $tenant = Tenant::where('is_active', true)->first();
        }

        if (!$tenant) {
            return response()->json(['status' => 'tenant_not_found'], 404);
        }

        $driver = $this->whatsAppManager->driver($tenant);
        if (!$driver->validateSignature($request)) {
            Log::warning('[MetaWebhook] Signature check failed');
            return response()->json(['status' => 'invalid_signature'], 403);
        }

        $messageDTO = $driver->parseIncomingRequest($request);

        // Deduplicate retries from Meta Cloud API using messageId (wamid)
        if (!empty($messageDTO->messageId)) {
            $dedupKey = 'meta_wamid:' . $messageDTO->messageId;
            if (\Illuminate\Support\Facades\Cache::has($dedupKey)) {
                Log::info("[MetaWebhook] Duplicate messageId {$messageDTO->messageId} ignored.");
                return response()->json(['status' => 'duplicate_ignored'], 200);
            }
            \Illuminate\Support\Facades\Cache::put($dedupKey, true, now()->addHours(6));
        }

        if (!empty($messageDTO->rawBody)) {
            try {
                if (app()->environment('testing') || config('queue.default') === 'sync') {
                    $this->engine->handle($tenant, $messageDTO);
                } else {
                    \App\Jobs\ProcessWhatsAppWebhookJob::dispatch($tenant, $messageDTO)->onQueue('whatsapp-outbound');
                }
            } catch (\Throwable $e) {
                Log::error('[MetaWebhook] Engine error: ' . $e->getMessage(), [
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}

