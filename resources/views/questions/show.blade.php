<x-app-layout>
    <x-slot name="title">{{ __('questions.show.title', ['id' => $question->external_id]) }}</x-slot>

    <div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ===== Pasek nawigacji ===== --}}
        <div class="flex items-center justify-between gap-2">
            <a href="{{ $backUrl }}" class="btn btn-ghost btn-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                {{ __('questions.show.back') }}
            </a>
            <div class="flex items-center gap-2">
                @if ($prevId)
                    <a href="{{ route('questions.show', $prevId) }}" class="btn btn-outline btn-sm">{{ __('questions.show.prev') }}</a>
                @endif
                @if ($nextId)
                    <a href="{{ route('questions.show', $nextId) }}" class="btn btn-outline btn-sm">{{ __('questions.show.next') }}</a>
                @endif
            </div>
        </div>

        <div class="card overflow-hidden">
            {{-- ===== Materiał (wspólny komponent) ===== --}}
            <x-question-media :question="$question" />

            <div class="p-5 sm:p-6">
                {{-- Plakietki --}}
                <div class="flex flex-wrap items-center gap-1.5">
                    <span class="badge {{ $question->type->value === 'basic' ? 'badge-brand' : 'badge-amber' }}">
                        {{ $question->type->label() }}
                    </span>
                    <span class="badge badge-slate">{{ __('session.review.points', ['n' => $question->points]) }}</span>
                    @foreach ($question->categories as $cat)
                        <span class="badge badge-slate">{{ $cat->code }}</span>
                    @endforeach
                    <span class="badge badge-slate font-mono">#{{ $question->external_id }}</span>
                    @if ($question->source === 'review')
                        <span class="badge badge-amber">{{ __('questions.show.in_review') }}</span>
                    @endif
                    @foreach ($question->trapTypes() as $tt)
                        <span class="badge {{ $tt->badgeClass() }}" title="{{ $tt->hint() }}">{{ $tt->label() }}</span>
                    @endforeach
                </div>

                {{-- Treść --}}
                <h1 class="mt-4 text-lg font-semibold leading-snug text-slate-900 dark:text-white sm:text-xl">
                    {{ $question->localizedText() }}
                </h1>

                {{-- Na co uważać (pułapki) --}}
                @php $traps = $question->trapTypes(); @endphp
                @if (! empty($traps))
                    <div class="mt-3 rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm dark:border-amber-500/30 dark:bg-amber-500/10">
                        <p class="mb-1 flex items-center gap-1.5 font-semibold text-amber-800 dark:text-amber-200">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                            {{ __('questions.show.watch_out') }}
                        </p>
                        <ul class="space-y-0.5 text-amber-900/90 dark:text-amber-100/90">
                            @foreach ($traps as $tt)
                                <li>• {{ $tt->hint() }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Odpowiedzi --}}
                <div class="mt-5 space-y-2.5">
                    @foreach ($question->answers as $a)
                        <div class="flex items-start gap-3 rounded-xl border px-4 py-3
                            {{ $a->is_correct
                                ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/40 dark:bg-emerald-500/10'
                                : 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' }}">

                            @if ($question->type->value === 'specialist')
                                <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold
                                    {{ $a->is_correct ? 'bg-emerald-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300' }}">
                                    {{ $a->label }}
                                </span>
                            @endif

                            <span class="flex-1 text-sm {{ $a->is_correct ? 'font-semibold text-emerald-800 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-200' }}">
                                {{ $question->localizedAnswerText($a) }}
                            </span>

                            @if ($a->is_correct)
                                <svg class="h-5 w-5 shrink-0 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                            @endif
                        </div>
                    @endforeach
                </div>

                {{-- Zastrzeżenie do odpowiedzi katalogowej — przed wyjaśnieniem, bo
                     wyjaśnienie AI opisuje katalog i bez tego kontekstu wprowadza w błąd. --}}
                <x-disputed-answer :question="$question" class="mt-5" />

                {{-- Wyjaśnienie: zapisane albo generowane AI na żądanie --}}
                <div class="mt-5">
                    <x-explanation :question="$question" />
                </div>

                {{-- Tłumaczenia. Zawsze pokazujemy JĘZYKI INNE niż wyświetlany (ten jest
                     już w treści wyżej), z polskim na pierwszym miejscu jako oryginał. --}}
                @php $variants = $question->languageVariants(); @endphp
                @if (! empty($variants))
                    <details class="group mt-5">
                        <summary class="flex cursor-pointer items-center gap-2 text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">
                            <svg class="h-4 w-4 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            {{ __('questions.show.translations') }}
                        </summary>
                        <div class="mt-3 space-y-3">
                            @foreach ($variants as $variant)
                                <div class="rounded-xl border border-slate-200 p-3 dark:border-slate-800">
                                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-400">{{ $variant['name'] }}</div>
                                    <p class="mt-1 text-sm text-slate-700 dark:text-slate-200">{{ $variant['q'] }}</p>
                                    @foreach ($variant['options'] as $label => $text)
                                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                                            <span class="font-mono font-semibold uppercase">{{ $label }}.</span> {{ $text }}
                                        </p>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    </details>
                @endif
            </div>
        </div>

        {{-- Pytania łatwe do pomylenia (pary mylące) --}}
        @if ($question->similars->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="flex items-center gap-2 text-base font-semibold text-slate-900 dark:text-white">
                        <svg class="h-5 w-5 text-amber-500" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                        {{ __('questions.show.twins_title') }}
                    </h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('questions.show.twins_lead') }}</p>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($question->similars->take(5) as $sim)
                        @php $sq = $sim->similarQuestion; @endphp
                        @if ($sq)
                            <div class="px-5 py-3.5">
                                <div class="flex items-center gap-2">
                                    <span class="badge {{ $sq->type->value === 'basic' ? 'badge-brand' : 'badge-amber' }}">{{ $sq->type->label() }}</span>
                                    <span class="badge badge-slate">{{ __('questions.show.similar', ['n' => $sim->scorePercent()]) }}</span>
                                </div>
                                <a href="{{ route('questions.show', $sq) }}"
                                   class="mt-1.5 block text-sm text-slate-700 transition hover:text-brand-700 dark:text-slate-200 dark:hover:text-brand-300">
                                    {{ $sq->localizedText() }}
                                </a>
                                <div class="mt-2">
                                    <x-twin-difference :pair="$sim" />
                                </div>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
