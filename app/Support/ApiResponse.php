<?php

namespace App\Support;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

class ApiResponse
{
    public static function success(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        $links = [];

        if ($data instanceof JsonResource) {
            // Pull meta/links from the full wrapped response, but resolve the
            // actual payload directly — response()->getData(true)['data'] is
            // unreliable when a resource's own fields happen to include a
            // top-level "data" key (Laravel then assumes it's pre-wrapped
            // and skips wrapping, so ['data'] would return the wrong thing).
            $response = $data->response()->getData(true);
            $meta = array_merge($response['meta'] ?? [], $meta);
            $links = $response['links'] ?? [];
            $data = $data->resolve(request());
        }

        $payload = ['data' => $data, 'message' => $message];

        if ($meta !== []) {
            $payload['meta'] = $meta;
        }

        if ($links !== []) {
            $payload['links'] = $links;
        }

        return response()->json($payload, $status);
    }

    public static function error(string $message, array $errors = [], int $status = 400): JsonResponse
    {
        return response()->json(array_filter([
            'message' => $message,
            'errors' => $errors ?: null,
        ], fn ($value) => ! is_null($value)), $status);
    }
}
