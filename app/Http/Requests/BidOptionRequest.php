<?php

namespace App\Http\Requests;

use App\Models\Enumerations\Status;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BidOptionRequest extends FormRequest
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
            'company_id' => ['required_without:id', Rule::unique('bid_options')->ignore($this->input('id'))],
            'bid_first_price' => 'required_without:id',
            'min_goods_price' => 'required_without:id',
            'mid_goods_price' => 'required_without:id',
            'working_time_deadline_min' => 'required_without:id',
            'resting_time_deadline_min' => 'required_without:id',
            'working_time_deadline_mid' => 'required_without:id',
            'resting_time_deadline_mid' => 'required_without:id',
            'working_time_deadline_max' => 'required_without:id',
            'resting_time_deadline_max' => 'required_without:id',
            'status' => [Rule::enum(Status::class)],
        ];
    }

    public function messages(): array
    {
        return [
            'company_id.unique' => '当前公司已存在配置',
            'status.*' => '未知的状态类型',
        ];
    }
}
