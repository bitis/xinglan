<?php

namespace App\Http\Controllers;

use App\Models\BidOption;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ConfigController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $company = $request->user()->company;

        $bid_opening = boolval(BidOption::where('company_id', $company->id)->first()?->status);

        return success(compact('bid_opening'));
    }
}
