<?php

namespace App\Http\Requests;

use App\Models\Heat;
use Illuminate\Contracts\Validation\ValidationRule;
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
                Rule::exists('heat_lanes', 'id')
                    ->where('heat_id', $heat->id)
                    ->whereNotNull('participant_id'),
            ],
            'lanes.*.finish_time' => [
                'nullable',
                'string',
                'regex:/^(?:\d{1,2}:[0-5]\d|\d{1,3})(?:\.\d{1,2})?$/',
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
                fn (mixed $lane): mixed => is_array($lane)
                    ? [...$lane, 'finish_time' => trim((string) ($lane['finish_time'] ?? '')) ?: null]
                    : $lane,
                $lanes,
            ),
        ]);
    }
}
