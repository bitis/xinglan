<?php

namespace App\Http\Controllers;

use App\Http\Requests\MenuRequest;
use App\Models\Company;
use App\Models\Menu;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MenuController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $company_type = Company::find($user->company_id)?->getRawOriginal('type');

        $isAdmin = $request->user()->hasRole('admin');
        $menus = $isAdmin ? Menu::all() : Menu::whereRaw("find_in_set($company_type, show_if_type)")->get();

        $permissions = $request->user()->getAllPermissions();

        foreach ($menus as $menu) {
            $menu->checked = $isAdmin;
            foreach ($permissions as $permission) {
                if ($menu->permission == $permission->name) {
                    $menu->checked = true;
                    break;
                }
            }
        }

        return success(array_values(Arr::where($menus->toArray(), fn($menu) => $menu['checked'])));
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
