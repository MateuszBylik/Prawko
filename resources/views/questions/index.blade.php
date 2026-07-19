<x-app-layout>
    <x-slot name="title">{{ __('questions.title') }}</x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            @php
                // Odmiana przez liczbę idzie przez trans_choice — każdy język ma własne
                // reguły (polski: 1/2-4/5+, angielski: 1/reszta), więc ręczne modulo
                // działałoby wyłącznie po polsku.
                $n = $questions->total();
            @endphp
            <div>
                <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('questions.title') }}</h1>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400 tnum">
                    {{ trans_choice('questions.count', $n, ['n' => number_format($n, 0, ',', ' ')]) }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ===== Filtry ===== --}}
        <form method="GET" action="{{ route('questions.index') }}" class="card p-4">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <label for="category" class="label">{{ __('questions.filters.category') }}</label>
                    <select id="category" name="category" onchange="this.form.submit()" class="field mt-1">
                        {{-- `! $categoryId` łapie i 0 (jawnie wybrane „wszystkie"), i null
                             (brak filtra — domyślnie u admina). Samo `=== 0` przepuszczało
                             null, a wtedy zaznaczenie wychodziło tylko przypadkiem: bo to
                             pierwsza opcja na liście. --}}
                        <option value="0" @selected(! $categoryId)>{{ __('questions.filters.all_categories') }}</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}" @selected((int) $categoryId === $category->id)>
                                {{ $category->code }} — {{ $category->localizedName() }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label for="type" class="label">{{ __('questions.filters.type') }}</label>
                    <select id="type" name="type" onchange="this.form.submit()" class="field mt-1">
                        <option value="" @selected($type === '')>{{ __('questions.filters.all_types') }}</option>
                        <option value="basic" @selected($type === 'basic')>{{ __('questions.filters.basic') }}</option>
                        <option value="specialist" @selected($type === 'specialist')>{{ __('questions.filters.specialist') }}</option>
                    </select>
                </div>

                <div>
                    <label for="media" class="label">{{ __('questions.filters.media') }}</label>
                    <select id="media" name="media" onchange="this.form.submit()" class="field mt-1">
                        <option value="" @selected($media === '')>{{ __('questions.filters.any') }}</option>
                        <option value="image" @selected($media === 'image')>{{ __('questions.filters.image') }}</option>
                        <option value="video" @selected($media === 'video')>{{ __('questions.filters.video') }}</option>
                        <option value="none" @selected($media === 'none')>{{ __('questions.filters.none') }}</option>
                    </select>
                </div>

                <div>
                    <label for="trap" class="label">{{ __('questions.filters.trap') }}</label>
                    <select id="trap" name="trap" onchange="this.form.submit()" class="field mt-1">
                        <option value="" @selected($trap === '')>{{ __('questions.filters.any_trap') }}</option>
                        @foreach (\App\Enums\TrapType::cases() as $tt)
                            <option value="{{ $tt->value }}" @selected($trap === $tt->value)>{{ $tt->label() }}</option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="mt-3">
                <label for="q" class="label">{{ __('questions.filters.search') }}</label>
                <div class="mt-1 flex gap-2">
                    <input id="q" name="q" value="{{ $search }}" type="search" placeholder="{{ __('questions.filters.search_placeholder') }}" class="field">
                    <button type="submit" class="btn btn-brand shrink-0 px-3">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M17 11a6 6 0 1 1-12 0 6 6 0 0 1 12 0Z"/></svg>
                    </button>
                </div>
            </div>

            @if ($type !== '' || $media !== '' || $trap !== '' || $search !== '' || $categoryId === 0)
                <div class="mt-3">
                    <a href="{{ route('questions.index') }}" class="text-xs font-medium text-brand-600 hover:underline dark:text-brand-400">
                        {{ __('questions.filters.clear') }}
                    </a>
                </div>
            @endif
        </form>

        {{-- ===== Wyniki ===== --}}
        @if ($questions->isEmpty())
            <div class="card p-10 text-center">
                <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('questions.empty') }}</p>
                <a href="{{ route('questions.index') }}" class="btn btn-outline btn-sm mt-4">{{ __('questions.filters.clear') }}</a>
            </div>
        @else
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($questions as $q)
                    <a href="{{ route('questions.show', $q) }}"
                       class="card group flex flex-col overflow-hidden transition hover:border-brand-300 hover:shadow-md dark:hover:border-brand-500/40">

                        {{-- Miniatura materiału. object-contain, żeby nie obcinać znaku;
                             onerror → czytelny placeholder zamiast ikony zepsutego obrazka. --}}
                        @if ($q->media_type->value === 'image' && $q->media_path)
                            <div class="h-40 w-full bg-slate-100 dark:bg-slate-800">
                                <img src="{{ route('media.show', ['name' => $q->media_path]) }}" loading="lazy" alt=""
                                     onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                                     class="h-40 w-full object-contain">
                                <div style="display:none" class="h-40 w-full flex-col items-center justify-center gap-1 text-slate-300 dark:text-slate-600">
                                    <svg class="h-8 w-8" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 3.75h16.5v16.5H3.75z"/></svg>
                                    <span class="text-xs">{{ __('questions.no_image') }}</span>
                                </div>
                            </div>
                        @elseif ($q->media_type->value === 'video')
                            <div class="flex h-40 w-full items-center justify-center bg-gradient-to-br from-slate-800 to-slate-900 text-slate-300">
                                <span class="flex flex-col items-center gap-1">
                                    <svg class="h-8 w-8" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                    <span class="text-xs font-medium">{{ __('questions.video') }}</span>
                                </span>
                            </div>
                        @else
                            <div class="flex h-40 w-full items-center justify-center bg-slate-100 text-slate-300 dark:bg-slate-800 dark:text-slate-600">
                                <svg class="h-9 w-9" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h7.5M8.25 12h7.5m-7.5 5.25h4.5M3.75 4.5h16.5v15H3.75z"/></svg>
                            </div>
                        @endif

                        <div class="flex flex-1 flex-col p-4">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="badge {{ $q->type->value === 'basic' ? 'badge-brand' : 'badge-amber' }}">
                                    {{ $q->type->label() }}
                                </span>
                                <span class="badge badge-slate">{{ $q->points }} pkt</span>
                                @foreach ($q->categories->take(3) as $cat)
                                    <span class="badge badge-slate">{{ $cat->code }}</span>
                                @endforeach
                            </div>
                            <p class="mt-2 line-clamp-3 text-sm text-slate-700 dark:text-slate-200">{{ $q->localizedText() }}</p>
                            <span class="mt-auto pt-3 text-xs font-mono text-slate-400">#{{ $q->external_id }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div>
                {{ $questions->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
