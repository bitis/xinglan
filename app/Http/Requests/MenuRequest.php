<?php

namespace App\Http\Requests;

use App\Models\Enumerations\MenuType;
use App\Models\Enumerations\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MenuRequest extends FormRequest
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
            'parent_id' => 'nullable|exists:companies',
            'name' => 'required_without:id|string|min:2|max:25',
            'path' => [
                Rule::unique('menus')->ignore($this->input('id')),
            ],
            'type' => [Rule::enum(MenuType::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_without' => '标题不能为空',
            'name.string' => '标题必须为字符串且不能为空',
            'name.min' => '姓名长度最少为2个字',
            'name.max' => '姓名长度最长为20个字',
            'path.unique' => '当前输入的路径已存在',
            'type.*' => '未知的菜单类型',
            'parent_id.exists' => '选择的上级菜单不存在',
        ];
    }
}
