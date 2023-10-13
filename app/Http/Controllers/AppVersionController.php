<?php

namespace App\Http\Controllers;

use App\Models\AppVersion;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AppVersionController extends Controller
{
    /**
     * 最新版本
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function latest(Request $request): JsonResponse
    {
        $type = $request->input('type') ?? 0;
        return success(AppVersion::where('type', $type)->latest()->first());
    }

    /**
     * 添加最新版本
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $version = AppVersion::create($request->only(
            'type', 'app_url', 'version', 'version_number', 'must_update', 'current_version_number', 'apk_url',
            'app_url2', 'apk_url2'
        ));
        return success($version);
    }
}
