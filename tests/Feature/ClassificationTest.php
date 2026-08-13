<?php

namespace Tests\Feature;

use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class ClassificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_create_a_classification(): void
    {
        $competition = Competition::factory()->create();

        $response = $this->post(route('classifications.store', $competition), [
            'name' => 'Novice',
        ]);

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('classifications', 0);
    }

    public function test_authenticated_users_can_create_a_root_classification(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('classifications.store', $competition), [
                'name' => 'Novice',
            ]);

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'classification-created');

        $classification = Classification::query()->first();

        $this->assertNotNull($classification);
        $this->assertSame('Novice', $classification->name);
        $this->assertSame($competition->id, $classification->competition_id);
        $this->assertNull($classification->parent_id);
        $this->assertSame(1, $classification->sort_order);
    }

    public function test_authenticated_users_can_create_a_child_classification(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $parent = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Developmental',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('classifications.store', $competition), [
                'name' => 'Class A',
                'parent_id' => $parent->id,
            ]);

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasNoErrors();

        $child = Classification::query()->where('name', 'Class A')->first();

        $this->assertNotNull($child);
        $this->assertSame($parent->id, $child->parent_id);
        $this->assertSame($competition->id, $child->competition_id);
    }

    public function test_parent_classification_must_be_a_root_of_the_same_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $otherCompetition = Competition::factory()->create();
        $foreignParent = Classification::factory()->create([
            'competition_id' => $otherCompetition->id,
        ]);
        $root = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $child = Classification::factory()->childOf($root)->create();

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('classifications.store', $competition), [
                'name' => 'Too Deep',
                'parent_id' => $child->id,
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('parent_id');

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('classifications.store', $competition), [
                'name' => 'Wrong Meet',
                'parent_id' => $foreignParent->id,
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('parent_id');
    }

    public function test_classification_requires_a_name(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('classifications.store', $competition), [
                'name' => '',
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('name');
    }

    public function test_authenticated_users_can_update_a_classification(): void
    {
        $user = User::factory()->create();
        $classification = Classification::factory()->create(['name' => 'Novice']);

        $response = $this
            ->actingAs($user)
            ->put(route('classifications.update', [
                $classification->competition,
                $classification,
            ]), [
                'name' => 'Open Novice',
            ]);

        $response
            ->assertRedirect(route('competitions.show', $classification->competition))
            ->assertSessionHas('status', 'classification-updated');

        $this->assertSame('Open Novice', $classification->fresh()->name);
    }

    public function test_classification_cannot_be_updated_for_another_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create();

        $this
            ->actingAs($user)
            ->put(route('classifications.update', [
                $competition,
                $classification,
            ]), [
                'name' => 'Hijacked',
            ])
            ->assertNotFound();
    }

    public function test_authenticated_users_can_delete_a_classification(): void
    {
        $user = User::factory()->create();
        $parent = Classification::factory()->create();
        $child = Classification::factory()->childOf($parent)->create();
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $parent->id,
        ]);

        $response = $this
            ->actingAs($user)
            ->delete(route('classifications.destroy', [
                $parent->competition,
                $parent,
            ]));

        $response
            ->assertRedirect(route('competitions.show', $parent->competition))
            ->assertSessionHas('status', 'classification-deleted');

        $this->assertModelMissing($parent);
        $this->assertModelMissing($child);
        $this->assertModelMissing($bracket);
    }

    public function test_deleting_a_competition_cascades_classifications_and_age_brackets(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
        ]);
        $bracket = AgeBracket::factory()->create([
            'classification_id' => $classification->id,
        ]);

        $this
            ->actingAs($user)
            ->delete(route('competitions.destroy', $competition));

        $this->assertModelMissing($competition);
        $this->assertModelMissing($classification);
        $this->assertModelMissing($bracket);
    }

    public function test_competition_show_includes_nested_classifications_and_age_brackets(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $developmental = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Developmental',
            'sort_order' => 1,
        ]);
        $classA = Classification::factory()->childOf($developmental)->create([
            'name' => 'Class A',
            'sort_order' => 1,
        ]);
        AgeBracket::factory()->create([
            'classification_id' => $developmental->id,
            'name' => '9 and below',
            'start_birthday' => '2017-01-01',
            'end_birthday' => null,
            'sort_order' => 1,
        ]);
        AgeBracket::factory()->create([
            'classification_id' => $classA->id,
            'name' => '10-15',
            'start_birthday' => '2011-01-01',
            'end_birthday' => '2016-12-31',
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->has('competition.classifications', 1)
                ->where('competition.classifications.0.name', 'Developmental')
                ->where('competition.classifications.0.age_brackets.0.name', '9 and below')
                ->where('competition.classifications.0.age_brackets.0.start_birthday', '2017-01-01')
                ->where('competition.classifications.0.age_brackets.0.end_birthday', null)
                ->where('competition.classifications.0.children.0.name', 'Class A')
                ->where('competition.classifications.0.children.0.age_brackets.0.name', '10-15')
                ->where('competition.classifications.0.children.0.inherits_age_brackets', false));
    }

    public function test_competition_show_child_inherits_parent_age_brackets_when_unset(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $developmental = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Developmental',
            'sort_order' => 1,
        ]);
        Classification::factory()->childOf($developmental)->create([
            'name' => 'Class A',
            'sort_order' => 1,
        ]);
        AgeBracket::factory()->create([
            'classification_id' => $developmental->id,
            'name' => '9 and below',
            'start_birthday' => '2017-01-01',
            'end_birthday' => null,
            'sort_order' => 1,
        ]);

        $this
            ->actingAs($user)
            ->get(route('competitions.show', $competition))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Competitions/Show')
                ->where('competition.classifications.0.children.0.name', 'Class A')
                ->where('competition.classifications.0.children.0.inherits_age_brackets', true)
                ->where('competition.classifications.0.children.0.age_brackets.0.name', '9 and below')
                ->where('competition.classifications.0.children.0.age_brackets.0.start_birthday', '2017-01-01'));
    }
}
