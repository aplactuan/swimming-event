<?php

namespace App\Http\Requests;

use App\Enums\EventGender;
use App\Enums\ProgramSortColumn;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GenerateEventProgramRequest extends FormRequest
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
            'columns' => ['required', 'array', 'size:4'],
            'columns.*' => [
                'required',
                'string',
                'distinct:strict',
                Rule::enum(ProgramSortColumn::class),
            ],
            'gender_order' => ['required', 'array', 'size:3'],
            'gender_order.*' => [
                'required',
                'string',
                'distinct:strict',
                Rule::enum(EventGender::class),
            ],
            'name_order' => ['required', 'array', 'min:1', 'max:500'],
            'name_order.*' => ['required', 'string', 'max:255', 'distinct:strict'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'columns.size' => 'Every sort column must be filled in exactly once.',
            'columns.*.distinct' => 'Each sort column must be used only once.',
            'gender_order.size' => 'All three genders must be ordered.',
            'gender_order.*.distinct' => 'Each gender must be listed only once.',
            'name_order.*.distinct' => 'Each event name must be listed only once.',
        ];
    }
}
