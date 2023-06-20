<?php

namespace App\Http\Requests;

use App\Models\Enumerations\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoodsTypeRequest extends FormRequest
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
        ];
    }

    public function messages(): array
    {
        return [
            'name.required_without' => '类型不能为空',
            'name.string' => '类型必须为字符串且不能为空',
            'name.min' => '类型长度最少为2个字',
            'name.max' => '类型长度最长为20个字',
            'company_id.exists' => '所属公司不存在',
            'status.*' => '未知的状态类型',
        ];
    }
}
