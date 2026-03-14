<div class="min-h-screen bg-[#042B7F] text-white flex flex-col pb-24" wire:poll.2000ms>

    {{-- ── Game finished ── --}}
    @if($session->status === 'finished')
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
            $aq        = $session->activeQuestion;
            $sq        = $session->sessionQuestions->firstWhere('question_id', $aq->id);
            $zoomLevel = $sq ? $sq->zoom_level : 4;
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
                @endif
            </div>

            {{-- ── Buzzer button ── --}}
            @if($aq->question_type !== 'number_guess')
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

    {{-- ── Lobby/board view ── --}}
    @else
        <div class="flex-1 p-4">
            @php $categories = $session->board->categories; @endphp

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
                            @php $isRevealed = in_array($q->id, $revealedIds); @endphp
                            <div class="py-4 rounded-xl text-center font-extrabold text-lg
                                        {{ $isRevealed
                                            ? 'bg-blue-950 text-blue-900 opacity-30'
                                            : 'bg-blue-700 text-yellow-300' }}">
                                @if(!$isRevealed) ${{ $q->points }} @endif
                            </div>
                        @else
                            <div class="py-4 rounded-xl bg-blue-950 opacity-20"></div>
                        @endif
                    @endforeach
                </div>
            @endfor
        </div>
    @endif

    {{-- ── Sticky footer scoreboard ── --}}
    <div class="fixed bottom-0 left-0 right-0 bg-blue-900/95 backdrop-blur border-t border-blue-700 px-4 py-3 z-50">
        <div class="flex gap-3 overflow-x-auto">
            @foreach($session->players->sortByDesc('score') as $p)
                <div class="flex-shrink-0 text-center min-w-[72px]
                             {{ $p->id === $playerId ? 'bg-yellow-400/20 ring-2 ring-yellow-400' : 'bg-blue-800' }}
                             rounded-xl px-3 py-2">
                    <p class="text-xs truncate max-w-[72px]
                               {{ $p->id === $playerId ? 'text-yellow-300 font-semibold' : 'text-blue-300' }}">
                        {{ $p->id === $playerId ? '★ '.$p->name : $p->name }}
                    </p>
                    <p class="font-bold text-sm
                               {{ $p->id === $playerId ? 'text-yellow-400' : 'text-yellow-300' }}">
                        ${{ number_format($p->score) }}
                    </p>
                </div>
            @endforeach
        </div>
    </div>

</div>
