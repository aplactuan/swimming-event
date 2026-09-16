<?php

namespace App\Http\Requests;

use App\Enums\EventGender;
use App\Models\AgeBracket;
use App\Models\Classification;
use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateEventsRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'genders' => ['required', 'array', 'min:1', 'max:3'],
            'genders.*' => ['required', 'string', 'distinct:strict', Rule::enum(EventGender::class)],
            'eligibilities' => ['required', 'array', 'min:1', 'max:100'],
            'eligibilities.*' => ['required', 'array:classification_id,age_bracket_id'],
            'eligibilities.*.classification_id' => [
                'required',
                'uuid',
            ],
            'eligibilities.*.age_bracket_id' => [
                'required',
                'uuid',
            ],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Competition $competition */
                $competition = $this->route('competition');
                $eligibilities = $this->input('eligibilities', []);
                $classificationIds = collect($eligibilities)
                    ->pluck('classification_id')
                    ->filter(fn (mixed $id): bool => is_string($id))
                    ->unique()
                    ->values();
                $classifications = Classification::query()
                    ->with(['ageBrackets', 'parent.ageBrackets'])
                    ->where('competition_id', $competition->id)
                    ->whereKey($classificationIds)
                    ->get()
                    ->keyBy('id');
                $seen = [];

                foreach ($eligibilities as $index => $row) {
                    $classificationId = $row['classification_id'];
                    $ageBracketId = $row['age_bracket_id'];
                    $pairKey = $classificationId.'|'.$ageBracketId;

                    if (isset($seen[$pairKey])) {
                        $validator->errors()->add(
                            "eligibilities.{$index}.age_bracket_id",
                            'Duplicate eligibility pair.',
                        );

                        continue;
                    }

                    $seen[$pairKey] = true;
                    $classification = $classifications->get($classificationId);

                    if ($classification === null) {
                        $validator->errors()->add(
                            "eligibilities.{$index}.classification_id",
                            'The selected classification is invalid.',
                        );

                        continue;
                    }

                    $isEffective = $classification->effectiveAgeBrackets()
                        ->contains(fn (AgeBracket $bracket): bool => $bracket->id === $ageBracketId);

                    if (! $isEffective) {
                        $validator->errors()->add(
                            "eligibilities.{$index}.age_bracket_id",
                            'The selected age bracket is not valid for this classification.',
                        );
                    }
                }
            },
        ];
    }
}
