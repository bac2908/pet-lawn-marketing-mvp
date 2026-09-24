<?php

namespace App\Models;

use App\Services\LeadScoringService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Lead extends Model
{
    public const STATUS_LABELS = [
        'new' => 'Mới',
        'contacted' => 'Đã liên hệ',
        'qualified' => 'Đủ điều kiện',
        'converted' => 'Đã chuyển đổi',
        'lost' => 'Không thành công',
    ];

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'contact',
        'pet_type',
        'location',
        'budget',
        'pain_point',
        'interest_level',
        'source',
        'score',
        'segment',
        'status',
    ];

    /**
     * Default values, including before the model is saved.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'score' => 0,
        'segment' => 'COLD',
        'status' => 'new',
    ];

    protected static function booted(): void
    {
        // Assign derived values in the initial insert; calculation never saves the model.
        static::creating(function (Lead $lead): void {
            $lead->fill(app(LeadScoringService::class)->calculate($lead));
        });
    }

    public function notes(): HasMany
    {
        return $this->hasMany(LeadNote::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(LeadStatusHistory::class);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'budget' => 'integer',
            'score' => 'integer',
        ];
    }
}
