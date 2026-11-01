<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Services\Bot\LottoConversationEngine;
use App\Services\WhatsApp\WhatsAppManager;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class TwilioWebhookController extends Controller
{
    public function __construct(
        protected WhatsAppManager $whatsAppManager,
        protected LottoConversationEngine $engine
    ) {}

    public function handle(Request $request): Response
    {
        $to = (string)$request->input('To', '');
        $from = (string)$request->input('From', '');
        $body = (string)$request->input('Body', '');

        Log::info("[TwilioWebhook] Incoming message from {$from} to {$to}: {$body}");

        // 1. Resolve tenant by destination WhatsApp number
        $cleanTo = str_replace('whatsapp:', '', $to);
        $tenant = Tenant::where('whatsapp_number', $to)
            ->orWhere('whatsapp_number', $cleanTo)
            ->where('is_active', true)
            ->first();

        if (!$tenant) {
            // Fallback to first active tenant in development
            $tenant = Tenant::where('is_active', true)->first();
            if (!$tenant) {
                Log::warning("[TwilioWebhook] No active tenant found for recipient: {$to}");
                return response('No tenant found', 404);
            }
        }

        // 2. Validate signature
        $driver = $this->whatsAppManager->driver($tenant);
        if (!$driver->validateSignature($request)) {
            Log::warning("[TwilioWebhook] Invalid signature from IP: " . $request->ip());
            return response('Invalid signature', 403);
        }

        // 3. Parse incoming message into DTO
        $messageDTO = $driver->parseIncomingRequest($request);

        // 4. Pass to conversational engine
        try {
            $this->engine->handle($tenant, $messageDTO);
        } catch (\Throwable $e) {
            Log::error("[TwilioWebhook] Engine execution error: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
        }

        // Return empty TwiML or 200 OK
        return response('<Response></Response>', 200, ['Content-Type' => 'text/xml']);
    }
}

