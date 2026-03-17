<div class="py-10 px-4 space-y-6">

    {{-- ── Header ── --}}
    <div class="max-w-5xl mx-auto flex items-center justify-between">
        <h1 class="text-3xl font-bold text-gray-800 dark:text-zinc-100">
            {{ $boardId ? 'Edit Board' : 'New Jeopardy Board' }}
        </h1>
        <button wire:click="save"
                class="px-6 py-2 rounded-xl text-white font-semibold transition-opacity hover:opacity-80"
                style="background-color:#42B9BD;">
            Save Board
        </button>
    </div>

    @if(session('success'))
        <div class="max-w-5xl mx-auto bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200 px-4 py-3 rounded-xl">
            {{ session('success') }}
        </div>
    @endif

    {{-- ── Board meta ── --}}
    <div class="max-w-5xl mx-auto bg-white dark:bg-zinc-800 rounded-2xl shadow p-6 space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Board name *</label>
            <input wire:model="name" type="text" placeholder="My Awesome Jeopardy"
                   class="w-full border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-teal-400">
            @error('name')<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">Description</label>
            <textarea wire:model="description" rows="2" placeholder="Optional description..."
                      class="w-full border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-teal-400"></textarea>
        </div>
        <div class="flex items-center gap-3">
            <input wire:model="isPublic" type="checkbox" id="is_public" class="w-4 h-4 accent-teal-500">
            <label for="is_public" class="text-sm text-gray-700 dark:text-zinc-300">Public (visible to all users)</label>
        </div>
    </div>

    {{-- ── Categories (horizontal scroll) ── --}}
    <div class="overflow-x-auto pb-4">
        <div class="flex gap-4 items-start px-4" style="min-width: max-content;">

            @foreach($categories as $catIdx => $category)
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow p-5 space-y-4 flex-shrink-0 w-80">

                    {{-- Category header --}}
                    <div class="space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-bold uppercase tracking-widest text-teal-600 dark:text-teal-400">
                                Category {{ $catIdx + 1 }}
                            </span>
                            @if(count($categories) > 1)
                                <button wire:click="removeCategory({{ $catIdx }})"
                                        class="text-red-400 hover:text-red-600 text-xs font-medium px-2 py-0.5 rounded hover:bg-red-50 dark:hover:bg-red-950">
                                    Remove
                                </button>
                            @endif
                        </div>
                        <input wire:model="categories.{{ $catIdx }}.name"
                               type="text" placeholder="Category name"
                               class="w-full border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 rounded-xl px-3 py-2 text-sm font-semibold focus:outline-none focus:ring-2 focus:ring-teal-400">
                        @error("categories.{$catIdx}.name")<p class="text-red-500 text-xs mt-1">{{ $message }}</p>@enderror
                    </div>

                    {{-- Questions --}}
                    <div class="space-y-3">
                        @foreach($category['questions'] as $qIdx => $question)
                            <div class="border border-gray-200 dark:border-zinc-700 rounded-xl p-3 space-y-2 bg-gray-50 dark:bg-zinc-900/50">

                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <span class="text-xs text-gray-400 dark:text-zinc-500 font-medium">Q{{ $qIdx + 1 }}</span>
                                        <span class="text-xs text-gray-400">$</span>
                                        <input wire:model="categories.{{ $catIdx }}.questions.{{ $qIdx }}.points"
                                               type="number" min="1"
                                               class="w-20 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-teal-400">
                                    </div>
                                    @if(count($category['questions']) > 1)
                                        <button wire:click="removeQuestion({{ $catIdx }}, {{ $qIdx }})"
                                                class="text-red-400 hover:text-red-600 text-xs">✕</button>
                                    @endif
                                </div>

                                {{-- Type --}}
                                <select wire:model.live="categories.{{ $catIdx }}.questions.{{ $qIdx }}.question_type"
                                        class="w-full border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-teal-400">
                                    <option value="text">Text only</option>
                                    <option value="image">Image</option>
                                    <option value="zoom_image">Zoom image</option>
                                    <option value="pixelate_image">🎨 Pixelated image</option>
                                    <option value="audio">Audio</option>
                                    <option value="video">Video</option>
                                    <option value="youtube">YouTube</option>
                                    <option value="number_guess">Number Guess</option>
                                </select>

                                {{-- Question text --}}
                                <textarea wire:model="categories.{{ $catIdx }}.questions.{{ $qIdx }}.question_text"
                                          rows="2" placeholder="Question / clue…"
                                          class="w-full border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-teal-400"></textarea>

                                {{-- Answer --}}
                                <input wire:model="categories.{{ $catIdx }}.questions.{{ $qIdx }}.answer_text"
                                       type="{{ $category['questions'][$qIdx]['question_type'] === 'number_guess' ? 'number' : 'text' }}"
                                       placeholder="{{ $category['questions'][$qIdx]['question_type'] === 'number_guess' ? 'Correct number…' : 'Answer…' }}"
                                       class="w-full border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-teal-400">

                                {{-- Media upload for image/audio/video/pixelate --}}
                                @if(in_array($category['questions'][$qIdx]['question_type'], ['image','zoom_image','pixelate_image','audio','video']))
                                    <div>
                                        <label class="block text-xs text-gray-500 dark:text-zinc-400 mb-1">
                                            Upload media
                                            @if(!empty($category['questions'][$qIdx]['media_path']))
                                                <span class="text-green-600 dark:text-green-400">(saved)</span>
                                            @endif
                                        </label>
                                        <input wire:model="categories.{{ $catIdx }}.questions.{{ $qIdx }}.media_file"
                                               type="file"
                                               accept="{{ match($category['questions'][$qIdx]['question_type']) {
                                                   'image','zoom_image','pixelate_image' => 'image/*',
                                                   'audio'                               => 'audio/*',
                                                   'video'                               => 'video/*',
                                                   default                               => '*'
                                               } }}"
                                               class="text-xs text-gray-600 dark:text-zinc-300 w-full">
                                    </div>
                                @endif

                                {{-- YouTube URL --}}
                                @if($category['questions'][$qIdx]['question_type'] === 'youtube')
                                    <input wire:model="categories.{{ $catIdx }}.questions.{{ $qIdx }}.media_url"
                                           type="url" placeholder="https://youtube.com/watch?v=…"
                                           class="w-full border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-gray-900 dark:text-zinc-100 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-teal-400">
                                @endif

                                {{-- ── Hints ── --}}
                                <div class="border-t border-gray-200 dark:border-zinc-700 pt-2 mt-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <input wire:model.live="categories.{{ $catIdx }}.questions.{{ $qIdx }}.hints_enabled"
                                               type="checkbox"
                                               id="hints_{{ $catIdx }}_{{ $qIdx }}"
                                               class="w-3.5 h-3.5 accent-amber-500">
                                        <label for="hints_{{ $catIdx }}_{{ $qIdx }}"
                                               class="text-xs font-semibold text-amber-600 dark:text-amber-400 cursor-pointer select-none">
                                            💡 Enable hints
                                        </label>
                                    </div>

                                    @if(!empty($category['questions'][$qIdx]['hints_enabled']))
                                        <div class="space-y-1 pl-1">
                                            @foreach($category['questions'][$qIdx]['hints'] ?? [] as $hintIdx => $hint)
                                                <div class="flex items-center gap-1">
                                                    <span class="text-[10px] text-amber-500 font-bold w-4 text-center flex-shrink-0">
                                                        {{ $hintIdx + 1 }}
                                                    </span>
                                                    <input wire:model="categories.{{ $catIdx }}.questions.{{ $qIdx }}.hints.{{ $hintIdx }}"
                                                           type="text"
                                                           placeholder="Hint {{ $hintIdx + 1 }}…"
                                                           class="flex-1 border border-amber-300 dark:border-amber-700 bg-amber-50 dark:bg-amber-950/30 text-gray-900 dark:text-zinc-100 rounded-lg px-2 py-1 text-xs focus:outline-none focus:ring-2 focus:ring-amber-400">
                                                    <button wire:click="removeHint({{ $catIdx }}, {{ $qIdx }}, {{ $hintIdx }})"
                                                            class="text-red-400 hover:text-red-600 text-xs flex-shrink-0 px-1">✕</button>
                                                </div>
                                            @endforeach

                                            <button wire:click="addHint({{ $catIdx }}, {{ $qIdx }})"
                                                    class="text-amber-600 dark:text-amber-400 hover:text-amber-800 dark:hover:text-amber-300 text-xs font-medium mt-1">
                                                + Add hint
                                            </button>
                                        </div>
                                    @endif
                                </div>

                            </div>
                        @endforeach
                    </div>

                    <button wire:click="addQuestion({{ $catIdx }})"
                            class="text-teal-600 dark:text-teal-400 hover:text-teal-800 dark:hover:text-teal-300 text-xs font-medium w-full text-center py-1">
                        + Add question
                    </button>
                </div>
            @endforeach

            {{-- Add category card --}}
            <div class="flex-shrink-0 w-40 self-stretch">
                <button wire:click="addCategory"
                        class="h-full min-h-[120px] w-full flex flex-col items-center justify-center gap-2
                               border-2 border-dashed border-teal-400 rounded-2xl
                               text-teal-600 dark:text-teal-400 font-semibold text-sm
                               hover:bg-teal-50 dark:hover:bg-teal-950 transition-colors">
                    <span class="text-2xl leading-none">+</span>
                    <span>Add category</span>
                </button>
            </div>

        </div>
    </div>

</div>
