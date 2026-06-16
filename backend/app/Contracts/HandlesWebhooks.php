<?php

namespace App\Contracts;

/**
 * Implemented only by gateways that receive asynchronous payment
 * notifications. Kept separate from {@see PaymentGateway} so methods
 * without webhooks (e.g. cash on delivery) aren't forced to implement
 * irrelevant methods (Interface Segregation).
 */
interface HandlesWebhooks
{
    /**
     * Verify an incoming webhook signature.
     *
     * @param  array<string, mixed>  $data
     */
    public function verifyWebhook(array $data, string $hmac): bool;

    /**
     * Apply a verified webhook to its corresponding order.
     *
     * @param  array<string, mixed>  $data
     */
    public function handleWebhook(array $data): void;
}
