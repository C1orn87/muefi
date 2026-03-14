<?php

namespace App\Livewire\Jeopardy;

use App\Models\JeopardyBuzz;
use App\Models\JeopardyGuess;
use App\Models\JeopardyPlayer;
use App\Models\JeopardySession;
use Livewire\Attributes\On;
use Livewire\Component;

class GamePlayer extends Component
{
    public string $code;
    public int    $playerId;
    public JeopardySession $session;
    public ?JeopardyPlayer $player = null;

    // Number-guess input value
    public string $guessInput = '';

    public function mount(string $code, int $playerId): void
    {
        $this->code     = $code;
        $this->playerId = $playerId;
        $this->session  = JeopardySession::where('code', $code)
            ->with(['board.categories.questions', 'players.team', 'teams', 'sessionQuestions', 'activeQuestion'])
            ->firstOrFail();
        $this->player   = JeopardyPlayer::find($playerId);
    }

    // ── Buzzer ────────────────────────────────────────────────────────────────

    public function buzz(): void
    {
        $this->session->refresh();

        if (! $this->session->buzzer_open || ! $this->session->active_question_id) {
            return;
        }

        // Check player hasn't already buzzed
        $alreadyBuzzed = JeopardyBuzz::where('session_id', $this->session->id)
            ->where('question_id', $this->session->active_question_id)
            ->where('player_id', $this->playerId)
            ->exists();

        if ($alreadyBuzzed) {
            return;
        }

        $order = JeopardyBuzz::where('session_id', $this->session->id)
            ->where('question_id', $this->session->active_question_id)
            ->count() + 1;

        JeopardyBuzz::create([
            'session_id'  => $this->session->id,
            'question_id' => $this->session->active_question_id,
            'player_id'   => $this->playerId,
            'buzz_order'  => $order,
            'buzzed_at'   => now(),
        ]);
    }

    // ── Number guess ──────────────────────────────────────────────────────────

    public function submitGuess(): void
    {
        $this->session->refresh();

        if (! $this->session->active_question_id) {
            return;
        }

        $this->validate(['guessInput' => 'required|numeric']);

        JeopardyGuess::updateOrCreate(
            [
                'session_id'  => $this->session->id,
                'question_id' => $this->session->active_question_id,
                'player_id'   => $this->playerId,
            ],
            [
                'guess'        => (float) $this->guessInput,
                'submitted_at' => now(),
            ]
        );
    }

    // ── Reverb hooks ──────────────────────────────────────────────────────────

    #[On('echo:jeopardy.{code},QuestionRevealed')]
    #[On('echo:jeopardy.{code},ScoreUpdated')]
    #[On('echo:jeopardy.{code},GameStateUpdated')]
    public function refresh(): void
    {
        $this->session->refresh();
        $this->player?->refresh();
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $this->session->load([
            'board.categories.questions',
            'players.team',
            'teams',
            'sessionQuestions',
            'activeQuestion',
        ]);
        $this->player?->refresh();

        $revealedIds = $this->session->revealedQuestionIds();

        // Has this player already buzzed for the active question?
        $hasBuzzed = $this->session->active_question_id
            ? JeopardyBuzz::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->where('player_id', $this->playerId)
                ->exists()
            : false;

        $myBuzzOrder = $hasBuzzed
            ? JeopardyBuzz::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->where('player_id', $this->playerId)
                ->value('buzz_order')
            : null;

        // Has this player already submitted a number guess?
        $hasGuessed = $this->session->active_question_id
            ? JeopardyGuess::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->where('player_id', $this->playerId)
                ->exists()
            : false;

        $myGuess = $hasGuessed
            ? JeopardyGuess::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->where('player_id', $this->playerId)
                ->value('guess')
            : null;

        return view('livewire.jeopardy.game-player', compact(
            'revealedIds', 'hasBuzzed', 'myBuzzOrder', 'hasGuessed', 'myGuess'
        ));
    }
}
