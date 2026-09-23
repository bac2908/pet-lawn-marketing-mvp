<?php

namespace Tests\Feature;

use App\Models\Lead;
use App\Services\LeadScoringService;
use Database\Seeders\LeadSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_defaults_casts_and_nullable_fields_are_saved(): void
    {
        $lead = new Lead([
            'name' => 'Sample Lead',
            'pet_type' => 'dog',
            'location' => 'Hà Nội',
            'budget' => '750000',
            'interest_level' => 'medium',
        ]);

        $this->assertSame(0, $lead->score);
        $this->assertSame('COLD', $lead->segment);
        $this->assertSame('new', $lead->status);

        $lead->save();
        $lead->refresh();

        $this->assertSame(750000, $lead->budget);
        $this->assertSame(40, $lead->score);
        $this->assertNull($lead->contact);
        $this->assertNull($lead->pain_point);
        $this->assertNull($lead->source);
        $this->assertNotNull($lead->created_at);
        $this->assertNotNull($lead->updated_at);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'name' => 'Sample Lead',
            'pet_type' => 'dog',
            'location' => 'Hà Nội',
            'interest_level' => 'medium',
            'score' => 40,
            'segment' => 'COLD',
            'status' => 'new',
        ]);
    }

    public function test_creating_a_lead_automatically_replaces_supplied_scoring_values(): void
    {
        $lead = Lead::create([
            'name' => 'Hot Lead',
            'pet_type' => 'dog',
            'location' => 'HCM',
            'budget' => 5_000_000,
            'pain_point' => 'Needs an easy-to-clean lawn for a dog.',
            'interest_level' => 'high',
            'score' => 1,
            'segment' => 'COLD',
        ]);

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'score' => 100,
            'segment' => 'HOT',
            'status' => 'new',
        ]);
    }

    public function test_an_existing_lead_can_be_explicitly_rescored_and_saved(): void
    {
        $lead = Lead::create([
            'name' => 'Updated Lead',
            'pet_type' => 'cat',
            'location' => 'Hà Nội',
            'budget' => 0,
            'interest_level' => 'low',
            'status' => 'contacted',
        ]);

        $lead->fill([
            'location' => 'HCM',
            'budget' => 5_000_000,
            'interest_level' => 'high',
            'pain_point' => 'Needs a lawn this week.',
        ]);

        $result = app(LeadScoringService::class)->calculate($lead);

        $this->assertDatabaseHas('leads', ['id' => $lead->id, 'score' => 30]);

        $lead->fill($result)->save();

        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'score' => 100,
            'segment' => 'HOT',
            'status' => 'contacted',
        ]);
    }

    public function test_database_defaults_apply_without_eloquent(): void
    {
        $id = DB::table('leads')->insertGetId([
            'name' => 'Direct Insert Lead',
            'pet_type' => 'cat',
            'location' => 'Đà Nẵng',
            'budget' => 0,
            'interest_level' => 'low',
        ]);

        $this->assertDatabaseHas('leads', [
            'id' => $id,
            'budget' => 0,
            'contact' => null,
            'pain_point' => null,
            'source' => null,
            'score' => 0,
            'segment' => 'COLD',
            'status' => 'new',
        ]);
    }

    public function test_database_seeder_creates_ten_varied_scored_leads(): void
    {
        $this->seed();

        $this->assertDatabaseCount('leads', 10);
        $this->assertSame(10, Lead::query()
            ->whereBetween('score', [30, 100])
            ->where('status', 'new')
            ->count());
        $this->assertEqualsCanonicalizing(
            ['HOT', 'WARM', 'COLD'],
            Lead::query()->distinct()->pluck('segment')->all(),
        );

        $scoringService = app(LeadScoringService::class);

        foreach (Lead::all() as $lead) {
            $this->assertSame($scoringService->calculate($lead), $lead->only('score', 'segment'));
        }
        $this->assertEqualsCanonicalizing(
            ['dog', 'cat'],
            Lead::query()->distinct()->pluck('pet_type')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['low', 'medium', 'high'],
            Lead::query()->distinct()->pluck('interest_level')->all(),
        );
        $this->assertEqualsCanonicalizing(
            ['Facebook', 'TikTok', 'Google', 'Referral'],
            Lead::query()->distinct()->pluck('source')->all(),
        );
        $this->assertGreaterThan(1, Lead::query()->distinct()->count('location'));
        $this->assertGreaterThan(1, Lead::query()->distinct()->count('budget'));
    }

    public function test_reseeding_recalculates_scores_without_overwriting_inputs_or_status(): void
    {
        $this->seed(LeadSeeder::class);

        $lead = Lead::query()->where('contact', 'minh.anh@example.com')->firstOrFail();
        $lead->update(['status' => 'contacted', 'budget' => 2_500_000]);

        // Simulate scoring values left over from step 2 without model events.
        DB::table('leads')->where('id', $lead->id)->update(['score' => 0, 'segment' => 'COLD']);

        $this->seed(LeadSeeder::class);

        $this->assertDatabaseCount('leads', 10);
        $this->assertDatabaseHas('leads', [
            'id' => $lead->id,
            'status' => 'contacted',
            'budget' => 2_500_000,
            'score' => 90,
            'segment' => 'HOT',
        ]);
    }
}
