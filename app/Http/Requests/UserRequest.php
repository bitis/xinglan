<?php

namespace App\Http\Requests;

use App\Models\Enumerations\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        return [
            'name' => 'required_without:id|string|min:2|max:10',
            'status' => [Rule::enum(Status::class)],
            'account' => [
                'required_without:id',
                Rule::unique('users')->ignore($this->input('id')),
            ],
            'mobile' => 'required_without:id',
            'company_id' => 'required_without:id|exists:companies,id',
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_without' => '姓名不能为空',
            'name.string' => '姓名必须为字符串且不能为空',
            'name.min' => '姓名长度最少为2个字',
            'name.max' => '姓名长度最长为20个字',
            'account.required_without' => '登录账号不能为空',
            'account.unique' => '当前输入的账号已存在',
            'mobile.required_without' => '当前输入的手机号已存在',
            'company_id.required_without' => '必须选择归属公司',
            'company_id.exists' => '所属公司不存在',
            'status.*' => '未知的状态类型',
        ];
    }
}
