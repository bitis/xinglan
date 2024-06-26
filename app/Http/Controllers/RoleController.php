<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use App\Models\Company;
use App\Models\Menu;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    /**
     * 角色列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        return success(Role::where('company_id', $request->user()->company_id)->get());
    }

    /**
     * 修改角色的权限
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function permission(Request $request): JsonResponse
    {
        $role_id = $request->input('id');
        $menu_id = $request->input('menu_id');

        if (!$role = Role::findById($role_id))
            return fail('修改的角色不存在');

        try {
            DB::beginTransaction();
            $role->syncPermissions(Menu::whereIn('id', $menu_id)->pluck('permission'));
            DB::commit();
        } catch (\Exception $exception) {
            DB::rollBack();
            return fail($exception->getMessage());
        }

        return success();
    }

    /**
     * 修改角色
     *
     * @param RoleRequest $request
     * @return JsonResponse
     */
    public function form(RoleRequest $request): JsonResponse
    {
        $role = Role::findOr($request->input('id'), fn() => new Role([
            'company_id' => $request->user()->company_id,
        ]));

        $role->fill($request->only('show_name', 'remark', 'status'));
        $role->name = $request->user()->company_id . '_' . $request->input('show_name');

        $role->save();

        return success();
    }

    /**
     * 获取带权限检查的菜单列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function menus(Request $request): JsonResponse
    {
        $user = $request->user();
        $company_type = Company::find($user->company_id)?->getRawOriginal('type');

        $isAdmin = $request->user()->hasRole('admin');
        $menus = $isAdmin ? Menu::all() : Menu::whereIn('show_if_type', [0, $company_type])->get();
        $permissions = Role::findById($request->input('role_id'))->getAllPermissions();

        foreach ($menus as $menu) {
            $menu->checked = false;
            foreach ($permissions as $permission) {
                if ($menu->permission == $permission->name) {
                    $menu->checked = true;
                    break;
                }
            }
        }

        return success($menus);
    }

    /**
     * 获取公司下的角色列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByCompany(Request $request): JsonResponse
    {
        return success(Role::where('company_id', $request->input('company_id'))->get());
    }
}
