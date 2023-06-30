<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BidOptionController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        return success();
    }

    public function form(Request $request): JsonResponse
    {
        return success();
    }
}
