<x-app-layout>
    <x-slot name="title">{{ __('admin.users.title') }}</x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('admin.users.title') }}</h1>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ __('admin.dashboard.subtitle') }}</p>
            </div>
            <a href="{{ route('admin.users.create') }}" class="btn btn-brand btn-sm">{{ __('admin.dashboard.new_account') }}</a>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ===== Podsumowanie ===== --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
            @foreach ([
                [__('admin.users.total_users'), $totals['users']],
                [__('admin.users.active_7d'), $totals['active_7d']],
                [__('admin.users.total_sessions'), $totals['sessions']],
                [__('admin.users.total_answers'), $totals['answers']],
            ] as [$label, $value])
                <div class="card p-4">
                    <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</div>
                    <div class="mt-1 font-display text-2xl font-bold tnum text-slate-900 dark:text-white">
                        {{ number_format($value, 0, ',', ' ') }}
                    </div>
                </div>
            @endforeach
        </div>

        {{-- ===== Lista: karty na mobile, tabela od sm ===== --}}
        <div class="space-y-3 sm:hidden">
            @foreach ($users as $user)
                <div class="card space-y-3 p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="truncate font-semibold text-slate-900 dark:text-white">{{ $user->name }}</div>
                            <div class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</div>
                            <x-trophy-badges :user="$user" class="mt-1.5" />
                        </div>
                        <span class="badge {{ $user->isAdmin() ? 'badge-brand' : 'badge-slate' }} shrink-0">
                            {{ $user->role->label() }}
                        </span>
                    </div>
                    <div class="grid grid-cols-3 gap-2 border-t border-slate-100 pt-3 text-center dark:border-slate-800">
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('admin.users.col_answers') }}</div>
                            <div class="font-semibold tnum text-slate-900 dark:text-white">{{ number_format($user->answers_count, 0, ',', ' ') }}</div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('admin.users.col_avg') }}</div>
                            <div class="font-semibold tnum text-slate-900 dark:text-white">
                                {{ $user->avg_percent !== null ? round($user->avg_percent).'%' : '—' }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('admin.users.col_exams') }}</div>
                            <div class="font-semibold tnum text-slate-900 dark:text-white">
                                {{ $user->exams_taken ? $user->exams_passed.'/'.$user->exams_taken : '—' }}
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="text-xs text-slate-500 dark:text-slate-400">
                            {{ __('admin.users.last_active') }}
                            <span class="font-medium text-slate-700 dark:text-slate-300">
                                {{ $user->last_active_at ? \Illuminate\Support\Carbon::parse($user->last_active_at)->diffForHumans() : __('admin.users.never') }}
                            </span>
                        </div>
                        <x-impersonate-button :user="$user" />
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card hidden overflow-x-auto sm:block">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:text-slate-400">
                    <tr>
                        <th class="px-4 py-3 font-semibold">{{ __('admin.users.col_user') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('admin.users.col_role') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('admin.users.col_answers') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('admin.users.col_mastered') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('admin.users.col_avg') }}</th>
                        <th class="px-4 py-3 text-right font-semibold">{{ __('admin.users.col_exams') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('admin.users.col_exam_date') }}</th>
                        <th class="px-4 py-3 font-semibold">{{ __('admin.users.col_activity') }}</th>
                        <th class="px-4 py-3 text-right font-semibold"><span class="sr-only">{{ __('admin.users.col_activity') }}</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($users as $user)
                        <tr class="align-middle">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900 dark:text-white">{{ $user->name }}</div>
                                <div class="text-xs text-slate-500 dark:text-slate-400">{{ $user->email }}</div>
                                <x-trophy-badges :user="$user" class="mt-1.5" />
                            </td>
                            <td class="px-4 py-3">
                                <span class="badge {{ $user->isAdmin() ? 'badge-brand' : 'badge-slate' }}">{{ $user->role->label() }}</span>
                            </td>
                            <td class="px-4 py-3 text-right tnum text-slate-700 dark:text-slate-300">
                                {{ number_format($user->answers_count, 0, ',', ' ') }}
                            </td>
                            <td class="px-4 py-3 text-right tnum text-slate-700 dark:text-slate-300">
                                {{ number_format($user->mastered, 0, ',', ' ') }}
                            </td>
                            <td class="px-4 py-3 text-right tnum text-slate-700 dark:text-slate-300">
                                {{ $user->avg_percent !== null ? round($user->avg_percent).'%' : '—' }}
                            </td>
                            <td class="px-4 py-3 text-right tnum text-slate-700 dark:text-slate-300">
                                {{ $user->exams_taken ? $user->exams_passed.'/'.$user->exams_taken : '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-700 dark:text-slate-300">
                                {{ $user->exam_date?->translatedFormat('j M Y') ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-slate-500 dark:text-slate-400">
                                {{ $user->last_active_at ? \Illuminate\Support\Carbon::parse($user->last_active_at)->diffForHumans() : __('admin.users.never') }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end">
                                    <x-impersonate-button :user="$user" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
