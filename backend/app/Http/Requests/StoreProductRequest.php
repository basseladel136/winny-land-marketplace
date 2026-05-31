<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nameEn'         => ['required', 'string', 'max:255'],
            'nameAr'         => ['required', 'string', 'max:255'],
            'descriptionEn'  => ['nullable', 'string', 'max:10000'],
            'descriptionAr'  => ['nullable', 'string', 'max:10000'],
            'categoryId'     => ['nullable', 'integer', 'exists:categories,id'],
            'price'          => ['required', 'numeric', 'min:0'],
            'comparePrice'   => ['nullable', 'numeric', 'min:0'],
            'stock'          => ['required', 'integer', 'min:0'],
            'sku'            => ['nullable', 'string', 'max:100', 'unique:products,sku'],
            'image'          => ['nullable', 'url', 'max:500'],
            'isActive'       => ['nullable', 'boolean'],
            'isFeatured'     => ['nullable', 'boolean'],
        ];
    }
}
