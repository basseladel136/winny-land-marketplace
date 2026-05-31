<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'             => $this->id,
            'categoryId'     => $this->category_id,
            'category'       => new CategoryResource($this->whenLoaded('category')),
            'name'           => $this->name,
            'nameEn'         => $this->name_en,
            'nameAr'         => $this->name_ar,
            'description'    => $this->description,
            'descriptionEn'  => $this->description_en,
            'descriptionAr'  => $this->description_ar,
            'slug'           => $this->slug,
            'price'          => (float) $this->price,
            'comparePrice'   => $this->compare_price ? (float) $this->compare_price : null,
            'stock'          => $this->stock,
            'sku'            => $this->sku,
            'image'          => $this->image,
            'images'         => $this->images ?? [],
            'isActive'       => $this->is_active,
            'isFeatured'     => $this->is_featured,
            'averageRating'  => $this->whenCounted('reviews', fn () => round($this->reviews_avg_rating, 1)),
            'reviewCount'    => $this->whenCounted('reviews'),
            'createdAt'      => $this->created_at?->toIso8601String(),
        ];
    }
}
