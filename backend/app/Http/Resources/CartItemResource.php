<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartItemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'productId' => $this->product_id,
            'quantity'  => $this->quantity,
            'product'   => new ProductResource($this->whenLoaded('product')),
            'lineTotal' => (float) ($this->product->price * $this->quantity),
        ];
    }
}
