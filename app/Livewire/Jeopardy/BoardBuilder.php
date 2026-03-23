<?php

namespace App\Livewire\Jeopardy;

use App\Models\JeopardyBoard;
use App\Models\JeopardyCategory;
use App\Models\JeopardyQuestion;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class BoardBuilder extends Component
{
    use WithFileUploads;

    // ── Board meta ──────────────────────────────────────────────────────────────
    public ?int   $boardId     = null;
    public string $name        = '';
    public string $description = '';
    public bool   $isPublic    = true;

    /**
     * Nested array of categories → questions.
     *
     * Each question has:
     *   points, question_text, answer_text, question_type,
     *   media_url, media_file (temp upload), media_path (saved),
     *   hints_enabled, hints[],
     *   choices_enabled, choices[], correct_choice (int|null),
     *   duel_files[], duel_paths[], duel_captions[]
     */
    public array $categories = [];

    protected array $defaultPoints = [200, 400, 600, 800, 1000];

    // ── Mount ───────────────────────────────────────────────────────────────────

    public function mount(?JeopardyBoard $board = null): void
    {
        if ($board && $board->exists) {
            $this->boardId     = $board->id;
            $this->name        = $board->name;
            $this->description = $board->description ?? '';
            $this->isPublic    = $board->is_public;

            foreach ($board->categories as $cat) {
                $questions = [];
                foreach ($cat->questions as $q) {
                    $hints      = $q->hints      ?? [];
                    $dbChoices  = $q->choices     ?? [];
                    $dbPaths    = $q->media_paths ?? [];

                    // Migrate legacy 'multiple_choice' type → 'text' + choices_enabled
                    $validTypes = ['text','image','zoom_image','pixelate_image','audio','video','youtube','number_guess','duel','image_hotspot'];
                    $qType      = in_array($q->question_type, $validTypes) ? $q->question_type : 'text';
                    $isDuel     = $qType === 'duel';

                    // If it was multiple_choice OR it has saved choices, treat as choices-enabled
                    $wasMultipleChoice = $q->question_type === 'multiple_choice';
                    $hasChoices = (! empty($dbChoices) || $wasMultipleChoice) && ! $isDuel;

                    $questions[] = [
                        'points'          => $q->points,
                        'question_text'   => $q->question_text ?? '',
                        'answer_text'     => $hasChoices ? '' : ($q->answer_text ?? ''),
                        'question_type'   => $qType,
                        'media_url'       => $q->media_url  ?? '',
                        'media_file'      => null,
                        'media_path'      => $q->media_path ?? '',
                        // hints
                        'hints_enabled'   => ! empty($hints),
                        'hints'           => $hints ?: [],
                        // multiple-choice toggle
                        'choices_enabled' => $hasChoices,
                        'choices'         => $hasChoices ? $dbChoices : ['', ''],
                        'correct_choice'  => $hasChoices && is_numeric($q->answer_text)
                                                ? (int) $q->answer_text
                                                : null,
                        // duel
                        'duel_files'      => array_fill(0, max(2, count($dbPaths)), null),
                        'duel_paths'      => $isDuel ? ($dbPaths ?: ['', '']) : ['', ''],
                        'duel_captions'   => $isDuel ? ($dbChoices ?: ['', '']) : ['', ''],
                    ];
                }
                $this->categories[] = ['name' => $cat->name, 'questions' => $questions];
            }
        }

        if (empty($this->categories)) {
            $this->addCategory();
        }
    }

    // ── Category management ─────────────────────────────────────────────────────

    public function addCategory(): void
    {
        $questions = [];
        foreach ($this->defaultPoints as $pts) {
            $questions[] = $this->blankQuestion($pts);
        }
        $this->categories[] = ['name' => '', 'questions' => $questions];
    }

    public function removeCategory(int $catIdx): void
    {
        array_splice($this->categories, $catIdx, 1);
    }

    // ── Question management ─────────────────────────────────────────────────────

    public function addQuestion(int $catIdx): void
    {
        $nextPoints = count($this->categories[$catIdx]['questions']) * 200 + 200;
        $this->categories[$catIdx]['questions'][] = $this->blankQuestion($nextPoints);
    }

    public function removeQuestion(int $catIdx, int $qIdx): void
    {
        array_splice($this->categories[$catIdx]['questions'], $qIdx, 1);
    }

    // ── Hint management ─────────────────────────────────────────────────────────

    public function addHint(int $catIdx, int $qIdx): void
    {
        $this->categories[$catIdx]['questions'][$qIdx]['hints'][] = '';
    }

    public function removeHint(int $catIdx, int $qIdx, int $hintIdx): void
    {
        array_splice($this->categories[$catIdx]['questions'][$qIdx]['hints'], $hintIdx, 1);
    }

    // ── Multiple-choice management ──────────────────────────────────────────────

    public function addChoice(int $catIdx, int $qIdx): void
    {
        if (count($this->categories[$catIdx]['questions'][$qIdx]['choices']) < 6) {
            $this->categories[$catIdx]['questions'][$qIdx]['choices'][] = '';
        }
    }

    public function removeChoice(int $catIdx, int $qIdx, int $choiceIdx): void
    {
        array_splice($this->categories[$catIdx]['questions'][$qIdx]['choices'], $choiceIdx, 1);

        $correct = $this->categories[$catIdx]['questions'][$qIdx]['correct_choice'];
        if ($correct !== null) {
            if ($correct === $choiceIdx) {
                $this->categories[$catIdx]['questions'][$qIdx]['correct_choice'] = null;
            } elseif ($correct > $choiceIdx) {
                $this->categories[$catIdx]['questions'][$qIdx]['correct_choice'] = $correct - 1;
            }
        }
    }

    // ── Duel image slot management ──────────────────────────────────────────────

    public function addDuelSlot(int $catIdx, int $qIdx): void
    {
        if (count($this->categories[$catIdx]['questions'][$qIdx]['duel_paths']) < 4) {
            $this->categories[$catIdx]['questions'][$qIdx]['duel_files'][]    = null;
            $this->categories[$catIdx]['questions'][$qIdx]['duel_paths'][]    = '';
            $this->categories[$catIdx]['questions'][$qIdx]['duel_captions'][] = '';
        }
    }

    public function removeDuelSlot(int $catIdx, int $qIdx, int $slotIdx): void
    {
        array_splice($this->categories[$catIdx]['questions'][$qIdx]['duel_files'],    $slotIdx, 1);
        array_splice($this->categories[$catIdx]['questions'][$qIdx]['duel_paths'],    $slotIdx, 1);
        array_splice($this->categories[$catIdx]['questions'][$qIdx]['duel_captions'], $slotIdx, 1);
    }

    // ── Blank question template ─────────────────────────────────────────────────

    protected function blankQuestion(int $points): array
    {
        return [
            'points'          => $points,
            'question_text'   => '',
            'answer_text'     => '',
            'question_type'   => 'text',
            'media_url'       => '',
            'media_file'      => null,
            'media_path'      => '',
            'hints_enabled'   => false,
            'hints'           => [],
            'choices_enabled' => false,
            'choices'         => ['', ''],
            'correct_choice'  => null,
            'duel_files'      => [null, null],
            'duel_paths'      => ['', ''],
            'duel_captions'   => ['', ''],
        ];
    }

    // ── Save ────────────────────────────────────────────────────────────────────

    public function save(): void
    {
        // NOTE: media_url is validated as string (not url) because non-YouTube
        // questions have media_url = '' and Laravel's nullable only exempts null,
        // not empty string, from the url rule.
        $this->validate([
            'name'                                   => 'required|string|max:120',
            'categories'                             => 'required|array|min:1',
            'categories.*.name'                      => 'required|string|max:80',
            'categories.*.questions'                 => 'required|array|min:1',
            'categories.*.questions.*.points'        => 'required|integer|min:1',
            'categories.*.questions.*.question_text' => 'nullable|string',
            'categories.*.questions.*.answer_text'   => 'nullable|string',
            'categories.*.questions.*.question_type' => 'required|in:text,image,zoom_image,pixelate_image,audio,video,youtube,number_guess,duel,image_hotspot',
            'categories.*.questions.*.media_url'     => 'nullable|string',
        ]);

        // Upsert board
        $board = $this->boardId
            ? JeopardyBoard::findOrFail($this->boardId)
            : new JeopardyBoard(['user_id' => Auth::id()]);

        $board->fill([
            'name'        => $this->name,
            'description' => $this->description ?: null,
            'is_public'   => $this->isPublic,
            'columns'     => count($this->categories),
            'rows'        => max(array_map(fn ($c) => count($c['questions']), $this->categories)),
        ])->save();

        // Wipe old categories/questions (cascade), then rebuild fresh
        if ($this->boardId) {
            $board->categories()->delete();
        }

        foreach ($this->categories as $catOrder => $catData) {
            $category = JeopardyCategory::create([
                'board_id' => $board->id,
                'name'     => $catData['name'],
                'order'    => $catOrder,
            ]);

            foreach ($catData['questions'] as $qOrder => $qData) {
                $type = $qData['question_type'];

                // ── Single-file media (image / audio / video) ───────────────
                $mediaPath = $qData['media_path'] ?: null;
                $mediaFile = $qData['media_file'] ?? null;
                if ($mediaFile instanceof TemporaryUploadedFile) {
                    $folder = match ($type) {
                        'image', 'zoom_image', 'pixelate_image', 'image_hotspot' => 'jeopardy/images',
                        'audio'  => 'jeopardy/audio',
                        'video'  => 'jeopardy/video',
                        default  => 'jeopardy/misc',
                    };
                    $mediaPath = $mediaFile->store($folder, 'public');
                }

                // ── Hints ───────────────────────────────────────────────────
                $hints = null;
                if (! empty($qData['hints_enabled'])) {
                    $filtered = array_values(array_filter(
                        $qData['hints'] ?? [],
                        fn ($h) => trim((string) $h) !== ''
                    ));
                    $hints = $filtered ?: null;
                }

                // ── Multiple-choice toggle (non-duel only) ──────────────────
                $choices    = null;
                $answerText = $qData['answer_text'] ?: null;

                if (! empty($qData['choices_enabled']) && $type !== 'duel') {
                    $filtered   = array_values(array_filter(
                        $qData['choices'] ?? [],
                        fn ($c) => trim((string) $c) !== ''
                    ));
                    $choices    = $filtered ?: null;
                    $cc         = $qData['correct_choice'] ?? null;
                    $answerText = ($cc !== null && $cc !== '') ? (string) $cc : null;
                }

                // ── Duel ─────────────────────────────────────────────────────
                $mediaPaths = null;
                if ($type === 'duel') {
                    $duelPaths = array_map('strval', $qData['duel_paths'] ?? []);

                    foreach (($qData['duel_files'] ?? []) as $slot => $file) {
                        if ($file instanceof TemporaryUploadedFile) {
                            $duelPaths[$slot] = $file->store('jeopardy/images', 'public');
                        }
                    }

                    $filtered   = array_values(array_filter($duelPaths, fn ($p) => $p !== ''));
                    $mediaPaths = $filtered ?: null;

                    $captions = array_values($qData['duel_captions'] ?? []);
                    $choices  = $captions ?: null;

                    $answerText = null; // duel has no free-text answer
                }

                JeopardyQuestion::create([
                    'category_id'   => $category->id,
                    'points'        => (int) $qData['points'],
                    'order'         => $qOrder,
                    'question_text' => $qData['question_text'] ?: null,
                    'answer_text'   => $answerText,
                    'question_type' => $type,
                    'media_path'    => $mediaPath,
                    'media_url'     => $qData['media_url'] ?: null,
                    'hints'         => $hints,
                    'choices'       => $choices,
                    'media_paths'   => $mediaPaths,
                ]);
            }
        }

        $this->boardId = $board->id;
        session()->flash('success', 'Board saved!');
        $this->redirect(route('games.jeopardy.index'));
    }

    public function render()
    {
        return view('livewire.jeopardy.board-builder');
    }
}
