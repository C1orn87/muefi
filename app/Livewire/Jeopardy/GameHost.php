<?php

namespace App\Livewire\Jeopardy;

use App\Events\Jeopardy\GameStateUpdated;
use App\Events\Jeopardy\QuestionRevealed;
use App\Events\Jeopardy\ScoreUpdated;
use App\Livewire\Concerns\BroadcastsSafely;
use App\Models\JeopardyBuzz;
use App\Models\JeopardyChoiceVote;
use App\Models\JeopardyClickVote;
use App\Models\JeopardyPlayer;
use App\Models\JeopardySession;
use App\Models\JeopardySessionQuestion;
use Livewire\Attributes\On;
use Livewire\Component;

class GameHost extends Component
{
    use BroadcastsSafely;
    public string $code;
    public JeopardySession $session;

    public bool $showAnswer = false;

    // Exposed as a plain int so Alpine can @entangle and watch for changes
    public ?int $activeQuestionId = null;

    public function mount(string $code): void
    {
        $this->code             = $code;
        $this->session          = JeopardySession::where('code', $code)
            ->with(['board.categories.questions', 'players.team', 'teams.players', 'sessionQuestions', 'activeQuestion'])
            ->firstOrFail();
        $this->activeQuestionId = $this->session->active_question_id;
        $this->showAnswer       = (bool) ($this->session->show_answer ?? false);
    }

    // ── Board ──────────────────────────────────────────────────────────────────

    public function selectQuestion(int $questionId): void
    {
        JeopardySessionQuestion::updateOrCreate(
            ['session_id' => $this->session->id, 'question_id' => $questionId],
            ['is_revealed' => true, 'pixelate_level' => 1]
        );

        // Clear buzzes, choice votes, and hotspot clicks for the fresh question
        $this->session->buzzes()->where('question_id', $questionId)->delete();
        JeopardyChoiceVote::where('session_id', $this->session->id)
            ->where('question_id', $questionId)->delete();
        JeopardyClickVote::where('session_id', $this->session->id)
            ->where('question_id', $questionId)->delete();

        $this->session->update([
            'active_question_id'  => $questionId,
            'show_answer'         => false,
            'buzzer_open'         => false,
            'question_opened_at'  => now(),
            'revealed_hint_count' => 0,
        ]);

        $this->session->refresh();
        $this->activeQuestionId = $questionId;
        $this->showAnswer       = false;

        $this->broadcast(new QuestionRevealed($this->session->code, $questionId));
    }

