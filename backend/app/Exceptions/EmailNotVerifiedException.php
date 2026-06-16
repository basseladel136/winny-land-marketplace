<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Thrown when a user with valid credentials tries to log in before verifying
 * their email. A fresh OTP is sent and the frontend is told to route the user
 * to the verification screen.
 */
class EmailNotVerifiedException extends Exception
{
    public function __construct(public string $emailAddress)
    {
        parent::__construct('Email address is not verified.');
    }

    public function render(Request $request): JsonResponse
    {
        return response()->json([
            'message'          => 'Please verify your email to continue. We sent a new code to your inbox.',
            'email_unverified' => true,
            'email'            => $this->emailAddress,
        ], 403);
    }
}
