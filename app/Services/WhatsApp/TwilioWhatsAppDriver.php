<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGatewayInterface;
use App\DTOs\IncomingMessageDTO;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Twilio\Rest\Client;
use Twilio\Security\RequestValidator;

class TwilioWhatsAppDriver implements WhatsAppGatewayInterface
{
    protected ?Client $client = null;
    protected ?string $accountSid = null;
    protected ?string $authToken = null;
    protected ?string $defaultFrom = null;

    public function __construct(array $config = [])
    {
        $this->accountSid = $config['account_sid'] ?? config('services.twilio.sid') ?? '';
        $this->authToken = $config['auth_token'] ?? config('services.twilio.token') ?? '';
        $this->defaultFrom = $config['from'] ?? config('services.twilio.whatsapp_from') ?? 'whatsapp:+14155238886';

        if (!empty($this->accountSid) && !empty($this->authToken)) {
            $this->client = new Client($this->accountSid, $this->authToken);
        }
    }

    public function parseIncomingRequest(Request $request): IncomingMessageDTO
    {
        return new IncomingMessageDTO(
            from: (string)$request->input('From', ''),
            to: (string)$request->input('To', ''),
            rawBody: (string)$request->input('Body', ''),
            messageId: (string)$request->input('MessageSid', ''),
            driver: 'twilio',
            rawPayload: $request->all()
        );
    }

    public function validateSignature(Request $request): bool
    {
        // In local development or testing with sandbox, allow bypassing if configured
        if (app()->environment('local', 'testing') && config('services.twilio.bypass_signature', false)) {
            return true;
        }

        $signature = $request->header('X-Twilio-Signature');
        if (empty($signature) || empty($this->authToken)) {
            return false;
        }

        $validator = new RequestValidator($this->authToken);
        $url = $request->fullUrl();
        $postData = $request->post();

        return $validator->validate($signature, $url, $postData);
    }

    public function sendTextMessage(string $to, string $text, ?string $from = null): bool
    {
        if (!$this->client) {
            Log::warning('[TwilioDriver] Client not initialized. Message logged: ' . $text);
            return false;
        }

        $sender = $from ?: $this->defaultFrom;
        $recipient = str_starts_with($to, 'whatsapp:') ? $to : "whatsapp:{$to}";

        try {
            $this->client->messages->create($recipient, [
                'from' => $sender,
                'body' => $text,
            ]);
            return true;
        } catch (\Throwable $e) {
            Log::error('[TwilioDriver] Failed to send message: ' . $e->getMessage(), [
                'to' => $recipient,
                'from' => $sender,
            ]);
            return false;
        }
    }

    public function sendQuickReplyButtons(string $to, string $text, array $buttons, ?string $from = null): bool
    {
        // For Twilio WhatsApp, if buttons are not pre-approved in Content Templates,
        // fallback gracefully to a clean, numbered menu format that USSD users love!
        $menuText = $text . "\n\n";
        foreach ($buttons as $index => $btn) {
            $num = $index + 1;
            $title = is_array($btn) ? ($btn['title'] ?? $btn['text']) : $btn;
            $menuText .= "👉 *{$num}* - {$title}\n";
        }
        $menuText .= "\n_Reply with the number or option name._";

        return $this->sendTextMessage($to, $menuText, $from);
    }

    public function sendListMenu(string $to, string $header, string $buttonTitle, array $sections, ?string $from = null): bool
    {
        $menuText = "*{$header}*\n\n";
        $counter = 1;
        foreach ($sections as $section) {
            if (!empty($section['title'])) {
                $menuText .= "📁 *" . strtoupper($section['title']) . "*\n";
            }
            foreach ($section['rows'] as $row) {
                $menuText .= "• *{$counter}* - {$row['title']}";
                if (!empty($row['description'])) {
                    $menuText .= " ({$row['description']})";
                }
                $menuText .= "\n";
                $counter++;
            }
            $menuText .= "\n";
        }
        $menuText .= "_Reply with your choice._";

        return $this->sendTextMessage($to, $menuText, $from);
    }
}
