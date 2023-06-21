<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class Register extends FormRequest
{

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'account' => 'required|unique:users',
            'password' => 'required',
            'mobile' => 'required',
            'invite_code' => 'required|exists:companies'
        ];
    }

    public function messages(): array
    {
        return [
            'account.required' => '账号信息必填',
            'account.unique' => '账号已存在',
            'mobile.required' => '请填写手机号',
            'invite_code.required' => '请填写邀请码',
            'invite_code.exists' => '当前邀请码无效',
        ];
    }
}
