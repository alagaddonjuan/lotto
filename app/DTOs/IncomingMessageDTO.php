<?php

namespace App\DTOs;

class IncomingMessageDTO
{
    public function __construct(
        public readonly string $from,           // e.g. "whatsapp:+2348012345678" or "+2348012345678"
        public readonly string $to,             // e.g. "whatsapp:+14155238886" (the tenant's WhatsApp sender number)
        public readonly string $rawBody,        // The text message or button payload
        public readonly string $messageId,      // Message SID or WAMID
        public readonly string $driver = 'meta', // "meta" Cloud API
        public readonly array $rawPayload = []   // Original payload for audit/debugging
    ) {}

    public function normalizedPhone(): string
    {
        return str_replace(['whatsapp:', '+', ' '], '', $this->from);
    }

    public function normalizedText(): string
    {
        return trim(strtoupper($this->rawBody));
    }
}

