<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'nameEn'    => ['required', 'string', 'max:255'],
            'nameAr'    => ['required', 'string', 'max:255'],
            'isActive'  => ['nullable', 'boolean'],
            'sortOrder' => ['nullable', 'integer'],
        ];
    }
}
