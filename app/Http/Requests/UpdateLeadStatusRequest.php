<?php

namespace App\Http\Requests;

use App\Models\Lead;
use App\Support\DashboardContext;
use Illuminate\Foundation\Http\FormRequest;

class UpdateLeadStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, list<string>> */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:'.implode(',', array_keys(Lead::STATUS_LABELS))],
        ];
    }

    /** @return array<string, string> */
    public function messages(): array
    {
        return [
            'status.required' => 'Vui lòng chọn trạng thái chăm sóc.',
            'status.string' => 'Trạng thái chăm sóc không hợp lệ.',
            'status.in' => 'Vui lòng chọn một trong các trạng thái được hỗ trợ.',
        ];
    }

    protected function getRedirectUrl(): string
    {
        return route('leads.show', ['lead' => $this->route('lead'), 'back' => DashboardContext::fromRequest($this)]);
    }
}
