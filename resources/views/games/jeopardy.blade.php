@extends('layouts.app')

@section('content')
<div class="min-h-screen flex flex-col items-center justify-center px-8 py-16" style="background-color: #042B7F;">

    {{-- Back link --}}
    <div class="w-full max-w-2xl mb-8">
        <a href="{{ route('games.index') }}"
           class="text-blue-300 hover:text-white text-sm font-medium transition-colors duration-200">
            ← Back to Games
        </a>
    </div>

    {{-- Jeopardy Logo / Title --}}
    <div class="text-center mb-10">
        <div class="text-7xl mb-4">🎯</div>
        <h1 class="text-5xl font-extrabold tracking-widest text-yellow-400 uppercase" style="font-family: serif;">
            Jeopardy!
        </h1>
        <p class="text-blue-200 mt-4 text-lg max-w-md mx-auto">
            This game is under construction. Check back soon to test your knowledge!
        </p>
    </div>

    {{-- Coming Soon Badge --}}
    <div class="bg-yellow-400 text-blue-900 font-bold text-sm uppercase tracking-widest px-6 py-2 rounded-full shadow-lg mb-10">
        Coming Soon
    </div>

    {{-- Placeholder Board Preview --}}
    <div class="grid grid-cols-3 gap-3 opacity-40 pointer-events-none select-none" aria-hidden="true">
        @foreach(['Category 1', 'Category 2', 'Category 3'] as $cat)
            <div class="bg-blue-800 text-white text-center text-xs font-bold py-2 px-4 rounded-lg uppercase tracking-wide">
                {{ $cat }}
            </div>
        @endforeach
        @foreach([100, 200, 300, 400, 500] as $points)
            @for($i = 0; $i < 3; $i++)
                <div class="bg-blue-700 text-yellow-400 text-center font-extrabold text-xl py-4 rounded-lg shadow">
                    ${{ $points }}
                </div>
            @endfor
        @endforeach
    </div>

</div>
@endsection
