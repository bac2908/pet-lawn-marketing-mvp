<?php

namespace App\Http\Requests;

use App\Support\DashboardContext;
use Illuminate\Foundation\Http\FormRequest;

class StoreLeadNoteRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:2000']];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'body.required' => 'Vui lòng nhập nội dung ghi chú.',
            'body.string' => 'Nội dung ghi chú phải là văn bản.',
            'body.max' => 'Ghi chú không được vượt quá 2.000 ký tự.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('leads.show', ['lead' => $this->route('lead'), 'back' => DashboardContext::fromRequest($this)]);
    }
}
