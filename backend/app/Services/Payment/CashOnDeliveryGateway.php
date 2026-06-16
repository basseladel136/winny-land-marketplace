<?php

namespace App\Services\Payment;

use App\Contracts\PaymentGateway;
use App\Models\Order;

/**
 * Cash on delivery: nothing is charged online. The order stays unpaid
 * until the courier collects payment, so there is no online step to
 * initiate and no webhook to handle.
 */
class CashOnDeliveryGateway implements PaymentGateway
{
    public function initiate(Order $order): array
    {
        return [
            'paymentMethod'  => 'cod',
            'requiresAction' => false,
        ];
    }
}
