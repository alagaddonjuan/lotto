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
        $mode = $request->query('hub_mode');
        $token = $request->query('hub_verify_token');
        $challenge = $request->query('hub_challenge');

        $verifyToken = config('services.meta.verify_token', 'q4i_lotto_meta_secret');

        if ($mode === 'subscribe' && $token === $verifyToken) {
            return response($challenge, 200);
        }

        return response('Forbidden', 403);
    }

    /**
     * Handle incoming Meta WhatsApp messages (POST).
     */
    public function handle(Request $request): JsonResponse
    {
        $displayPhoneNumber = (string)$request->input('entry.0.changes.0.value.metadata.display_phone_number', '');
        $phoneNumberId = (string)$request->input('entry.0.changes.0.value.metadata.phone_number_id', '');

        // Resolve tenant
        $tenant = Tenant::where('whatsapp_number', $displayPhoneNumber)
            ->orWhere('whatsapp_number', "+{$displayPhoneNumber}")
            ->where('is_active', true)
            ->first();

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

        if (!empty($messageDTO->rawBody)) {
            try {
                if (app()->environment('testing') || config('queue.default') === 'sync') {
                    $this->engine->handle($tenant, $messageDTO);
                } else {
                    \App\Jobs\ProcessWhatsAppWebhookJob::dispatch($tenant, $messageDTO)->onQueue('whatsapp-outbound');
                }
            } catch (\Throwable $e) {
                Log::error('[MetaWebhook] Engine error: ' . $e->getMessage());
            }
        }

        return response()->json(['status' => 'success'], 200);
    }
}

