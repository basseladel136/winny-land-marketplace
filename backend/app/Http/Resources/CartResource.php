<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $items = $this->items->load('product');

        return [
            'id'        => $this->id,
            'items'     => CartItemResource::collection($items),
            'itemCount' => $items->sum('quantity'),
            'subtotal'  => (float) $items->sum(fn ($i) => $i->product->price * $i->quantity),
        ];
    }
}
