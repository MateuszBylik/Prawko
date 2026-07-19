@props(['user'])

@php
    // Te same warunki, których pilnuje ImpersonationController — przycisk nie ma
    // prowadzić do 403. Kontroler pozostaje jedyną prawdziwą bramką; to tylko UI.
    $available = ! $user->isAdmin() && ! $user->is(Auth::user());
@endphp

@if ($available)
    <form method="POST" action="{{ route('admin.users.impersonate', $user) }}" class="shrink-0">
        @csrf
        <button type="submit" class="btn btn-outline btn-sm"
                title="{{ __('admin.impersonate.title') }}">
            <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
            </svg>
            {{ __('admin.impersonate.button') }}
        </button>
    </form>
@endif
