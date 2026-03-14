<div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-md overflow-hidden flex flex-col hover:shadow-lg transition-shadow duration-300">
    <div class="h-32 flex items-center justify-center text-5xl font-extrabold text-yellow-400"
         style="background-color:#042B7F; font-family:serif;">
        ?
    </div>
    <div class="p-5 flex flex-col flex-1 gap-2">
        <span class="text-xs font-semibold uppercase tracking-widest text-teal-600 dark:text-teal-400">
            {{ $board->categories()->count() }} categories · by {{ $board->owner->name }}
        </span>
        <h3 class="text-lg font-bold text-gray-800 dark:text-zinc-100">{{ $board->name }}</h3>
        @if($board->description)
            <p class="text-gray-500 dark:text-zinc-400 text-sm flex-1">{{ Str::limit($board->description, 80) }}</p>
        @endif

        <div class="flex gap-2 mt-3">
            @auth
                <form method="POST" action="{{ route('games.jeopardy.host.create', $board) }}">
                    @csrf
                    <button type="submit"
                            class="px-4 py-2 rounded-xl text-white text-sm font-semibold hover:opacity-80 transition-opacity"
                            style="background-color:#42B9BD;">
                        Host Game
                    </button>
                </form>
                @if($isOwner)
                    <a href="{{ route('games.jeopardy.edit', $board) }}"
                       class="px-4 py-2 rounded-xl border border-gray-300 dark:border-zinc-600 text-gray-600 dark:text-zinc-300 text-sm font-medium hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                        Edit
                    </a>
                    <form method="POST" action="{{ route('games.jeopardy.destroy', $board) }}"
                          onsubmit="return confirm('Delete this board?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="px-4 py-2 rounded-xl border border-red-200 dark:border-red-800 text-red-500 dark:text-red-400 text-sm font-medium hover:bg-red-50 dark:hover:bg-red-950 transition-colors">
                            Delete
                        </button>
                    </form>
                @endif
            @else
                <p class="text-xs text-gray-400 dark:text-zinc-500 italic">Log in to host this board</p>
            @endauth
        </div>
    </div>
</div>
