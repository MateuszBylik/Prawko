@props(['code'])

@php
    use Illuminate\Support\Str;

    // Union Jack potrzebuje clipPath, a ten identyfikuje się po `id`. Flaga renderuje
    // się na stronie kilka razy (przycisk + lista), więc stały `id` dałby duplikaty
    // w DOM — przeglądarka użyłaby pierwszego i pozostałe flagi by się rozjechały.
    $uid = 'flag-'.Str::random(8);
@endphp

{{-- Flagi jako inline SVG, nie emoji.
     Emoji flag NIE MA na Windowsie — system nie dostarcza czcionki z tymi glifami
     i przeglądarka degraduje je do liter kraju („PL", „GB"), czyli ikona po prostu
     nie działa u dużej części użytkowników. SVG rysuje się wszędzie tak samo,
     nie wymaga żadnego żądania sieciowego i skaluje się z tekstem.

     Każda flaga ma WŁASNE proporcje (PL 5:8, DE 3:5, UA 2:3, GB 1:2) — nie wtłaczamy
     ich na siłę w jeden prostokąt, bo Union Jack ściśnięty do kwadratu wygląda źle.
     Zamiast tego stała jest WYSOKOŚĆ, a opakowanie ma stałą szerokość i centruje —
     dzięki temu etykiety w liście i tak są równo. `ring` daje obrys, bez którego
     biel polskiej flagi znika na jasnym tle. --}}
<span {{ $attributes->merge(['class' => 'inline-flex w-7 shrink-0 justify-center']) }} aria-hidden="true">
    @switch($code)
        @case('pl')
            <svg viewBox="0 0 16 10" class="h-3.5 w-auto rounded-[2px] ring-1 ring-slate-900/10 dark:ring-white/15">
                <rect width="16" height="5" fill="#fff"/>
                <rect y="5" width="16" height="5" fill="#DC143C"/>
            </svg>
            @break

        @case('de')
            <svg viewBox="0 0 5 3" class="h-3.5 w-auto rounded-[2px] ring-1 ring-slate-900/10 dark:ring-white/15">
                <rect width="5" height="1" fill="#000"/>
                <rect y="1" width="5" height="1" fill="#D00"/>
                <rect y="2" width="5" height="1" fill="#FFCE00"/>
            </svg>
            @break

        @case('uk')
            <svg viewBox="0 0 3 2" class="h-3.5 w-auto rounded-[2px] ring-1 ring-slate-900/10 dark:ring-white/15">
                <rect width="3" height="1" fill="#005BBB"/>
                <rect y="1" width="3" height="1" fill="#FFD500"/>
            </svg>
            @break

        @case('en')
            {{-- Union Jack: granatowe tło, biały krzyż św. Andrzeja (przekątne), na nim
                 czerwony krzyż św. Patryka przycięty do połówek przekątnych (stąd clipPath),
                 a na wierzchu biały i czerwony krzyż św. Jerzego. --}}
            <svg viewBox="0 0 60 30" class="h-3.5 w-auto rounded-[2px] ring-1 ring-slate-900/10 dark:ring-white/15">
                <clipPath id="{{ $uid }}">
                    <path d="M30,15 h30 v15 z v15 h-30 z h-30 v-15 z v-15 h30 z"/>
                </clipPath>
                <rect width="60" height="30" fill="#012169"/>
                <path d="M0,0 L60,30 M60,0 L0,30" stroke="#fff" stroke-width="6"/>
                <path d="M0,0 L60,30 M60,0 L0,30" stroke="#C8102E" stroke-width="4" clip-path="url(#{{ $uid }})"/>
                <path d="M30,0 V30 M0,15 H60" stroke="#fff" stroke-width="10"/>
                <path d="M30,0 V30 M0,15 H60" stroke="#C8102E" stroke-width="6"/>
            </svg>
            @break
    @endswitch
</span>
