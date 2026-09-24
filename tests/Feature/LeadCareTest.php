<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Models\LeadStatusHistory;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use RuntimeException;
use Tests\TestCase;

class LeadCareTest extends TestCase
{
    use RefreshDatabase;

    public function test_note_is_attached_only_to_the_requested_lead_without_rescoring(): void
    {
        $lead = $this->createLead();
        $other = $this->createLead();
        $before = $lead->getAttributes();
        $this->mock(LeadScoringService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('calculate');
            $mock->shouldNotReceive('explain');
        });

        $this->post(route('leads.notes.store', $lead), [
            'body' => "Đã tư vấn.\nHẹn gọi lại chiều mai.", 'lead_id' => $other->id, 'status' => 'converted', 'score' => 999,
        ])->assertRedirect(route('leads.show', $lead))->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Đã thêm ghi chú chăm sóc.');

        $this->assertDatabaseHas('lead_notes', ['lead_id' => $lead->id, 'body' => "Đã tư vấn.\nHẹn gọi lại chiều mai."]);
        $this->assertSame(0, $other->notes()->count());
        $this->assertSame($before, $lead->fresh()->getAttributes());
        $this->assertDatabaseCount('lead_status_histories', 0);
    }

    #[DataProvider('invalidNotes')]
    public function test_invalid_notes_are_rejected_and_render_errors_safely(mixed $body): void
    {
        $lead = $this->createLead();
        $this->post(route('leads.notes.store', $lead), ['body' => $body])
            ->assertRedirect(route('leads.show', $lead))->assertSessionHasErrors('body');
        $this->get(route('leads.show', $lead))->assertOk()->assertSee('Chưa thể thêm ghi chú.');
        $this->assertDatabaseCount('lead_notes', 0);
    }

    public static function invalidNotes(): array
    {
        return [[null], [''], ['   '], [str_repeat('a', 2001)], [['unexpected']], [123]];
    }

    public function test_notes_are_escaped_newest_first_and_paginated(): void
    {
        $lead = $this->createLead();
        for ($index = 1; $index <= 6; $index++) {
            $lead->notes()->create(['body' => 'Care note '.$index]);
        }
        $unsafe = '<script>alert("note")</script>';
        $lead->notes()->create(['body' => $unsafe]);
        $other = $this->createLead();
        $other->notes()->create(['body' => 'Other lead private note']);

        $this->get(route('leads.show', $lead))->assertOk()
            ->assertSee($unsafe)->assertDontSee($unsafe, false)
            ->assertSeeInOrder(['Care note 6', 'Care note 5', 'Care note 4', 'Care note 3'])
            ->assertDontSee('Care note 1')->assertDontSee('Other lead private note')
            ->assertViewHas('notes', fn ($notes) => $notes->total() === 7 && $notes->count() === 5);
        $this->get(route('leads.show', ['lead' => $lead, 'notes_page' => 2]))->assertOk()
            ->assertSeeInOrder(['Care note 2', 'Care note 1'])->assertViewHas('notes', fn ($notes) => $notes->count() === 2);
    }

    public function test_status_history_records_actual_changes_and_skips_repeat_saves(): void
    {
        $lead = $this->createLead();
        foreach (['contacted', 'contacted', 'qualified', 'converted'] as $status) {
            $this->patch(route('leads.status.update', $lead), ['status' => $status])->assertSessionHasNoErrors();
        }

        $history = $lead->statusHistories()->orderBy('id')->get();
        $this->assertSame(['new', 'contacted', 'qualified'], $history->pluck('from_status')->all());
        $this->assertSame(['contacted', 'qualified', 'converted'], $history->pluck('to_status')->all());
        $this->assertNotNull($history->first()->created_at);
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => 'converted', 'score' => 60, 'segment' => 'WARM']);

        $this->get(route('leads.show', $lead))->assertOk()->assertSee('Lịch sử trạng thái')
            ->assertViewHas('statusHistory', fn ($items) => $items->getCollection()->pluck('to_status')->all() === ['converted', 'qualified', 'contacted']);
    }

    public function test_failed_history_insert_rolls_back_the_status_update(): void
    {
        $lead = $this->createLead();
        $before = $lead->getAttributes();
        LeadStatusHistory::creating(function (): void {
            throw new RuntimeException('History storage unavailable');
        });
        $this->withoutExceptionHandling();

        try {
            $this->patch(route('leads.status.update', $lead), ['status' => 'contacted']);
            $this->fail('Expected history insert to fail.');
        } catch (RuntimeException $exception) {
            $this->assertSame('History storage unavailable', $exception->getMessage());
        }

        $this->assertSame($before, $lead->fresh()->getAttributes());
        $this->assertDatabaseCount('lead_status_histories', 0);
    }

    public function test_invalid_status_and_unknown_lead_never_create_care_records(): void
    {
        $lead = $this->createLead();
        $this->patch(route('leads.status.update', $lead), ['status' => 'invalid'])->assertSessionHasErrors('status');
        $this->post('/leads/999999/notes', ['body' => 'Note'])->assertNotFound();
        $this->assertDatabaseCount('lead_status_histories', 0);
        $this->assertDatabaseCount('lead_notes', 0);
    }

    private function createLead(): Lead
    {
        return Lead::create([
            'name' => 'Care client', 'pet_type' => 'cat', 'location' => 'Hà Nội', 'budget' => 450_000,
            'interest_level' => 'medium', 'pain_point' => 'Cần thảm dễ vệ sinh.', 'source' => 'Facebook',
        ])->refresh();
    }
}
