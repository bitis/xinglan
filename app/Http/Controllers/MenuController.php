<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        return success(Menu::with('children')->get());
    }

    /**
     * 修改菜单
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(MenuRequest $request): JsonResponse
    {
        $menu = Menu::updateOrCreate(
            ['id' => $request->input('id')],
            $request->only(['parent_id', 'type', 'name', 'icon', 'path', 'visible', 'sort_num', 'remark'])
        );
        $menu->save();

        $permission = $menu->path ?: "PERMISSION_" . $menu->id . "_" . $menu->name;
        Permission::where('name', $menu->permission)->update(['name' => $permission]);

        $menu->permission = $permission;
        $menu->save();
        return success();
    }

    public function delete(Request $request): JsonResponse
    {
        $id = $request->input('id');

        if (!Menu::where('id', $id)->exists())
            return success();

        Menu::where('id', $id)->delete();
        Menu::where('parent_id', $id)->delete();

        return success();
    }
}
