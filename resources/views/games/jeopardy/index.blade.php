@extends('layouts.app')

@section('content')
<div class="min-h-screen px-8 py-12 bg-gray-100 dark:bg-zinc-900">
    <div class="max-w-6xl mx-auto">

        {{-- Header --}}
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-4xl font-bold text-gray-800 dark:text-zinc-100">Jeopardy!</h1>
                <p class="text-gray-500 dark:text-zinc-400 mt-1">Browse boards or create your own.</p>
            </div>
            @auth
                <a href="{{ route('games.jeopardy.create') }}"
                   class="px-5 py-3 rounded-xl text-white font-semibold hover:opacity-80 transition-opacity"
                   style="background-color:#42B9BD;">
                    + New Board
                </a>
            @endauth
        </div>

        @if(session('success'))
            <div class="bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-3 rounded-xl mb-6">{{ session('success') }}</div>
        @endif

        {{-- My boards (logged-in) --}}
        @auth
            @if($myBoards->isNotEmpty())
                <h2 class="text-xl font-bold text-gray-700 dark:text-zinc-200 mb-4">My Boards</h2>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-10">
                    @foreach($myBoards as $board)
                        @include('games.jeopardy._board-card', ['board' => $board, 'isOwner' => true])
                    @endforeach
                </div>
            @endif
        @endauth

        {{-- Public boards --}}
        <h2 class="text-xl font-bold text-gray-700 dark:text-zinc-200 mb-4">Public Boards</h2>
        @if($boards->isEmpty())
            <p class="text-gray-400 dark:text-zinc-500">No public boards yet. Be the first!</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($boards as $board)
                    @include('games.jeopardy._board-card', ['board' => $board, 'isOwner' => false])
                @endforeach
            </div>
            <div class="mt-6">{{ $boards->links() }}</div>
        @endif

    </div>
</div>
@endsection
