<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-xl font-bold text-slate-900 dark:text-white">{{ __('auth_ui.verify.title') }}</h1>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('auth_ui.verify.subtitle') }}
        </p>
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 badge badge-emerald w-full justify-start rounded-lg px-3 py-2 text-sm">
            {{ __('auth_ui.verify.resent') }}
        </div>
    @endif

    <div class="flex items-center justify-between gap-3">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf
            <button type="submit" class="btn btn-brand">{{ __('auth_ui.verify.resend') }}</button>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">
                Wyloguj
            </button>
        </form>
    </div>
</x-guest-layout>
