<?php

namespace App\Common\HuJiaBao;

use Illuminate\Http\JsonResponse;

/**
 * 沪家保接收推送响应
 */
class Response
{
    public static function success(): JsonResponse
    {
        return response()->json([
            "Head" => [
                "ErrorMessage" => "",
                "RequestCode" => "W01",
                "ResponseCode" => "1"
            ]
        ]);
    }

    public static function failed($message = ''): JsonResponse
    {
        return response()->json([
            "Head" => [
                "ErrorMessage" => $message,
                "RequestCode" => "W01",
                "ResponseCode" => "0"
            ]
        ]);
    }
}
