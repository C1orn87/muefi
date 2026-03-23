<?php

namespace App\Livewire\Jeopardy;

use App\Events\Jeopardy\GameStateUpdated;
use App\Livewire\Concerns\BroadcastsSafely;
use App\Models\JeopardyPlayer;
use App\Models\JeopardySession;
use Livewire\Attributes\On;
use Livewire\Component;

class GameLobby extends Component
{
    use BroadcastsSafely;

    public string $code;
    public JeopardySession $session;

    public function mount(string $code): void
    {
        $this->code    = $code;
        $this->session = JeopardySession::where('code', $code)
            ->with(['players.team', 'teams', 'board'])
            ->firstOrFail();
    }

    /** Host clicks "Start Game" */
    public function startGame(): void
    {
        $this->session->update(['status' => 'active']);
        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
        $this->redirect(route('games.jeopardy.host', $this->code));
    }

    /** Host kicks a player from the lobby */
    public function kickPlayer(int $playerId): void
    {
        if (auth()->id() !== $this->session->host_id) {
            return;
        }

        JeopardyPlayer::where('id', $playerId)
            ->where('session_id', $this->session->id)
            ->update(['is_kicked' => true]);

        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    /** WebSocket: game state changed — redirect non-host players if game went live */
    #[On('echo:jeopardy.{code},GameStateUpdated')]
    public function onGameStateUpdated(): void
    {
        $this->session->refresh();

        if ($this->session->status === 'active' && auth()->id() !== $this->session->host_id) {
            $this->redirect(route('games.jeopardy.play', $this->code));
        }
    }

    public function render()
    {
        $this->session->load(['players.team', 'teams']);

        // Poll fallback: redirect non-host players once game goes active
        if ($this->session->status === 'active' && auth()->id() !== $this->session->host_id) {
            $this->redirect(route('games.jeopardy.play', $this->code));
            return view('livewire.jeopardy.game-lobby');
        }

        return view('livewire.jeopardy.game-lobby');
    }
}
