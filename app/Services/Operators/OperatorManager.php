<?php

namespace App\Services\Operators;

use App\Contracts\LottoOperatorContract;
use App\Models\Tenant;
use InvalidArgumentException;

class OperatorManager
{
    /**
     * Resolve the operator backend driver for a specific tenant.
     */
    public function driver(Tenant $tenant): LottoOperatorContract
    {
        $driverCode = $tenant->operator_driver ?: 'modern_lottery';
        $config = [
            'api_url' => $tenant->operator_api_url,
            'api_key' => $tenant->operator_api_key,
        ];

        return match ($driverCode) {
            'modern_lottery' => new ModernLotteryDriver($config),
            default => throw new InvalidArgumentException("Unsupported lottery operator driver [{$driverCode}]"),
        };
    }
}

