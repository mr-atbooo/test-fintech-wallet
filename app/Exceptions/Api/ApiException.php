<?php

namespace App\Exceptions\Api;

use App\Support\ApiResponse;
use Exception;
use Illuminate\Contracts\Debug\ShouldntReport;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ApiException extends Exception implements ShouldntReport
{
    public function __construct(string $message, protected int $status = 400, protected array $errors = [])
    {
        parent::__construct($message);
    }

    public function render(Request $request): JsonResponse
    {
        return ApiResponse::error($this->getMessage(), $this->errors, $this->status);
    }
}
