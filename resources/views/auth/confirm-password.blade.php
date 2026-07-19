<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-slate-900 dark:text-white">{{ __('auth_ui.confirm.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('auth_ui.confirm.subtitle') }}
        </p>
    </div>

    <form method="POST" action="{{ route('password.confirm') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="password" :value="__('auth_ui.confirm.password')" />
            <x-text-input id="password" class="mt-1" type="password" name="password" required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <button type="submit" class="btn btn-brand w-full">{{ __('auth_ui.confirm.submit') }}</button>
    </form>
</x-guest-layout>
