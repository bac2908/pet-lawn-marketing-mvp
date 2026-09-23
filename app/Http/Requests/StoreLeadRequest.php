<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreLeadRequest extends FormRequest
{
    protected $redirectRoute = 'home';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'contact' => ['nullable', 'string', 'max:255'],
            'pet_type' => ['required', 'string', 'in:Dog,Cat,Other'],
            'location' => ['required', 'string', 'max:255'],
            // Match the unsigned INT column so oversized input cannot cause a database error.
            'budget' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'pain_point' => ['nullable', 'string', 'max:2000'],
            'interest_level' => ['required', 'string', 'in:low,medium,high'],
            'source' => ['nullable', 'string', 'max:100'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'required' => 'Vui lòng nhập :attribute.',
            'string' => ':attribute phải là văn bản.',
            'max' => ':attribute không được vượt quá :max ký tự.',
            'pet_type.required' => 'Vui lòng chọn loại thú cưng.',
            'pet_type.in' => 'Vui lòng chọn Chó, Mèo hoặc Khác.',
            'budget.required' => 'Vui lòng chọn ngân sách dự kiến.',
            'budget.integer' => 'Ngân sách phải là số nguyên.',
            'budget.min' => 'Ngân sách không được âm.',
            'budget.max' => 'Ngân sách không được vượt quá 4.294.967.295 VND.',
            'interest_level.required' => 'Vui lòng chọn mức độ quan tâm.',
            'interest_level.in' => 'Vui lòng chọn mức độ quan tâm hợp lệ.',
        ];
    }

    /** @return array<string, string> */
    public function attributes(): array
    {
        return [
            'name' => 'họ tên',
            'contact' => 'thông tin liên hệ',
            'pet_type' => 'loại thú cưng',
            'location' => 'tỉnh / thành phố',
            'budget' => 'ngân sách',
            'pain_point' => 'nhu cầu của bạn',
            'interest_level' => 'mức độ quan tâm',
            'source' => 'nguồn giới thiệu',
        ];
    }
}
