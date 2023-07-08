<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderQuotation;
use Exception;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use function Composer\Autoload\includeFile;

class OrderQuotationController extends Controller
{

    /**
     * 报价大厅
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return success();
    }

    /**
     * 获取当前公司某工单的报价详情 （物损公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByOrderId(Request $request): JsonResponse
    {
        $quotation = OrderQuotation::where('company_id', $request->user()->id)
            ->where('order_id', $request->input('order_id'))
            ->first();

        return success($quotation);
    }

    /**
     * 提交报价（物损公司）
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $user = $request->user();

        $order = Order::find($request->input('order_id'));

        $quotation = OrderQuotation::where('company_id', $user->compnay_id)
            ->findOr($request->input('id'), fn() => new OrderQuotation(['security_code' => Str::random()]));

        $quotation->fill($request->only([
            'order_id',
            'plan_type',
            'repair_days',
            'repair_cost',
            'other_cost',
            'total_cost',
            'profit_margin',
            'profit_margin_ratio',
            'repair_remark',
            'total_price',
            'images',
            'submit'
        ]));

        $quotation->company_id = $user->company_id;

        $quotation->save();

        $quotation->items()->delete();

        $quotation->items()->createMany($request->input('items'));

        if ($quotation->submit) {
            // TODO 提交审核
        }

        return success();
    }

    /**
     * 导入报价明细
     * @param Request $request
     * @return JsonResponse
     */
    public function import(Request $request): JsonResponse
    {
        $file = $request->file('file');

        $extension = strtolower($file->extension());

        if ($extension !== 'xlsx' && $extension !== 'xls') {
            return fail('文件格式不正确');
        }

        try {
            $quotation = OrderQuotation::where('order_id', $request->input('order_id'))
                ->where('company_id', $request->user()->company_id)
                ->first();

            if (!$quotation) {
                $quotation = new OrderQuotation([
                    'order_id' => $request->input('order_id'),
                    'company_id' => $request->user()->company_id,
                ]);
                $quotation->save();
            }

            $reader = match ($extension) {
                'xlsx' => new Xlsx(),
                'xls' => new Xls(),
            };

            $items = [];
            $sheet = $reader->load($file->getRealPath())->getSheet(0)->toArray();
            foreach ($sheet as $index => $row) {
                if ($index === 0) continue;
                $items[] = [
                    'order_quotation_id' => $quotation->id,
                    'sort_num' => $index,
                    'name' => $row[0],
                    'specs' => $row[1],
                    'unit' => $row[2],
                    'number' => $row[3],
                    'price' => $row[4],
                    'total_price' => $row[5],
                    'remark' => $row[6],
                ];
            }

            $quotation->items()->delete();

            $quotation->items()->createMany($items);
        } catch (Exception $e) {
            return fail($e->getMessage());
        }

        return success();
    }

    /**
     * 生成报价单
     *
     * @param string $code
     * @return View
     */
    public
    function getBySecurityCode(string $code): View
    {
        $quotation = OrderQuotation::with(['company', 'order', 'order.company'])->where('security_code', $code)->first();

        return view('quota.table')
            ->with(compact('quotation'));
    }
}
