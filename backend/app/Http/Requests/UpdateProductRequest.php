<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nameEn'        => ['sometimes', 'string', 'max:255'],
            'nameAr'        => ['sometimes', 'string', 'max:255'],
            'descriptionEn' => ['nullable', 'string', 'max:10000'],
            'descriptionAr' => ['nullable', 'string', 'max:10000'],
            'categoryId'    => ['nullable', 'integer', 'exists:categories,id'],
            'price'         => ['sometimes', 'numeric', 'min:0'],
            'comparePrice'  => ['nullable', 'numeric', 'min:0'],
            'stock'         => ['sometimes', 'integer', 'min:0'],
            'sku'           => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($this->route('product'))],
            'image'         => ['nullable', 'url:http,https', 'max:500'],
            'isActive'      => ['nullable', 'boolean'],
            'isFeatured'    => ['nullable', 'boolean'],
        ];
    }
}
