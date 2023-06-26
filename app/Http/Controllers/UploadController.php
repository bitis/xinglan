<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function form(Request $request): JsonResponse
    {
        $file = $request->file('file');

        $fileName = '/uploads/' . date('Ymd') . '/' . $file->hashName();

        if (Storage::disk('oss')->put($fileName, $file->getContent()))
            return success($fileName);

        return fail('上传失败');
    }
}
