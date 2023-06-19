<?php

use Illuminate\Http\JsonResponse;

function fail($msg = 'FAIL', $code = -1): JsonResponse
{
    return response()->json([
        'code' => $code,
        'msg' => $msg,
        'data' => null
    ]);
}

function success($data = null): JsonResponse
{
    return response()->json([
        'code' => 0,
        'msg' => 'OK',
        'data' => $data
    ]);
}
