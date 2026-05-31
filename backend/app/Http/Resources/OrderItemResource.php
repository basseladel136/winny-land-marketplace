<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'productId'    => $this->product_id,
            'productName'  => $this->product_name,
            'productImage' => $this->product_image,
            'price'        => (float) $this->price,
            'quantity'     => $this->quantity,
            'subtotal'     => (float) $this->subtotal,
        ];
    }
}
