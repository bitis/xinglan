<?php

namespace App\Http\Controllers;

use App\Models\Menu;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        return success(Menu::all());
    }

    /**
     * 修改菜单
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $menu = Menu::updateOrCreate(
            ['id' => $request->input('id')],
            $request->only([
                'parent_id', 'order', 'title', 'icon', 'uri'
            ])
        );

        $menu->roles = $request->input('roles');
        $menu->save();

        return success();
    }
}
