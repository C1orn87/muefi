<div class="min-h-screen bg-[#042B7F] text-white p-4 flex flex-col gap-4"
     x-data="hostBuzzer(@entangle('activeQuestionId'), {{ $session->buzzer_delay_seconds ?? 3 }})">

    {{-- ══ TOP BAR ══ --}}
    <div class="flex flex-wrap items-center justify-between gap-3 bg-blue-900 rounded-2xl px-5 py-3 shadow">
        <div>
            <span class="text-yellow-400 font-extrabold text-lg tracking-widest uppercase" style="font-family:serif;">JEOPARDY!</span>
            <span class="text-blue-300 text-sm ml-3">{{ $session->board->name }}</span>
        </div>

        {{-- Point percentage toggle --}}
        <div class="flex items-center gap-2">
            <span class="text-blue-300 text-xs uppercase tracking-wide font-medium">Award:</span>
            @foreach([100, 50, 0] as $pct)
                <button wire:click="setPercentage({{ $pct }})"
                        class="px-4 py-1.5 rounded-xl font-bold text-sm transition-colors
                               {{ $session->point_percentage === $pct
                                  ? 'bg-yellow-400 text-blue-900'
                                  : 'bg-blue-700 text-blue-200 hover:bg-blue-600' }}">
                    {{ $pct }}%
                </button>
            @endforeach
        </div>

        {{-- Game controls --}}
        <div class="flex items-center gap-2">
            <span class="text-blue-300 text-xs">
                Join: <span class="font-bold text-white">{{ url('/games/jeopardy/join/'.$session->code) }}</span>
            </span>
            <button wire:click="endGame"
                    onclick="return confirm('End the game?')"
                    class="bg-red-600 hover:bg-red-500 text-white text-xs px-4 py-1.5 rounded-xl font-semibold">
                End Game
            </button>
        </div>
    </div>

    <div class="flex gap-4 flex-1">

        {{-- ══ BOARD ══ --}}
        <div class="flex-1">
            @php $categories = $session->board->categories; @endphp

            {{-- ── Pending card selection alert ── --}}
            @if($pendingQuestion && !$session->activeQuestion)
                <div class="mb-3 bg-yellow-400 text-blue-900 rounded-2xl px-4 py-3 flex items-center justify-between gap-3">
                    <div class="flex items-center gap-2">
                        <span class="text-xl">⭐</span>
                        <div>
                            <p class="font-extrabold text-sm leading-tight">
                                {{ $currentTurnPlayer?->name ?? 'Player' }} wants:
                                <span class="ml-1">${{ $pendingQuestion->points }}</span>
                            </p>
                            <p class="text-xs opacity-70">Click the highlighted card to open it</p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Category headers --}}
            <div class="grid gap-2 mb-2" style="grid-template-columns: repeat({{ count($categories) }}, minmax(0, 1fr))">
                @foreach($categories as $cat)
                    <div class="bg-blue-800 text-yellow-400 font-bold text-center text-sm py-3 px-2 rounded-xl uppercase tracking-wide">
                        {{ $cat->name }}
                    </div>
                @endforeach
            </div>

            {{-- Question cells --}}
            @php
                $maxRows = $categories->map(fn($c) => $c->questions->count())->max() ?? 0;
            @endphp
            @for($row = 0; $row < $maxRows; $row++)
                <div class="grid gap-2 mb-2" style="grid-template-columns: repeat({{ count($categories) }}, minmax(0, 1fr))">
                    @foreach($categories as $cat)
                        @php $q = $cat->questions->get($row); @endphp
                        @if($q)
                            @php
                                $isRevealed  = in_array($q->id, $revealedIds);
                                $isActive    = $session->active_question_id === $q->id;
                                $isPending   = $pendingQuestion?->id === $q->id;
                            @endphp
                            <button wire:click="selectQuestion({{ $q->id }})"
                                    class="py-5 rounded-xl font-extrabold text-xl transition-all
                                           {{ $isActive
                                               ? 'bg-yellow-400 text-blue-900 ring-4 ring-white'
                                               : ($isRevealed
                                                  ? 'bg-blue-950 text-blue-500 opacity-50 hover:opacity-90 hover:bg-blue-800 hover:text-blue-200 hover:scale-105'
                                                  : ($isPending
                                                     ? 'bg-green-400 text-blue-900 ring-4 ring-yellow-300 animate-pulse hover:scale-105'
                                                     : 'bg-blue-700 text-yellow-300 hover:bg-blue-600 hover:scale-105')) }}">
                                ${{ $q->points }}
                            </button>
                        @else
                            <div class="py-5 rounded-xl bg-blue-950 opacity-20"></div>
                        @endif
                    @endforeach
                </div>
            @endfor
        </div>

        {{-- ══ RIGHT PANEL ══ --}}
        <div class="w-96 flex flex-col gap-4">

            {{-- ── Active question ── --}}
            @if($session->activeQuestion)
                @php
                    $aq            = $session->activeQuestion;
                    $sq            = $session->sessionQuestions->firstWhere('question_id', $aq->id);
                    $zoomLevel     = $sq ? $sq->zoom_level    : 4;
                    $pixLevel      = $sq ? $sq->pixelate_level : 1;
                    $zoomPct       = $zoomLevel * 100;
                    $hints         = $aq->hints ?? [];
                    $hintCount     = count($hints);
                    $revealedHints = $session->revealed_hint_count ?? 0;
                    // Fibonacci steps: 1 2 3 5 8 13 21 34 55 89 100
                    $pixPcts       = [1, 2, 3, 5, 8, 13, 21, 34, 55, 89, 100];
                @endphp
                <div class="bg-blue-800 rounded-2xl p-4 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <span class="text-yellow-400 font-bold">${{ $aq->points }}</span>
                        <div class="flex gap-2 flex-wrap">
                            <button wire:click="toggleAnswer"
                                    class="text-xs px-3 py-1 rounded-lg transition-colors
                                           {{ $showAnswer ? 'bg-green-600 hover:bg-green-500' : 'bg-blue-600 hover:bg-blue-500' }}">
                                {{ $showAnswer ? '👁 Hide from players' : '👁 Show to players' }}
                            </button>
                            <button wire:click="closeQuestion"
                                    class="text-xs px-3 py-1 rounded-lg bg-blue-600 hover:bg-blue-500">
                                ✕ Close
                            </button>
                        </div>
                    </div>

                    {{-- Question content --}}
                    <div class="bg-[#042B7F] rounded-xl p-3 min-h-[100px] flex flex-col gap-2">

                        @if($aq->question_text)
                            <p class="text-white text-sm">{{ $aq->question_text }}</p>
                        @endif

                        @if($aq->question_type === 'image')
                            <img src="{{ Storage::url($aq->media_path) }}" alt="Question image"
                                 class="rounded-lg max-h-40 object-contain mx-auto">

                        @elseif($aq->question_type === 'zoom_image')
                            <div class="relative overflow-hidden rounded-lg h-40 bg-black flex items-center justify-center">
                                <img src="{{ Storage::url($aq->media_path) }}" alt="Zoom image"
                                     class="transition-transform duration-500 object-cover w-full h-full"
                                     style="transform: scale({{ $zoomLevel }}); transform-origin: center center;">
                            </div>
                            @if($zoomLevel > 1)
                                <button wire:click="zoomIn"
                                        class="bg-yellow-400 text-blue-900 font-bold text-sm px-4 py-2 rounded-xl hover:bg-yellow-300 transition-colors">
                                    🔍 Reveal more ({{ $zoomPct }}% → {{ ($zoomLevel - 1) * 100 }}%)
                                </button>
                            @endif

                        @elseif($aq->question_type === 'pixelate_image')
                            {{-- wire:key on the canvas itself: different key = element replaced,
                                 Alpine re-inits with new level. wire:ignore prevents Livewire
                                 from morphing the canvas during polls when nothing has changed,
                                 which would trigger Alpine re-init and clear the drawn pixels. --}}
                            <div class="flex flex-col gap-2">
                                <canvas
                                    wire:key="pixelate-{{ $aq->id }}-{{ $pixLevel }}"
                                    wire:ignore
                                    x-data="pixelateImg('{{ Storage::url($aq->media_path) }}', {{ $pixLevel }})"
                                    x-init="draw()"
                                    class="rounded-lg w-full"
                                    style="max-height:160px; image-rendering: pixelated; image-rendering: crisp-edges;">
                                </canvas>
                                <div class="flex items-center justify-between text-xs text-blue-300">
                                    <span>Clarity: <strong class="text-white">{{ $pixPcts[$pixLevel - 1] }}%</strong></span>
                                    @if($pixLevel < 11)
                                        <button wire:click="pixelateReveal"
                                                class="bg-yellow-400 text-blue-900 font-bold text-xs px-3 py-1.5 rounded-lg hover:bg-yellow-300 transition-colors">
                                            🔍 Reveal → {{ $pixPcts[min($pixLevel, 10)] }}%
                                        </button>
                                    @else
                                        <span class="text-green-400 font-semibold">✓ Fully revealed</span>
                                    @endif
                                </div>
                            </div>

                        @elseif($aq->question_type === 'audio')
                            <audio controls class="w-full">
                                <source src="{{ Storage::url($aq->media_path) }}">
                            </audio>

                        @elseif($aq->question_type === 'video')
                            <video controls class="w-full rounded-lg max-h-40">
                                <source src="{{ Storage::url($aq->media_path) }}">
                            </video>

                        @elseif($aq->question_type === 'youtube')
                            @php $embedUrl = $aq->youtubeEmbedUrl(); @endphp
                            @if($embedUrl)
                                <iframe src="{{ $embedUrl }}" class="w-full h-36 rounded-lg"
                                        allow="autoplay; encrypted-media" allowfullscreen></iframe>
                            @endif
                        @endif

                        {{-- ── HOST always sees the answer ── --}}
                        @if($aq->answer_text)
                            <div class="mt-1 bg-green-900/60 border border-green-600 rounded-lg px-3 py-2">
                                <span class="text-green-400 text-[10px] uppercase font-bold tracking-wide">Host answer</span>
                                <p class="text-green-100 font-semibold text-sm mt-0.5">{{ $aq->answer_text }}</p>
                            </div>
                        @endif
                    </div>

                    {{-- ── Hints panel ── --}}
                    @if($hintCount > 0)
                        <div class="bg-amber-900/30 border border-amber-600/50 rounded-xl p-3 flex flex-col gap-2">
                            <div class="flex items-center justify-between">
                                <span class="text-amber-400 text-xs font-bold uppercase tracking-wide">
                                    💡 Hints ({{ $revealedHints }}/{{ $hintCount }} shown to players)
                                </span>
                                @if($revealedHints < $hintCount)
                                    <button wire:click="showNextHint"
                                            class="text-xs px-3 py-1 rounded-lg bg-amber-500 hover:bg-amber-400 text-blue-900 font-bold">
                                        Show hint {{ $revealedHints + 1 }}
                                    </button>
                                @else
                                    <span class="text-amber-300 text-xs font-medium">All revealed</span>
                                @endif
                            </div>
                            <div class="space-y-1">
                                @foreach($hints as $hIdx => $hintText)
                                    <div class="flex items-start gap-2 text-xs rounded-lg px-2 py-1.5
                                                {{ $hIdx < $revealedHints ? 'bg-amber-800/50 text-amber-100' : 'bg-blue-900/60 text-blue-400' }}">
                                        <span class="font-bold flex-shrink-0 w-4 text-amber-400">{{ $hIdx + 1 }}.</span>
                                        <span>{{ $hintText }}</span>
                                        @if($hIdx >= $revealedHints)
                                            <span class="ml-auto text-[10px] italic opacity-60 flex-shrink-0">hidden</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- ── Buzzer panel ── --}}
                <div class="bg-blue-800 rounded-2xl p-4 flex flex-col gap-3">
                    <div class="flex items-center justify-between">
                        <h3 class="text-yellow-400 font-bold uppercase tracking-wide text-xs">Buzzer</h3>
                        <div class="flex items-center gap-2">
                            {{-- Countdown badge --}}
                            <span x-show="countdown > 0"
                                  x-text="'Opens in ' + countdown + 's'"
                                  class="text-xs text-blue-300 bg-blue-700 rounded-lg px-2 py-1"></span>
                            {{-- Status badge --}}
                            @if($session->buzzer_open)
                                <span class="text-xs bg-green-600 text-white rounded-lg px-2 py-1 font-semibold">OPEN</span>
                            @else
                                <span class="text-xs bg-red-700 text-white rounded-lg px-2 py-1 font-semibold" x-show="countdown <= 0">CLOSED</span>
                            @endif
                            <button wire:click="reopenBuzzer"
                                    class="text-xs px-3 py-1 rounded-lg bg-yellow-500 hover:bg-yellow-400 text-blue-900 font-semibold">
                                ↺ Reopen
                            </button>
                        </div>
                    </div>

                    {{-- Buzz queue --}}
                    @if($buzzes->isNotEmpty())
                        <ol class="space-y-1">
                            @foreach($buzzes as $i => $buzz)
                                <li class="flex items-center gap-2 bg-blue-700 rounded-xl px-3 py-2">
                                    <span class="text-yellow-400 font-extrabold text-sm w-5 text-center">{{ $i + 1 }}</span>
                                    <span class="text-white text-sm font-medium">{{ $buzz->player->name }}</span>
                                    <span class="text-blue-300 text-xs ml-auto">{{ $buzz->buzzed_at->format('H:i:s') }}</span>
                                </li>
                            @endforeach
                        </ol>
                    @else
                        <p class="text-blue-400 text-xs text-center">No buzzes yet.</p>
                    @endif
                </div>

                {{-- ── Number guess results ── --}}
                @if($aq->question_type === 'number_guess' && $guesses->isNotEmpty())
                    <div class="bg-blue-800 rounded-2xl p-4 flex flex-col gap-2">
                        <h3 class="text-yellow-400 font-bold uppercase tracking-wide text-xs mb-1">
                            Guesses
                            @if($aq->answer_text)
                                <span class="text-blue-300 normal-case ml-1">(answer: {{ $aq->answer_text }})</span>
                            @endif
                        </h3>
                        @foreach($guesses as $i => $guess)
                            <div class="flex items-center gap-2 bg-blue-700 rounded-xl px-3 py-2">
                                <span class="text-yellow-400 font-bold text-sm w-5 text-center">{{ $i + 1 }}</span>
                                <span class="text-white text-sm font-medium flex-1">{{ $guess->player->name }}</span>
                                <span class="text-yellow-300 font-bold text-sm">{{ number_format($guess->guess, 2) }}</span>
                                @if($aq->answer_text)
                                    @php $diff = abs($guess->guess - (float)$aq->answer_text); @endphp
                                    <span class="text-blue-300 text-xs ml-1">
                                        ±{{ number_format($diff, 2) }}
                                    </span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

            @else
                <div class="bg-blue-800 rounded-2xl p-4 text-blue-300 text-sm text-center">
                    Click a cell on the board to open a question.
                </div>
            @endif

            {{-- ── Score panel ── --}}
            <div class="bg-blue-800 rounded-2xl p-4 flex-1 overflow-y-auto">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-yellow-400 font-bold uppercase tracking-wide text-xs">Scoreboard</h3>
                    {{-- Advance turn button --}}
                    @if($session->current_turn_player_id)
                        <button wire:click="advanceTurn"
                                class="text-xs px-3 py-1 rounded-lg bg-purple-600 hover:bg-purple-500 text-white font-semibold">
                            ⏭ Next Turn
                        </button>
                    @endif
                </div>

                {{-- Current turn banner --}}
                @if($currentTurnPlayer)
                    <div class="mb-3 bg-green-700/50 border border-green-500 rounded-xl px-3 py-2 flex items-center gap-2">
                        <span class="text-green-400 text-sm">▶</span>
                        <span class="text-green-300 text-xs font-semibold">{{ $currentTurnPlayer->name }}'s turn</span>
                    </div>
                @endif

                {{-- Solo players --}}
                @php $soloPlayers = $session->players->whereNull('team_id'); @endphp
                @foreach($soloPlayers as $player)
                    @php $isTurn = $player->id === $session->current_turn_player_id; @endphp
                    <div class="flex items-center justify-between mb-2 rounded-xl px-3 py-2
                                {{ $isTurn ? 'bg-green-700/40 ring-1 ring-green-500' : 'bg-blue-700' }}">
                        <div class="flex items-center gap-2 min-w-0">
                            @if($isTurn)
                                <span class="text-green-400 text-xs flex-shrink-0">▶</span>
                            @endif
                            <span class="text-sm font-medium truncate">{{ $player->name }}</span>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            {{-- Set turn --}}
                            @if(!$isTurn)
                                <button wire:click="setTurn({{ $player->id }})"
                                        title="Set turn"
                                        class="w-6 h-6 rounded-lg bg-purple-700 hover:bg-purple-600 text-white text-xs font-bold">▶</button>
                            @endif
                            <button wire:click="adjustPlayerScore({{ $player->id }}, '-')"
                                    class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-500 font-bold text-sm">−</button>
                            <span class="text-yellow-300 font-bold w-14 text-center">${{ number_format($player->score) }}</span>
                            <button wire:click="adjustPlayerScore({{ $player->id }}, '+')"
                                    class="w-7 h-7 rounded-lg bg-green-600 hover:bg-green-500 font-bold text-sm">+</button>
                        </div>
                    </div>
                @endforeach

                {{-- Teams --}}
                @foreach($session->teams as $team)
                    <div class="mb-3">
                        <div class="flex items-center justify-between bg-blue-600 rounded-xl px-3 py-2 mb-1">
                            <span class="text-yellow-400 text-xs font-bold uppercase tracking-wide">{{ $team->name }}</span>
                            <span class="text-yellow-300 font-bold">${{ number_format($team->score) }}</span>
                        </div>
                        @foreach($team->players as $player)
                            @php $isTurn = $player->id === $session->current_turn_player_id; @endphp
                            <div class="flex items-center justify-between ml-3 mb-1 rounded-xl px-3 py-1.5
                                        {{ $isTurn ? 'bg-green-700/40 ring-1 ring-green-500' : 'bg-blue-700' }}">
                                <div class="flex items-center gap-1 min-w-0">
                                    @if($isTurn) <span class="text-green-400 text-xs flex-shrink-0">▶</span> @endif
                                    <span class="text-xs truncate">{{ $player->name }}</span>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    @if(!$isTurn)
                                        <button wire:click="setTurn({{ $player->id }})"
                                                title="Set turn"
                                                class="w-5 h-5 rounded-lg bg-purple-700 hover:bg-purple-600 text-white text-[10px] font-bold">▶</button>
                                    @endif
                                    <button wire:click="adjustPlayerScore({{ $player->id }}, '-')"
                                            class="w-6 h-6 rounded-lg bg-red-600 hover:bg-red-500 font-bold text-xs">−</button>
                                    <span class="text-yellow-300 font-bold text-xs w-12 text-center">${{ number_format($player->score) }}</span>
                                    <button wire:click="adjustPlayerScore({{ $player->id }}, '+')"
                                            class="w-6 h-6 rounded-lg bg-green-600 hover:bg-green-500 font-bold text-xs">+</button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>

        </div>
    </div>

