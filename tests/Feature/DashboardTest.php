<?php

namespace Tests\Feature;

use App\Models\Lead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_seeded_leads_and_global_statistics(): void
    {
        $this->seed();

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Tổng quan khách hàng')
            ->assertSee('Nguyễn Minh Anh')
            ->assertSee('minh.anh@example.com')
            ->assertSee('1.200.000 ₫')
            ->assertSee('Quan tâm: Cao')
            ->assertSee('Mới')
            ->assertSee('Hiển thị 1–10 / 10 lead')
            ->assertViewHas('stats', ['total' => 10, 'HOT' => 2, 'WARM' => 7, 'COLD' => 1])
            ->assertViewHas('leads', fn ($leads) => $leads->count() === 10)
            ->assertViewHas('sources', fn ($sources) => $sources->all() === ['Facebook', 'Google', 'Referral', 'TikTok']);
    }

    #[DataProvider('searchableFields')]
    public function test_search_matches_name_contact_or_location(string $field, string $value, string $search): void
    {
        $expected = $this->createLead([$field => $value]);
        $this->createLead(['name' => 'Unrelated person']);

        $this->get(route('dashboard', ['q' => $search]))->assertOk()
            ->assertViewHas('leads', fn ($leads) => $leads->getCollection()->modelKeys() === [$expected->id]);
    }

    public static function searchableFields(): array
    {
        return [
            'name' => ['name', 'Nguyễn Minh Anh', 'Minh'],
            'contact' => ['contact', 'person@example.com', 'person@'],
            'location' => ['location', 'Đà Nẵng', 'Đà Nẵng'],
            'zero is a valid query' => ['name', 'Khách 0', '0'],
            'percent is literal' => ['name', 'Khách 50%', '%'],
            'underscore is literal' => ['contact', 'special_name@example.com', '_'],
            'escape character is literal' => ['name', 'A!B', '!'],
            'quote is bound safely' => ['name', "O'Brien", "O'Brien"],
        ];
    }

    public function test_search_and_filters_are_combined_without_affecting_global_counts(): void
    {
        $expected = $this->createLead($this->hotInput(['name' => 'Matching client', 'status' => 'contacted']));
        $this->createLead(['name' => 'Matching but cold', 'status' => 'contacted']);
        $this->createLead($this->hotInput(['name' => 'Matching wrong source', 'source' => 'TikTok', 'status' => 'contacted']));
        $this->createLead($this->hotInput(['name' => 'Matching wrong status']));
        $this->createLead($this->hotInput(['name' => 'Different person', 'status' => 'contacted']));

        $this->get(route('dashboard', [
            'q' => 'Matching', 'segment' => 'HOT', 'status' => 'contacted', 'source' => 'Facebook',
        ]))->assertOk()
            ->assertViewHas('stats', ['total' => 5, 'HOT' => 4, 'WARM' => 0, 'COLD' => 1])
            ->assertViewHas('leads', fn ($leads) => $leads->getCollection()->modelKeys() === [$expected->id])
            ->assertSee('value="HOT" selected', false)
            ->assertSee('value="contacted" selected', false)
            ->assertSee('value="Facebook" selected', false);
    }

    #[DataProvider('statuses')]
    public function test_each_status_can_be_filtered(string $status): void
    {
        $expected = $this->createLead(['status' => $status]);
        $this->createLead(['status' => $status === 'new' ? 'lost' : 'new']);

        $this->get(route('dashboard', ['status' => $status]))->assertOk()
            ->assertViewHas('leads', fn ($leads) => $leads->getCollection()->modelKeys() === [$expected->id]);
    }

    public static function statuses(): array
    {
        return array_map(fn ($status) => [$status], ['new', 'contacted', 'qualified', 'converted', 'lost']);
    }

    public function test_default_sort_is_newest_and_score_sort_has_a_stable_tie_breaker(): void
    {
        $hot = $this->createLead($this->hotInput(['created_at' => '2026-09-20 08:00:00']));
        $cold = $this->createLead(['created_at' => '2026-09-21 08:00:00']);
        $anotherHot = $this->createLead($this->hotInput(['created_at' => '2026-09-20 08:00:00']));

        $this->get(route('dashboard'))->assertOk()
            ->assertViewHas('leads', fn ($leads) => $leads->getCollection()->modelKeys() === [$cold->id, $anotherHot->id, $hot->id]);

        $this->get(route('dashboard', ['sort' => 'score_desc']))->assertOk()
            ->assertViewHas('leads', fn ($leads) => $leads->getCollection()->modelKeys() === [$anotherHot->id, $hot->id, $cold->id]);
    }

    public function test_pagination_preserves_filters_and_only_returns_ten_results_per_page(): void
    {
        for ($index = 1; $index <= 12; $index++) {
            $this->createLead($this->hotInput(['name' => 'Page client '.$index]));
        }
        $this->createLead(['name' => 'Page cold lead']);
        $filters = ['q' => 'Page', 'segment' => 'HOT', 'status' => 'new', 'source' => 'Facebook', 'sort' => 'score_desc'];

        $response = $this->get(route('dashboard', $filters))->assertOk()
            ->assertViewHas('leads', fn ($leads) => $leads->count() === 10 && $leads->total() === 12)
            ->assertSee('Trang 1 / 2');

        $nextPage = $response->viewData('leads')->nextPageUrl();
        parse_str(parse_url($nextPage, PHP_URL_QUERY), $query);
        $this->assertSame(array_merge($filters, ['page' => '2']), $query);

        $this->get($nextPage)->assertOk()
            ->assertViewHas('leads', fn ($leads) => $leads->count() === 2 && $leads->total() === 12)
            ->assertSee('Hiển thị 11–12 / 12 lead')
            ->assertSee('Trang 2 / 2');
    }

    public function test_empty_database_and_no_matching_results_have_useful_messages(): void
    {
        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Chưa có lead nào')
            ->assertViewHas('stats', ['total' => 0, 'HOT' => 0, 'WARM' => 0, 'COLD' => 0]);

        $this->createLead();

        $this->get(route('dashboard', ['q' => 'No match']))->assertOk()
            ->assertSee('Không có lead phù hợp')
            ->assertSee('Xem tất cả lead')
            ->assertSee('Hiển thị 0–0 / 0 lead');
    }

    public function test_nullable_fields_are_displayed_with_fallbacks(): void
    {
        $this->createLead(['contact' => null, 'source' => null]);

        $this->get(route('dashboard'))->assertOk()
            ->assertSee('Chưa có liên hệ')
            ->assertSee('Chưa rõ')
            ->assertViewHas('sources', fn ($sources) => $sources->isEmpty());
    }

    public function test_lead_content_and_search_input_are_html_escaped(): void
    {
        $unsafe = '<script>alert("test")</script>';
        $this->createLead(['name' => $unsafe, 'contact' => $unsafe, 'location' => $unsafe, 'source' => $unsafe]);

        $this->get(route('dashboard', ['q' => $unsafe]))->assertOk()
            ->assertSee($unsafe)
            ->assertDontSee($unsafe, false);
    }

    public function test_dashboard_reads_saved_scores_without_recalculating_or_writing_leads(): void
    {
        $lead = $this->createLead($this->hotInput());
        // Simulate previously stored scores that differ from the current rules.
        $lead->forceFill(['score' => 70, 'segment' => 'WARM'])->saveQuietly();
        $before = $lead->fresh()->getAttributes();

        $this->get(route('dashboard'))->assertOk()
            ->assertViewHas('stats', ['total' => 1, 'HOT' => 0, 'WARM' => 1, 'COLD' => 0])
            ->assertViewHas('leads', fn ($leads) => $leads->first()->score === 70);

        $this->assertSame($before, $lead->fresh()->getAttributes());
        $this->assertDatabaseCount('leads', 1);
    }

    #[DataProvider('invalidFilters')]
    public function test_invalid_filters_redirect_to_a_clean_dashboard_url(array $input, array $errors): void
    {
        $url = route('dashboard', $input);
        $this->from($url)->get($url)->assertRedirect(route('dashboard'))->assertSessionHasErrors($errors);

        $this->get(route('dashboard'))->assertOk()->assertSee('Bộ lọc chưa hợp lệ. Vui lòng chọn lại.');
    }

    public static function invalidFilters(): array
    {
        return [
            'arrays instead of strings' => [['q' => ['x'], 'segment' => ['HOT'], 'status' => ['new'], 'source' => ['Facebook'], 'sort' => ['newest']], ['q', 'segment', 'status', 'source', 'sort']],
            'invalid options' => [['segment' => 'unknown', 'status' => 'deleted', 'sort' => 'name; DROP TABLE leads'], ['segment', 'status', 'sort']],
            'overlong text' => [['q' => str_repeat('a', 256), 'source' => str_repeat('a', 256)], ['q', 'source']],
            'zero page' => [['page' => 0], ['page']],
            'non-integer page' => [['page' => '1.5'], ['page']],
            'array page' => [['page' => ['1']], ['page']],
            'excessive page' => [['page' => '999999999999999999999999'], ['page']],
        ];
    }

    private function createLead(array $overrides = []): Lead
    {
        return Lead::forceCreate(array_replace([
            'name' => 'Sample client',
            'contact' => null,
            'pet_type' => 'dog',
            'location' => 'Hà Nội',
            'budget' => 1_000_000,
            'interest_level' => 'low',
            'pain_point' => null,
            'source' => 'Facebook',
            'status' => 'new',
        ], $overrides));
    }

    private function hotInput(array $overrides = []): array
    {
        return array_replace([
            'budget' => 5_000_000,
            'interest_level' => 'high',
            'pain_point' => 'Cần dễ vệ sinh.',
            'location' => 'HCM',
        ], $overrides);
    }
}
