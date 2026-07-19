<x-guest-layout>
    <div class="text-center">
        <span class="badge badge-amber mx-auto">{{ __('auth_ui.disabled.badge') }}</span>
        <h1 class="mt-4 font-display text-xl font-bold text-slate-900 dark:text-white">
            {{ __('auth_ui.disabled.title') }}
        </h1>
        <p class="mt-2 text-sm text-slate-500 dark:text-slate-400">
            {{ __('auth_ui.disabled.body') }}
        </p>
        <a href="{{ route('login') }}" class="btn btn-brand mt-6 w-full">{{ __('auth_ui.disabled.to_login') }}</a>
    </div>
</x-guest-layout>
