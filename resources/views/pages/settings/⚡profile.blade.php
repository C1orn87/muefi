<?php

use App\Concerns\ProfileValidationRules;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Validate;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {
    use ProfileValidationRules, WithFileUploads;

    public string $name = '';
    public string $email = '';

    #[Validate(['nullable', 'image', 'max:2048'])] // 2 MB max
    public $avatar = null;

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name  = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate($this->profileRules($user->id));

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        $this->dispatch('profile-updated', name: $user->name);
    }

    /**
     * Upload a new avatar image.
     */
    public function uploadAvatar(): void
    {
        $this->validateOnly('avatar');

        $user = Auth::user();

        // Delete previous avatar if one exists
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $this->avatar->store('avatars', 'public');

        $user->avatar = $path;
        $user->save();

        $this->avatar = null;

        $this->dispatch('avatar-updated');
    }

    /**
     * Remove the current avatar.
     */
    public function removeAvatar(): void
    {
        $user = Auth::user();

        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
            $user->avatar = null;
            $user->save();
        }

        $this->dispatch('avatar-updated');
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));

            return;
        }

        $user->sendEmailVerificationNotification();

        Session::flash('status', 'verification-link-sent');
    }

    #[Computed]
    public function hasUnverifiedEmail(): bool
    {
        return Auth::user() instanceof MustVerifyEmail && ! Auth::user()->hasVerifiedEmail();
    }

    #[Computed]
    public function showDeleteUser(): bool
    {
        return ! Auth::user() instanceof MustVerifyEmail
            || (Auth::user() instanceof MustVerifyEmail && Auth::user()->hasVerifiedEmail());
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading class="sr-only">{{ __('Profile Settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name, email, and profile picture')">

        {{-- ── Avatar Upload ─────────────────────────────────────────── --}}
        <div class="my-6">
            <flux:heading size="sm" class="mb-4">{{ __('Profile Picture') }}</flux:heading>

            <div class="flex items-center gap-6">
                {{-- Current avatar or initials --}}
                <div class="relative shrink-0">
                    @if (auth()->user()->avatar)
                        <img
                            src="{{ auth()->user()->avatarUrl() }}"
                            alt="{{ auth()->user()->name }}"
                            class="h-20 w-20 rounded-full object-cover ring-2 ring-white shadow"
                        />
                    @else
                        <flux:avatar
                            :name="auth()->user()->name"
                            :initials="auth()->user()->initials()"
                            class="!h-20 !w-20 !text-2xl"
                        />
                    @endif

                    {{-- Live preview while a new file is selected --}}
                    @if ($avatar)
                        <img
                            src="{{ $avatar->temporaryUrl() }}"
                            alt="Preview"
                            class="absolute inset-0 h-20 w-20 rounded-full object-cover ring-2 ring-blue-400 shadow"
                        />
                    @endif
                </div>

                <div class="flex flex-col gap-2">
                    {{-- File picker --}}
                    <div>
                        <label
                            for="avatar-upload"
                            class="cursor-pointer inline-flex items-center gap-2 rounded-md border border-zinc-300 bg-white px-3 py-1.5 text-sm font-medium text-zinc-700 shadow-sm hover:bg-zinc-50 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-200 dark:hover:bg-zinc-700 transition"
                        >
                            {{ __('Choose image') }}
                        </label>
                        <input
                            id="avatar-upload"
                            type="file"
                            wire:model="avatar"
                            accept="image/*"
                            class="sr-only"
                        />
                        <flux:text class="mt-1 text-xs text-zinc-500">JPG, PNG, GIF — max 2 MB</flux:text>
                    </div>

                    <div class="flex gap-2">
                        @if ($avatar)
                            <flux:button wire:click="uploadAvatar" variant="primary" size="sm">
                                {{ __('Save picture') }}
                            </flux:button>
                        @endif

                        @if (auth()->user()->avatar)
                            <flux:button wire:click="removeAvatar" variant="ghost" size="sm" class="text-red-500 hover:text-red-700">
                                {{ __('Remove') }}
                            </flux:button>
                        @endif
                    </div>

                    <x-action-message on="avatar-updated" class="text-sm text-green-600">
                        {{ __('Avatar updated!') }}
                    </x-action-message>
                </div>
            </div>
        </div>

        <flux:separator />

        {{-- ── Name & Email form ─────────────────────────────────────── --}}
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                @if ($this->hasUnverifiedEmail)
                    <div>
                        <flux:text class="mt-4">
                            {{ __('Your email address is unverified.') }}

                            <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                {{ __('Click here to re-send the verification email.') }}
                            </flux:link>
                        </flux:text>

                        @if (session('status') === 'verification-link-sent')
                            <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                {{ __('A new verification link has been sent to your email address.') }}
                            </flux:text>
                        @endif
                    </div>
                @endif
            </div>

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full" data-test="update-profile-button">
                        {{ __('Save') }}
                    </flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        @if ($this->showDeleteUser)
            <livewire:pages::settings.delete-user-form />
        @endif
    </x-pages::settings.layout>
</section>
