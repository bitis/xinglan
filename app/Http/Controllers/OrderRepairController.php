<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderRepairController extends Controller
{
    public function detail(Request $request): JsonResponse
    {
        $id = $request->user()->company_id;

        return success();
    }

    public function form(Request $request): JsonResponse
    {
        return success();
    }
}
