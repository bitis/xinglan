<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

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
            'account' => 'required',
            'password' => 'required',
        ];
    }

    public function messages(): array
    {
        return [
            'account.required' => '账号信息必填',
            'account.exits' => '账号不存在',
        ];
    }
}
