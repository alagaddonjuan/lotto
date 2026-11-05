<?php

namespace App\Services\WhatsApp;

use App\Contracts\WhatsAppGatewayInterface;
use App\Models\Tenant;

class WhatsAppManager
{
    /**
     * Resolve the WhatsApp gateway driver for a specific tenant or globally.
     * Exclusively uses Meta Cloud API (graph.facebook.com/v20.0).
     */
    public function driver(?Tenant $tenant = null): WhatsAppGatewayInterface
    {
        $config = $tenant?->driver_config ?? [];

        return new MetaCloudApiDriver($config);
    }
}


