<section class="space-y-6">
    <header>
        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('profile.delete.title') }}</h2>
        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
            {{ __('profile.delete.subtitle') }}
        </p>
    </header>

    <x-danger-button x-data="" x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')">
        {{ __('profile.delete.button') }}
    </x-danger-button>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-6">
            @csrf
            @method('delete')

            <h2 class="text-base font-semibold text-slate-900 dark:text-white">
                {{ __('profile.delete.confirm_title') }}
            </h2>

            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                {{ __('profile.delete.confirm_body') }}
            </p>

            <div class="mt-6">
                <x-input-label for="password" :value="__('profile.delete.password')" class="sr-only" />
                <x-text-input id="password" name="password" type="password" class="mt-1 block w-3/4" placeholder="{{ __('profile.delete.password') }}" />
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button x-on:click="$dispatch('close')">{{ __('profile.delete.cancel') }}</x-secondary-button>
                <x-danger-button>{{ __('profile.delete.confirm_button') }}</x-danger-button>
            </div>
        </form>
    </x-modal>
</section>
