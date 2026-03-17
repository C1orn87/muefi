@extends('layouts.game')

@section('content')
<div class="min-h-screen bg-[#042B7F] flex items-center justify-center px-4 py-10">
    <div class="bg-blue-900 rounded-2xl shadow-2xl w-full max-w-md p-8">

        <h1 class="text-3xl font-extrabold text-yellow-400 text-center tracking-widest uppercase mb-1"
            style="font-family:serif;">Jeopardy!</h1>
        <p class="text-blue-300 text-center mb-6">{{ $session->board->name }}</p>

        @if($errors->any())
            <div class="bg-red-800 text-red-200 rounded-xl px-4 py-3 mb-4 text-sm">
                @foreach($errors->all() as $e)<p>{{ $e }}</p>@endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('games.jeopardy.join.store', $session->code) }}"
              x-data="{ mode: '{{ old('mode', 'solo') }}' }">
            @csrf

            <div class="space-y-5">

                {{-- ── Name ── --}}
                <div>
                    <label class="block text-blue-300 text-sm font-medium mb-1">Your name *</label>
                    <input name="player_name" type="text" required value="{{ old('player_name') }}"
                           placeholder="Enter your name"
                           class="w-full bg-blue-800 border border-blue-600 rounded-xl px-4 py-3 text-white placeholder-blue-400 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>

                {{-- ── Play as ── --}}
                <div>
                    <label class="block text-blue-300 text-sm font-medium mb-2">Play as</label>

                    {{-- @change on the container delegates all radio changes here --}}
                    <div class="flex gap-2" @change="mode = $event.target.value">
                        {{-- Solo --}}
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="mode" value="solo" class="sr-only peer"
                                   {{ old('mode', 'solo') === 'solo' ? 'checked' : '' }}>
                            <div class="text-center py-2.5 rounded-xl text-sm font-semibold transition-colors
                                        bg-blue-700 text-blue-200
                                        peer-checked:bg-yellow-400 peer-checked:text-blue-900">
                                Solo
                            </div>
                        </label>

                        {{-- New team --}}
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="mode" value="new_team" class="sr-only peer"
                                   {{ old('mode') === 'new_team' ? 'checked' : '' }}>
                            <div class="text-center py-2.5 rounded-xl text-sm font-semibold transition-colors
                                        bg-blue-700 text-blue-200
                                        peer-checked:bg-yellow-400 peer-checked:text-blue-900">
                                New Team
                            </div>
                        </label>

                        {{-- Join team --}}
                        <label class="flex-1 cursor-pointer">
                            <input type="radio" name="mode" value="join_team" class="sr-only peer"
                                   {{ old('mode') === 'join_team' ? 'checked' : '' }}>
                            <div class="text-center py-2.5 rounded-xl text-sm font-semibold transition-colors
                                        bg-blue-700 text-blue-200
                                        peer-checked:bg-yellow-400 peer-checked:text-blue-900">
                                Join Team
                            </div>
                        </label>
                    </div>
                </div>

                {{-- ── New team name ── --}}
                <div x-show="mode === 'new_team'" style="display:none">
                    <label class="block text-blue-300 text-sm font-medium mb-1">Team name *</label>
                    <input name="team_name" type="text" value="{{ old('team_name') }}"
                           placeholder="Enter a team name"
                           :required="mode === 'new_team'"
                           class="w-full bg-blue-800 border border-blue-600 rounded-xl px-4 py-3 text-white placeholder-blue-400 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>

                {{-- ── Join existing team ── --}}
                <div x-show="mode === 'join_team'" style="display:none">
                    @if($session->teams->isNotEmpty())
                        <label class="block text-blue-300 text-sm font-medium mb-2">Choose a team</label>
                        <div class="space-y-2">
                            @foreach($session->teams as $team)
                                <label class="block cursor-pointer">
                                    <input type="radio" name="team_id" value="{{ $team->id }}"
                                           class="sr-only peer"
                                           {{ old('team_id') == $team->id ? 'checked' : '' }}>
                                    <div class="flex items-center justify-between px-4 py-3 rounded-xl border-2 transition-colors
                                                border-blue-700 bg-blue-800 text-white
                                                peer-checked:border-yellow-400 peer-checked:bg-yellow-400/10">
                                        <span class="font-semibold">{{ $team->name }}</span>
                                        <span class="text-blue-300 peer-checked:text-yellow-300 text-sm">
                                            {{ $team->players->count() }}
                                            {{ Str::plural('player', $team->players->count()) }}
                                        </span>
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="bg-blue-800 rounded-xl px-4 py-3 text-blue-300 text-sm italic">
                            No teams yet — switch to "New Team" to create one.
                        </div>
                    @endif
                </div>

                {{-- ── Submit ── --}}
                <button type="submit"
                        class="w-full bg-yellow-400 text-blue-900 font-extrabold py-3 rounded-xl hover:bg-yellow-300 transition-colors">
                    Join Game →
                </button>

            </div>
        </form>

    </div>
</div>
@endsection
