<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'phone'           => $this->phone,
            'address'         => $this->address,
            'role'            => $this->role,
            'locale'          => $this->locale,
            'avatar'          => $this->avatarUrl(),
            'isActive'        => $this->is_active,
            'emailVerifiedAt' => $this->email_verified_at?->toIso8601String(),
            'createdAt'       => $this->created_at?->toIso8601String(),
        ];
    }

    /**
     * Resolve the avatar to an absolute URL. Uploaded avatars are stored as a
     * relative path on the public disk; externally-seeded avatars may already
     * be full URLs.
     */
    protected function avatarUrl(): ?string
    {
        if (! $this->avatar) {
            return null;
        }

        if (str_starts_with($this->avatar, 'http://') || str_starts_with($this->avatar, 'https://')) {
            return $this->avatar;
        }

        return Storage::disk('public')->url($this->avatar);
    }
}
