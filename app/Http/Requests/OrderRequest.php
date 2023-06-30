<?php

namespace App\Http\Requests;

use App\Models\Enumerations\InsuranceType;
use App\Models\Enumerations\OrderCloseStatus;
use App\Models\Enumerations\OrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrderRequest extends FormRequest
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
            'insurance_company_id' => 'required|exists:companies,id',
            'case_number' => [Rule::unique('orders')->ignore($this->input('id'))],
            'post_time' => 'date_format:Y-m-d H:i:s',
            'insurance_type' => [Rule::enum(InsuranceType::class)],
            'license_plate' => [Rule::requiredIf($this->input('insurance_type') == InsuranceType::Car->value)],
            'vin' => [Rule::requiredIf($this->input('insurance_type') == InsuranceType::Car->value)],
            'locations' => 'required',
            'province' => 'required',
            'city' => 'required',
            'area' => 'required',
            'address' => 'required',
            'order_status' => [Rule::enum(OrderStatus::class)],
            'close_status' => [Rule::enum(OrderCloseStatus::class)],
            'goods_types' => '',
            'images',
            'goods_remark',
        ];
    }

    public function messages()
    {
        return [
            'case_number.unique' => '报案号已经存在',
            'insurance_type.enum' => '未知的保险类型',
            'order_status.enum' => '未知的工单状态',
            'close_status.enum' => '未知的工单关闭状态',
        ];
    }
}
