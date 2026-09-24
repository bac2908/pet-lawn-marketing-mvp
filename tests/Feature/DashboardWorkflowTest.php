<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_status_statistics_cover_all_five_statuses_and_ignore_filters(): void
    {
        $this->get(route('dashboard'))->assertOk()->assertViewHas('statusStats', [
            'new' => 0, 'contacted' => 0, 'qualified' => 0, 'converted' => 0, 'lost' => 0,
        ]);
        foreach (array_keys(Lead::STATUS_LABELS) as $status) {
            $this->createLead(['status' => $status]);
        }
        $lead = $this->createLead();

        $this->get(route('dashboard', ['status' => 'new']))->assertOk()
            ->assertSee('Tiến độ chăm sóc')
            ->assertViewHas('statusStats', ['new' => 2, 'contacted' => 1, 'qualified' => 1, 'converted' => 1, 'lost' => 1])
            ->assertViewHas('leads', fn ($leads) => $leads->total() === 2);

        $this->patch(route('leads.status.update', $lead), ['status' => 'converted'])->assertSessionHasNoErrors();
        $this->get(route('dashboard', ['q' => 'No results']))->assertOk()
            ->assertViewHas('statusStats', ['new' => 1, 'contacted' => 1, 'qualified' => 1, 'converted' => 2, 'lost' => 1])
            ->assertViewHas('leads', fn ($leads) => $leads->total() === 0);
    }

    public function test_filters_page_and_sort_survive_the_complete_care_workflow(): void
    {
        for ($index = 0; $index < 12; $index++) {
            $lead = $this->createLead(['name' => 'Care '.$index]);
        }
        $back = $this->filters();
        $parameters = ['lead' => $lead, 'back' => $back];
        $detailUrl = route('leads.show', $parameters);
        $editUrl = route('leads.edit', $parameters);
        $dashboardUrl = route('dashboard', $back);

        $dashboard = $this->get($dashboardUrl)->assertOk()->assertViewHas('back', $back);
        $listedLead = $dashboard->viewData('leads')->first();
        $dashboard->assertSee(route('leads.show', ['lead' => $listedLead, 'back' => $back]));

        $this->get($detailUrl)->assertOk()->assertSee($dashboardUrl)->assertSee($editUrl)
            ->assertSee(route('leads.status.update', $parameters))->assertSee(route('leads.notes.store', $parameters));
        $this->get($editUrl)->assertOk()->assertSee($detailUrl)->assertSee(route('leads.update', $parameters));

        $input = array_merge($lead->only('name', 'contact', 'location', 'budget', 'interest_level', 'pain_point', 'source'), ['pet_type' => 'Cat', 'budget' => 5_000_000]);
        $this->put(route('leads.update', $parameters), $input)->assertRedirect($detailUrl)->assertSessionHasNoErrors();
        $this->patch(route('leads.status.update', $parameters), ['status' => 'contacted'])->assertRedirect($detailUrl);
        $this->post(route('leads.notes.store', $parameters), ['body' => 'Follow up tomorrow.'])->assertRedirect($detailUrl);
        $this->get($detailUrl)->assertOk()->assertSee($dashboardUrl)->assertSee('Follow up tomorrow.');
    }

    public function test_validation_errors_preserve_navigation_context(): void
    {
        $lead = $this->createLead();
        $parameters = ['lead' => $lead, 'back' => $this->filters()];
        $this->put(route('leads.update', $parameters), [])->assertRedirect(route('leads.edit', $parameters))
            ->assertSessionHasErrors('name');
        $this->get(route('leads.edit', $parameters))->assertOk()->assertSee(route('dashboard', $this->filters()));
        $this->patch(route('leads.status.update', $parameters), ['status' => 'bad'])
            ->assertRedirect(route('leads.show', $parameters))->assertSessionHasErrors('status');
        $this->post(route('leads.notes.store', $parameters), ['body' => ''])
            ->assertRedirect(route('leads.show', $parameters))->assertSessionHasErrors('body');
        $this->get(route('leads.show', $parameters))->assertOk()->assertSee(route('dashboard', $this->filters()));
    }

    #[DataProvider('invalidContexts')]
    public function test_invalid_navigation_context_falls_back_to_dashboard(mixed $back): void
    {
        $lead = $this->createLead();
        $parameters = ['lead' => $lead, 'back' => $back];
        $this->get(route('leads.show', $parameters))->assertOk()->assertViewHas('back', [])
            ->assertSee('href="'.route('dashboard').'"', false);
        $this->post(route('leads.notes.store', $parameters), ['body' => 'Safe local redirect.'])
            ->assertRedirect(route('leads.show', $lead));
    }

    public static function invalidContexts(): array
    {
        return [
            ['https://example.org/redirect'], [['q' => ['unexpected']]], [['page' => -1]],
            [['sort' => 'name desc']], [['status' => 'unknown']],
        ];
    }

    public function test_navigation_ignores_unknown_keys_and_does_not_leak_between_tabs(): void
    {
        $lead = $this->createLead();
        $this->get(route('leads.show', ['lead' => $lead, 'back' => ['q' => 'First tab', 'redirect' => 'https://example.org']]))
            ->assertOk()->assertViewHas('back', ['q' => 'First tab'])->assertDontSee('https://example.org');
        $this->get(route('leads.show', ['lead' => $lead, 'back' => ['segment' => 'HOT']]))
            ->assertOk()->assertViewHas('back', ['segment' => 'HOT']);
        $this->get(route('leads.show', $lead))->assertOk()->assertViewHas('back', []);
    }

    public function test_returning_to_a_page_that_disappeared_preserves_filters_and_uses_last_page(): void
    {
        for ($index = 0; $index < 11; $index++) {
            $lead = $this->createLead();
        }
        $this->get(route('dashboard', $this->filters()))->assertOk();
        $this->patch(route('leads.status.update', $lead), ['status' => 'contacted'])->assertSessionHasNoErrors();

        $lastPage = route('dashboard', array_merge($this->filters(), ['page' => 1]));
        $this->get(route('dashboard', $this->filters()))->assertRedirect($lastPage);
        $this->get($lastPage)->assertOk()->assertViewHas('leads', fn ($leads) => $leads->count() === 10);
    }

    private function filters(): array
    {
        return ['q' => 'Care', 'segment' => 'WARM', 'status' => 'new', 'source' => 'Facebook', 'sort' => 'score_desc', 'page' => 2];
    }

    private function createLead(array $overrides = []): Lead
    {
        return Lead::create(array_replace([
            'name' => 'Care client', 'pet_type' => 'cat', 'location' => 'Hà Nội', 'budget' => 450_000,
            'interest_level' => 'medium', 'pain_point' => 'Cần thảm dễ vệ sinh.', 'source' => 'Facebook',
        ], $overrides));
    }
}
