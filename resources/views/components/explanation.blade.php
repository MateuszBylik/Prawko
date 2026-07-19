@props(['question', 'chosenId' => null, 'auto' => false])
@php $aiEnabled = app(\App\Services\ExplanationService::class)->enabled(); @endphp

@if (filled($question->explanation))
    {{-- Zapisane wyjaśnienie (źródłowe lub wcześniej wygenerowane) --}}
    <div class="rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-slate-700 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-slate-200">
        <p class="mb-1 flex items-center gap-1.5 font-semibold text-brand-700 dark:text-brand-300">
            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.95-6.95-1.4 1.4M6.46 17.54l-1.42 1.42M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>
            {{ __('session.explanation.title') }}
        </p>
        {{ $question->explanation }}
    </div>
@elseif ($aiEnabled)
    <div x-data="{
            loading: false, text: '', error: false, done: false,
            load() {
                if (this.loading || this.done) return;
                this.loading = true; this.error = false;
                fetch('{{ route('questions.explain', $question) }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                        'Accept': 'application/json',
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ chosen_answer_id: {{ $chosenId ?? 'null' }} })
                })
                .then(r => r.json())
                .then(d => { this.loading = false; this.done = true; (d.available && d.explanation) ? this.text = d.explanation : this.error = true; })
                .catch(() => { this.loading = false; this.error = true; });
            }
         }"
         @if ($auto) x-init="load()" @endif>

        @unless ($auto)
            <button type="button" x-show="!text && !loading && !error" x-on:click="load()" class="btn btn-outline btn-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.95-6.95-1.4 1.4M6.46 17.54l-1.42 1.42M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>
                {{ __('session.explanation.generate') }}
            </button>
        @endunless

        <div x-show="loading" x-cloak class="flex items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
            <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8V0C5.4 0 0 5.4 0 12h4Z"/></svg>
            {{ __('session.explanation.generating') }}
        </div>

        <div x-show="text" x-cloak class="rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-slate-700 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-slate-200">
            <p class="mb-1 flex items-center gap-1.5 font-semibold text-brand-700 dark:text-brand-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2m0 14v2m9-9h-2M5 12H3m14.95-6.95-1.4 1.4M6.46 17.54l-1.42 1.42M12 8a4 4 0 1 0 0 8 4 4 0 0 0 0-8Z"/></svg>
                {{ __('session.explanation.ai_title') }}
            </p>
            <span x-text="text"></span>
        </div>

        <div x-show="error" x-cloak class="text-sm text-slate-400 dark:text-slate-500">{{ __('session.explanation.error') }}</div>
    </div>
@endif
