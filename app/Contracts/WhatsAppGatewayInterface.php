<?php

namespace App\Contracts;

use App\DTOs\IncomingMessageDTO;
use Illuminate\Http\Request;

interface WhatsAppGatewayInterface
{
    /**
     * Parse incoming webhook payload into a normalized DTO.
     */
    public function parseIncomingRequest(Request $request): IncomingMessageDTO;

    /**
     * Validate the webhook signature (X-Twilio-Signature or X-Hub-Signature-256).
     */
    public function validateSignature(Request $request): bool;

    /**
     * Send a standard text message.
     */
    public function sendTextMessage(string $to, string $text, ?string $from = null): bool;

    /**
     * Send interactive buttons (or fallback to numbered text menu if driver lacks button support).
     */
    public function sendQuickReplyButtons(string $to, string $text, array $buttons, ?string $from = null): bool;

    /**
     * Send an interactive list menu.
     */
    public function sendListMenu(string $to, string $header, string $buttonTitle, array $sections, ?string $from = null): bool;
}

