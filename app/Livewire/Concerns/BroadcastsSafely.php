<?php

namespace App\Livewire\Concerns;

use Illuminate\Broadcasting\BroadcastException;

/**
 * Wraps every broadcast event in a try/catch so that a missing
 * Reverb / Pusher connection never crashes the Livewire action.
 * The game state is still saved to the DB; players just won't
 * receive a push until Reverb is running.
 */
trait BroadcastsSafely
{
    protected function broadcast(object $event): void
    {
        try {
            event($event);
        } catch (BroadcastException $e) {
            logger()->warning('[Broadcast] Reverb not reachable — ' . $e->getMessage());
        }
    }
}
