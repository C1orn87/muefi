<?php

namespace App\Events\Jeopardy;

use Illuminate\Broadcasting\Channel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;

class ClickVoted implements ShouldBroadcastNow
{
    public function __construct(
        public readonly string $code,
        public readonly int    $playerId,
        public readonly float  $xPct,
        public readonly float  $yPct,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("jeopardy.{$this->code}");
    }

    public function broadcastAs(): string
    {
        return 'ClickVoted';
    }
}
