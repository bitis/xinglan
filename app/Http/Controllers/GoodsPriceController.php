<?php

namespace App\Http\Controllers;

use App\Models\GoodsPriceCat;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GoodsPriceController extends Controller
{
    public function index(Request $request)
    {

    }

    public function form(Request $request)
    {

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
}
