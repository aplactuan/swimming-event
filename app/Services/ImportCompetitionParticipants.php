<?php

namespace App\Services;

use App\Enums\ParticipantGender;
use App\Models\Classification;
use App\Models\Competition;
use Carbon\Carbon;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ImportCompetitionParticipants
{
    /**
     * @var array<string, string>
     */
    private const HEADER_ALIASES = [
        'first name' => 'first_name',
        'firstname' => 'first_name',
        'last name' => 'last_name',
        'lastname' => 'last_name',
        'team' => 'team',
        'gen' => 'gender',
        'gender' => 'gender',
        'birthday' => 'birthdate',
        'birthdate' => 'birthdate',
        'classification' => 'classification',
    ];

    /**
     * @var list<string>
     */
    private const REQUIRED_COLUMNS = [
        'first_name',
        'last_name',
        'team',
        'gender',
        'birthdate',
        'classification',
    ];

    /**
     * @return array{imported: int, skipped_duplicates: int, skipped_invalid: int, classifications_created: int}
     */
    public function import(Competition $competition, UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false) {
            throw ValidationException::withMessages([
                'file' => 'The CSV file could not be read.',
            ]);
        }

        $handle = fopen($path, 'r');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'The CSV file could not be read.',
            ]);
        }

        try {
            $header = fgetcsv($handle);

            if ($header === false) {
                throw ValidationException::withMessages([
                    'file' => 'The CSV file is missing the required columns: First Name, Last Name, Team, Gen, Birthday, and Classification.',
                ]);
            }

            $columnIndexes = $this->columnIndexes($header);

            return DB::transaction(function () use ($competition, $handle, $columnIndexes): array {
                return $this->importRows($competition, $handle, $columnIndexes);
            });
        } finally {
            fclose($handle);
        }
    }

    /**
     * @param  list<string|null>  $header
     * @return array<string, int>
     */
    private function columnIndexes(array $header): array
    {
        if ($header !== [] && is_string($header[0])) {
            $header[0] = Str::of($header[0])->replace("\u{FEFF}", '')->toString();
        }

        $indexes = [];

        foreach ($header as $index => $column) {
            $normalized = $this->normalizeHeader((string) $column);
            $alias = self::HEADER_ALIASES[$normalized] ?? null;

            if ($alias !== null && ! array_key_exists($alias, $indexes)) {
                $indexes[$alias] = $index;
            }
        }

        $missing = array_values(array_diff(self::REQUIRED_COLUMNS, array_keys($indexes)));

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'file' => 'The CSV file is missing the required columns: First Name, Last Name, Team, Gen, Birthday, and Classification.',
            ]);
        }

        return $indexes;
    }

    /**
     * @param  resource  $handle
     * @param  array<string, int>  $columnIndexes
     * @return array{imported: int, skipped_duplicates: int, skipped_invalid: int, classifications_created: int}
     */
    private function importRows(Competition $competition, $handle, array $columnIndexes): array
    {
        $existingNames = $competition->participants()
            ->get(['first_name', 'last_name'])
            ->mapWithKeys(fn ($participant): array => [
                $this->nameKey($participant->first_name, $participant->last_name) => true,
            ])
            ->all();

        /** @var array<string, Classification> $classificationsByName */
        $classificationsByName = $competition->classifications()
            ->get()
            ->mapWithKeys(fn (Classification $classification): array => [
                Str::lower(Str::squish($classification->name)) => $classification,
            ])
            ->all();

        $imported = 0;
        $skippedDuplicates = 0;
        $skippedInvalid = 0;
        $classificationsCreated = 0;

        while (($row = fgetcsv($handle)) !== false) {
            if ($this->isEmptyRow($row, $columnIndexes)) {
                continue;
            }

            $firstName = Str::squish((string) ($row[$columnIndexes['first_name']] ?? ''));
            $lastName = Str::squish((string) ($row[$columnIndexes['last_name']] ?? ''));
            $team = Str::squish((string) ($row[$columnIndexes['team']] ?? ''));
            $gender = $this->parseGender((string) ($row[$columnIndexes['gender']] ?? ''));
            $birthdate = $this->parseBirthdate((string) ($row[$columnIndexes['birthdate']] ?? ''));
            $classificationName = Str::squish((string) ($row[$columnIndexes['classification']] ?? ''));

            if (
                $firstName === ''
                || $lastName === ''
                || $team === ''
                || $gender === null
                || $birthdate === null
                || $classificationName === ''
                || Str::length($firstName) > 255
                || Str::length($lastName) > 255
                || Str::length($team) > 255
                || Str::length($classificationName) > 255
            ) {
                $skippedInvalid++;

                continue;
            }

            $nameKey = $this->nameKey($firstName, $lastName);

            if (isset($existingNames[$nameKey])) {
                $skippedDuplicates++;

                continue;
            }

            $classificationKey = Str::lower($classificationName);

            if (! isset($classificationsByName[$classificationKey])) {
                $classificationsByName[$classificationKey] = $competition->classifications()->create([
                    'name' => $classificationName,
                    'parent_id' => null,
                    'sort_order' => Classification::nextSortOrder($competition->id, null),
                ]);
                $classificationsCreated++;
            }

            $competition->participants()->create([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'gender' => $gender,
                'team' => $team,
                'birthdate' => $birthdate,
                'classification_id' => $classificationsByName[$classificationKey]->id,
                'paid' => false,
            ]);

            $existingNames[$nameKey] = true;
            $imported++;
        }

        return [
            'imported' => $imported,
            'skipped_duplicates' => $skippedDuplicates,
            'skipped_invalid' => $skippedInvalid,
            'classifications_created' => $classificationsCreated,
        ];
    }

    /**
     * @param  list<string|null>  $row
     * @param  array<string, int>  $columnIndexes
     */
    private function isEmptyRow(array $row, array $columnIndexes): bool
    {
        foreach ($columnIndexes as $index) {
            if (Str::squish((string) ($row[$index] ?? '')) !== '') {
                return false;
            }
        }

        return true;
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)
            ->replace("\u{FEFF}", '')
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }

    private function nameKey(string $firstName, string $lastName): string
    {
        return Str::lower(Str::squish($firstName)).'|'.Str::lower(Str::squish($lastName));
    }

    private function parseGender(string $value): ?ParticipantGender
    {
        return match (Str::lower(Str::squish($value))) {
            'm', 'male' => ParticipantGender::Male,
            'f', 'female' => ParticipantGender::Female,
            default => null,
        };
    }

    private function parseBirthdate(string $value): ?string
    {
        $value = trim($value);

        if ($value === '') {
            return null;
        }

        foreach (['m/d/Y', 'n/j/Y', 'Y-m-d'] as $format) {
            try {
                $date = Carbon::createFromFormat('!'.$format, $value);
            } catch (InvalidFormatException) {
                continue;
            }

            if ($date === false || $date->format($format) !== $value) {
                continue;
            }

            return $date->toDateString();
        }

        return null;
    }
}
