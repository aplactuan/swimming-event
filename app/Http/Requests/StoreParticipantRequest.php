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
            'team' => ['required', 'string', 'max:255'],
            'birthdate' => ['required', 'date'],
            'classification_id' => [
                'required',
                'uuid',
                Rule::exists('classifications', 'id')->where('competition_id', $competition->id),
            ],
            'paid' => ['sometimes', 'boolean'],
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
    }
}
