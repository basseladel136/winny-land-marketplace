<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use InvalidArgumentException;

/**
 * Resolves the {@see PaymentGateway} for a given payment method.
 *
 * This is the single place that maps a method string to a concrete
 * gateway, so adding a new payment provider is a one-line change here
 * plus a new class — callers depend only on the PaymentGateway abstraction.
 */
class PaymentManager
{
    /** @var array<string, class-string<PaymentGateway>> */
    private const GATEWAYS = [
        'paymob' => PaymobGateway::class,
        'cod'    => CashOnDeliveryGateway::class,
    ];

    public function gateway(string $method): PaymentGateway
    {
        $class = self::GATEWAYS[$method] ?? null;

        if ($class === null) {
            throw new InvalidArgumentException("Unsupported payment method: {$method}");
        }

        return app($class);
    }
}
