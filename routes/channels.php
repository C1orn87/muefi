<?php

use Illuminate\Support\Facades\Broadcast;

/*
 * All jeopardy game channels are public (no auth required to listen).
 * The host uses the authenticated web session to perform write actions.
 */
Broadcast::channel('jeopardy.{code}', function () {
    return true; // public channel — anyone with the code can listen
});
