<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\Login;
use App\Http\Requests\Auth\Register;
use App\Models\Company;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AccountController extends Controller
{
    /**
     * 注册
     *
     * @param Login $request
     * @return JsonResponse
     */
    public function register(Register $request): JsonResponse
    {
        $company_id = Company::where('invite_code', $request->input('invite_code'))->first()?->id;

        $user = User::create(array_merge($request->only([
            'name', 'account'
        ]), [
            'api_token' => Str::random(32),
            'password' => bcrypt($request->input('password')),
            'company_id' => $company_id
        ]));

        $user->assignRole($company_id . '_查勘人员');

        return success($user);
    }

    /**
     * 登录
     *
     * @param Login $request
     * @return JsonResponse
     */
    public function login(Login $request): JsonResponse
    {
        $account = $request->input('account');
        $password = $request->input('password');

        $user = User::where('account', $account)->first();

        if (!Hash::check($password, $user->password)) {
            return fail('密码校验失败');
        }

        $user->api_token = Str::random(32);
        $user->save();

        return success($user);
    }


    /**
     * 退出登录
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->api_token = Str::random(32);
        $user->save();

        return success();
    }


    /**
     * 个人资料
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function detail(Request $request): JsonResponse
    {
        $user = $request->user();

        return success($user);
    }


    /**
     * 编辑资料
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function form(Request $request): JsonResponse
    {
        $user = $request->user();

        $password = $request->input('password');
        $editPassword = $request->input('editPassword');

        if (!empty($password) && !empty($editPassword)) {
            if (!Hash::check($password, $user->password)) {
                return fail('密码校验失败');
            }

            $user->password = encrypt($editPassword);

            $user->api_token = Str::random(32);
        }

        $user->fill($request->only(['name', 'account']));

        $user->save();

        return success();
    }

}
