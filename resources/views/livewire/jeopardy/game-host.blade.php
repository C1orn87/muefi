<div class="min-h-screen bg-[#042B7F] text-white p-4 flex flex-col gap-4"
     x-data="hostBuzzer(@entangle('activeQuestionId'), {{ $session->buzzer_delay_seconds ?? 3 }})"
     wire:poll.2000ms="refresh">

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
                    $pixPcts       = [0, 20, 40, 60, 80, 100]; // level 1–6: ×4 each step
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
                                    @if($pixLevel < 6)
                                        <button wire:click="pixelateReveal"
                                                class="bg-yellow-400 text-blue-900 font-bold text-xs px-3 py-1.5 rounded-lg hover:bg-yellow-300 transition-colors">
                                            🔍 Reveal → {{ $pixPcts[min($pixLevel, 5)] }}%
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

                        @elseif($aq->question_type === 'image_hotspot')
                            {{-- Image with coloured dot overlay per player click.
                                 No height cap or overflow-hidden: the container must wrap the
                                 image exactly so that left/top % dots align with the player's
                                 click coords, which were also recorded relative to the full
                                 rendered image. object-contain with a fixed height would add
                                 letterboxing and shift every dot. --}}
                            <div class="relative rounded-lg"
                                 wire:key="hotspot-{{ $aq->id }}-{{ $clickVotes->count() }}">
                                <img src="{{ Storage::url($aq->media_path) }}"
                                     alt="Hotspot image"
                                     class="w-full block rounded-lg">
                                {{-- Coloured dots for each player click --}}
                                @foreach($clickVotes as $cv)
                                    @php
                                        $colors = ['#FF6B6B','#4ECDC4','#FFE66D','#A8E6CF','#FF8B94','#B8B8FF','#FFDAC1','#B5EAD7'];
                                        $color  = $colors[$loop->index % count($colors)];
                                    @endphp
                                    <div title="{{ $cv->player->name }}"
                                         style="position:absolute; left:{{ $cv->x_pct }}%; top:{{ $cv->y_pct }}%; background:{{ $color }};
                                                width:16px; height:16px; border-radius:50%; transform:translate(-50%,-50%);
                                                border:2px solid white; box-shadow:0 0 4px rgba(0,0,0,.6); pointer-events:none;">
                                    </div>
                                @endforeach
                            </div>
                            {{-- Legend --}}
                            @if($clickVotes->isNotEmpty())
                                @php
                                    $colors = ['#FF6B6B','#4ECDC4','#FFE66D','#A8E6CF','#FF8B94','#B8B8FF','#FFDAC1','#B5EAD7'];
                                @endphp
                                <div class="flex flex-wrap gap-1 mt-1">
                                    @foreach($clickVotes as $cv)
                                        <span class="text-[10px] rounded-md px-1.5 py-0.5 font-semibold text-blue-900"
                                              style="background:{{ $colors[$loop->index % count($colors)] }}">
                                            {{ $cv->player->name }}
                                        </span>
                                    @endforeach
                                </div>
                                <p class="text-blue-400 text-[10px] text-right">{{ $clickVotes->count() }} / {{ $session->players->where('is_kicked',false)->count() }} clicked</p>
                            @else
                                <p class="text-blue-400 text-xs text-center">Waiting for players to click…</p>
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

                {{-- ── Multiple choice vote breakdown ── --}}
                @if(!empty($aq->choices) && $aq->question_type !== 'duel')
                    @php
                        $choices     = $aq->choices ?? [];
                        $correctIdx  = is_numeric($aq->answer_text) ? (int)$aq->answer_text : null;
                    @endphp
                    <div class="bg-blue-800 rounded-2xl p-4 flex flex-col gap-2">
                        <div class="flex items-center justify-between mb-1">
                            <h3 class="text-yellow-400 font-bold uppercase tracking-wide text-xs">🔘 Votes</h3>
                            <span class="text-blue-300 text-xs">{{ $totalVotes }} / {{ $session->players->count() }} answered</span>
                        </div>

                        @foreach($choices as $cIdx => $choiceLabel)
                            @php
                                $count   = $votesByChoice->get($cIdx)?->count() ?? 0;
                                $pct     = $totalVotes > 0 ? round($count / $totalVotes * 100) : 0;
                                $isRight = $correctIdx === $cIdx;
                                $voters  = $votesByChoice->get($cIdx) ?? collect();
                            @endphp
                            <div class="space-y-0.5">
                                <div class="flex items-center gap-2">
                                    <span class="text-yellow-400 font-extrabold text-sm w-5 text-center flex-shrink-0">{{ chr(65 + $cIdx) }}</span>
                                    <div class="flex-1 min-w-0">
                                        <div class="flex items-center gap-2 mb-0.5">
                                            <span class="text-white text-xs font-medium truncate">{{ $choiceLabel }}</span>
                                            @if($isRight && $showAnswer)
                                                <span class="text-green-400 text-[10px] font-bold flex-shrink-0">✓ correct</span>
                                            @endif
                                        </div>
                                        {{-- Progress bar --}}
                                        <div class="h-2 rounded-full bg-blue-900 overflow-hidden">
                                            <div class="h-full rounded-full transition-all duration-300
                                                        {{ $isRight && $showAnswer ? 'bg-green-500' : 'bg-yellow-400' }}"
                                                 style="width: {{ $pct }}%"></div>
                                        </div>
                                    </div>
                                    <span class="text-yellow-300 font-bold text-xs w-16 text-right flex-shrink-0">
                                        {{ $count }} ({{ $pct }}%)
                                    </span>
                                </div>
                                {{-- Voter names --}}
                                @if($voters->isNotEmpty())
                                    <div class="flex flex-wrap gap-1 pl-7">
                                        @foreach($voters as $vote)
                                            <span class="text-[10px] bg-blue-700 text-blue-200 rounded-md px-1.5 py-0.5">
                                                {{ $vote->player->name }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- ── Duel vote breakdown ── --}}
                @if($aq->question_type === 'duel')
                    @php
                        $duelPaths    = $aq->media_paths ?? [];
                        $duelCaptions = $aq->choices      ?? [];
                    @endphp
                    <div class="bg-blue-800 rounded-2xl p-4 flex flex-col gap-3">
                        <div class="flex items-center justify-between">
                            <h3 class="text-yellow-400 font-bold uppercase tracking-wide text-xs">⚔️ Duel Votes</h3>
                            <span class="text-blue-300 text-xs">{{ $totalVotes }} / {{ $session->players->count() }} voted</span>
                        </div>

                        <div class="flex gap-2">
                            @foreach($duelPaths as $sIdx => $duelPath)
                                @php
                                    $count  = $votesByChoice->get($sIdx)?->count() ?? 0;
                                    $pct    = $totalVotes > 0 ? round($count / $totalVotes * 100) : 0;
                                    $voters = $votesByChoice->get($sIdx) ?? collect();
                                @endphp
                                <div class="flex-1 min-w-0 flex flex-col gap-1">
                                    <div class="relative rounded-xl overflow-hidden">
                                        <img src="{{ Storage::url($duelPath) }}"
                                             alt="Option {{ $sIdx + 1 }}"
                                             class="w-full block rounded-xl">
                                        {{-- Vote count badge --}}
                                        <div class="absolute top-1.5 right-1.5 bg-yellow-400 text-blue-900 font-extrabold text-xs rounded-full min-w-[22px] h-[22px] flex items-center justify-center px-1 shadow">
                                            {{ $count }}
                                        </div>
                                    </div>
                                    {{-- Label + bar --}}
                                    @if(!empty($duelCaptions[$sIdx]))
                                        <p class="text-white text-[11px] font-semibold text-center truncate">{{ $duelCaptions[$sIdx] }}</p>
                                    @endif
                                    <div class="h-1.5 rounded-full bg-blue-900 overflow-hidden">
                                        <div class="h-full rounded-full bg-yellow-400 transition-all duration-300"
                                             style="width: {{ $pct }}%"></div>
                                    </div>
                                    <p class="text-yellow-300 text-[10px] text-center font-bold">{{ $pct }}%</p>
                                    {{-- Voter names --}}
                                    @if($voters->isNotEmpty())
                                        <div class="flex flex-wrap gap-1 justify-center">
                                            @foreach($voters as $vote)
                                                <span class="text-[10px] bg-blue-700 text-blue-200 rounded-md px-1.5 py-0.5">
                                                    {{ $vote->player->name }}
                                                </span>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
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
                @php $soloPlayers = $session->players->whereNull('team_id')->where('is_kicked', false); @endphp
                @foreach($soloPlayers as $player)
                    @php $isTurn = $player->id === $session->current_turn_player_id; @endphp
                    <div class="flex items-center justify-between mb-2 rounded-xl px-3 py-2
                                {{ $isTurn ? 'bg-green-700/40 ring-1 ring-green-500' : 'bg-blue-700' }}
                                {{ $player->is_kicked ? 'opacity-40' : '' }}">
                        <div class="flex items-center gap-2 min-w-0">
                            @if($isTurn)
                                <span class="text-green-400 text-xs flex-shrink-0">▶</span>
                            @endif
                            <span class="text-sm font-medium truncate">{{ $player->name }}</span>
                            @if($player->is_kicked)
                                <span class="text-red-400 text-[10px] font-bold flex-shrink-0">kicked</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0">
                            {{-- Set turn --}}
                            @if(!$isTurn && !$player->is_kicked)
                                <button wire:click="setTurn({{ $player->id }})"
                                        title="Set turn"
                                        class="w-6 h-6 rounded-lg bg-purple-700 hover:bg-purple-600 text-white text-xs font-bold">▶</button>
                            @endif
                            @if(!$player->is_kicked)
                                <button wire:click="adjustPlayerScore({{ $player->id }}, '-')"
                                        class="w-7 h-7 rounded-lg bg-red-600 hover:bg-red-500 font-bold text-sm">−</button>
                                <span class="text-yellow-300 font-bold w-14 text-center">${{ number_format($player->score) }}</span>
                                <button wire:click="adjustPlayerScore({{ $player->id }}, '+')"
                                        class="w-7 h-7 rounded-lg bg-green-600 hover:bg-green-500 font-bold text-sm">+</button>
                                <button wire:click="kickPlayer({{ $player->id }})"
                                        onclick="return confirm('Kick {{ $player->name }}?')"
                                        title="Kick player"
                                        class="w-7 h-7 rounded-lg bg-gray-600 hover:bg-red-700 text-white text-xs font-bold ml-1">✕</button>
                            @else
                                <span class="text-yellow-300 font-bold w-14 text-center">${{ number_format($player->score) }}</span>
                            @endif
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
                        @foreach($team->players->where('is_kicked', false) as $player)
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
                                    <button wire:click="kickPlayer({{ $player->id }})"
                                            onclick="return confirm('Kick {{ $player->name }}?')"
                                            title="Kick player"
                                            class="w-6 h-6 rounded-lg bg-gray-600 hover:bg-red-700 text-white text-[10px] font-bold">✕</button>
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
        // Resolution: 5 → 20 → 80 → 320 → 1280 px (×4 each step)
        draw() {
            const canvas = this.$el;
            const img    = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = () => {
                canvas.width  = img.naturalWidth;
                canvas.height = img.naturalHeight;

                const res     = Math.round(5 * Math.pow(4, level - 1));
                const longest = Math.max(img.naturalWidth, img.naturalHeight);

                if (res >= longest) {
                    canvas.getContext('2d').drawImage(img, 0, 0);
                    return;
                }

                const scale = res / longest;
                const w = Math.max(1, Math.round(img.naturalWidth  * scale));
                const h = Math.max(1, Math.round(img.naturalHeight * scale));

                const tmp = document.createElement('canvas');
                tmp.width  = w;
                tmp.height = h;
                tmp.getContext('2d').drawImage(img, 0, 0, w, h);

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
