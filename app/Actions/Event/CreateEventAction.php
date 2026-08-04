<?php

namespace App\Actions\Event;

use App\Models\Event;

class CreateEventAction
{
    /**
     * @param  array{name: string, description?: string|null, date_start: string, date_end: string}  $data
     */
    public function handle(array $data): Event
    {
        return Event::query()->create($data);
    }
}
