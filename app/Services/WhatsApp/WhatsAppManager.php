<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGatewayInterface;
use App\Models\Tenant;
use InvalidArgumentException;

class WhatsAppManager
{
    /**
     * Resolve the WhatsApp gateway driver for a specific tenant or globally.
     */
    public function driver(?Tenant $tenant = null): WhatsAppGatewayInterface
    {
        $driverName = $tenant?->whatsapp_driver ?? config('services.whatsapp.default', 'twilio');
        $config = $tenant?->driver_config ?? [];

        return match ($driverName) {
            'twilio' => new TwilioWhatsAppDriver($config),
            'meta' => new MetaCloudApiDriver($config),
            default => throw new InvalidArgumentException("Unsupported WhatsApp driver [{$driverName}]"),
        };
    }
}

