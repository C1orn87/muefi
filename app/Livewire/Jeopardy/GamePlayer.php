<?php

namespace App\Livewire\Jeopardy;

use App\Events\Jeopardy\BuzzerPressed;
use App\Events\Jeopardy\ChoiceVoted;
use App\Events\Jeopardy\ClickVoted;
use App\Events\Jeopardy\GameStateUpdated;
use App\Events\Jeopardy\GuessSubmitted;
use App\Livewire\Concerns\BroadcastsSafely;
use App\Models\JeopardyBuzz;
use App\Models\JeopardyChoiceVote;
use App\Models\JeopardyClickVote;
use App\Models\JeopardyGuess;
use App\Models\JeopardyPlayer;
use App\Models\JeopardySession;
use Livewire\Attributes\On;
use Livewire\Component;

class GamePlayer extends Component
{
    use BroadcastsSafely;
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

        $this->broadcast(new BuzzerPressed($this->session->code, $this->playerId, $order));
    }

    // ── Card selection (turn system) ──────────────────────────────────────────

    public function selectCard(int $questionId): void
    {
        $this->session->refresh();

        // Only the current-turn player can select, and only when no question is active
        if ($this->session->active_question_id) {
            return;
        }
        if ($this->session->current_turn_player_id !== $this->playerId) {
            return;
        }

        $revealedIds = $this->session->revealedQuestionIds();
        if (in_array($questionId, $revealedIds)) {
            return;
        }

        $this->session->update(['pending_question_id' => $questionId]);
        $this->session->refresh();

        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    public function selectRandomCard(): void
    {
        $this->session->refresh();

        if ($this->session->active_question_id) {
            return;
        }
        if ($this->session->current_turn_player_id !== $this->playerId) {
            return;
        }

        $this->session->load('board.categories.questions');
        $revealedIds    = $this->session->revealedQuestionIds();
        $allQuestions   = $this->session->board->categories->flatMap(fn ($c) => $c->questions);
        $available      = $allQuestions->filter(fn ($q) => ! in_array($q->id, $revealedIds));

        if ($available->isEmpty()) {
            return;
        }

        $random = $available->random();
        $this->session->update(['pending_question_id' => $random->id]);
        $this->session->refresh();

        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
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

        $this->broadcast(new GuessSubmitted($this->session->code, $this->playerId));
        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    // ── Multiple choice / duel vote ───────────────────────────────────────────

    public function submitChoice(int $choiceIndex): void
    {
        $this->session->refresh();

        if (! $this->session->active_question_id) {
            return;
        }

        JeopardyChoiceVote::updateOrCreate(
            [
                'session_id'  => $this->session->id,
                'question_id' => $this->session->active_question_id,
                'player_id'   => $this->playerId,
            ],
            [
                'choice_index' => $choiceIndex,
            ]
        );

        $this->broadcast(new ChoiceVoted($this->session->code, $this->playerId, $choiceIndex));
    }

    // ── Hotspot click ─────────────────────────────────────────────────────────

    public function submitHotspot(float $xPct, float $yPct): void
    {
        $this->session->refresh();

        if (! $this->session->active_question_id) {
            return;
        }

        // Clamp to 0–100
        $xPct = max(0.0, min(100.0, $xPct));
        $yPct = max(0.0, min(100.0, $yPct));

        JeopardyClickVote::updateOrCreate(
            [
                'session_id'  => $this->session->id,
                'question_id' => $this->session->active_question_id,
                'player_id'   => $this->playerId,
            ],
            [
                'x_pct' => $xPct,
                'y_pct' => $yPct,
            ]
        );

        $this->broadcast(new ClickVoted($this->session->code, $this->playerId, $xPct, $yPct));
    }

    // ── Reverb hooks ──────────────────────────────────────────────────────────

    #[On('echo:jeopardy.{code},QuestionRevealed')]
    #[On('echo:jeopardy.{code},ScoreUpdated')]
    #[On('echo:jeopardy.{code},GameStateUpdated')]
    #[On('echo:jeopardy.{code},ChoiceVoted')]
    #[On('echo:jeopardy.{code},ClickVoted')]
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
            'currentTurnPlayer',
            'pendingQuestion',
        ]);
        $this->player?->refresh();

        // Redirect kicked players to the join page
        if ($this->player?->is_kicked) {
            $this->redirect(route('games.jeopardy.join', $this->code));
            return view('livewire.jeopardy.game-player', ['kicked' => true]);
        }

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

        $isMyTurn          = $this->session->current_turn_player_id === $this->playerId;
        $currentTurnPlayer = $this->session->currentTurnPlayer;
        $pendingQuestionId = $this->session->pending_question_id;

        // Has this player voted on a choice/duel question?
        $myVote = $this->session->active_question_id
            ? JeopardyChoiceVote::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->where('player_id', $this->playerId)
                ->first()
            : null;

        $hasVoted    = $myVote !== null;
        $myVoteIndex = $myVote?->choice_index;

        // Hotspot click — has this player already placed a dot?
        $myHotspot = ($this->session->activeQuestion?->question_type === 'image_hotspot' && $this->session->active_question_id)
            ? JeopardyClickVote::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->where('player_id', $this->playerId)
                ->first()
            : null;
        $hasClickedHotspot = $myHotspot !== null;

        $kicked = false;

        return view('livewire.jeopardy.game-player', compact(
            'revealedIds', 'hasBuzzed', 'myBuzzOrder', 'hasGuessed', 'myGuess',
            'isMyTurn', 'currentTurnPlayer', 'pendingQuestionId',
            'hasVoted', 'myVoteIndex', 'hasClickedHotspot', 'myHotspot', 'kicked'
        ));
    }
}
