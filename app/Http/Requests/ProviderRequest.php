<?php

namespace App\Http\Requests;

use App\Models\Enumerations\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [];
    }

    public function messages()
    {
        return [
            'name.required_without' => '公司名称不能为空',
            'name.string' => '公司名称必须为字符串且不能为空',
            'name.unique' => '当前输入的公司名称已经存在',
            'account.required_without' => '登录账号不能为空',
            'account.unique' => '当前输入的账号已存在',
            'province.required_without' => '归属地必须填写完整',
            'city.required_without' => '归属地必须填写完整',
            'area.required_without' => '归属地必须填写完整',
            'address.required_without' => '归属地必须填写完整',
            'bank_name.required_without' => '归属地必须填写完整',
            'status.*' => '未知的状态类型',
        ];
    }

}
