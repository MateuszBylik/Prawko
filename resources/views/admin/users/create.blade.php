<x-app-layout>
    <x-slot name="title">{{ __('admin.create.title') }}</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('admin.create.title') }}</h1>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ __('admin.create.subtitle') }}</p>
        </div>
    </x-slot>

    <div class="mx-auto max-w-lg space-y-5 px-4 py-6 sm:px-6 lg:px-8">
        {{-- `role` w x-data steruje widocznością pola kategorii: admin się nie uczy,
             więc kategoria go nie dotyczy (walidacja w StoreUserRequest robi to samo
             po stronie serwera). --}}
        <form method="POST" action="{{ route('admin.users.store') }}" class="card space-y-4 p-5 sm:p-6"
              x-data="{ role: '{{ old('role', 'student') }}' }">
            @csrf

            <div>
                <x-input-label for="name" :value="__('admin.create.name')" />
                <x-text-input id="name" name="name" type="text" class="field mt-1" :value="old('name')" required autofocus autocomplete="name" />
                <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="email" :value="__('admin.create.email')" />
                <x-text-input id="email" name="email" type="email" class="field mt-1" :value="old('email')" required autocomplete="username" />
                <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="role" :value="__('admin.create.role')" />
                <select id="role" name="role" class="field mt-1" required x-model="role">
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', 'student') === $role->value)>
                            {{ $role->label() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('role')" class="mt-2" />
            </div>

            <div x-show="role !== 'admin'" x-cloak>
                <x-input-label for="driving_category_id" :value="__('app.category.profile_label')" />
                <select id="driving_category_id" name="driving_category_id" class="field mt-1">
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}"
                                @selected(old('driving_category_id', $categories->firstWhere('code', 'B')?->id) == $category->id)>
                            {{ $category->code }} — {{ $category->localizedName() }}
                        </option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('driving_category_id')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password" :value="__('admin.create.password')" />
                <x-text-input id="password" name="password" type="password" class="field mt-1" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="password_confirmation" :value="__('admin.create.password_confirm')" />
                <x-text-input id="password_confirmation" name="password_confirmation" type="password" class="field mt-1" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-4 dark:border-slate-800">
                <a href="{{ route('admin.users.index') }}" class="btn btn-ghost btn-sm">{{ __('admin.create.cancel') }}</a>
                <button type="submit" class="btn btn-brand btn-sm">{{ __('admin.create.submit') }}</button>
            </div>
        </form>
    </div>
</x-app-layout>
