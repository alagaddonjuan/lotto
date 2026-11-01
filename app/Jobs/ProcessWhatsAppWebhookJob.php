<?php

namespace App\Jobs;

use App\DTOs\IncomingMessageDTO;
use App\Models\Tenant;
use App\Services\Bot\LottoConversationEngine;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWhatsAppWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Tenant $tenant,
        public IncomingMessageDTO $messageDto
    ) {}

    /**
     * Execute the job.
     */
    public function handle(LottoConversationEngine $engine): void
    {
        try {
            $engine->handle($this->tenant, $this->messageDto);
        } catch (\Throwable $e) {
            Log::error('[ProcessWhatsAppWebhookJob] Exception while executing bot state machine', [
                'tenant_id' => $this->tenant->id,
                'phone' => $this->messageDto->from,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }
}

