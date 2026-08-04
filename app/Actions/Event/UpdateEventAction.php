<?php

namespace App\Actions\Event;

use App\Models\Event;

class UpdateEventAction
{
    /**
     * @param  array{name?: string, description?: string|null, date_start?: string, date_end?: string}  $data
     */
    public function handle(Event $event, array $data): Event
    {
        $event->update($data);

        return $event->refresh();
    }
}
