<?php

namespace App\Http\Controllers;

use App\Models\Insurer;
use Illuminate\Http\JsonResponse;

class InsurerController extends Controller
{
    public function index(): JsonResponse
    {
        return success(Insurer::all());
    }
}
