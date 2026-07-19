<x-app-layout>
    <x-slot name="title">{{ __('app.category.title') }}</x-slot>

    <div class="mx-auto max-w-2xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="card p-6 sm:p-8">
            <span class="badge badge-brand">{{ __('app.category.badge') }}</span>
            <h1 class="mt-3 font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ __('app.category.heading') }}
            </h1>
            <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                {{ __('app.category.lead') }}
            </p>

            <form method="POST" action="{{ route('category.store') }}" class="mt-6" x-data="{ selected: null }">
                @csrf

                <div class="grid gap-2 sm:grid-cols-2">
                    @foreach ($categories as $category)
                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition"
                               :class="selected === {{ $category->id }}
                                   ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200 dark:border-brand-400 dark:bg-brand-500/10 dark:ring-brand-500/30'
                                   : 'border-slate-200 hover:border-brand-300 dark:border-slate-800 dark:hover:border-brand-500/40'">
                            <input type="radio" name="driving_category_id" value="{{ $category->id }}"
                                   x-model.number="selected" class="sr-only">
                            <span class="mt-0.5 grid h-8 w-8 shrink-0 place-items-center rounded-lg text-sm font-bold transition"
                                  :class="selected === {{ $category->id }}
                                      ? 'bg-brand-600 text-white'
                                      : 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300'">
                                {{ $category->code }}
                            </span>
                            <span class="flex-1 text-sm text-slate-800 dark:text-slate-100">{{ $category->localizedName() }}</span>
                        </label>
                    @endforeach
                </div>

                @error('driving_category_id')
                    <p class="mt-3 text-sm text-rose-500">{{ $message }}</p>
                @enderror

                <button type="submit" :disabled="selected === null"
                        class="btn btn-brand mt-6 w-full disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto">
                    {{ __('app.category.submit') }}
                </button>
            </form>
        </div>
    </div>
</x-app-layout>
