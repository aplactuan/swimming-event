<?php

namespace App\Http\Requests;

use App\Models\Competition;
use App\Models\Event;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventParticipantRequest extends FormRequest
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

        /** @var Event $event */
        $event = $this->route('event');

        return [
            'participant_id' => [
                'required',
                'uuid',
                Rule::exists('participants', 'id')
                    ->where('competition_id', $competition->id)
                    ->where('paid', true),
                Rule::unique('event_participant', 'participant_id')
                    ->where('event_id', $event->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'participant_id.exists' => 'Only paid participants from this competition can be added.',
            'participant_id.unique' => 'This participant is already entered in the event.',
        ];
    }
}
