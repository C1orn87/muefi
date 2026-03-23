<div class="min-h-screen bg-[#042B7F] text-white flex flex-col pb-24"
     wire:poll.2000ms="refresh">

    {{-- ── Kicked ── --}}
    @if($kicked ?? false)
        <div class="flex-1 flex flex-col items-center justify-center gap-4 p-8 text-center">
            <p class="text-5xl">🚫</p>
            <p class="text-red-400 text-2xl font-extrabold">You've been removed</p>
            <p class="text-blue-300 text-sm">The host has removed you from this game.</p>
        </div>

    {{-- ── Game finished ── --}}
    @elseif($session->status === 'finished')
        <div class="flex-1 flex flex-col items-center justify-center gap-6 p-4">
            <p class="text-4xl font-extrabold text-yellow-400">Game Over!</p>
            <div class="bg-blue-800 rounded-2xl p-6 w-full max-w-sm">
                <h3 class="text-yellow-400 font-bold uppercase tracking-wide text-sm mb-4 text-center">Final Scores</h3>
                @foreach($session->players->sortByDesc('score') as $p)
                    <div class="flex justify-between items-center mb-2 {{ $p->id === $playerId ? 'text-yellow-300 font-bold' : 'text-white' }}">
                        <span>{{ $p->name }}</span>
                        <span>${{ number_format($p->score) }}</span>
                    </div>
                @endforeach
            </div>
        </div>

    {{-- ── Active question ── --}}
    @elseif($session->activeQuestion)
        @php
            $aq            = $session->activeQuestion;
            $sq            = $session->sessionQuestions->firstWhere('question_id', $aq->id);
            $zoomLevel     = $sq ? $sq->zoom_level    : 4;
            $pixLevel      = $sq ? $sq->pixelate_level : 1;
            $hints         = $aq->hints ?? [];
            $revealedHints = $session->revealed_hint_count ?? 0;
        @endphp
        <div class="flex-1 flex flex-col items-center justify-center gap-4 p-4 relative">

            {{-- Answer reveal overlay --}}
            @if($session->show_answer && $aq->answer_text)
                <div class="absolute inset-0 flex items-center justify-center bg-[#042B7F]/95 z-10 rounded-2xl">
                    <div class="text-center px-6">
                        <p class="text-green-400 text-sm font-semibold uppercase tracking-widest mb-2">Answer</p>
                        <p class="text-white text-3xl font-extrabold">{{ $aq->answer_text }}</p>
                    </div>
                </div>
            @endif

            <p class="text-yellow-400 font-extrabold text-2xl">${{ $aq->points }}</p>

            <div class="bg-blue-800 rounded-2xl p-6 w-full max-w-lg">

                @if($aq->question_text)
                    <p class="text-white text-lg text-center mb-4">{{ $aq->question_text }}</p>
                @endif

                @if($aq->question_type === 'image')
                    <img src="{{ Storage::url($aq->media_path) }}" alt="Question"
                         class="rounded-xl max-h-64 object-contain mx-auto">

                @elseif($aq->question_type === 'zoom_image')
                    <div class="relative overflow-hidden rounded-xl h-64 bg-black flex items-center justify-center">
                        <img src="{{ Storage::url($aq->media_path) }}" alt="Zoom"
                             class="transition-transform duration-700 object-cover w-full h-full"
                             style="transform: scale({{ $zoomLevel }}); transform-origin: center center;">
                    </div>

                @elseif($aq->question_type === 'pixelate_image')
                    {{-- wire:key on the canvas itself: different key = element replaced,
                         Alpine re-inits with new level. wire:ignore prevents Livewire
                         from morphing the canvas during polls (or any other re-render
                         where the question/level hasn't changed), which would trigger
                         Alpine re-init and erase the drawn pixels. --}}
                    <canvas
                        wire:key="pixelate-{{ $aq->id }}-{{ $pixLevel }}"
                        wire:ignore
                        x-data="pixelateImg('{{ Storage::url($aq->media_path) }}', {{ $pixLevel }})"
                        x-init="draw()"
                        class="rounded-xl w-full"
                        style="max-height:256px; image-rendering: pixelated; image-rendering: crisp-edges;">
                    </canvas>

                @elseif($aq->question_type === 'audio')
                    <audio controls class="w-full mt-2">
                        <source src="{{ Storage::url($aq->media_path) }}">
                    </audio>

                @elseif($aq->question_type === 'video')
                    <video controls class="w-full rounded-xl max-h-64">
                        <source src="{{ Storage::url($aq->media_path) }}">
                    </video>

                @elseif($aq->question_type === 'youtube')
                    @php $embedUrl = $aq->youtubeEmbedUrl(); @endphp
                    @if($embedUrl)
                        <iframe src="{{ $embedUrl }}" class="w-full h-48 rounded-xl"
                                allow="autoplay; encrypted-media" allowfullscreen></iframe>
                    @endif

                @elseif($aq->question_type === 'image_hotspot')
                    {{-- Tappable image — click records x/y as percentage coordinates --}}
                    <div class="relative cursor-crosshair select-none"
                         wire:key="hotspot-player-{{ $aq->id }}"
                         x-data="hotspotTap($wire)"
                         @click="tap($event)">
                        <img src="{{ Storage::url($aq->media_path) }}"
                             alt="Click on the image"
                             class="rounded-xl w-full block"
                             draggable="false">
                        {{-- Show placed dot --}}
                        @if($hasClickedHotspot)
                            <div style="position:absolute; left:{{ $myHotspot->x_pct }}%; top:{{ $myHotspot->y_pct }}%;
                                        width:20px; height:20px; border-radius:50%;
                                        background:#FFE66D; border:3px solid #042B7F;
                                        transform:translate(-50%,-50%); box-shadow:0 0 6px rgba(0,0,0,.5);
                                        pointer-events:none;">
                            </div>
                        @endif
                    </div>
                    @if($hasClickedHotspot)
                        <p class="text-green-400 text-sm text-center font-semibold mt-1">📍 Tap again to move your dot</p>
                    @else
                        <p class="text-blue-300 text-sm text-center mt-1">Tap anywhere on the image</p>
                    @endif
                @endif
            </div>

            {{-- ── Revealed hints ── --}}
            @if($revealedHints > 0 && count($hints) > 0)
                <div class="w-full max-w-lg bg-amber-900/40 border border-amber-600/50 rounded-2xl px-4 py-3">
                    <p class="text-amber-400 text-xs font-bold uppercase tracking-wide mb-2">💡 Hints</p>
                    <div class="space-y-1">
                        @foreach(array_slice($hints, 0, $revealedHints) as $hIdx => $hintText)
                            <div class="flex items-start gap-2 text-sm text-amber-100">
                                <span class="text-amber-400 font-bold flex-shrink-0">{{ $hIdx + 1 }}.</span>
                                <span>{{ $hintText }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            {{-- ── Multiple Choice ── --}}
            @if(!empty($aq->choices) && $aq->question_type !== 'duel')
                @php $choices = $aq->choices ?? []; @endphp
                @if($hasVoted)
                    <div class="w-full max-w-lg space-y-2">
                        @foreach($choices as $cIdx => $choiceLabel)
                            <div class="flex items-center gap-3 rounded-2xl px-5 py-3
                                        {{ $cIdx === $myVoteIndex ? 'bg-yellow-400 text-blue-900' : 'bg-blue-800 text-blue-300' }}">
                                <span class="font-extrabold text-lg w-7 text-center flex-shrink-0">{{ chr(65 + $cIdx) }}</span>
                                <span class="text-sm font-medium">{{ $choiceLabel }}</span>
                                @if($cIdx === $myVoteIndex)
                                    <span class="ml-auto text-blue-900 font-bold text-xs">✓ Your pick</span>
                                @endif
                            </div>
                        @endforeach
                        <p class="text-blue-400 text-xs text-center pt-1">Waiting for results…</p>
                    </div>
                @else
                    <div class="w-full max-w-lg space-y-2">
                        <p class="text-blue-300 text-sm text-center mb-1">Choose your answer:</p>
                        @foreach($choices as $cIdx => $choiceLabel)
                            <button wire:click="submitChoice({{ $cIdx }})"
                                    class="w-full flex items-center gap-3 rounded-2xl px-5 py-4 bg-blue-700 hover:bg-yellow-400 hover:text-blue-900 text-white transition-all active:scale-95 group">
                                <span class="font-extrabold text-xl w-7 text-center flex-shrink-0 text-yellow-400 group-hover:text-blue-900">
                                    {{ chr(65 + $cIdx) }}
                                </span>
                                <span class="text-sm font-semibold">{{ $choiceLabel }}</span>
                            </button>
                        @endforeach
                    </div>
                @endif
            @endif

            {{-- ── Duel (image vote) ── --}}
            @if($aq->question_type === 'duel')
                @php
                    $duelPaths    = $aq->media_paths ?? [];
                    $duelCaptions = $aq->choices      ?? [];
                    $slotCount    = count($duelPaths);
                @endphp
                @if($hasVoted)
                    <div class="w-full px-2">
                        <div class="flex gap-2 items-start">
                            @foreach($duelPaths as $sIdx => $duelPath)
                                <div class="relative flex-1 min-w-0 rounded-2xl overflow-hidden
                                            {{ $sIdx === $myVoteIndex ? 'ring-4 ring-yellow-400' : 'opacity-50' }}">
                                    <img src="{{ Storage::url($duelPath) }}"
                                         alt="Option {{ $sIdx + 1 }}"
                                         class="w-full block rounded-2xl">
                                    @if(!empty($duelCaptions[$sIdx]))
                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-xs text-center py-1 px-2 font-semibold">
                                            {{ $duelCaptions[$sIdx] }}
                                        </div>
                                    @endif
                                    @if($sIdx === $myVoteIndex)
                                        <div class="absolute top-2 right-2 bg-yellow-400 text-blue-900 rounded-full w-7 h-7 flex items-center justify-center font-extrabold text-xs shadow">
                                            ✓
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <p class="text-blue-400 text-xs text-center pt-3">Voted! Waiting for results…</p>
                    </div>
                @else
                    <div class="w-full px-2">
                        <p class="text-blue-300 text-sm text-center mb-2">Tap an image to vote:</p>
                        <div class="flex gap-2 items-start">
                            @foreach($duelPaths as $sIdx => $duelPath)
                                <button wire:click="submitChoice({{ $sIdx }})"
                                        class="relative flex-1 min-w-0 rounded-2xl overflow-hidden
                                               active:scale-95 transition-transform
                                               hover:ring-4 hover:ring-yellow-400 focus:outline-none">
                                    <img src="{{ Storage::url($duelPath) }}"
                                         alt="Option {{ $sIdx + 1 }}"
                                         class="w-full block rounded-2xl pointer-events-none">
                                    @if(!empty($duelCaptions[$sIdx]))
                                        <div class="absolute bottom-0 left-0 right-0 bg-black/60 text-white text-xs text-center py-1 px-2 font-semibold">
                                            {{ $duelCaptions[$sIdx] }}
                                        </div>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    </div>
                @endif
            @endif

            {{-- ── Buzzer button (standard types with no choices and not duel/number_guess) ── --}}
            @if($aq->question_type !== 'number_guess' && $aq->question_type !== 'duel' && empty($aq->choices))
                @if($session->buzzer_open)
                    @if($hasBuzzed)
                        <div class="text-center">
                            <div class="bg-blue-700 rounded-2xl px-8 py-4 inline-block">
                                <p class="text-yellow-400 font-extrabold text-xl">Buzzed in! #{{ $myBuzzOrder }}</p>
                                <p class="text-blue-300 text-sm mt-1">Waiting for host…</p>
                            </div>
                        </div>
                    @else
                        <button wire:click="buzz"
                                class="w-full max-w-xs py-5 rounded-2xl bg-yellow-400 text-blue-900 font-extrabold text-2xl uppercase tracking-widest shadow-lg hover:bg-yellow-300 active:scale-95 transition-all">
                            BUZZ!
                        </button>
                    @endif
                @else
                    <div class="text-center text-blue-400 text-sm">
                        Waiting for buzzer…
                    </div>
                @endif
            @endif

            {{-- ── Number guess input ── --}}
            @if($aq->question_type === 'number_guess')
                @if($hasGuessed)
                    <div class="bg-blue-700 rounded-2xl px-8 py-4 text-center">
                        <p class="text-green-400 font-bold text-lg">Your guess: {{ $myGuess }}</p>
                        <p class="text-blue-300 text-sm mt-1">Waiting for results…</p>
                    </div>
                @else
                    <div class="w-full max-w-xs">
                        <label class="block text-blue-300 text-sm font-medium mb-2 text-center">Enter your guess</label>
                        <div class="flex gap-2">
                            <input wire:model="guessInput"
                                   type="number"
                                   step="any"
                                   placeholder="0"
                                   class="flex-1 bg-blue-800 border border-blue-600 rounded-xl px-4 py-3 text-white placeholder-blue-400 text-lg text-center focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            <button wire:click="submitGuess"
                                    class="px-5 py-3 bg-yellow-400 text-blue-900 font-extrabold rounded-xl hover:bg-yellow-300 active:scale-95 transition-all">
                                Submit
                            </button>
                        </div>
                        @error('guessInput')
                            <p class="text-red-400 text-xs mt-1 text-center">{{ $message }}</p>
                        @enderror
                    </div>
                @endif
            @endif

        </div>

    {{-- ── Board / turn view ── --}}
    @else
        <div class="flex-1 p-4 pb-0">
            @php $categories = $session->board->categories; @endphp

            {{-- ── Turn banner ── --}}
            @if($session->current_turn_player_id)
                @if($isMyTurn)
                    <div class="mb-3 bg-yellow-400 text-blue-900 rounded-2xl px-4 py-3 flex items-center gap-3">
                        <span class="text-2xl">⭐</span>
                        <div>
                            <p class="font-extrabold text-lg leading-tight">It's your turn!</p>
                            <p class="text-sm font-medium opacity-75">Pick a card below or tap Random</p>
                        </div>
                    </div>
                @else
                    <div class="mb-3 bg-blue-800 text-white rounded-2xl px-4 py-3 flex items-center gap-3">
                        <span class="text-2xl">🎯</span>
                        <div>
                            <p class="text-blue-300 text-xs uppercase tracking-wide font-semibold">Currently picking…</p>
                            <p class="font-bold text-base">{{ $currentTurnPlayer?->name ?? 'Someone' }}</p>
                        </div>
                    </div>
                @endif
            @endif

            {{-- ── Pending selection feedback (only visible to the picking player) ── --}}
            @if($isMyTurn && $pendingQuestionId)
                <div class="mb-3 bg-green-700 text-white rounded-2xl px-4 py-2 flex items-center gap-2 text-sm font-semibold">
                    <span>✅</span>
                    <span>Card selected — waiting for the host to open it…</span>
                </div>
            @endif

            {{-- Category headers --}}
            <div class="grid gap-2 mb-2" style="grid-template-columns: repeat({{ count($categories) }}, minmax(0, 1fr))">
                @foreach($categories as $cat)
                    <div class="bg-blue-800 text-yellow-400 font-bold text-center text-xs py-2 px-1 rounded-xl uppercase tracking-wide">
                        {{ $cat->name }}
                    </div>
                @endforeach
            </div>

            @php $maxRows = $categories->map(fn($c) => $c->questions->count())->max() ?? 0; @endphp
            @for($row = 0; $row < $maxRows; $row++)
                <div class="grid gap-2 mb-2" style="grid-template-columns: repeat({{ count($categories) }}, minmax(0, 1fr))">
                    @foreach($categories as $cat)
                        @php $q = $cat->questions->get($row); @endphp
                        @if($q)
                            @php
                                $isRevealed  = in_array($q->id, $revealedIds);
                                $isPending   = $pendingQuestionId === $q->id;
                            @endphp
                            @if($isMyTurn && !$isRevealed)
                                {{-- Clickable for the player whose turn it is --}}
                                <button wire:click="selectCard({{ $q->id }})"
                                        class="py-4 rounded-xl text-center font-extrabold text-lg transition-all active:scale-95
                                               {{ $isPending
                                                   ? 'bg-green-400 text-blue-900 ring-2 ring-white scale-105'
                                                   : 'bg-blue-600 text-yellow-300 hover:bg-yellow-400 hover:text-blue-900' }}">
                                    ${{ $q->points }}
                                </button>
                            @else
                                <div class="py-4 rounded-xl text-center font-extrabold text-lg
                                            {{ $isRevealed
                                                ? 'bg-blue-950 text-blue-900 opacity-30'
                                                : 'bg-blue-700 text-yellow-300' }}">
                                    @if(!$isRevealed) ${{ $q->points }} @endif
                                </div>
                            @endif
                        @else
                            <div class="py-4 rounded-xl bg-blue-950 opacity-20"></div>
                        @endif
                    @endforeach
                </div>
            @endfor
        </div>

        {{-- ── Random card button (only shown on your turn) ── --}}
        @if($isMyTurn)
            <div class="px-4 py-3">
                <button wire:click="selectRandomCard"
                        class="w-full py-4 rounded-2xl bg-purple-600 hover:bg-purple-500 active:scale-95
                               text-white font-extrabold text-lg uppercase tracking-widest shadow-lg transition-all
                               flex items-center justify-center gap-3">
                    <span class="text-2xl">🎲</span>
                    Random Card
                </button>
            </div>
        @endif
    @endif

    {{-- ── Sticky footer: player list + turn indicator ── --}}
    <div class="fixed bottom-0 left-0 right-0 bg-blue-900/95 backdrop-blur border-t border-blue-700 px-4 py-3 z-50">
        <div class="flex gap-3 overflow-x-auto">
            @foreach($session->players->sortByDesc('score') as $p)
                @php
                    $isSelf   = $p->id === $playerId;
                    $isTurn   = $p->id === $session->current_turn_player_id;
                @endphp
                <div class="flex-shrink-0 text-center min-w-[72px] rounded-xl px-3 py-2
                             {{ $isSelf  ? 'bg-yellow-400/20 ring-2 ring-yellow-400' : 'bg-blue-800' }}
                             {{ $isTurn  ? 'ring-2 ring-green-400' : '' }}">
                    <p class="text-xs truncate max-w-[72px]
                               {{ $isSelf ? 'text-yellow-300 font-semibold' : 'text-blue-300' }}">
                        @if($isTurn) ▶ @elseif($isSelf) ★ @endif{{ $p->name }}
                    </p>
                    <p class="font-bold text-sm
                               {{ $isSelf ? 'text-yellow-400' : 'text-yellow-300' }}">
                        ${{ number_format($p->score) }}
                    </p>
                    @if($isTurn)
                        <p class="text-green-400 text-[10px] font-bold leading-tight">TURN</p>
                    @endif
                </div>
            @endforeach
        </div>
    </div>

</div>

@script
<script>
    Alpine.data('hotspotTap', (wire) => ({
        tap(event) {
            const el   = event.currentTarget;
            const img  = el.querySelector('img');
            const rect = img.getBoundingClientRect();
            const x    = ((event.clientX - rect.left) / rect.width)  * 100;
            const y    = ((event.clientY - rect.top)  / rect.height) * 100;
            wire.submitHotspot(
                Math.max(0, Math.min(100, parseFloat(x.toFixed(2)))),
                Math.max(0, Math.min(100, parseFloat(y.toFixed(2))))
            );
        },
    }));

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
                    // Full resolution — no pixelation needed
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
</script>
@endscript
