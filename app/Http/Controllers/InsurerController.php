<?php

namespace App\Http\Controllers;

use App\Models\Insurer;
use Illuminate\Http\JsonResponse;

class InsurerController extends Controller
{
    /**
     * 保险公司列表
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        return success(Insurer::all());
    }
}
