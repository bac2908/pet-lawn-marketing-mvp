<?php

namespace App\Http\Requests;

use App\Support\DashboardContext;

class UpdateLeadRequest extends StoreLeadRequest
{
    // Editing uses the same eight input fields and validation as lead capture.
    protected function getRedirectUrl(): string
    {
        return route('leads.edit', ['lead' => $this->route('lead'), 'back' => DashboardContext::fromRequest($this)]);
    }
}
