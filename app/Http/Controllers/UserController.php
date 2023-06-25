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

        $userList = User::with('roles')
            ->when(!$user->hasRole('admin'), function ($query) use ($company_id, $user) {
                if ($company_id) $query->where('company_id', $company_id);
                else $query->whereIn('company_id', Company::getGroupId($user->company_id));
            })->when($company_id, function ($query, $company_id) {
                $query->where('company_id', $company_id);
            })->when($role, function ($query, $role) {
                $query->role($role);
            })->when($status, function ($query, $status) {
                $query->where('status', $status);
            })->when($text, function ($query, $text) {
                $query->where('name', 'like', "%$text%")
                    ->where('account', 'like', "%$text%")
                    ->where('mobile', 'like', "%$text%");
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

        $user->fill($request->only(['name', 'account', 'mobile', 'company_id', 'status', 'identity_id', 'employee_id', 'remark']));

        $user->save();

        if (!empty($role)) {
            $user->syncRoles($role);
        }

        return success($user);
    }

}
