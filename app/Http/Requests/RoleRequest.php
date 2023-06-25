<?php

namespace App\Http\Requests;

use App\Models\Enumerations\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
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
            'show_name' => [
                'required_without:id',
                'string',
                'min:2',
                'max:10',
                Rule::unique('roles')->where('company_id', $this->user()->company_id)->ignore($this->input('id'))
            ],
            'status' => [Rule::enum(Status::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'show_name.required_without' => '类型不能为空',
            'show_name.string' => '类型必须为字符串且不能为空',
            'show_name.min' => '类型长度最少为2个字',
            'show_name.max' => '类型长度最长为20个字',
            'show_name.unique' => '角色名不能重复',
            'status.*' => '未知的状态类型',
        ];
    }
}
