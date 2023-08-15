<?php

namespace App\Http\Controllers;

use App\Models\History;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * 历史数据查询
 */
class StoryController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        History::when($request->input('name'), function ($query, $name) {
            $query->where('name', 'like', '%' . $name . '%');
        })->when($request->input('province'), function ($query, $province) {
            $query->where('province', $province);
        })->when($request->input('city'), function ($query, $city) {
            $query->where('city', $city);
        })->when($request->input('area'), function ($query, $area) {
            $query->where('area', $area);
        })->orderBy('id', 'desc')->paginate(getPerPage());

        return success();
    }
}
