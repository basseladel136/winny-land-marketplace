<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CategoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'nameEn'       => $this->name_en,
            'nameAr'       => $this->name_ar,
            'slug'         => $this->slug,
            'isActive'     => $this->is_active,
            'sortOrder'    => $this->sort_order,
            'productCount' => $this->whenCounted('products'),
        ];
    }
}
