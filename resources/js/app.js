import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

window.Pusher = Pusher;
window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
});

document.addEventListener('alpine:init', () => {

    Alpine.data('darkMode', () => ({
        isDark: localStorage.getItem('theme') === 'dark'
            || (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches),

        toggle() {
            this.isDark = !this.isDark;
            localStorage.setItem('theme', this.isDark ? 'dark' : 'light');
        },
    }));

    // Join form: tracks which mode is selected to show/hide extra fields
    Alpine.data('joinForm', () => ({
        mode: document.querySelector('[name="mode"]:checked')?.value ?? 'solo',
    }));

});
