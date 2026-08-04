<?php

namespace App\Http\Requests\Api\Event;

use App\Models\Event;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var Event $event */
        $event = $this->route('event');

        return $this->user()?->can('update', $event) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['sometimes', 'nullable', 'string'],
            'date_start' => ['sometimes', 'required', 'date'],
            'date_end' => ['sometimes', 'required', 'date'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                /** @var Event $event */
                $event = $this->route('event');

                $dateStart = $this->input('date_start', $event->date_start->toDateString());
                $dateEnd = $this->input('date_end', $event->date_end->toDateString());

                if ($dateEnd < $dateStart) {
                    $validator->errors()->add(
                        'date_end',
                        __('validation.after_or_equal', [
                            'attribute' => 'date end',
                            'date' => 'date start',
                        ]),
                    );
                }
            },
        ];
    }
}
