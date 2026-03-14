<?php

namespace App\Livewire\Jeopardy;

use App\Events\Jeopardy\GameStateUpdated;
use App\Models\JeopardySession;
use Livewire\Attributes\On;
use Livewire\Component;

class GameLobby extends Component
{
    public string $code;
    public JeopardySession $session;

    public function mount(string $code): void
    {
        $this->code    = $code;
        $this->session = JeopardySession::where('code', $code)->with(['players.team', 'teams', 'board'])->firstOrFail();
    }

    /** Host clicks "Start Game" */
    public function startGame(): void
    {
        $this->session->update(['status' => 'active']);
        event(new GameStateUpdated($this->session->code, 'active'));
        $this->redirect(route('games.jeopardy.host', $this->code));
    }

    /** Refresh from Reverb broadcast (no-op if Reverb not installed; polling handles it) */
    #[On('echo:jeopardy.{code},GameStateUpdated')]
    public function onGameStateUpdated(): void
    {
        $this->session->refresh();
    }

    public function render()
    {
        $this->session->load(['players.team', 'teams']);
        return view('livewire.jeopardy.game-lobby');
    }
}
