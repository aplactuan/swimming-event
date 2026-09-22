<?php

namespace App\Http\Requests;

use App\Models\Competition;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class CloseCompetitionRequest extends FormRequest
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
        return [];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                /** @var Competition $competition */
                $competition = $this->route('competition');

                if ($competition->isClosed()) {
                    $validator->errors()->add('is_close', 'This competition is already closed.');

                    return;
                }

                if ($competition->events()->doesntExist()) {
                    $validator->errors()->add(
                        'is_close',
                        'Add at least one event before closing the competition.',
                    );

                    return;
                }

                $missingHeats = $competition->countEventsMissingHeats();

                if ($missingHeats > 0) {
                    $validator->errors()->add('is_close', sprintf(
                        '%d %s still %s heats and lanes. Generate heats for every event before closing.',
                        $missingHeats,
                        $missingHeats === 1 ? 'event' : 'events',
                        $missingHeats === 1 ? 'needs' : 'need',
                    ));
                }
            },
        ];
    }
}
