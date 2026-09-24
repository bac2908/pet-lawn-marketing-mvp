<?php

namespace App\Support;

use App\Http\Requests\DashboardRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DashboardContext
{
    /**
     * Keep only valid dashboard parameters, never a user-supplied redirect URL.
     * Invalid navigation context is optional and falls back to the full list.
     *
     * @return array<string, string|int>
     */
    public static function fromRequest(Request $request): array
    {
        $input = $request->query('back', []);
        if (! is_array($input)) {
            return [];
        }

        $validator = Validator::make($input, (new DashboardRequest)->rules());
        if ($validator->fails()) {
            return [];
        }

        return array_filter($validator->validated(), fn ($value) => $value !== null && $value !== '');
    }
}
