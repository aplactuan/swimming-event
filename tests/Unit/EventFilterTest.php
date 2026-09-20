<?php

namespace Tests\Unit;

use App\Enums\EventGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Event;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventFilterTest extends TestCase
{
    use RefreshDatabase;

    private Competition $competition;

    private Classification $novice;

    private Classification $noviceJuniors;

    private Classification $developmental;

    private AgeBracket $noviceSixAndBelow;

    private AgeBracket $noviceJuniorsSevenToTen;

    private AgeBracket $developmentalSixAndBelow;

    private AgeBracket $developmentalSevenToTen;

    protected function setUp(): void
    {
        parent::setUp();

        $this->competition = Competition::factory()->create();

        $this->novice = Classification::factory()->create([
            'competition_id' => $this->competition->id,
            'name' => 'Novice',
        ]);

        $this->noviceJuniors = Classification::factory()
            ->childOf($this->novice)
            ->create(['name' => 'Juniors']);

        $this->developmental = Classification::factory()->create([
            'competition_id' => $this->competition->id,
            'name' => 'Developmental',
        ]);

        $this->noviceSixAndBelow = AgeBracket::factory()->create([
            'classification_id' => $this->novice->id,
            'name' => '6 and below',
        ]);

        $this->noviceJuniorsSevenToTen = AgeBracket::factory()->create([
            'classification_id' => $this->noviceJuniors->id,
            'name' => '7 - 10',
        ]);

        $this->developmentalSixAndBelow = AgeBracket::factory()->create([
            'classification_id' => $this->developmental->id,
            'name' => '6 and below',
        ]);

        $this->developmentalSevenToTen = AgeBracket::factory()->create([
            'classification_id' => $this->developmental->id,
            'name' => '7 - 10',
        ]);
    }

    public function test_events_can_be_filtered_by_exact_name(): void
    {
        $matching = $this->createEvent('50m Freestyle');
        $this->createEvent('50m Freestyle Relay');

        $results = $this->filter(name: '50m Freestyle');

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matching));
    }

    public function test_the_name_filter_ignores_a_blank_selection(): void
    {
        $this->createEvent('50m Freestyle');
        $this->createEvent('50m Backstroke');

        $this->assertCount(2, $this->filter(name: ''));
        $this->assertCount(2, $this->filter(name: '   '));
    }

    public function test_events_can_be_filtered_by_classification(): void
    {
        $matching = $this->createEvent('50m Freestyle', [
            [$this->novice->id, $this->noviceSixAndBelow->id],
        ]);

        $this->createEvent('50m Backstroke', [
            [$this->developmental->id, $this->developmentalSixAndBelow->id],
        ]);

        $results = $this->filter(classificationId: $this->novice->id);

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matching));
    }

    public function test_the_classification_filter_also_matches_its_child_classifications(): void
    {
        $matching = $this->createEvent('50m Freestyle', [
            [$this->noviceJuniors->id, $this->noviceJuniorsSevenToTen->id],
        ]);

        $this->createEvent('50m Backstroke', [
            [$this->developmental->id, $this->developmentalSixAndBelow->id],
        ]);

        $results = $this->filter(classificationId: $this->novice->id);

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matching));
    }

    public function test_events_can_be_filtered_by_gender(): void
    {
        $matching = $this->createEvent('50m Freestyle', gender: EventGender::Female);
        $this->createEvent('50m Freestyle', gender: EventGender::Male);
        $this->createEvent('50m Freestyle', gender: EventGender::Mixed);

        $results = $this->filter(gender: 'female');

        $this->assertCount(1, $results);
        $this->assertTrue($results->contains($matching));
    }

    public function test_the_gender_filter_ignores_a_blank_selection(): void
    {
        $this->createEvent('50m Freestyle', gender: EventGender::Female);
        $this->createEvent('50m Freestyle', gender: EventGender::Male);

        $this->assertCount(2, $this->filter(gender: ''));
        $this->assertCount(2, $this->filter(gender: '   '));
    }

    public function test_events_can_be_filtered_by_age_bracket_name_across_classifications(): void
    {
        $novice = $this->createEvent('50m Freestyle', [
            [$this->novice->id, $this->noviceSixAndBelow->id],
        ]);

        $developmental = $this->createEvent('50m Backstroke', [
            [$this->developmental->id, $this->developmentalSixAndBelow->id],
        ]);

        $this->createEvent('100m Freestyle', [
            [$this->noviceJuniors->id, $this->noviceJuniorsSevenToTen->id],
        ]);

        $results = $this->filter(ageBracketName: '6 and below');

        $this->assertCount(2, $results);
        $this->assertTrue($results->contains($novice));
        $this->assertTrue($results->contains($developmental));
    }

    public function test_eligibility_filters_must_both_be_satisfied_by_the_same_row(): void
    {
        $this->createEvent('50m Freestyle', [
            [$this->novice->id, $this->noviceSixAndBelow->id],
            [$this->developmental->id, $this->developmentalSevenToTen->id],
        ]);

        $this->assertCount(
            0,
            $this->filter(classificationId: $this->developmental->id, ageBracketName: '6 and below'),
        );

        $this->assertCount(
            1,
            $this->filter(classificationId: $this->developmental->id, ageBracketName: '7 - 10'),
        );
    }

    public function test_events_are_unfiltered_when_no_eligibility_filter_is_selected(): void
    {
        $this->createEvent('50m Freestyle');
        $this->createEvent('50m Backstroke');

        $this->assertCount(2, $this->filter());
    }

    /**
     * @param  list<array{0: string, 1: string}>  $eligibilities
     */
    private function createEvent(
        string $name,
        array $eligibilities = [],
        EventGender $gender = EventGender::Mixed,
    ): Event {
        $event = Event::factory()->create([
            'competition_id' => $this->competition->id,
            'name' => $name,
            'gender' => $gender,
        ]);

        foreach ($eligibilities as [$classificationId, $ageBracketId]) {
            $event->eligibilities()->create([
                'classification_id' => $classificationId,
                'age_bracket_id' => $ageBracketId,
            ]);
        }

        return $event;
    }

    /**
     * @return Collection<int, Event>
     */
    private function filter(
        string $name = '',
        ?string $classificationId = null,
        ?string $ageBracketName = null,
        string $gender = '',
    ): Collection {
        return Event::query()
            ->where('competition_id', $this->competition->id)
            ->ofName($name)
            ->ofGender($gender)
            ->eligibleFor([
                'classification_id' => $classificationId,
                'age_bracket_name' => $ageBracketName,
            ])
            ->get();
    }
}
