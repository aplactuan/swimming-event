<?php

namespace App\Http\Requests;

use App\Enums\ParticipantGender;
use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreParticipantRequest extends FormRequest
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
        /** @var Competition $competition */
        $competition = $this->route('competition');

        return [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', Rule::enum(ParticipantGender::class)],
            'team_id' => [
                'required',
                'uuid',
                Rule::exists('teams', 'id')->where('competition_id', $competition->id),
            ],
            'birthdate' => ['required', 'date'],
            'classification_id' => [
                'required',
                'uuid',
                Rule::exists('classifications', 'id')->where('competition_id', $competition->id),
            ],
            'paid' => ['sometimes', 'boolean'],
            'seed_time' => [
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
            'seed_time.regex' => 'Enter the seed time as m:ss.hh, for example 1:02.45 or 38.20.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('paid')) {
            $this->merge([
                'paid' => filter_var($this->input('paid'), FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false,
            ]);
        }

        if ($this->exists('seed_time')) {
            $this->merge([
                'seed_time' => trim((string) $this->input('seed_time')) ?: null,
            ]);
        }
    }
}
