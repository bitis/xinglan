<?php

namespace App\Http\Controllers;

use App\Http\Requests\BidOptionRequest;
use App\Models\BidOption;
use App\Models\Company;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class BidOptionController extends Controller
{

    public function index(Request $request): JsonResponse
    {
        $options = BidOption::with('company:id,name')
            ->where(function ($query) use ($request) {
                if ($request->input('company_id'))
                    return $query->where('company_id', $request->input('company_id'));
                return $query->whereIn('company_id', Company::getGroupId($request->user()->company_id));
            })
            ->when(strlen($status = $request->input('status')), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->paginate(getPerPage());

        return success($options);
    }

    public function form(BidOptionRequest $request): JsonResponse
    {
        $params = $request->only([
            'company_id',
            'bid_first_price',
            'min_goods_price',
            'mid_goods_price',
            'working_time_deadline_min',
            'resting_time_deadline_min',
            'working_time_deadline_mid',
            'resting_time_deadline_mid',
            'working_time_deadline_max',
            'resting_time_deadline_max',
            'order_dispatch_role',
            'status',
            'auto'
        ]);

        $bidOption = BidOption::findOr($request->input('id'), fn() => new BidOption());

        $bidOption->fill(Arr::whereNotNull($params));
        $bidOption->save();

        return success();
    }
}
