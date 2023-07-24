<?php

namespace App\Traits;

use App\Utils\HttpStatusCode;
use Illuminate\Http\JsonResponse;

trait ResponseHelper
{
    /**
     * @param array|string|object|mixed $data
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public function success($data, string $message = "Data retrieved successfully", int $code = HttpStatusCode::OK): JsonResponse
    {
        return response()->json([
            'error' => false,
            'message' => $message,
            'data' => $data,
        ], $code);
    }

    /**
     * @param string $message
     * @param int $code
     * @return JsonResponse
     */
    public function error(string $message = "Bad Request", int $code = HttpStatusCode::BAD_REQUEST): JsonResponse
    {
        return response()->json([
            'error' => true,
            'message' => $message,
        ], $code);
    }
}
