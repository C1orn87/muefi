<?php

namespace App\Livewire\Jeopardy;

use App\Models\JeopardyBoard;
use App\Models\JeopardyCategory;
use App\Models\JeopardyQuestion;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;

class BoardBuilder extends Component
{
    use WithFileUploads;

    // ── Board meta ─────────────────────────────────────────────────────────────
    public ?int   $boardId    = null;
    public string $name       = '';
    public string $description= '';
    public bool   $isPublic   = true;

    /**
     * categories: array of
     *   [
     *     'name'      => string,
     *     'questions' => [
     *       [
     *         'points'        => int,
     *         'question_text' => string,
     *         'answer_text'   => string,
     *         'question_type' => 'text'|'image'|'zoom_image'|'audio'|'video'|'youtube',
     *         'media_url'     => string|null,   // for youtube
     *         'media_file'    => UploadedFile|null, // temp upload
     *         'media_path'    => string|null,   // saved path
     *       ],
     *       ...
     *     ],
     *   ]
     */
    public array $categories = [];

    // Default point values per row
    protected array $defaultPoints = [200, 400, 600, 800, 1000];

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
                    $questions[] = [
                        'points'        => $q->points,
                        'question_text' => $q->question_text ?? '',
                        'answer_text'   => $q->answer_text   ?? '',
                        'question_type' => $q->question_type,
                        'media_url'     => $q->media_url     ?? '',
                        'media_file'    => null,
                        'media_path'    => $q->media_path    ?? '',
                    ];
                }
                $this->categories[] = ['name' => $cat->name, 'questions' => $questions];
            }
        }

        if (empty($this->categories)) {
            $this->addCategory();
        }
    }

    // ── Category management ───────────────────────────────────────────────────

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

    // ── Question management ───────────────────────────────────────────────────

    public function addQuestion(int $catIdx): void
    {
        $nextPoints = count($this->categories[$catIdx]['questions']) * 200 + 200;
        $this->categories[$catIdx]['questions'][] = $this->blankQuestion($nextPoints);
    }

    public function removeQuestion(int $catIdx, int $qIdx): void
    {
        array_splice($this->categories[$catIdx]['questions'], $qIdx, 1);
    }

    protected function blankQuestion(int $points): array
    {
        return [
            'points'        => $points,
            'question_text' => '',
            'answer_text'   => '',
            'question_type' => 'text',
            'media_url'     => '',
            'media_file'    => null,
            'media_path'    => '',
        ];
    }

    // ── Save ─────────────────────────────────────────────────────────────────

    public function save(): void
    {
        $this->validate([
            'name'                              => 'required|string|max:120',
            'categories'                        => 'required|array|min:1',
            'categories.*.name'                 => 'required|string|max:80',
            'categories.*.questions'            => 'required|array|min:1',
            'categories.*.questions.*.points'   => 'required|integer|min:1',
            'categories.*.questions.*.question_text' => 'nullable|string',
            'categories.*.questions.*.answer_text'   => 'nullable|string',
            'categories.*.questions.*.question_type' => 'required|in:text,image,zoom_image,audio,video,youtube,number_guess',
            'categories.*.questions.*.media_url'     => 'nullable|url',
        ]);

        // Upsert the board
        $board = $this->boardId
            ? JeopardyBoard::findOrFail($this->boardId)
            : new JeopardyBoard(['user_id' => Auth::id()]);

        $board->fill([
            'name'        => $this->name,
            'description' => $this->description,
            'is_public'   => $this->isPublic,
            'columns'     => count($this->categories),
            'rows'        => max(array_map(fn ($c) => count($c['questions']), $this->categories)),
        ])->save();

        // Rebuild categories and questions
        // Delete old ones (cascade deletes questions too)
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
                // Handle file upload
                $mediaPath = $qData['media_path'] ?? null;
                if (! empty($qData['media_file'])) {
                    $file = $qData['media_file'];
                    // Determine sub-folder by type
                    $folder = match ($qData['question_type']) {
                        'image', 'zoom_image' => 'jeopardy/images',
                        'audio'               => 'jeopardy/audio',
                        'video'               => 'jeopardy/video',
                        default               => 'jeopardy/misc',
                    };
                    $mediaPath = $file->store($folder, 'public');
                }

                JeopardyQuestion::create([
                    'category_id'   => $category->id,
                    'points'        => $qData['points'],
                    'order'         => $qOrder,
                    'question_text' => $qData['question_text'],
                    'answer_text'   => $qData['answer_text'],
                    'question_type' => $qData['question_type'],
                    'media_path'    => $mediaPath,
                    'media_url'     => $qData['media_url'] ?? null,
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
