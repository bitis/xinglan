<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\GoodsPrice;
use App\Models\GoodsPriceCat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;

class GoodsPriceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $list = GoodsPrice::when($request->input('province'), function ($query, $province) {
            $query->where('province', $province);
        })->when($request->input('cat_id'), function ($query, $cat_id) {
            $query->where(function ($query) use ($cat_id) {
                $query->where('cat_id', $cat_id)->orWhere('cat_parent_id', $cat_id);
            });
        })->when($request->input('name'), function ($query, $name) {
            $query->where(function ($query) use ($name) {
                $query->where('product_name', 'like', '%' . $name . '%');
            });
        })->paginate(getPerPage());

        return success($list);
    }

    /**
     * 编辑
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $goods = GoodsPrice::updateOrCreate(['id' => $request->input('id')], $request->only([
            'province',
            'cat_name',
            'product_name',
            'spec',
            'unit',
            'brand',
            'unit_price',
            'describe_image',
            'remark',
            'status',
        ]));

        if (!$goodsCat = GoodsPriceCat::where('name', $goods->cat_name)->first()) return fail('分类不存在');

        $goods->cat_id = $goodsCat->id;
        $goods->cat_parent_id = $goodsCat->parent_id;

        $goods->save();

        return success();
    }

    public function cats(Request $request): JsonResponse
    {
        $cats = GoodsPriceCat::when($request->input('parent_id'), function ($query, $parent_id) {
            return $query->where('parent_id', $parent_id);
        })->when($request->input('name'), function ($query, $name) {
            return $query->where('name', 'like', '%' . $name . '%');
        })->paginate(getPerPage());

        return success($cats);
    }

    public function catsTree(): JsonResponse
    {
        $cats = GoodsPriceCat::with('children')->where('level', 1)->get();

        return success($cats);
    }

    /**
     * 导入
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $file = $request->file('file');

        if (empty($file)) return fail('请上传文件');

        $extension = strtolower($file->extension());

        if ($extension !== 'xlsx' && $extension !== 'xls') return fail('文件格式不正确');

        $reader = match ($extension) {
            'xlsx' => new Xlsx(),
            'xls' => new Xls(),
        };

        $company_id = $request->user()->company_id;
        $company_name = Company::find($company_id)?->name;

        $cats = [];

        foreach (GoodsPriceCat::all() as $item) {
            $cats[$item->name] = ['id' => $item->id, 'pid' => $item->parent_id];
        }

        $now = now()->toDateTimeString();
        $items = [];
        $images = [];
        $sheet = $reader->load($file->getRealPath())->getSheet(0);

        $table = $sheet->toArray();

        foreach ($table as $index => $row) {
            if ($index === 0) continue;
            if (!isset($cats[$row[1]])) return fail(sprintf("第%s行分类填写有误：%s", $index + 1, $row[1]));
        }

        if ($sheet->getDrawingCollection()->count() != count($table) - 1) return fail('请保证文件内图片数量与数据条数一致');
        foreach ($sheet->getDrawingCollection() as $drawing) {
            if ($drawing instanceof MemoryDrawing) {
                ob_start();
                call_user_func(
                    $drawing->getRenderingFunction(),
                    $drawing->getImageResource()
                );
                $imageContents = ob_get_contents();
                ob_end_clean();

                switch ($drawing->getMimeType()) {
                    case MemoryDrawing::MIMETYPE_PNG :
                        $imagesExtension = 'png';
                        break;
                    case MemoryDrawing::MIMETYPE_GIF:
                        $imagesExtension = 'gif';
                        break;
                    case MemoryDrawing::MIMETYPE_JPEG :
                        $imagesExtension = 'jpg';
                        break;
                }
            } else {
                if ($drawing->getPath()) {
                    // Check if the source is a URL or a file path
                    if ($drawing->getIsURL()) {
                        $imageContents = file_get_contents($drawing->getPath());
                        $filePath = tempnam(sys_get_temp_dir(), 'Drawing');
                        file_put_contents($filePath, $imageContents);
                        $mimeType = mime_content_type($filePath);
                        // You could use the below to find the extension from mime type.
                        // https://gist.github.com/alexcorvi/df8faecb59e86bee93411f6a7967df2c#gistcomment-2722664
                        $imagesExtension = mime2ext($mimeType);
                        unlink($filePath);
                    } else {
                        $zipReader = fopen($drawing->getPath(), 'r');
                        $imageContents = '';
                        while (!feof($zipReader)) {
                            $imageContents .= fread($zipReader, 1024);
                        }
                        fclose($zipReader);
                        $imagesExtension = $drawing->getExtension();
                    }
                }
            }
            $fileName = '/upload/goods/' . date('Ymd') . '/' . Str::random(40) . '.' . $imagesExtension;
            if (!Storage::disk('qcloud')->put($fileName, $imageContents)) return fail('图片上传失败');
            $images[] = $fileName;
        }

        foreach ($table as $index => $row) {
            if ($index === 0) continue;

            $items[] = [
                'company_id' => $company_id,
                'company_name' => $company_name,
                'province' => $row[0],
                'cat_id' => $cats[$row[1]]['id'],
                'cat_name' => $row[1],
                'cat_parent_id' => $cats[$row[1]]['pid'],
                'product_name' => $row[2],
                'spec' => $row[3],
                'brand' => $row[4],
                'unit' => $row[5],
                'unit_price' => $row[6],
                'remark' => $row[7],
                'describe_image' => $images[$index - 1],
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        GoodsPrice::insert($items);

        return success();
    }
}
