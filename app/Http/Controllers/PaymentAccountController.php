<?php

namespace App\Http\Controllers;

use App\Models\PaymentAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentAccountController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $account = PaymentAccount::where('user_id', $request->user()->id)
            ->orderBy('id', 'desc')
            ->paginate(getPerPage());

        return success($account);
    }
}
