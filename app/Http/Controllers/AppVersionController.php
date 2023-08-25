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
     * @return JsonResponse
     */
    public function latest(): JsonResponse
    {
        return success(AppVersion::latest()->first());
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
            'app_url', 'version', 'version_number', 'must_update', 'current_version_number', 'apk_url'
        ));
        return success($version);
    }
}