    public function closeQuestion(): void
    {
        $this->session->update([
            'active_question_id'  => null,
            'show_answer'         => false,
            'buzzer_open'         => false,
            'question_opened_at'  => null,
            'pending_question_id' => null,
            'revealed_hint_count' => 0,
        ]);
        $this->session->refresh();
        $this->activeQuestionId = null;
        $this->showAnswer       = false;

        // Auto-advance to the next player's turn
        $this->advanceTurn();

        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    // ── Turn management ────────────────────────────────────────────────────────

    /** Cycle to the next player in join order. */
    public function advanceTurn(): void
    {
        $players = $this->session->players->sortBy('id')->values();

        if ($players->isEmpty()) {
            return;
        }

        $currentId    = $this->session->current_turn_player_id;
        $currentIndex = $players->search(fn ($p) => $p->id === $currentId);
        $nextIndex    = ($currentIndex === false) ? 0 : ($currentIndex + 1) % $players->count();
        $nextPlayer   = $players[$nextIndex];

        $this->session->update([
            'current_turn_player_id' => $nextPlayer->id,
            'pending_question_id'    => null,
        ]);
        $this->session->refresh();

        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    /** Host manually sets whose turn it is. */
    public function setTurn(int $playerId): void
    {
        $this->session->update([
            'current_turn_player_id' => $playerId,
            'pending_question_id'    => null,
        ]);
        $this->session->refresh();

        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    // ── Hints ──────────────────────────────────────────────────────────────────

    /** Reveal the next hint to players. */
    public function showNextHint(): void
    {
        $q = $this->session->activeQuestion;
        if (! $q) {
            return;
        }

        $hints = $q->hints ?? [];
        if ($this->session->revealed_hint_count < count($hints)) {
            $this->session->increment('revealed_hint_count');
            $this->session->refresh();
            $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
        }
    }

    // ── Pixelate reveal ────────────────────────────────────────────────────────

    /** Step the pixelate_image one level clearer (1 = most pixelated, 8 = full). */
    public function pixelateReveal(): void
    {
        if (! $this->session->active_question_id) {
            return;
        }

        $sq = JeopardySessionQuestion::where('session_id', $this->session->id)
            ->where('question_id', $this->session->active_question_id)
            ->first();

        if ($sq && $sq->pixelate_level < 6) { // 6 steps: 5→20→80→320→1280px (×4 each)
            $sq->update(['pixelate_level' => $sq->pixelate_level + 1]);
        }

        $this->session->refresh();
        $this->broadcast(new QuestionRevealed($this->session->code, $this->session->active_question_id));
    }

    // ── Answer reveal ──────────────────────────────────────────────────────────

    public function toggleAnswer(): void
    {
        $this->showAnswer = ! $this->showAnswer;
        $this->session->update(['show_answer' => $this->showAnswer]);
        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    // ── Zoom ──────────────────────────────────────────────────────────────────

    public function zoomIn(): void
    {
        if (! $this->session->active_question_id) {
            return;
        }
        $sq = JeopardySessionQuestion::where('session_id', $this->session->id)
            ->where('question_id', $this->session->active_question_id)
            ->first();
        if ($sq && $sq->zoom_level > 1) {
            $sq->update(['zoom_level' => $sq->zoom_level - 1]);
        }
        $this->session->refresh();
        $this->broadcast(new QuestionRevealed($this->session->code, $this->session->active_question_id));
    }

    // ── Buzzer ────────────────────────────────────────────────────────────────

    /** Called by Alpine setTimeout after the configured delay */
    public function openBuzzer(): void
    {
        if (! $this->session->active_question_id) {
            return;
        }
        $this->session->update(['buzzer_open' => true]);
        $this->session->refresh();
        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    /** Host manually reopens buzzer — clears existing buzzes for a fresh round */
    public function reopenBuzzer(): void
    {
        if (! $this->session->active_question_id) {
            return;
        }
        $this->session->buzzes()
            ->where('question_id', $this->session->active_question_id)
            ->delete();
        $this->session->update(['buzzer_open' => true]);
        $this->session->refresh();
        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    // ── Scoring ───────────────────────────────────────────────────────────────

    public function setPercentage(int $pct): void
    {
        $this->session->update(['point_percentage' => $pct]);
        $this->session->refresh();
    }

    public function adjustPlayerScore(int $playerId, string $type): void
    {
        $player   = $this->session->players->find($playerId);
        $question = $this->session->activeQuestion;

        if (! $player || ! $question) {
            return;
        }

        $amount = (int) round($question->points * ($this->session->point_percentage / 100));
        $delta  = $type === '+' ? $amount : -$amount;

        $player->update(['score' => $player->score + $delta]);

        if ($player->team_id) {
            $team = $this->session->teams->find($player->team_id);
            $team?->update(['score' => $team->score + $delta]);
        }

        $this->session->refresh();
        $this->broadcast(new ScoreUpdated($this->session->code));
    }

    // ── Game end ──────────────────────────────────────────────────────────────

    public function endGame(): void
    {
        $this->session->update([
            'status'             => 'finished',
            'active_question_id' => null,
            'buzzer_open'        => false,
            'show_answer'        => false,
        ]);
        $this->session->refresh();
        $this->activeQuestionId = null;
        $this->broadcast(new GameStateUpdated($this->session->code, 'finished'));
    }

    // ── Player management ─────────────────────────────────────────────────────

    public function kickPlayer(int $playerId): void
    {
        JeopardyPlayer::where('id', $playerId)
            ->where('session_id', $this->session->id)
            ->update(['is_kicked' => true]);

        $this->broadcast(new GameStateUpdated($this->session->code, 'active'));
    }

    // ── Reverb hooks ──────────────────────────────────────────────────────────

    #[On('echo:jeopardy.{code},BuzzerPressed')]
    #[On('echo:jeopardy.{code},GameStateUpdated')]
    #[On('echo:jeopardy.{code},QuestionRevealed')]
    #[On('echo:jeopardy.{code},ScoreUpdated')]
    #[On('echo:jeopardy.{code},ChoiceVoted')]
    #[On('echo:jeopardy.{code},GuessSubmitted')]
    #[On('echo:jeopardy.{code},ClickVoted')]
    public function refresh(): void
    {
        $this->session->refresh();
        $this->activeQuestionId = $this->session->active_question_id;
        $this->showAnswer       = (bool) ($this->session->show_answer ?? false);
    }

    // ── Render ────────────────────────────────────────────────────────────────

    public function render()
    {
        $this->session->load([
            'board.categories.questions',
            'players.team',
            'teams.players',
            'sessionQuestions',
            'activeQuestion',
            'currentTurnPlayer',
            'pendingQuestion',
        ]);

        $revealedIds = $this->session->revealedQuestionIds();

        $buzzes = $this->session->active_question_id
            ? JeopardyBuzz::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->with('player')
                ->orderBy('buzz_order')
                ->get()
            : collect();

        $guesses = ($this->session->activeQuestion?->question_type === 'number_guess')
            ? $this->session->guesses()
                ->where('question_id', $this->session->active_question_id)
                ->with('player')
                ->get()
                ->sortBy(fn ($g) => abs((float) $g->guess - (float) ($this->session->activeQuestion->answer_text ?? 0)))
                ->values()
            : collect();

        // Vote tallies for multiple_choice and duel
        $votesByChoice = collect();
        $totalVotes    = 0;
        $playerVotes   = collect(); // keyed by player_id => choice_index

        $aqType    = $this->session->activeQuestion?->question_type;
        $aqChoices = $this->session->activeQuestion?->choices;
        $needsVotes = $this->session->active_question_id
            && ($aqType === 'duel' || (!empty($aqChoices) && $aqType !== 'duel'));

        if ($needsVotes) {
            $allVotes = JeopardyChoiceVote::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->with('player')
                ->get();

            $totalVotes    = $allVotes->count();
            $votesByChoice = $allVotes->groupBy('choice_index')
                ->map(fn ($group) => $group->values());
            $playerVotes   = $allVotes->keyBy('player_id');
        }

        // Click vote heatmap for image_hotspot questions
        $clickVotes = ($this->session->activeQuestion?->question_type === 'image_hotspot')
            ? JeopardyClickVote::where('session_id', $this->session->id)
                ->where('question_id', $this->session->active_question_id)
                ->with('player')
                ->get()
            : collect();

        $pendingQuestion   = $this->session->pendingQuestion;
        $currentTurnPlayer = $this->session->currentTurnPlayer;

        return view('livewire.jeopardy.game-host', compact(
            'revealedIds', 'buzzes', 'guesses', 'pendingQuestion', 'currentTurnPlayer',
            'votesByChoice', 'totalVotes', 'playerVotes', 'clickVotes'
        ));
    }
}
