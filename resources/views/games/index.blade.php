@extends('layouts.app')

@section('content')
<div class="min-h-screen px-8 py-12 bg-gray-100 dark:bg-zinc-900">

    {{-- Header --}}
    <div class="max-w-6xl mx-auto mb-10">
        <h1 class="text-4xl font-bold text-gray-800 dark:text-zinc-100">Games</h1>
        <p class="text-gray-500 dark:text-zinc-400 mt-2 text-lg">A collection of games I've built — from browser-based to Steam releases.</p>
    </div>

    {{-- Game Cards Grid --}}
    <div class="max-w-6xl mx-auto grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-8">

        {{-- Jeopardy Card --}}
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden flex flex-col hover:shadow-lg transition-shadow duration-300">
            <div class="h-48 flex items-center justify-center text-6xl" style="background-color: #042B7F;">
                🎯
            </div>
            <div class="p-6 flex flex-col flex-1">
                <span class="text-xs font-semibold uppercase tracking-widest text-teal-600 dark:text-teal-400 mb-1">Web Game</span>
                <h2 class="text-xl font-bold text-gray-800 dark:text-zinc-100 mb-2">Jeopardy</h2>
                <p class="text-gray-500 dark:text-zinc-400 text-sm flex-1">
                    A browser-based Jeopardy-style quiz game. Pick a category, choose a point value, and answer questions — coming soon!
                </p>
                <a href="{{ route('games.jeopardy.index') }}"
                   class="mt-5 inline-block text-center text-white font-semibold py-2 px-5 rounded-xl transition-opacity duration-200 hover:opacity-80"
                   style="background-color: #42B9BD;">
                    Play Now →
                </a>
            </div>
        </div>

        {{-- Example Steam Game Card (duplicate & edit for your real games) --}}
        {{--
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden flex flex-col hover:shadow-lg transition-shadow duration-300">
            <div class="h-48 flex items-center justify-center text-6xl bg-gray-800">
                🎮
            </div>
            <div class="p-6 flex flex-col flex-1">
                <span class="text-xs font-semibold uppercase tracking-widest text-blue-600 mb-1">Steam</span>
                <h2 class="text-xl font-bold text-gray-800 dark:text-zinc-100 mb-2">Your Game Title</h2>
                <p class="text-gray-500 dark:text-zinc-400 text-sm flex-1">Short description of your game here.</p>
                <a href="https://store.steampowered.com/app/YOUR_APP_ID"
                   target="_blank" rel="noopener noreferrer"
                   class="mt-5 inline-block text-center text-white font-semibold py-2 px-5 rounded-xl transition-opacity duration-200 hover:opacity-80 bg-blue-700">
                    View on Steam →
                </a>
            </div>
        </div>
        --}}

    </div>
</div>
@endsection
