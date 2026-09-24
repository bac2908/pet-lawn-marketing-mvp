<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeadDetailsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_links_to_a_lead_with_full_details_and_score_explanation(): void
    {
        $lead = $this->createLead();

        $this->get(route('dashboard'))->assertOk()->assertSee(route('leads.show', $lead), false);
        $this->get(route('leads.show', $lead))->assertOk()
            ->assertSee($lead->name)
            ->assertSee($lead->contact)
            ->assertSee('Chó')
            ->assertSee('HCM')
            ->assertSee('5.000.000 ₫')
            ->assertSee('Cao')
            ->assertSee('Facebook')
            ->assertSee($lead->pain_point)
            ->assertSee('Giải thích điểm')
            ->assertSee('30 + 30 + 20 + 20 = 100')
            ->assertSee('Tổng điểm từ 80 trở lên.')
            ->assertSee('HOT')
            ->assertSee('name="_token"', false)
            ->assertSee('name="_method" value="PATCH"', false)
            ->assertSee('value="new" selected', false)
            ->assertDontSee('Điểm đã lưu khác kết quả hiện tại.');
    }

    public function test_details_use_the_scoring_service_and_do_not_modify_saved_values(): void
    {
        $lead = $this->createLead();
        $before = $lead->getAttributes();
        $this->mock(LeadScoringService::class, function (MockInterface $mock) use ($lead): void {
            $mock->shouldReceive('explain')->once()
                ->withArgs(fn (Lead $argument) => $argument->is($lead))
                ->andReturn([
                    'score' => 77,
                    'segment' => 'WARM',
                    'segment_reason' => 'Giải thích từ service.',
                    'breakdown' => [['label' => 'Tiêu chí từ service', 'points' => 77, 'reason' => 'Kết quả kiểm thử.']],
                ]);
        });

        $this->get(route('leads.show', $lead))->assertOk()
            ->assertSee('Tiêu chí từ service')
            ->assertSee('Giải thích từ service.')
            ->assertSee('Điểm đã lưu khác kết quả hiện tại.')
            ->assertSee('Kết quả bên dưới là 77 điểm / WARM.');

        $this->assertSame($before, $lead->fresh()->getAttributes());
    }

    public function test_nullable_fields_and_multiline_pain_point_render_safely(): void
    {
        $lead = $this->createLead(['contact' => null, 'source' => null, 'pain_point' => null]);
        $this->get(route('leads.show', $lead))->assertOk()
            ->assertSee('Chưa có liên hệ')->assertSee('Chưa rõ')
            ->assertSee('Khách hàng chưa mô tả nhu cầu.')
            ->assertSee('Chưa mô tả nhu cầu cần giải quyết.');

        $unsafe = '<script>alert("test")</script>';
        $lead->update(['name' => $unsafe, 'contact' => $unsafe, 'location' => $unsafe, 'source' => $unsafe, 'pain_point' => "Dòng đầu\n".$unsafe]);
        $this->get(route('leads.show', $lead))->assertOk()->assertSee('Dòng đầu')
            ->assertSee($unsafe)->assertDontSee($unsafe, false);
    }

    public function test_timestamps_are_shown_in_vietnam_time(): void
    {
        $lead = $this->createLead();
        $lead->forceFill(['created_at' => '2026-09-23 20:30:00', 'updated_at' => '2026-09-23 20:30:00'])->saveQuietly();

        $this->get(route('leads.show', $lead))->assertOk()->assertSee('24/09/2026 03:30');
    }

    #[DataProvider('validStatuses')]
    public function test_supported_status_can_be_saved_and_is_visible_in_dashboard_filter(string $status, string $label): void
    {
        $lead = $this->createLead(['status' => $status === 'new' ? 'converted' : 'new']);
        $before = Arr::except($lead->getAttributes(), ['status', 'updated_at']);

        $this->patch(route('leads.status.update', $lead), ['status' => $status])
            ->assertRedirect(route('leads.show', $lead))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Đã cập nhật trạng thái chăm sóc.');

        $this->assertSame($before, Arr::except($lead->fresh()->getAttributes(), ['status', 'updated_at']));
        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'status' => $status]);
        $this->assertDatabaseCount('leads', 1);
        $this->get(route('leads.show', $lead))->assertOk()
            ->assertSee('Đã cập nhật trạng thái chăm sóc.')
            ->assertSee('value="'.$status.'" selected', false);
        $this->get(route('dashboard', ['status' => $status]))->assertOk()
            ->assertSee($label)
            ->assertViewHas('leads', fn ($leads) => $leads->getCollection()->modelKeys() === [$lead->id]);
        $this->get(route('dashboard', ['status' => $status === 'new' ? 'lost' : 'new']))->assertOk()
            ->assertViewHas('leads', fn ($leads) => $leads->isEmpty());
    }

    public static function validStatuses(): array
    {
        return [
            ['new', 'Mới'], ['contacted', 'Đã liên hệ'], ['qualified', 'Đủ điều kiện'],
            ['converted', 'Đã chuyển đổi'], ['lost', 'Không thành công'],
        ];
    }

    public function test_status_update_ignores_extra_fields_and_never_recalculates_score(): void
    {
        $lead = $this->createLead();
        $lead->forceFill(['score' => 60, 'segment' => 'WARM'])->saveQuietly();
        $this->mock(LeadScoringService::class, function (MockInterface $mock): void {
            $mock->shouldNotReceive('calculate');
            $mock->shouldNotReceive('explain');
        });

        $this->patch(route('leads.status.update', $lead), [
            'status' => 'contacted', 'name' => 'Tampered', 'budget' => 0,
            'contact' => 'changed@example.com', 'score' => 999, 'segment' => 'HOT',
        ])->assertRedirect(route('leads.show', $lead))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id, 'status' => 'contacted', 'name' => 'Khách hàng chi tiết',
            'budget' => 5_000_000, 'contact' => 'details-test@example.com', 'score' => 60, 'segment' => 'WARM',
        ]);
    }

    public function test_saving_the_same_status_does_not_change_the_record(): void
    {
        $lead = $this->createLead();
        $before = $lead->getAttributes();
        $this->travel(1)->minutes();

        $this->patch(route('leads.status.update', $lead), ['status' => 'new'])->assertSessionHasNoErrors();

        $this->assertSame($before, $lead->fresh()->getAttributes());
    }

    #[DataProvider('invalidStatuses')]
    public function test_invalid_status_is_rejected_with_no_database_changes(array $input): void
    {
        $lead = $this->createLead();
        $before = $lead->getAttributes();

        $this->patch(route('leads.status.update', $lead), $input)
            ->assertRedirect(route('leads.show', $lead))->assertSessionHasErrors('status');
        $this->get(route('leads.show', $lead))->assertOk()
            ->assertSee('Chưa thể cập nhật trạng thái.')
            ->assertSee('aria-invalid="true"', false);

        $this->assertSame($before, $lead->fresh()->getAttributes());
    }

    public static function invalidStatuses(): array
    {
        return [
            'missing' => [[]], 'null' => [['status' => null]], 'empty' => [['status' => '']],
            'whitespace' => [['status' => '   ']], 'unknown' => [['status' => 'deleted']],
            'wrong case' => [['status' => 'NEW']], 'array' => [['status' => ['new']]],
            'number' => [['status' => 1]],
        ];
    }

    public function test_missing_or_invalid_lead_ids_return_not_found(): void
    {
        $this->get('/leads/999999')->assertNotFound();
        $this->patch('/leads/999999/status', ['status' => 'contacted'])->assertNotFound();
        $this->get('/leads/not-a-number')->assertNotFound();
        $this->patch('/leads/not-a-number/status', ['status' => 'contacted'])->assertNotFound();
    }

    private function createLead(array $overrides = []): Lead
    {
        return Lead::create(array_replace([
            'name' => 'Khách hàng chi tiết', 'contact' => 'details-test@example.com',
            'pet_type' => 'dog', 'location' => 'HCM', 'budget' => 5_000_000,
            'interest_level' => 'high', 'pain_point' => 'Ban công khó vệ sinh.', 'source' => 'Facebook',
        ], $overrides))->refresh();
    }
}
