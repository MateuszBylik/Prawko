@props(['pair'])
@php $aiEnabled = app(\App\Services\ExplanationService::class)->enabled(); @endphp

@if (filled($pair->difference))
    <div class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
        <span class="font-semibold">{{ __('session.twins.difference') }}</span> {{ $pair->difference }}
    </div>
@elseif ($aiEnabled)
    <div x-data="{
            loading: false, diff: '', error: false, done: false,
            load() {
                if (this.loading || this.done) return;
                this.loading = true;
                fetch('{{ route('twins.difference', $pair) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json'
                    }
                })
                .then(r => r.json())
                .then(d => { this.loading = false; this.done = true; (d.available && d.difference) ? this.diff = d.difference : this.error = true; })
                .catch(() => { this.loading = false; this.error = true; });
            }
         }">
        <button type="button" x-show="!diff && !loading && !error" x-on:click="load()" class="btn btn-outline btn-sm">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.95-6.95-1.4 1.4M6.46 17.54l-1.42 1.42M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>
            {{ __('session.twins.analyse') }}
        </button>
        <div x-show="loading" x-cloak class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/></svg>
            {{ __('session.twins.analysing') }}
        </div>
        <div x-show="diff" x-cloak class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-100">
            <span class="font-semibold">{{ __('session.twins.difference') }}</span> <span x-text="diff"></span>
        </div>
        <div x-show="error" x-cloak class="text-xs text-slate-400 dark:text-slate-500">{{ __('session.twins.error') }}</div>
    </div>
@endif
