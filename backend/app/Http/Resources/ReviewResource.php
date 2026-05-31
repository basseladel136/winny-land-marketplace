<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReviewResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'userId'    => $this->user_id,
            'productId' => $this->product_id,
            'rating'    => $this->rating,
            'body'      => $this->body,
            'user'      => new UserResource($this->whenLoaded('user')),
            'createdAt' => $this->created_at?->toIso8601String(),
        ];
    }
}
