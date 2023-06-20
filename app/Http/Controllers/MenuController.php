<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(): JsonResponse
    {
        return success(Menu::with('children')->orderBy('order')->get());
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
            $request->only(['parent_id', 'type', 'name', 'icon', 'path', 'visible', 'order', 'remark'])
        );
        $menu->save();

        $permission_name = $menu->path ?: "PERMISSION_" . $menu->id . "_" . $menu->name;

        if ($permission = Permission::where('name', $menu->permission)->firstOr(fn() => new Permission())) {
            $permission->name = $permission_name;
            $permission->save();
        }

        $menu->permission = $permission_name;
        $menu->save();
        return success();
    }

    public function delete(Request $request): JsonResponse
    {
        $id = $request->input('id');

        if (!$menu = Menu::find($id))
            return success();

        if ($permission = Permission::where('name', $menu->permission)->first()) {
            $permission->delete();
            DB::table('role_has_permissions')->where('permission_id', $permission->id)->delete();
        }

        Menu::where('id', $id)->delete();
        Menu::where('parent_id', $id)->update(['parent_id' => $menu->parent_id]);

        return success();
    }
}
