<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-slate-900 dark:text-white">{{ __('auth_ui.forgot.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('auth_ui.forgot.subtitle') }}
        </p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button type="submit" class="btn btn-brand w-full">{{ __('auth_ui.forgot.submit') }}</button>
    </form>

    <p class="mt-6 text-center text-sm">
        <a href="{{ route('login') }}" class="font-medium text-brand-600 hover:underline dark:text-brand-400">{{ __('auth_ui.forgot.back') }}</a>
    </p>
</x-guest-layout>
