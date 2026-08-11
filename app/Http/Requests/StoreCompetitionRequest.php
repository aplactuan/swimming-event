<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCompetitionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'warm_up_time' => $this->filled('warm_up_time') ? $this->input('warm_up_time') : null,
            'coaches_meeting_time' => $this->filled('coaches_meeting_time') ? $this->input('coaches_meeting_time') : null,
        ]);
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
            'venue' => ['required', 'string', 'max:255'],
            'competition_date' => ['required', 'date'],
            'warm_up_time' => ['nullable', 'date_format:H:i'],
            'coaches_meeting_time' => ['nullable', 'date_format:H:i'],
            'registration_deadline' => ['required', 'date', 'before_or_equal:competition_date'],
            'entry_fee' => ['required', 'integer', 'min:0'],
        ];
    }
}
