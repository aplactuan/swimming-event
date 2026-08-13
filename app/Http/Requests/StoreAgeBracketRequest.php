<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAgeBracketRequest extends FormRequest
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
            'start_birthday' => $this->filled('start_birthday') ? $this->input('start_birthday') : null,
            'end_birthday' => $this->filled('end_birthday') ? $this->input('end_birthday') : null,
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
            'start_birthday' => ['nullable', 'date'],
            'end_birthday' => [
                'nullable',
                'date',
                Rule::when($this->filled('start_birthday'), ['after_or_equal:start_birthday']),
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
                if (! $this->filled('start_birthday') && ! $this->filled('end_birthday')) {
                    $validator->errors()->add(
                        'start_birthday',
                        'A start birthday or end birthday is required.',
                    );
                }
            },
        ];
    }
}
