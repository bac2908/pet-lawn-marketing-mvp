<?php

namespace App\Services;

use App\Models\Lead;
use InvalidArgumentException;

class LeadScoringService
{
    private const TARGET_LOCATIONS = [
        'hcm',
        'ho chi minh',
        'ho chi minh city',
        'tp.hcm',
        'tp hcm',
        'thành phố hồ chí minh',
    ];

    /**
     * Calculate a score without changing or saving the lead.
     *
     * @return array{score: int, segment: string}
     */
    public function calculate(Lead $lead): array
    {
        $budgetPoints = match (true) {
            $lead->budget >= 5_000_000 => 30,
            $lead->budget >= 2_000_000 => 20,
            default => 10,
        };

        $interestPoints = match (strtolower(trim((string) $lead->interest_level))) {
            'high' => 30,
            'medium' => 20,
            'low' => 10,
            default => throw new InvalidArgumentException('Interest level must be low, medium, or high.'),
        };

        $painPointPoints = trim((string) $lead->pain_point) !== '' ? 20 : 0;

        $location = mb_strtolower(trim((string) $lead->location), 'UTF-8');
        $locationPoints = in_array($location, self::TARGET_LOCATIONS, true) ? 20 : 10;

        $score = $budgetPoints + $interestPoints + $painPointPoints + $locationPoints;

        return [
            'score' => $score,
            'segment' => match (true) {
                $score >= 80 => 'HOT',
                $score >= 50 => 'WARM',
                default => 'COLD',
            },
        ];
    }
}
