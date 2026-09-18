<?php

namespace App\Http\Requests;

use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReorderClassificationsRequest extends FormRequest
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
            'parent_id' => $this->filled('parent_id') ? $this->input('parent_id') : null,
        ]);
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

        $parentRule = Rule::exists('classifications', 'id')
            ->where('competition_id', $competition->id)
            ->whereNull('parent_id');

        $idsRule = function () use ($competition) {
            $parentId = $this->input('parent_id');

            $rule = Rule::exists('classifications', 'id')
                ->where('competition_id', $competition->id);

            if ($parentId === null) {
                $rule->whereNull('parent_id');
            } else {
                $rule->where('parent_id', $parentId);
            }

            return $rule;
        };

        return [
            'parent_id' => ['nullable', 'uuid', $parentRule],
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['uuid', $idsRule()],
        ];
    }
}
