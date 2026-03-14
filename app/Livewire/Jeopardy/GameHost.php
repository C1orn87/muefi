<?php

namespace App\Livewire\Jeopardy;

use App\Events\Jeopardy\GameStateUpdated;
use App\Events\Jeopardy\QuestionRevealed;
use App\Events\Jeopardy\ScoreUpdated;
use App\Models\JeopardyBuzz;
use App\Models\JeopardySession;
use App\Models\JeopardySessionQuestion;
use Livewire\Component;

class GameHost extends Component
{
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
            ['is_revealed' => true]
        );

        // Clear buzzes and reset all flags for the fresh question
        $this->session->buzzes()->where('question_id', $questionId)->delete();

        $this->session->update([
            'active_question_id' => $questionId,
            'show_answer'        => false,
            'buzzer_open'        => false,
            'question_opened_at' => now(),
        ]);

        $this->session->refresh();
        $this->activeQuestionId = $questionId;
        $this->showAnswer       = false;

        event(new QuestionRevealed($this->session->code, $questionId));
    }

    public function closeQuestion(): void
    {
        $this->session->update([
            'active_question_id' => null,
            'show_answer'        => false,
            'buzzer_open'        => false,
            'question_opened_at' => null,
        ]);
        $this->session->refresh();
        $this->activeQuestionId = null;
        $this->showAnswer       = false;

        event(new GameStateUpdated($this->session->code, 'active'));
    }

    // ── Answer reveal ──────────────────────────────────────────────────────────

    public function toggleAnswer(): void
    {
        $this->showAnswer = ! $this->showAnswer;
        $this->session->update(['show_answer' => $this->showAnswer]);
        event(new GameStateUpdated($this->session->code, 'active'));
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
        event(new QuestionRevealed($this->session->code, $this->session->active_question_id));
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
        event(new GameStateUpdated($this->session->code, 'active'));
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
        event(new GameStateUpdated($this->session->code, 'active'));
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
        event(new ScoreUpdated($this->session->code));
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
        event(new GameStateUpdated($this->session->code, 'finished'));
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

        return view('livewire.jeopardy.game-host', compact('revealedIds', 'buzzes', 'guesses'));
    }
}
