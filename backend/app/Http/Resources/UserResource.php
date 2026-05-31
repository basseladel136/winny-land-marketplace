<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'role'            => $this->role,
            'locale'          => $this->locale,
            'avatar'          => $this->avatar,
            'isActive'        => $this->is_active,
            'emailVerifiedAt' => $this->email_verified_at?->toIso8601String(),
            'createdAt'       => $this->created_at?->toIso8601String(),
        ];
    }
}
