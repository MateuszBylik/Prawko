<section>
    <header>
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('profile.info.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('profile.info.subtitle') }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-5">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" :value="__('profile.info.name')" />
            <x-text-input id="name" name="name" type="text" class="mt-1" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('profile.info.email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-slate-700 dark:text-slate-300">
                        {{ __('profile.info.unverified') }}
                        <button form="send-verification" class="font-medium text-brand-600 hover:underline dark:text-brand-400">
                            {{ __('profile.info.resend') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-sm font-medium text-emerald-600 dark:text-emerald-400">
                            {{ __('profile.info.resent') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Kategoria: tylko dla kont uczących się. Zmiana przestawia pulę pytań,
             skład arkusza i prognozę — dlatego mówimy o tym wprost przy polu. --}}
        @if ($categories->isNotEmpty())
            <div>
                <x-input-label for="driving_category_id" :value="__('app.category.profile_label')" />
                <select id="driving_category_id" name="driving_category_id" class="field mt-1">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                                @selected(old('driving_category_id', $user->driving_category_id) === $category->id)>
                            {{ $category->code }} — {{ $category->localizedName() }}
                        </option>
                    @endforeach
                </select>
                <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ __('app.category.profile_hint') }}
                </p>
                <x-input-error class="mt-2" :messages="$errors->get('driving_category_id')" />
            </div>
        @endif

        <div class="flex items-center gap-4">
            <x-primary-button>{{ __('profile.info.save') }}</x-primary-button>

            @if (session('status') === 'profile-updated')
                <p x-data="{ show: true }" x-show="show" x-transition x-init="setTimeout(() => show = false, 2000)"
                   class="text-sm text-slate-500 dark:text-slate-400">{{ __('profile.info.saved') }}</p>
            @endif
        </div>
    </form>
</section>
