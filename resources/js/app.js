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
