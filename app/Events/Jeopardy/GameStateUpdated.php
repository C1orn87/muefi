<?php

namespace App\Events\Jeopardy;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GameStateUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $code,
        public string $status,
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel("jeopardy.{$this->code}");
    }

    public function broadcastAs(): string
    {
        return 'GameStateUpdated';
    }
}
