<?php

namespace App\Http\Requests;

use App\Models\Enumerations\InsuranceType;
use App\Models\Enumerations\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProviderOptionRequest extends FormRequest
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
            'company_id' => 'required',
            'provider_id' => 'required',
            'insurance_type' => [Rule::enum(InsuranceType::class)],
            'province' => 'required',
            'city' => 'required',
            'area' => 'required|array',
            'status' => [Rule::enum(Status::class)]
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.required' => '必须选择归属公司',
            'provider_id.required' => '必须选择外协公司',
            'insurance_type.*' => '未知的保险类型',
            'province.required' => '省份必须选择',
            'city.required' => '城市必须选择',
            'area.required' => '区/县必须选择',
            'area.array' => '区/县数据类型错误',
            'status.*' => '未知的状态类型',
        ];
    }
}
