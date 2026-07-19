<section>
    <header>
        <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-white">
            <span aria-hidden="true">🏆</span> {{ __('profile.trophies.title') }}
        </h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('profile.trophies.subtitle') }}</p>
    </header>

    <div class="mt-5 grid gap-3 sm:grid-cols-2">
        @foreach ($trophies as $trophy)
            <div class="flex items-center gap-3 rounded-xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                <span class="grid h-12 w-12 shrink-0 place-items-center rounded-full bg-amber-100 text-2xl dark:bg-amber-500/20" aria-hidden="true">🏆</span>
                <div class="min-w-0">
                    <div class="font-display text-lg font-bold text-amber-900 dark:text-amber-100">
                        {{ __('profile.trophies.category', ['code' => $trophy->category?->code ?? '—']) }}
                    </div>
                    @if ($trophy->category)
                        <div class="truncate text-xs text-amber-800/80 dark:text-amber-200/70">{{ $trophy->category->localizedName() }}</div>
                    @endif
                    <div class="mt-0.5 text-xs text-amber-700 dark:text-amber-300/80">
                        {{ __('profile.trophies.passed_on', ['date' => $trophy->taken_on->translatedFormat('j F Y')]) }}
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</section>
