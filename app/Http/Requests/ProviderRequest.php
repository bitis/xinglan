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
        return [
            'name' => [
                'exclude_with:id,provider_id',
                'required',
                Rule::unique('companies')->ignore($this->input('provider_id'))
            ],
            'account' => [
                'exclude_with:id,provider_id',
                'required',
                Rule::unique('users')->ignore($this->input('id')),
            ],
            'contract_name' => 'exclude_with:id,provider_id',
            'contract_phone' => 'exclude_with:id,provider_id',
            'province' => 'exclude_with:id,provider_id',
            'city' => 'exclude_with:id,provider_id',
            'area' => 'exclude_with:id,provider_id',
            'address' => 'exclude_with:id,provider_id',
            'bank_name' => 'required',
            'bank_account_name' => 'required',
            'bank_account_number' => 'required',
            'license_no' => 'required',
            'license_image' => 'required|array',
            'status' => [
                Rule::enum(Status::class)
            ]
        ];
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
