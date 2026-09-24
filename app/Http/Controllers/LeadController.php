<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLeadNoteRequest;
use App\Http\Requests\StoreLeadRequest;
use App\Http\Requests\UpdateLeadRequest;
use App\Http\Requests\UpdateLeadStatusRequest;
use App\Models\Lead;
use App\Services\LeadScoringService;
use App\Support\DashboardContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class LeadController extends Controller
{
    public function create(): View
    {
        return view('landing');
    }

    public function show(Request $request, Lead $lead, LeadScoringService $scoringService): View
    {
        $back = DashboardContext::fromRequest($request);
        $explanation = $scoringService->explain($lead);
        $notes = $lead->notes()->latest()->latest('id')->paginate(5, ['*'], 'notes_page')->appends(['back' => $back]);
        $statusHistory = $lead->statusHistories()->latest()->latest('id')->paginate(5, ['*'], 'history_page')->appends(['back' => $back]);

        return view('leads.show', compact('lead', 'explanation', 'back', 'notes', 'statusHistory'));
    }

    public function edit(Request $request, Lead $lead): View
    {
        $back = DashboardContext::fromRequest($request);

        return view('leads.edit', compact('lead', 'back'));
    }

    public function update(UpdateLeadRequest $request, Lead $lead, LeadScoringService $scoringService): RedirectResponse
    {
        $attributes = $request->validated();
        $attributes['pet_type'] = strtolower($attributes['pet_type']);
        $lead->fill($attributes);
        // Calculate explicitly on edit; status changes and notes never rescore.
        $lead->fill($scoringService->calculate($lead))->save();

        return to_route('leads.show', ['lead' => $lead, 'back' => DashboardContext::fromRequest($request)])
            ->with('success', 'Đã lưu thông tin và tính lại điểm lead.');
    }

    public function storeNote(StoreLeadNoteRequest $request, Lead $lead): RedirectResponse
    {
        $lead->notes()->create(['body' => $request->validated('body')]);

        return to_route('leads.show', ['lead' => $lead, 'back' => DashboardContext::fromRequest($request)])
            ->with('success', 'Đã thêm ghi chú chăm sóc.');
    }

    public function updateStatus(UpdateLeadStatusRequest $request, Lead $lead): RedirectResponse
    {
        DB::transaction(function () use ($request, $lead): void {
            // Read the actual previous status under a lock, including concurrent updates.
            $currentLead = Lead::query()->lockForUpdate()->findOrFail($lead->id);
            $newStatus = $request->validated('status');
            if ($currentLead->status === $newStatus) {
                return;
            }

            $previousStatus = $currentLead->status;
            $currentLead->update(['status' => $newStatus]);
            $currentLead->statusHistories()->create(['from_status' => $previousStatus, 'to_status' => $newStatus]);
        });

        return to_route('leads.show', ['lead' => $lead, 'back' => DashboardContext::fromRequest($request)])
            ->with('success', 'Đã cập nhật trạng thái chăm sóc.');
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
