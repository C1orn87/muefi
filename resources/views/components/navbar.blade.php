<nav class="flex items-center justify-between px-8 py-5 sticky top-0 z-50 shadow-md" style="background-color: #42B9BD;">

    {{-- Brand --}}
    <a class="text-white font-bold text-2xl tracking-wide hover:opacity-75 transition-opacity" href="/">
        Muefi
    </a>

    {{-- Nav links --}}
    <div class="flex items-center gap-6">
        <a class="text-white text-lg font-medium hover:opacity-75 transition-opacity" href="/website">Websites</a>
        <a class="text-white text-lg font-medium hover:opacity-75 transition-opacity" href="/3D">3D Modelling</a>
        <a class="text-white text-lg font-medium hover:opacity-75 transition-opacity" href="/2D">2D Art</a>
        <a class="text-white text-lg font-medium hover:opacity-75 transition-opacity" href="/games">Games</a>
        <a class="text-white text-lg font-medium hover:opacity-75 transition-opacity" href="/contact">Contact</a>
        <a class="text-white text-lg font-medium hover:opacity-75 transition-opacity" href="/about">About Me</a>
    </div>

    {{-- Auth area + dark mode toggle (right side) --}}
    <div class="flex items-center gap-3">

        {{-- Dark mode toggle --}}
        <button
            @click="toggle()"
            type="button"
            title="Toggle dark mode"
            class="flex items-center justify-center w-9 h-9 rounded-full bg-white/20 hover:bg-white/30 transition text-white focus:outline-none"
            aria-label="Toggle dark mode"
        >
            {{-- Sun icon (shown in dark mode) --}}
            <svg x-show="isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
            {{-- Moon icon (shown in light mode) --}}
            <svg x-show="!isDark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>
        @auth
            {{-- ── Logged-in: avatar dropdown ───────────────────────── --}}
            <flux:dropdown position="bottom" align="end">

                {{-- Trigger button: avatar or initials --}}
                <button
                    type="button"
                    class="flex items-center rounded-full ring-2 ring-white/40 hover:ring-white transition focus:outline-none"
                >
                    @if (auth()->user()->avatarUrl())
                        <img
                            src="{{ auth()->user()->avatarUrl() }}"
                            alt="{{ auth()->user()->name }}"
                            class="h-10 w-10 rounded-full object-cover"
                        />
                    @else
                        <flux:avatar
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                            class="!h-10 !w-10"
                        />
                    @endif
                </button>

                {{-- Dropdown menu --}}
                <flux:menu class="min-w-52">
                    {{-- User info header --}}
                    <div class="flex items-center gap-3 px-3 py-2">
                        @if (auth()->user()->avatarUrl())
                            <img
                                src="{{ auth()->user()->avatarUrl() }}"
                                alt="{{ auth()->user()->name }}"
                                class="h-9 w-9 rounded-full object-cover shrink-0"
                            />
                        @else
                            <flux:avatar
                                :name="auth()->user()->name"
                                :initials="auth()->user()->initials()"
                                class="!h-9 !w-9 shrink-0"
                            />
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-semibold text-zinc-900 dark:text-white">
                                {{ auth()->user()->name }}
                            </p>
                            <p class="truncate text-xs text-zinc-500 dark:text-zinc-400">
                                {{ auth()->user()->email }}
                            </p>
                        </div>
                    </div>

                    <flux:menu.separator />

                    <flux:menu.item :href="route('profile.edit')" icon="user-circle">
                        {{ __('Profile & Settings') }}
                    </flux:menu.item>

                    <flux:menu.item :href="route('dashboard')" icon="squares-2x2">
                        {{ __('Dashboard') }}
                    </flux:menu.item>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item
                            as="button"
                            type="submit"
                            icon="arrow-right-start-on-rectangle"
                            class="w-full cursor-pointer text-red-600 hover:text-red-700"
                        >
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        @else
            {{-- ── Guest: Login button ───────────────────────────────── --}}
            <a
                href="{{ route('login') }}"
                class="inline-flex items-center gap-1.5 rounded-full bg-white/20 hover:bg-white/30 transition px-4 py-1.5 text-sm font-semibold text-white ring-1 ring-white/40"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                </svg>
                Log In
            </a>
        @endauth
    </div>{{-- end right side --}}

</nav>
