<?php

namespace App\Actions\Event;

use App\Models\Event;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ListEventsAction
{
    /**
     * @return LengthAwarePaginator<int, Event>
     */
    public function handle(int $perPage = 15): LengthAwarePaginator
    {
        return Event::query()
            ->orderBy('date_start')
            ->orderBy('id')
            ->paginate($perPage);
    }
}
