<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'orderNumber'     => $this->order_number,
            'status'          => $this->status,
            'subtotal'        => (float) $this->subtotal,
            'discountAmount'  => (float) $this->discount_amount,
            'total'           => (float) $this->total,
            'couponCode'      => $this->coupon_code,
            'paymentStatus'   => $this->payment_status,
            'paymentMethod'   => $this->payment_method,
            'paymentReference'=> $this->payment_reference,
            'customerName'    => $this->customer_name,
            'customerEmail'   => $this->customer_email,
            'customerPhone'   => $this->customer_phone,
            'shippingAddress' => $this->shipping_address,
            'notes'           => $this->notes,
            'items'           => OrderItemResource::collection($this->whenLoaded('items')),
            'createdAt'       => $this->created_at?->toIso8601String(),
            'updatedAt'       => $this->updated_at?->toIso8601String(),
        ];
    }
}
