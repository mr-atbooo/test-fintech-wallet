<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin array{user: \App\Models\User, access_token: string, access_token_expires_at: \Illuminate\Support\Carbon, refresh_token: string, refresh_token_expires_at: \Illuminate\Support\Carbon}
 */
class AuthResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'user' => UserResource::make($this->resource['user']),
            'access_token' => $this->resource['access_token'],
            'access_token_expires_at' => $this->resource['access_token_expires_at']->toIso8601String(),
            'refresh_token' => $this->resource['refresh_token'],
            'refresh_token_expires_at' => $this->resource['refresh_token_expires_at']->toIso8601String(),
            'token_type' => 'Bearer',
        ];
    }
}
