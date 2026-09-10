<?php

namespace Tests\Feature;

use App\Enums\ParticipantGender;
use App\Models\Classification;
use App\Models\Competition;
use App\Models\Participant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class ParticipantImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_import_participants(): void
    {
        $competition = Competition::factory()->create();

        $response = $this->post(
            route('participants.import', $competition),
            ['file' => $this->csvFile($this->validCsv())],
        );

        $response->assertRedirect(route('login'));
        $this->assertDatabaseCount('participants', 0);
        $this->assertDatabaseCount('classifications', 0);
    }

    public function test_authenticated_users_can_import_participants_and_create_missing_classifications(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $response = $this
            ->actingAs($user)
            ->post(route('participants.import', $competition), [
                'file' => $this->csvFile($this->validCsv()),
            ]);

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('status', 'participants-imported')
            ->assertSessionHas('import_summary', [
                'imported' => 2,
                'skipped_duplicates' => 0,
                'skipped_invalid' => 0,
                'classifications_created' => 2,
            ]);

        $novice = Classification::query()->where('name', 'Novice')->first();
        $developmental = Classification::query()->where('name', 'Developmental')->first();

        $this->assertNotNull($novice);
        $this->assertNotNull($developmental);
        $this->assertSame($competition->id, $novice->competition_id);
        $this->assertSame($competition->id, $developmental->competition_id);
        $this->assertNull($novice->parent_id);
        $this->assertNull($developmental->parent_id);

        $elias = Participant::query()->where('first_name', 'Elias Matteo')->first();
        $princess = Participant::query()->where('first_name', 'Princess Elijah')->first();

        $this->assertNotNull($elias);
        $this->assertSame('Aban', $elias->last_name);
        $this->assertSame(ParticipantGender::Male, $elias->gender);
        $this->assertSame('Leganes Aqua Phoenix', $elias->team);
        $this->assertSame('2020-08-19', $elias->birthdate->toDateString());
        $this->assertSame($developmental->id, $elias->classification_id);
        $this->assertFalse($elias->paid);

        $this->assertNotNull($princess);
        $this->assertSame('Abdon', $princess->last_name);
        $this->assertSame(ParticipantGender::Female, $princess->gender);
        $this->assertSame($novice->id, $princess->classification_id);
        $this->assertFalse($princess->paid);

        $this->assertDatabaseCount('event_participant', 0);
    }

    public function test_import_skips_existing_names_on_the_same_competition(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $otherCompetition = Competition::factory()->create();
        $classification = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'Novice',
        ]);

        Participant::factory()->create([
            'competition_id' => $competition->id,
            'classification_id' => $classification->id,
            'first_name' => 'ELIAS MATTEO',
            'last_name' => 'Aban',
            'team' => 'Existing Team',
        ]);

        Participant::factory()->create([
            'competition_id' => $otherCompetition->id,
            'first_name' => 'Princess Elijah',
            'last_name' => 'Abdon',
        ]);

        $response = $this
            ->actingAs($user)
            ->post(route('participants.import', $competition), [
                'file' => $this->csvFile($this->validCsv()),
            ]);

        $response
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('import_summary.imported', 1)
            ->assertSessionHas('import_summary.skipped_duplicates', 1);

        $this->assertSame(
            1,
            $competition->participants()->where('last_name', 'Aban')->count(),
        );
        $this->assertSame(
            'Existing Team',
            $competition->participants()->where('last_name', 'Aban')->value('team'),
        );
        $this->assertSame(
            1,
            $competition->participants()->where('first_name', 'Princess Elijah')->count(),
        );
    }

    public function test_import_reuses_existing_classifications_instead_of_duplicating_them(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();
        $novice = Classification::factory()->create([
            'competition_id' => $competition->id,
            'name' => 'novice',
        ]);

        $this
            ->actingAs($user)
            ->post(route('participants.import', $competition), [
                'file' => $this->csvFile($this->validCsv()),
            ])
            ->assertRedirect(route('competitions.show', $competition));

        $this->assertSame(2, $competition->classifications()->count());
        $this->assertSame(
            $novice->id,
            $competition->participants()->where('last_name', 'Abdon')->value('classification_id'),
        );
        $this->assertSame(
            1,
            $competition->classifications()->whereRaw('lower(name) = ?', ['novice'])->count(),
        );
    }

    public function test_import_requires_a_file(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('participants.import', $competition))
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('file');
    }

    public function test_import_rejects_a_csv_missing_required_headers(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $csv = "No.,Name,Team\n1,\"Aban, Elias Matteo\",Leganes Aqua Phoenix\n";

        $this
            ->actingAs($user)
            ->from(route('competitions.show', $competition))
            ->post(route('participants.import', $competition), [
                'file' => $this->csvFile($csv),
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHasErrors('file');

        $this->assertDatabaseCount('participants', 0);
    }

    public function test_import_skips_invalid_rows_and_still_imports_valid_ones(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $csv = implode("\n", [
            'No.,Name,Team,Gen,Age,First Name,Last Name,Birthday,Classification',
            '1,"Aban, Elias Matteo",Leganes Aqua Phoenix,M,6,Elias Matteo,Aban,08/19/2020,Developmental',
            '2,"Bad, Gender",Some Team,X,10,Bad,Gender,08/19/2020,Novice',
            '3,"Bad, Birthday",Some Team,F,10,Bad,Birthday,not-a-date,Novice',
            '4,"Missing, First",Some Team,F,10,,Missing,08/19/2020,Novice',
            '',
        ]);

        $this
            ->actingAs($user)
            ->post(route('participants.import', $competition), [
                'file' => $this->csvFile($csv),
            ])
            ->assertRedirect(route('competitions.show', $competition))
            ->assertSessionHas('import_summary', [
                'imported' => 1,
                'skipped_duplicates' => 0,
                'skipped_invalid' => 3,
                'classifications_created' => 1,
            ]);

        $this->assertSame(1, $competition->participants()->count());
        $this->assertTrue($competition->participants()->where('last_name', 'Aban')->exists());
    }

    public function test_import_skips_duplicate_names_within_the_same_file(): void
    {
        $user = User::factory()->create();
        $competition = Competition::factory()->create();

        $csv = implode("\n", [
            'No.,Name,Team,Gen,Age,First Name,Last Name,Birthday,Classification',
            '1,"Aban, Elias Matteo",Leganes Aqua Phoenix,M,6,Elias Matteo,Aban,08/19/2020,Developmental',
            '2,"Aban, Elias Matteo",Other Team,M,6,Elias Matteo,Aban,08/19/2020,Novice',
            '',
        ]);

        $this
            ->actingAs($user)
            ->post(route('participants.import', $competition), [
                'file' => $this->csvFile($csv),
            ])
            ->assertSessionHas('import_summary.imported', 1)
            ->assertSessionHas('import_summary.skipped_duplicates', 1);

        $this->assertSame(1, $competition->participants()->count());
        $this->assertSame(
            'Leganes Aqua Phoenix',
            $competition->participants()->value('team'),
        );
    }

    private function validCsv(): string
    {
        return implode("\n", [
            'No.,Name,Team,Gen,Age,First Name,Last Name,Birthday,Classification',
            '1,"Aban, Elias Matteo",Leganes Aqua Phoenix,M,6,Elias Matteo,Aban,08/19/2020,Developmental',
            '2,"Abdon, Princess Elijah",Sta Barbara Sailfish Swim Team,F,12,Princess Elijah,Abdon,02/14/2014,Novice',
            '',
        ]);
    }

    private function csvFile(string $contents): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('participants.csv', $contents);
    }
}
