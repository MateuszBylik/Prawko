<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-slate-900 dark:text-white">{{ __('auth_ui.login.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('auth_ui.login.subtitle') }}</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="E-mail" />
            <x-text-input id="email" class="mt-1" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" :value="__('auth_ui.login.password')" />
            <div class="mt-1">
                <x-password-input id="password" name="password" required autocomplete="current-password" />
            </div>
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-slate-300 text-brand-600 shadow-sm focus:ring-brand-500 dark:border-slate-700 dark:bg-slate-900">
                <span class="ms-2 text-sm text-slate-600 dark:text-slate-400">{{ __('auth_ui.login.remember') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-400" href="{{ route('password.request') }}">
                    {{ __('auth_ui.login.forgot') }}
                </a>
            @endif
        </div>

        <button type="submit" class="btn btn-brand w-full">{{ __('auth_ui.login.submit') }}</button>
    </form>

    <p class="mt-6 text-center text-xs text-slate-400 dark:text-slate-500">
        {{ __('auth_ui.login.registration_off') }}
    </p>
</x-guest-layout>
