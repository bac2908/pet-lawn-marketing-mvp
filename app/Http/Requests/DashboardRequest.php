<?php

namespace App\Http\Requests;

use App\Models\Lead;
use Illuminate\Foundation\Http\FormRequest;

class DashboardRequest extends FormRequest
{
    protected $redirectRoute = 'dashboard';

    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:255'],
            'segment' => ['nullable', 'string', 'in:HOT,WARM,COLD'],
            'status' => ['nullable', 'string', 'in:'.implode(',', array_keys(Lead::STATUS_LABELS))],
            'source' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', 'in:newest,score_desc'],
            'page' => ['nullable', 'integer', 'min:1', 'max:1000000'],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'q.string' => 'Từ khóa tìm kiếm phải là văn bản.',
            'q.max' => 'Từ khóa tìm kiếm không được vượt quá 255 ký tự.',
            'segment.*' => 'Vui lòng chọn phân nhóm HOT, WARM hoặc COLD.',
            'status.*' => 'Vui lòng chọn trạng thái hợp lệ.',
            'source.*' => 'Nguồn marketing phải là văn bản, tối đa 255 ký tự.',
            'sort.*' => 'Vui lòng chọn cách sắp xếp hợp lệ.',
            'page.*' => 'Số trang phải là số nguyên từ 1 đến 1.000.000.',
        ];
    }
}
