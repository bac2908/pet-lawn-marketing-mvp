<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProviderExternal;
use Tests\TestCase;

class LeadEditingTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_form_is_prefilled_without_changing_data(): void
    {
        $lead = $this->createLead();
        $before = $lead->getAttributes();

        $this->get(route('leads.edit', $lead))->assertOk()
            ->assertSee('value="Care client"', false)
            ->assertSee('value="Cat" selected', false)
            ->assertSee('value="450000"', false)
            ->assertSee('value="medium" selected', false)
            ->assertSee('name="_method" value="PUT"', false)
            ->assertSee('name="_token"', false)
            ->assertSee('Lưu và tính lại điểm');

        $this->assertSame($before, $lead->fresh()->getAttributes());
    }

    public function test_editing_budget_rescores_warm_to_hot_and_preserves_care_data(): void
    {
        $lead = $this->createLead();
        $lead->update(['status' => 'contacted']);
        $lead->notes()->create(['body' => 'Đã tư vấn kích thước.']);
        $lead->statusHistories()->create(['from_status' => 'new', 'to_status' => 'contacted']);
        $this->assertSame(60, $lead->score);

        $this->put(route('leads.update', $lead), $this->input([
            'budget' => 5_000_000, 'score' => 999, 'segment' => 'COLD', 'status' => 'converted',
        ]))->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors()->assertSessionHas('success', 'Đã lưu thông tin và tính lại điểm lead.');

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id, 'budget' => 5_000_000, 'pet_type' => 'cat',
            'score' => 80, 'segment' => 'HOT', 'status' => 'contacted',
        ]);
        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseCount('lead_notes', 1);
        $this->assertDatabaseCount('lead_status_histories', 1);
        $this->get(route('leads.show', $lead))->assertOk()
            ->assertSee('30 + 20 + 20 + 10 = 80')->assertSee('Đã tư vấn kích thước.');
    }

    public function test_editing_calls_the_existing_scoring_service_once(): void
    {
        $lead = $this->createLead();
        $this->mock(LeadScoringService::class, function (MockInterface $mock) use ($lead): void {
            $mock->shouldReceive('calculate')->once()
                ->withArgs(fn (Lead $candidate) => $candidate->is($lead) && $candidate->budget === 5_000_000)
                ->andReturn(['score' => 87, 'segment' => 'HOT']);
        });

        $this->put(route('leads.update', $lead), $this->input(['budget' => 5_000_000]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'score' => 87, 'segment' => 'HOT']);
    }

    #[DataProviderExternal(LeadCaptureTest::class, 'invalidSubmissions')]
    public function test_invalid_edit_uses_shared_validation_and_preserves_the_record(array $overrides, array $errors): void
    {
        $lead = $this->createLead();
        $before = $lead->getAttributes();

        $this->put(route('leads.update', $lead), $this->input($overrides))
            ->assertRedirect(route('leads.edit', $lead))->assertSessionHasErrors($errors);
        $this->get(route('leads.edit', $lead))->assertOk()->assertSee('Chưa thể lưu. Vui lòng kiểm tra lại thông tin:');

        $this->assertSame($before, $lead->fresh()->getAttributes());
    }

    public function test_invalid_edit_retains_values_and_escapes_html(): void
    {
        $lead = $this->createLead();
        $unsafe = '<script>alert("test")</script>';

        $this->put(route('leads.update', $lead), $this->input(['name' => $unsafe, 'budget' => 5_000_000, 'location' => '']))
            ->assertSessionHasErrors('location')->assertSessionHasInput('budget', 5_000_000);
        $this->get(route('leads.edit', $lead))->assertOk()->assertSee($unsafe)->assertDontSee($unsafe, false)
            ->assertSee('value="5000000"', false)->assertSee('Vui lòng nhập tỉnh / thành phố.');
    }

    public function test_optional_fields_can_be_cleared_and_score_can_decrease(): void
    {
        $lead = $this->createLead();

        $this->put(route('leads.update', $lead), $this->input(['contact' => '', 'pain_point' => '', 'source' => '']))
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id, 'contact' => null, 'pain_point' => null, 'source' => null, 'score' => 40, 'segment' => 'COLD',
        ]);
    }

    public function test_missing_fields_and_missing_leads_are_rejected(): void
    {
        $lead = $this->createLead();
        $this->put(route('leads.update', $lead), [])->assertSessionHasErrors(['name', 'pet_type', 'location', 'budget', 'interest_level']);
        $this->get('/leads/999999/edit')->assertNotFound();
        $this->put('/leads/999999', $this->input())->assertNotFound();
    }

    private function createLead(): Lead
    {
        return Lead::create(array_replace($this->input(), ['pet_type' => 'cat']))->refresh();
    }

    private function input(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Care client', 'contact' => 'care@example.com', 'pet_type' => 'Cat',
            'location' => 'Hà Nội', 'budget' => 450_000, 'interest_level' => 'medium',
            'pain_point' => 'Cần thảm dễ vệ sinh.', 'source' => 'Facebook',
        ], $overrides);
    }
}
