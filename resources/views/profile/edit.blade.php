<x-app-layout>
    <x-slot name="title">{{ __('profile.title') }}</x-slot>
    <x-slot name="header">
        <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('profile.title') }}</h1>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        @if ($trophies->isNotEmpty())
            <div class="card p-5 sm:p-7">
                @include('profile.partials.trophies')
            </div>
        @endif

        <div class="card p-5 sm:p-7">
            <div class="max-w-xl">
                @include('profile.partials.update-profile-information-form')
            </div>
        </div>

        <div class="card p-5 sm:p-7">
            <div class="max-w-xl">
                @include('profile.partials.update-password-form')
            </div>
        </div>

        <div class="card p-5 sm:p-7">
            <div class="max-w-xl">
                @include('profile.partials.delete-user-form')
            </div>
        </div>
    </div>
</x-app-layout>
