@props(['user'])

@php $trophies = $user->trophies(); @endphp

@if ($trophies->isNotEmpty())
    <div {{ $attributes->merge(['class' => 'flex flex-wrap gap-1']) }}>
        @foreach ($trophies as $trophy)
            <span class="badge badge-amber gap-1"
                  title="{{ __('admin.users.trophy_title', ['code' => $trophy->category?->code ?? '—']) }}">
                <span aria-hidden="true">🏆</span> {{ $trophy->category?->code ?? '—' }}
            </span>
        @endforeach
    </div>
@endif
