<?php

namespace App\Http\Requests;

use App\Models\Event;
use App\Models\Heat;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SwapHeatLanesRequest extends FormRequest
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
        /** @var Event $event */
        $event = $this->route('event');

        $laneInEvent = Rule::exists('heat_lanes', 'id')->where(
            fn (Builder $query) => $query->whereIn(
                'heat_id',
                Heat::query()->where('event_id', $event->id)->select('id'),
            ),
        );

        return [
            'from_lane_id' => ['required', 'uuid', $laneInEvent],
            'to_lane_id' => ['required', 'uuid', 'different:from_lane_id', $laneInEvent],
        ];
    }
}
