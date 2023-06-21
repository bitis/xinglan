<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    /**
     * 获取外协公司列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $providers = $request->user()->company->providers()
            ->when($request->input('name'), function ($query, $name) {
                $query->where('name', 'like', "%$name%");
            })->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('id', 'desc')
            ->paginate();

        return success($providers);
    }
}
