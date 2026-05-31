<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'customerName'    => ['required', 'string', 'max:255'],
            'customerEmail'   => ['required', 'email', 'max:255'],
            'customerPhone'   => ['nullable', 'string', 'max:30'],
            'shippingAddress' => ['required', 'string', 'max:1000'],
            'notes'           => ['nullable', 'string', 'max:1000'],
            'couponCode'      => ['nullable', 'string', 'max:50'],
            'paymentMethod'   => ['required', 'string', 'in:cod,paymob'],
        ];
    }
}
