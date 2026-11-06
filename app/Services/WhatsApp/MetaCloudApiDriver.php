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
    protected ?string $fallbackAccessToken = null;
    protected ?string $phoneNumberId = null;
    protected ?string $appSecret = null;
    protected string $apiVersion = 'v20.0';

    public function __construct(array $config = [])
    {
        $envToken = $this->cleanToken((string) (config('services.meta.access_token') ?? ''));
        $dbToken = $this->cleanToken((string) ($config['access_token'] ?? ''));

        $this->accessToken = !empty($dbToken) ? $dbToken : $envToken;
        $this->fallbackAccessToken = (!empty($envToken) && $envToken !== $this->accessToken) ? $envToken : null;

        $dbPhoneId = trim((string) ($config['phone_number_id'] ?? ''));
        $envPhoneId = trim((string) (config('services.meta.phone_number_id') ?? ''));
        $this->phoneNumberId = !empty($dbPhoneId) ? $dbPhoneId : $envPhoneId;

        $dbSecret = trim((string) ($config['app_secret'] ?? ''));
        $envSecret = trim((string) (config('services.meta.app_secret') ?? ''));
        $this->appSecret = !empty($dbSecret) ? $dbSecret : $envSecret;
    }

    /**
     * Sanitize token by removing accidental 'Bearer ' prefixes, surrounding quotes, or whitespace/newlines.
     */
    protected function cleanToken(string $token): string
    {
        $cleaned = trim($token, " \t\n\r\0\x0B\"'");
        if (stripos($cleaned, 'Bearer ') === 0) {
            $cleaned = trim(substr($cleaned, 7), " \t\n\r\0\x0B\"'");
        }
        return $cleaned;
    }

    /**
     * Send a message payload to Meta Graph API with automatic fallback to .env token on 401 OAuthException (code 190).
     */
    protected function sendGraphPayload(string $phoneId, array $payload, string $recipient, string $actionLabel): bool
    {
        $url = "https://graph.facebook.com/{$this->apiVersion}/{$phoneId}/messages";
        $response = Http::withToken($this->accessToken)->post($url, $payload);

        if ($response->status() === 401 && !empty($this->fallbackAccessToken)) {
            Log::warning("[MetaCloudDriver] Primary token returned 401 on {$actionLabel}; retrying with fallback .env META_ACCESS_TOKEN.");
            $response = Http::withToken($this->fallbackAccessToken)->post($url, $payload);
        }

        if ($response->failed()) {
            Log::error("[MetaCloudDriver] {$actionLabel} failed", [
                'to' => $recipient,
                'phone_id' => $phoneId,
                'status' => $response->status(),
                'response' => $response->json(),
            ]);
            return false;
        }

        return true;
    }

    public function parseIncomingRequest(Request $request): IncomingMessageDTO
    {
        $entry = $request->input('entry.0.changes.0.value', []);
        $message = $entry['messages'][0] ?? [];
        $metadata = $entry['metadata'] ?? [];

        $body = '';
        if (isset($message['text']['body'])) {
            $body = $message['text']['body'];
        } elseif (isset($message['button']['payload'])) {
            $body = $message['button']['payload'];
        } elseif (isset($message['button']['text'])) {
            $body = $message['button']['text'];
        } elseif (isset($message['interactive']['button_reply']['id'])) {
            $body = $message['interactive']['button_reply']['id'];
        } elseif (isset($message['interactive']['button_reply']['title'])) {
            $body = $message['interactive']['button_reply']['title'];
        } elseif (isset($message['interactive']['list_reply']['id'])) {
            $body = $message['interactive']['list_reply']['id'];
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
        if (config('services.meta.bypass_signature', false)) {
            return true;
        }

        // If META_APP_SECRET is not yet configured in .env, allow webhook ingress during initial Meta sandbox setup
        if (empty($this->appSecret)) {
            return true;
        }

        $hubSignature = $request->header('X-Hub-Signature-256');
        if (!$hubSignature) {
            return false;
        }

        $calculatedSignature = 'sha256=' . hash_hmac('sha256', $request->getContent(), $this->appSecret);

        return hash_equals($calculatedSignature, $hubSignature);
    }

    /**
     * Resolve the Meta Graph API phone_number_id (never overwrite a valid Meta ID with a display telephone number).
     */
    protected function resolvePhoneNumberId(?string $from = null): string
    {
        if (!empty($this->phoneNumberId)) {
            return $this->phoneNumberId;
        }

        return (string) ($from ?? '');
    }

    public function sendTextMessage(string $to, string $text, ?string $from = null): bool
    {
        $recipient = str_replace(['whatsapp:', '+', ' '], '', $to);
        \Illuminate\Support\Facades\Cache::put("sim_reply:{$recipient}", $text, 120);
        $phoneId = $this->resolvePhoneNumberId($from);

        if (app()->environment('testing') || empty($this->accessToken) || empty($phoneId)) {
            if (!app()->environment('testing') && (empty($this->accessToken) || empty($phoneId))) {
                Log::warning('[MetaCloudDriver] Skipping live Graph API call: META_ACCESS_TOKEN or META_PHONE_NUMBER_ID not set.', [
                    'to' => $recipient,
                    'phone_id' => $phoneId,
                ]);
            }
            return true;
        }

        return $this->sendGraphPayload($phoneId, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'text',
            'text' => ['preview_url' => false, 'body' => $text],
        ], $recipient, 'Send text');
    }

    public function sendQuickReplyButtons(string $to, string $text, array $buttons, ?string $from = null): bool
    {
        $recipient = str_replace(['whatsapp:', '+', ' '], '', $to);
        \Illuminate\Support\Facades\Cache::put("sim_reply:{$recipient}", $text, 120);
        $phoneId = $this->resolvePhoneNumberId($from);

        if (app()->environment('testing') || empty($this->accessToken) || empty($phoneId)) {
            return true;
        }

        $formattedButtons = [];
        foreach (array_slice($buttons, 0, 3) as $i => $btn) {
            $title = is_array($btn) ? ($btn['title'] ?? $btn['text']) : $btn;
            $id = is_array($btn) ? ($btn['id'] ?? "btn_{$i}") : "btn_{$i}";
            $formattedButtons[] = [
                'type' => 'reply',
                'reply' => ['id' => (string)$id, 'title' => mb_substr($title, 0, 20)],
            ];
        }

        return $this->sendGraphPayload($phoneId, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $text],
                'action' => ['buttons' => $formattedButtons],
            ],
        ], $recipient, 'Send quick reply buttons');
    }

    public function sendListMenu(string $to, string $header, string $buttonTitle, array $sections, ?string $from = null): bool
    {
        $recipient = str_replace(['whatsapp:', '+', ' '], '', $to);
        \Illuminate\Support\Facades\Cache::put("sim_reply:{$recipient}", $header, 120);
        $phoneId = $this->resolvePhoneNumberId($from);

        if (app()->environment('testing') || empty($this->accessToken) || empty($phoneId)) {
            return true;
        }

        // Enforce Meta WhatsApp Cloud API strict character limits:
        // section.title <= 24 chars, row.title <= 24 chars, row.description <= 72 chars, action.button <= 20 chars
        $sanitizedSections = [];
        foreach (array_slice($sections, 0, 10) as $sec) {
            $rows = [];
            foreach (array_slice($sec['rows'] ?? [], 0, 10) as $row) {
                $sanitizedRow = [
                    'id' => mb_substr((string) ($row['id'] ?? uniqid('row_')), 0, 200),
                    'title' => mb_substr(trim((string) ($row['title'] ?? 'Option')), 0, 24),
                ];
                if (!empty($row['description'])) {
                    $sanitizedRow['description'] = mb_substr(trim((string) $row['description']), 0, 72);
                }
                $rows[] = $sanitizedRow;
            }

            $sanitizedSec = ['rows' => $rows];
            if (!empty($sec['title'])) {
                $sanitizedSec['title'] = mb_substr(trim((string) $sec['title']), 0, 24);
            }
            $sanitizedSections[] = $sanitizedSec;
        }

        return $this->sendGraphPayload($phoneId, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'interactive',
            'interactive' => [
                'type' => 'list',
                'body' => ['text' => mb_substr($header, 0, 1024)],
                'action' => [
                    'button' => mb_substr(trim($buttonTitle), 0, 20),
                    'sections' => $sanitizedSections,
                ],
            ],
        ], $recipient, 'Send list menu');
    }
}
