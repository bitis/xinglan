<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * 用户列表
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        $company_id = $request->input('company_id');
        $role = $request->input('role');
        $status = $request->input('status');
        $text = $request->input('text');

        $user = $request->user();

        $userList = User::with(['roles', 'company:id,name'])
            ->when(!$user->hasRole('admin'), function ($query) use ($company_id, $user) {
                if ($company_id) $query->where('company_id', $company_id);
                else $query->whereIn('company_id', Company::getGroupId($user->company_id));
            })->when($company_id, function ($query, $company_id) {
                $query->where('company_id', $company_id);
            })->when($role, function ($query, $role) {
                $query->role($role);
            })->when(strlen($status), function ($query) use ($status) {
                $query->where('status', $status);
            })->when($text, function ($query, $text) {
                $query->where('name', 'like', "%$text%")
                    ->orWhere('account', 'like', "%$text%")
                    ->orWhere('mobile', 'like', "%$text%");
            })->paginate(getPerPage());

        return success($userList);
    }

    /**
     * 新增、编辑
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(UserRequest $request): JsonResponse
    {
        $user = User::findOr($request->input('id'), fn() => new User());

        $role = $request->input('role');
        $password = $request->input('password');

        if (!empty($password)) {
            $user->password = bcrypt($password);
            $user->api_token = Str::random(32);
        }

        if (empty($user->id)) {
            $user->password = bcrypt(config('default.password'));
        }

        $user->fill($request->only(['name', 'company_id', 'account', 'mobile', 'status', 'identity_id', 'employee_id', 'remark']));

        $user->save();

        if (!empty($role)) {
            $user->syncRoles($user->company_id . '_' . Str::after($role, '_'));
        }

        return success($user);
    }

    /**
     * 根据权限获取用户
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getByRoles(Request $request): JsonResponse
    {
        $roleStr = $request->input('roles');

        $withStr = $request->input('with', '');

        if ($request->user()->hasRole('admin')) return success();

        $currentCompanyId = $request->input('company_id') ?: $request->user()->company_id;

        $roleNames = [];
        $group = Company::getGroupId($currentCompanyId);
        foreach ($group as $company_id) {
            $roleNames = array_merge($roleNames, array_map(fn($role) => $company_id . '_' . $role, explode(',', $roleStr)));
        }

        $wantWith = $withStr ? explode(',', $withStr) : false;

        $withs = $wantWith ? array_map(fn($with) => match ($with) {
            'roles' => 'roles',
            'company' => 'company:id,name',
        }, $wantWith) : false;

        $users = User::when($roleStr, function ($query) use ($roleNames) {
            $query->role($roleNames);
        })
            ->when($withs, fn($query, $withs) => $query->with($withs))
            ->whereIn('company_id', $group)
            ->when(strlen($status = $request->input('status')), function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($request->input('name'), function ($query, $text) {
                $query->where('name', 'like', "%$text%")
                    ->orWhere('mobile', 'like', "%$text%");
            })->paginate(getPerPage());

        return success($users);
    }
}
