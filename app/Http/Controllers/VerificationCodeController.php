<?php

namespace App\Http\Controllers;

use App\Common\Messages\VerificationCode;
use App\Models\VerificationCode as VerificationCodeModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Overtrue\EasySms\EasySms;
use Overtrue\EasySms\Exceptions\NoGatewayAvailableException;

class VerificationCodeController extends Controller
{
    public function get(Request $request, EasySms $easySms): JsonResponse
    {
        $code = rand(100000, 999999);

        try {
            $result = $easySms->send($request->input('phone_number'), new VerificationCode($code));

            VerificationCodeModel::create([
                'phone_number' => $request->input('phone_number'),
                'code' => $code,
                'getaway' => $request->input('getaway'),
                'expiration_date' => now()->addMinutes(5)
            ]);
        } catch (NoGatewayAvailableException  $e) {
            Log::error('SMS_ERROR', $e->results);
        }

        return success();
    }
}
