<?php

namespace App\Contracts;

use App\Models\Order;

/**
 * A payment gateway knows how to begin payment for an order.
 *
 * New payment methods are added by implementing this interface and
 * registering them in {@see \App\Services\Payment\PaymentManager} — no
 * existing gateway or the OrderService needs to change (Open/Closed).
 */
interface PaymentGateway
{
    /**
     * Begin payment for the given order.
     *
     * @return array<string, mixed> Gateway-specific payload for the client
     *                              (e.g. an iframe/redirect URL, or a marker
     *                              that no online action is required).
     */
    public function initiate(Order $order): array;
}
