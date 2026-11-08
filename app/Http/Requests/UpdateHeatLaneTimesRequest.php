<?php

namespace App\Http\Requests;

use App\Enums\HeatLaneStatus;
use App\Models\Heat;
use App\Models\HeatLane;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateHeatLaneTimesRequest extends FormRequest
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
        /** @var Heat $heat */
        $heat = $this->route('heat');

        return [
            'lanes' => ['present', 'array'],
            'lanes.*.id' => [
                'required',
                'uuid',
                Rule::exists('heat_lanes', 'id')->where('heat_id', $heat->id),
            ],
            'lanes.*.finish_time' => [
                'nullable',
                'string',
                'regex:/^(?:\d{1,2}:[0-5]\d|\d{1,3})(?:\.\d{1,2})?$/',
            ],
            'lanes.*.status' => [
                'required',
                Rule::enum(HeatLaneStatus::class),
            ],
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'lanes.*.finish_time.regex' => 'Enter the finish time as m:ss.hh, for example 1:02.45 or 38.20.',
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

                /** @var Heat $heat */
                $heat = $this->route('heat');
                $ids = collect($this->input('lanes', []))->pluck('id')->filter()->all();

                $assignedIds = HeatLane::query()
                    ->where('heat_id', $heat->id)
                    ->whereIn('id', $ids)
                    ->where(fn ($query) => $query
                        ->whereNotNull('participant_id')
                        ->orWhereNotNull('team_id'))
                    ->pluck('id')
                    ->all();

                foreach ($this->input('lanes', []) as $index => $lane) {
                    $id = $lane['id'] ?? null;

                    if (! is_string($id) || in_array($id, $assignedIds, true)) {
                        continue;
                    }

                    $validator->errors()->add(
                        "lanes.{$index}.id",
                        'The selected lane is empty.',
                    );
                }
            },
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $lanes = $this->input('lanes');

        if (! is_array($lanes)) {
            return;
        }

        $this->merge([
            'lanes' => array_map(
                function (mixed $lane): mixed {
                    if (! is_array($lane)) {
                        return $lane;
                    }

                    return [
                        ...$lane,
                        'finish_time' => trim((string) ($lane['finish_time'] ?? '')) ?: null,
                        'status' => $lane['status'] ?? HeatLaneStatus::Swam->value,
                    ];
                },
                $lanes,
            ),
        ]);
    }
}
