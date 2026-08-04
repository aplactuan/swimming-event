<?php

namespace App\Actions\Event;

use App\Models\Event;

class DeleteEventAction
{
    public function handle(Event $event): void
    {
        $event->delete();
    }
}
