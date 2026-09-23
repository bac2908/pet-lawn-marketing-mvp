<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\LeadScoringService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class LeadCaptureTest extends TestCase
{
    use RefreshDatabase;

    public function test_landing_page_shows_the_public_form(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk()
            ->assertSee('<title>Pet Lawn</title>', false)
            ->assertSee('Giải pháp Pet Lawn phù hợp cho căn hộ, ban công và sân nhỏ.')
            ->assertSee('Nhận tư vấn')
            ->assertSee('name="_token"', false)
            ->assertSee('5.000.000 ₫');

        foreach (array_keys($this->validInput()) as $field) {
            $response->assertSee('name="'.$field.'"', false);
        }

        $response->assertDontSee('name="score"', false)
            ->assertDontSee('name="segment"', false)
            ->assertDontSee('name="status"', false);
    }

    #[DataProvider('scoredSubmissions')]
    public function test_valid_submissions_are_saved_and_scored(array $overrides, int $score, string $segment): void
    {
        $input = $this->validInput($overrides);

        $response = $this->post(route('leads.store'), $input);

        $response->assertRedirect(route('home').'#lead-form')
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success', 'Cảm ơn bạn! Thông tin của bạn đã được ghi nhận. Chúng tôi sẽ liên hệ tư vấn sớm.');

        $this->assertDatabaseCount('leads', 1);
        $this->assertDatabaseHas('leads', [
            'name' => $input['name'],
            'contact' => $input['contact'],
            'pet_type' => strtolower($input['pet_type']),
            'location' => $input['location'],
            'budget' => $input['budget'],
            'pain_point' => $input['pain_point'],
            'interest_level' => $input['interest_level'],
            'source' => $input['source'],
            'score' => $score,
            'segment' => $segment,
            'status' => 'new',
        ]);

        $this->get(route('home'))->assertOk()
            ->assertSee('Cảm ơn bạn! Thông tin của bạn đã được ghi nhận.')
            ->assertDontSee('HOT')
            ->assertDontSee('WARM')
            ->assertDontSee('COLD')
            ->assertDontSee('name="score"', false);
    }

    public static function scoredSubmissions(): array
    {
        return [
            'hot' => [[], 100, 'HOT'],
            'warm' => [['pet_type' => 'Cat', 'budget' => 2_000_000, 'interest_level' => 'medium', 'pain_point' => null], 60, 'WARM'],
            'cold' => [['pet_type' => 'Other', 'budget' => 1_000_000, 'interest_level' => 'low', 'pain_point' => null, 'location' => 'Hà Nội'], 30, 'COLD'],
        ];
    }

    public function test_submission_uses_the_existing_scoring_service_once(): void
    {
        $this->mock(LeadScoringService::class, function (MockInterface $mock): void {
            $mock->shouldReceive('calculate')->once()
                ->withArgs(fn (Lead $lead) => ! $lead->exists
                    && $lead->budget === 5_000_000
                    && $lead->interest_level === 'high'
                    && $lead->location === 'HCM')
                ->andReturn(['score' => 87, 'segment' => 'HOT']);
        });

        $this->post(route('leads.store'), $this->validInput())
            ->assertRedirect(route('home').'#lead-form');

        // 87 is deliberately different from the rules' 100, proving the service result is used.
        $this->assertDatabaseHas('leads', ['score' => 87, 'segment' => 'HOT']);
    }

    public function test_required_fields_return_vietnamese_errors_without_creating_a_lead(): void
    {
        $this->post(route('leads.store'), [])
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors([
                'name' => 'Vui lòng nhập họ tên.',
                'pet_type' => 'Vui lòng chọn loại thú cưng.',
                'location' => 'Vui lòng nhập tỉnh / thành phố.',
                'budget' => 'Vui lòng chọn ngân sách dự kiến.',
                'interest_level' => 'Vui lòng chọn mức độ quan tâm.',
            ]);

        $this->assertDatabaseCount('leads', 0);

        $this->get(route('home'))->assertOk()
            ->assertSee('Vui lòng kiểm tra lại thông tin:')
            ->assertSee('Vui lòng nhập họ tên.')
            ->assertSee('aria-invalid="true"', false);
    }

    #[DataProvider('invalidSubmissions')]
    public function test_invalid_input_is_rejected_without_creating_a_lead(array $overrides, array $errors): void
    {
        $this->post(route('leads.store'), $this->validInput($overrides))
            ->assertRedirect(route('home'))
            ->assertSessionHasErrors($errors);

        $this->assertDatabaseCount('leads', 0);
        $this->get(route('home'))->assertOk()->assertSee('Vui lòng kiểm tra lại thông tin:');
    }

    public static function invalidSubmissions(): array
    {
        return [
            'invalid pet type' => [['pet_type' => 'Horse'], ['pet_type']],
            'invalid interest' => [['interest_level' => 'unknown'], ['interest_level']],
            'negative budget' => [['budget' => -1], ['budget']],
            'fractional budget' => [['budget' => '1000.5'], ['budget']],
            'non-numeric budget' => [['budget' => 'one million'], ['budget']],
            'budget exceeds unsigned integer' => [['budget' => 4294967296], ['budget']],
            'text too long' => [[
                'name' => str_repeat('a', 256),
                'contact' => str_repeat('a', 256),
                'location' => str_repeat('a', 256),
                'pain_point' => str_repeat('a', 2001),
                'source' => str_repeat('a', 101),
            ], ['name', 'contact', 'location', 'pain_point', 'source']],
            'array instead of text' => [['name' => ['unexpected']], ['name']],
            'whitespace required fields' => [['name' => '   ', 'location' => '   '], ['name', 'location']],
        ];
    }

    public function test_validation_errors_preserve_and_safely_display_old_input(): void
    {
        $input = $this->validInput(['name' => '<script>alert("test")</script>', 'location' => '']);

        $response = $this->post(route('leads.store'), $input)->assertSessionHasErrors('location');

        foreach (['name', 'contact', 'pet_type', 'budget', 'pain_point', 'interest_level', 'source'] as $field) {
            $response->assertSessionHasInput($field, $input[$field]);
        }

        $this->get(route('home'))->assertOk()
            ->assertSee($input['name'])
            ->assertDontSee($input['name'], false)
            ->assertSee('value="landing-test@example.com"', false)
            ->assertSee('value="5000000" selected', false)
            ->assertSee('Ban công khó vệ sinh.')
            ->assertSee('Vui lòng nhập tỉnh / thành phố.');
    }

    public function test_optional_fields_can_be_omitted(): void
    {
        $input = $this->validInput();
        unset($input['contact'], $input['pain_point'], $input['source']);

        $this->post(route('leads.store'), $input)->assertSessionHasNoErrors();

        $lead = Lead::query()->sole();

        $this->assertNull($lead->contact);
        $this->assertNull($lead->pain_point);
        $this->assertNull($lead->source);
        $this->assertSame(80, $lead->score);
        $this->assertSame('HOT', $lead->segment);
    }

    public function test_public_input_cannot_set_internal_scoring_or_status(): void
    {
        $this->post(route('leads.store'), $this->validInput([
            'budget' => 1_000_000,
            'interest_level' => 'low',
            'location' => 'Hà Nội',
            'pain_point' => null,
            'score' => 999,
            'segment' => 'HOT',
            'status' => 'converted',
        ]))->assertSessionHasNoErrors();

        $this->assertDatabaseHas('leads', ['score' => 30, 'segment' => 'COLD', 'status' => 'new']);
    }

    private function validInput(array $overrides = []): array
    {
        return array_replace([
            'name' => 'Khách hàng thử nghiệm',
            'contact' => 'landing-test@example.com',
            'pet_type' => 'Dog',
            'location' => 'HCM',
            'budget' => 5_000_000,
            'pain_point' => 'Ban công khó vệ sinh.',
            'interest_level' => 'high',
            'source' => 'Facebook',
        ], $overrides);
    }
}
