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
        $explanation = $this->explain($lead);

        return ['score' => $explanation['score'], 'segment' => $explanation['segment']];
    }

    /**
     * Explain the same calculation without changing or saving the lead.
     *
     * @return array{score: int, segment: string, segment_reason: string, breakdown: list<array{label: string, points: int, reason: string}>}
     */
    public function explain(Lead $lead): array
    {
        [$budgetPoints, $budgetReason] = match (true) {
            $lead->budget >= 5_000_000 => [30, 'Ngân sách từ 5.000.000 VND trở lên.'],
            $lead->budget >= 2_000_000 => [20, 'Ngân sách từ 2.000.000 đến dưới 5.000.000 VND.'],
            default => [10, 'Ngân sách dưới 2.000.000 VND.'],
        };

        [$interestPoints, $interestReason] = match (strtolower(trim((string) $lead->interest_level))) {
            'high' => [30, 'Mức độ quan tâm cao.'],
            'medium' => [20, 'Mức độ quan tâm trung bình.'],
            'low' => [10, 'Mức độ quan tâm thấp.'],
            default => throw new InvalidArgumentException('Interest level must be low, medium, or high.'),
        };

        $hasPainPoint = trim((string) $lead->pain_point) !== '';
        $location = mb_strtolower(trim((string) $lead->location), 'UTF-8');
        $isTargetLocation = in_array($location, self::TARGET_LOCATIONS, true);

        $breakdown = [
            ['label' => 'Ngân sách', 'points' => $budgetPoints, 'reason' => $budgetReason],
            ['label' => 'Mức độ quan tâm', 'points' => $interestPoints, 'reason' => $interestReason],
            [
                'label' => 'Nhu cầu',
                'points' => $hasPainPoint ? 20 : 0,
                'reason' => $hasPainPoint ? 'Đã mô tả nhu cầu cần giải quyết.' : 'Chưa mô tả nhu cầu cần giải quyết.',
            ],
            [
                'label' => 'Địa điểm',
                'points' => $isTargetLocation ? 20 : 10,
                'reason' => $isTargetLocation
                    ? 'Địa điểm khớp danh sách tên TP.HCM được hỗ trợ.'
                    : 'Địa điểm không khớp danh sách tên TP.HCM được hỗ trợ.',
            ],
        ];

        $score = array_sum(array_column($breakdown, 'points'));
        [$segment, $segmentReason] = match (true) {
            $score >= 80 => ['HOT', 'Tổng điểm từ 80 trở lên.'],
            $score >= 50 => ['WARM', 'Tổng điểm từ 50 đến dưới 80.'],
            default => ['COLD', 'Tổng điểm dưới 50.'],
        };

        return [
            'score' => $score,
            'segment' => $segment,
            'segment_reason' => $segmentReason,
            'breakdown' => $breakdown,
        ];
    }
}
