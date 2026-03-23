<?php

namespace App\Events\Jeopardy;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ChoiceVoted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $code,
        public int    $playerId,
        public int    $choiceIndex,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("jeopardy.{$this->code}");
    }

    public function broadcastAs(): string
    {
        return 'ChoiceVoted';
    }
}
