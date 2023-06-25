<?php

namespace App\Http\Controllers;

use App\Models\Area;
use Illuminate\Http\JsonResponse;

class AreaController extends Controller
{
    public function index(): JsonResponse
    {
        return success(Area::where('pid', 0)->get());
    }
}
