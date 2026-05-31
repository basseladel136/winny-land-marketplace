<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }

    /**
     * Throttle the login attempt using the built-in ThrottlesLogins trait
     * (we handle rate limiting at the route level via middleware instead).
     */
}
