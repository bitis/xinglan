<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UploadController extends Controller
{
    public function form(Request $request)
    {
        $file = $request->file('file');

        if (!$file) return fail('必须上传一个文件');

        $fileName = '/uploads/' . date('Ymd') . '/' . $file->hashName();

        if (Storage::disk('qcloud')->put($fileName, $file->getContent()))
            return success($fileName);

        return fail('上传失败');
    }
}
