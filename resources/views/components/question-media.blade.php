@props(['question', 'preload' => 'metadata'])
@php
    $img = $question->imageUrl();
    $video = $question->videoUrl();
    $mediaType = $question->media_type->value;
@endphp

@if ($mediaType === 'image')
    <div class="bg-slate-100 dark:bg-slate-800">
        @if ($img)
            <img src="{{ $img }}" alt="" loading="lazy"
                 onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';"
                 class="mx-auto max-h-[26rem] w-full object-contain">
        @endif
        <div class="{{ $img ? 'hidden' : 'flex' }} flex-col items-center justify-center gap-2 px-6 py-12 text-center text-slate-300 dark:text-slate-600">
            <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 3.75h16.5v16.5H3.75z"/>
            </svg>
            <span class="text-sm">{{ __('session.media.image_missing') }}</span>
        </div>
    </div>
@elseif ($mediaType === 'video')
    @if ($video)
        <video controls preload="{{ $preload }}" playsinline
               class="max-h-[26rem] w-full bg-black object-contain">
            <source src="{{ $video }}" type="video/mp4">
            {{ __('session.media.no_video_support') }}
        </video>
    @else
        <div class="flex flex-col items-center justify-center gap-2 bg-slate-100 px-6 py-12 text-center text-slate-400 dark:bg-slate-800 dark:text-slate-500">
            <svg class="h-10 w-10" fill="none" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                <path d="M3 3l18 18M10.5 8.25 15.75 12 10.5 15.75V8.25ZM3.75 6.75h12.5v10.5H3.75zM19.5 8.4l1.6-.9v9l-4.3-2.4"/>
            </svg>
            <p class="text-sm font-medium">{{ __('session.media.video_missing') }}</p>
        </div>
    @endif
@endif
