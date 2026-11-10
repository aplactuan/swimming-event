<?php

namespace App\Http\Requests;

use App\Models\Classification;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CopyAgeBracketRequest extends FormRequest
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
        /** @var Classification $classification */
        $classification = $this->route('classification');

        return [
            'classification_ids' => ['required', 'array', 'min:1'],
            'classification_ids.*' => [
                'required',
                'uuid',
                'distinct',
                Rule::exists('classifications', 'id')->where(
                    fn ($query) => $query
                        ->where('competition_id', $classification->competition_id)
                        ->where('id', '!=', $classification->id),
                ),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'classification_ids.required' => 'Select at least one classification to copy to.',
            'classification_ids.min' => 'Select at least one classification to copy to.',
        ];
    }
}
