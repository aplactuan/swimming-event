<?php

namespace App\Console\Commands;

use App\Models\Competition;
use App\Models\Participant;
use App\Services\ParticipantEventSync;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('participants:sync-events {competition : The competition UUID}')]
#[Description('Re-sync paid participants into matching events for a competition')]
class SyncCompetitionParticipantEventsCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(ParticipantEventSync $sync): int
    {
        $competitionId = (string) $this->argument('competition');
        $competition = Competition::query()->find($competitionId);

        if ($competition === null) {
            $this->error("Competition [{$competitionId}] was not found.");

            return self::FAILURE;
        }

        $participants = $competition->participants()->get();
        $paidSynced = 0;
        $unpaidCleared = 0;
        $entriesBefore = $competition->events()
            ->withCount('participants')
            ->get()
            ->sum('participants_count');

        foreach ($participants as $participant) {
            /** @var Participant $participant */
            if ($participant->paid) {
                $sync->syncForPaid($participant);
                $paidSynced++;

                continue;
            }

            if ($participant->events()->exists()) {
                $sync->clearEvents($participant);
                $unpaidCleared++;
            }
        }

        $entriesAfter = $competition->events()
            ->withCount('participants')
            ->get()
            ->sum('participants_count');

        $this->info("Synced {$paidSynced} paid participant(s) for [{$competition->name}].");

        if ($unpaidCleared > 0) {
            $this->comment("Cleared event entries for {$unpaidCleared} unpaid participant(s).");
        }

        $this->line("Event entries: {$entriesBefore} → {$entriesAfter}.");

        return self::SUCCESS;
    }
}
