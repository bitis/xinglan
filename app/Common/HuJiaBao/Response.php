<?php

namespace App\Common\HuJiaBao;

use Illuminate\Http\JsonResponse;

/**
 * 沪家保接收推送响应
 */
class Response
{
    public static function success($requestCode): JsonResponse
    {
        return response()->json([
            "Head" => [
                "ErrorMessage" => "",
                "RequestCode" => $requestCode,
                "ResponseCode" => "1"
            ]
        ]);
    }

    public static function failed($requestCode, $message = ''): JsonResponse
    {
        return response()->json([
            "Head" => [
                "ErrorMessage" => $message,
                "RequestCode" => $requestCode,
                "ResponseCode" => "0"
            ]
        ]);
    }
}
