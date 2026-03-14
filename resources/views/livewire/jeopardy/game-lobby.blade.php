<div class="min-h-screen bg-[#042B7F] text-white px-6 py-10 flex flex-col items-center" wire:poll.3000ms>

    <h1 class="text-4xl font-extrabold tracking-widest text-yellow-400 uppercase mb-2" style="font-family:serif;">
        Jeopardy!
    </h1>
    <p class="text-blue-200 mb-8">{{ $session->board->name }}</p>

    {{-- Join link --}}
    <div class="bg-blue-900 rounded-2xl px-8 py-5 mb-8 text-center shadow-lg">
        <p class="text-blue-300 text-sm mb-1">Share this link to join:</p>
        <p class="text-yellow-300 font-bold text-xl tracking-widest">
            {{ url('/games/jeopardy/join/' . $session->code) }}
        </p>
        <p class="text-blue-300 text-sm mt-2">Code: <span class="font-bold text-white">{{ $session->code }}</span></p>
    </div>

    {{-- Players & teams --}}
    <div class="w-full max-w-2xl grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">

        {{-- Solo players --}}
        @php $soloPlayers = $session->players->whereNull('team_id'); @endphp
        @if($soloPlayers->isNotEmpty())
            <div class="bg-blue-800 rounded-2xl p-5">
                <h3 class="text-yellow-400 font-bold uppercase tracking-wide text-sm mb-3">Solo Players</h3>
                <ul class="space-y-2">
                    @foreach($soloPlayers as $player)
                        <li class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-400"></span>
                            <span>{{ $player->name }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- Teams --}}
        @foreach($session->teams as $team)
            <div class="bg-blue-800 rounded-2xl p-5">
                <h3 class="text-yellow-400 font-bold uppercase tracking-wide text-sm mb-3">
                    Team: {{ $team->name }}
                </h3>
                <ul class="space-y-2">
                    @foreach($team->players as $player)
                        <li class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-green-400"></span>
                            <span>{{ $player->name }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </div>

    {{-- Host controls --}}
    @auth
        @if(auth()->id() === $session->host_id)
            <button wire:click="startGame"
                    class="bg-yellow-400 text-blue-900 font-extrabold text-lg px-10 py-4 rounded-2xl shadow-xl hover:bg-yellow-300 transition-colors">
                Start Game →
            </button>
        @endif
    @endauth

    @guest
        <p class="text-blue-300 text-sm">Waiting for the host to start the game…</p>
    @endguest

</div>