</div>

@script
<script>
    // ── Pixelated image renderer ───────────────────────────────────────────────
    // Draws the image at a low fraction of its native resolution, then scales
    // back up without smoothing to produce a blocky pixel effect.
    Alpine.data('pixelateImg', (src, level) => ({
        // Fibonacci steps: 1 2 3 5 8 13 21 34 55 89 100 %
        levels: [0.01, 0.02, 0.03, 0.05, 0.08, 0.13, 0.21, 0.34, 0.55, 0.89, 1.0],
        draw() {
            const canvas = this.$el;
            const pct    = this.levels[Math.min(level - 1, this.levels.length - 1)];
            const img    = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                const w = Math.max(1, Math.round(img.naturalWidth  * pct));
                const h = Math.max(1, Math.round(img.naturalHeight * pct));

                // Draw at small size into a temp canvas
                const tmp = document.createElement('canvas');
                tmp.width  = w;
                tmp.height = h;
                tmp.getContext('2d').drawImage(img, 0, 0, w, h);

                // Scale back up with no smoothing
                canvas.width  = img.naturalWidth;
                canvas.height = img.naturalHeight;
                const ctx = canvas.getContext('2d');
                ctx.imageSmoothingEnabled = false;
                ctx.drawImage(tmp, 0, 0, canvas.width, canvas.height);
            };
            img.src = src;
        },
    }));

    Alpine.data('hostBuzzer', (activeQuestionIdRef, delaySeconds) => ({
        countdown: 0,
        timer: null,
        activeQuestionId: activeQuestionIdRef,

        init() {
            this.$watch('activeQuestionId', (newId) => {
                clearInterval(this.timer);
                this.countdown = 0;

                if (newId) {
                    this.countdown = delaySeconds;
                    this.timer = setInterval(() => {
                        this.countdown--;
                        if (this.countdown <= 0) {
                            clearInterval(this.timer);
                            this.countdown = 0;
                            $wire.openBuzzer();
                        }
                    }, 1000);
                }
            });
        },
    }));
</script>
@endscript
