<?php

namespace App\Observers;

use App\Models\Participant;

class ParticipantObserver
{
    /**
     * Keep the participant's age in sync with their age on competition day.
     */
    public function saving(Participant $participant): void
    {
        if (! $participant->isDirty(['birthdate', 'competition_id'])) {
            return;
        }

        $participant->age = $participant->ageOnCompetitionDay();
       
    }
}
