<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadRequest;
use App\Models\Lead;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function create(): View
    {
        return view('landing');
    }

    public function store(StoreLeadRequest $request): RedirectResponse
    {
        $attributes = $request->validated();
        $attributes['pet_type'] = strtolower($attributes['pet_type']);

        // The existing creating event calls LeadScoringService before this insert.
        Lead::create($attributes);

        return redirect(route('home').'#lead-form')->with(
            'success',
            'Cảm ơn bạn! Thông tin của bạn đã được ghi nhận. Chúng tôi sẽ liên hệ tư vấn sớm.',
        );
    }
}
