<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCouponRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'code'           => ['required', 'string', 'max:50', 'unique:coupons,code'],
            'type'           => ['required', 'string', 'in:percent,fixed'],
            'value'          => ['required', 'numeric', 'min:0'],
            'minOrderAmount' => ['nullable', 'numeric', 'min:0'],
            'maxUses'        => ['nullable', 'integer', 'min:1'],
            'isActive'       => ['nullable', 'boolean'],
            'expiresAt'      => ['nullable', 'date'],
        ];
    }
}
