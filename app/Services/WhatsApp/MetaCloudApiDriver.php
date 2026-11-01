<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGatewayInterface;
use App\DTOs\IncomingMessageDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MetaCloudApiDriver implements WhatsAppGatewayInterface
{
    protected ?string $accessToken = null;
    protected ?string $phoneNumberId = null;
    protected ?string $appSecret = null;
    protected string $apiVersion = 'v20.0';

    public function __construct(array $config = [])
    {
        $this->accessToken = $config['access_token'] ?? config('services.meta.access_token') ?? '';
        $this->phoneNumberId = $config['phone_number_id'] ?? config('services.meta.phone_number_id') ?? '';
        $this->appSecret = $config['app_secret'] ?? config('services.meta.app_secret') ?? '';
    }

    public function parseIncomingRequest(Request $request): IncomingMessageDTO
    {
        $entry = $request->input('entry.0.changes.0.value', []);
        $message = $entry['messages'][0] ?? [];
        $metadata = $entry['metadata'] ?? [];

        $body = '';
        if (isset($message['text']['body'])) {
            $body = $message['text']['body'];
        } elseif (isset($message['button']['text'])) {
            $body = $message['button']['text'];
        } elseif (isset($message['interactive']['button_reply']['title'])) {
            $body = $message['interactive']['button_reply']['title'];
        } elseif (isset($message['interactive']['list_reply']['title'])) {
            $body = $message['interactive']['list_reply']['title'];
        }

        return new IncomingMessageDTO(
            from: (string)($message['from'] ?? ''),
            to: (string)($metadata['display_phone_number'] ?? $this->phoneNumberId),
            rawBody: $body,
            messageId: (string)($message['id'] ?? ''),
            driver: 'meta',
            rawPayload: $request->all()
        );
    }

    public function validateSignature(Request $request): bool
    {
        if (app()->environment('local', 'testing') && config('services.meta.bypass_signature', false)) {
            return true;
        }

        $hubSignature = $request->header('X-Hub-Signature-256');
        if (!$hubSignature || empty($this->appSecret)) {
            return false;
        }

        $calculatedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $this->appSecret);

        return hash_equals($calculatedSignature, $hubSignature);
    }

    public function sendTextMessage(string $to, string $text, ?string $from = null): bool
    {
        $recipient = str_replace(['whatsapp:', '+', ' '], '', $to);
        $phoneId = $from ?: $this->phoneNumberId;

        $response = Http::withToken($this->accessToken)
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'text',
                'text' => ['preview_url' => false, 'body' => $text],
            ]);

        if ($response->failed()) {
            Log::error('[MetaCloudDriver] Send text failed', ['response' => $response->json()]);
            return false;
        }

        return true;
    }

    public function sendQuickReplyButtons(string $to, string $text, array $buttons, ?string $from = null): bool
    {
        $recipient = str_replace(['whatsapp:', '+', ' '], '', $to);
        $phoneId = $from ?: $this->phoneNumberId;

        $formattedButtons = [];
        foreach (array_slice($buttons, 0, 3) as $i => $btn) {
            $title = is_array($btn) ? ($btn['title'] ?? $btn['text']) : $btn;
            $id = is_array($btn) ? ($btn['id'] ?? "btn_{$i}") : "btn_{$i}";
            $formattedButtons[] = [
                'type' => 'reply',
                'reply' => ['id' => (string)$id, 'title' => substr($title, 0, 20)],
            ];
        }

        $response = Http::withToken($this->accessToken)
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'button',
                    'body' => ['text' => $text],
                    'action' => ['buttons' => $formattedButtons],
                ],
            ]);

        return $response->successful();
    }

    public function sendListMenu(string $to, string $header, string $buttonTitle, array $sections, ?string $from = null): bool
    {
        $recipient = str_replace(['whatsapp:', '+', ' '], '', $to);
        $phoneId = $from ?: $this->phoneNumberId;

        $response = Http::withToken($this->accessToken)
            ->post("https://graph.facebook.com/{$this->apiVersion}/{$phoneId}/messages", [
                'messaging_product' => 'whatsapp',
                'recipient_type' => 'individual',
                'to' => $recipient,
                'type' => 'interactive',
                'interactive' => [
                    'type' => 'list',
                    'body' => ['text' => $header],
                    'action' => [
                        'button' => substr($buttonTitle, 0, 20),
                        'sections' => $sections,
                    ],
                ],
            ]);

        return $response->successful();
    }
}
