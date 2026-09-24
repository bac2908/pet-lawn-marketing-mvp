<?php

namespace Tests\Unit;

use App\Models\Lead;
use App\Services\LeadScoringService;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class LeadScoringServiceTest extends TestCase
{
    #[DataProvider('scoringCases')]
    public function test_calculates_scores_and_segments(
        int $budget,
        string $interest,
        ?string $painPoint,
        string $location,
        int $score,
        string $segment,
    ): void {
        $lead = new Lead([
            'budget' => $budget,
            'interest_level' => $interest,
            'pain_point' => $painPoint,
            'location' => $location,
        ]);

        $this->assertSame(
            ['score' => $score, 'segment' => $segment],
            (new LeadScoringService)->calculate($lead),
        );
    }

    public static function scoringCases(): array
    {
        return [
            'A: HOT maximum' => [5_000_000, 'high', 'Needs a lawn.', 'HCM', 100, 'HOT'],
            'B: WARM' => [2_000_000, 'medium', null, 'HCM', 60, 'WARM'],
            'C: COLD minimum' => [0, 'low', null, 'Hà Nội', 30, 'COLD'],
            'D: exactly 80' => [2_000_000, 'high', 'Needs a lawn.', 'Hà Nội', 80, 'HOT'],
            'E: exactly 50' => [0, 'low', 'Needs a lawn.', 'Hà Nội', 50, 'WARM'],
            'just below two million' => [1_999_999, 'low', null, 'Hà Nội', 30, 'COLD'],
            'exactly two million' => [2_000_000, 'low', null, 'Hà Nội', 40, 'COLD'],
            'just below five million' => [4_999_999, 'low', null, 'Hà Nội', 40, 'COLD'],
            'exactly five million' => [5_000_000, 'low', null, 'Hà Nội', 50, 'WARM'],
            'above five million' => [10_000_000, 'low', null, 'Hà Nội', 50, 'WARM'],
            'empty pain point' => [0, 'low', '', 'Hà Nội', 30, 'COLD'],
            'whitespace pain point' => [0, 'low', " \t\n ", 'Hà Nội', 30, 'COLD'],
            'string zero is non-empty' => [0, 'low', '0', 'Hà Nội', 50, 'WARM'],
            'interest normalization' => [5_000_000, ' HIGH ', 'Needs a lawn.', 'HCM', 100, 'HOT'],
        ];
    }

    #[DataProvider('locationCases')]
    public function test_location_matching_is_case_insensitive_and_exact(string $location, int $score): void
    {
        $lead = new Lead([
            'budget' => 5_000_000,
            'interest_level' => 'high',
            'pain_point' => 'Needs a lawn.',
            'location' => $location,
        ]);

        $this->assertSame($score, (new LeadScoringService)->calculate($lead)['score']);
    }

    public static function locationCases(): array
    {
        return [
            ['HCM', 100],
            ['hcm', 100],
            ['hCm', 100],
            ['Ho Chi Minh', 100],
            ['HO CHI MINH', 100],
            ['Ho Chi Minh City', 100],
            ['ho chi minh city', 100],
            ['TP.HCM', 100],
            ['tp.hcm', 100],
            ['TP HCM', 100],
            ['tp hcm', 100],
            ['Thành phố Hồ Chí Minh', 100],
            ['THÀNH PHỐ HỒ CHÍ MINH', 100],
            ['  HCM  ', 100],
            ['Hà Nội', 90],
            ['Not HCM', 90],
            ['HCM outskirts', 90],
            ['', 90],
        ];
    }

    public function test_calculation_does_not_change_or_save_the_lead(): void
    {
        $lead = new Lead([
            'budget' => 5_000_000,
            'interest_level' => 'high',
            'pain_point' => 'Needs a lawn.',
            'location' => 'HCM',
        ]);
        $attributes = $lead->getAttributes();
        $service = new LeadScoringService;

        $this->assertSame($service->calculate($lead), $service->calculate($lead));
        $this->assertSame($service->explain($lead), $service->explain($lead));
        $this->assertSame($attributes, $lead->getAttributes());
        $this->assertFalse($lead->exists);
    }

    #[DataProvider('explanationCases')]
    public function test_explanation_shows_the_points_and_reasons_used_by_calculate(
        int $budget, string $interest, ?string $painPoint, string $location, array $points, string $segment,
    ): void {
        $lead = new Lead(['budget' => $budget, 'interest_level' => $interest, 'pain_point' => $painPoint, 'location' => $location]);
        $service = new LeadScoringService;
        $result = $service->explain($lead);

        $this->assertSame($points, array_column($result['breakdown'], 'points'));
        $this->assertSame(array_sum($points), $result['score']);
        $this->assertSame($segment, $result['segment']);
        $this->assertNotEmpty($result['segment_reason']);
        $this->assertSame(['Ngân sách', 'Mức độ quan tâm', 'Nhu cầu', 'Địa điểm'], array_column($result['breakdown'], 'label'));
        foreach ($result['breakdown'] as $factor) {
            $this->assertNotEmpty($factor['reason']);
        }
        $this->assertSame($service->calculate($lead), ['score' => $result['score'], 'segment' => $result['segment']]);
    }

    public static function explanationCases(): array
    {
        return [
            'HOT' => [5_000_000, 'high', 'Cần dễ vệ sinh.', 'HCM', [30, 30, 20, 20], 'HOT'],
            'WARM' => [2_000_000, 'medium', null, 'HCM', [20, 20, 0, 20], 'WARM'],
            'COLD' => [1_000_000, 'low', '   ', 'Hà Nội', [10, 10, 0, 10], 'COLD'],
            'exactly 80' => [2_000_000, 'high', 'Cần dễ vệ sinh.', 'Hà Nội', [20, 30, 20, 10], 'HOT'],
            'exactly 50' => [0, 'low', '0', 'Hà Nội', [10, 10, 20, 10], 'WARM'],
            'normalized inputs' => [4_999_999, ' MEDIUM ', null, ' thành PHỐ hồ chí minh ', [20, 20, 0, 20], 'WARM'],
        ];
    }

    public function test_unknown_interest_is_rejected_instead_of_silently_scored(): void
    {
        $lead = new Lead([
            'budget' => 0,
            'interest_level' => 'unknown',
            'location' => 'HCM',
        ]);

        $this->expectException(InvalidArgumentException::class);

        (new LeadScoringService)->calculate($lead);
    }
}
