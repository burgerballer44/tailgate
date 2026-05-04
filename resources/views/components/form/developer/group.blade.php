@props(['group' => null, 'users' => collect(), 'action' => '', 'method' => 'POST'])

<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Group details"
            description="Enter the group name and assign an owner."
        >
            <div>
                <x-inputs.input-label for="name" class="font-semibold" :value="__('Name')" />
                <x-inputs.text-input
                    id="name"
                    name="name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('name', $group?->name)"
                    required
                    autofocus
                    autocomplete="name"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('name')" />
            </div>

            <div class="mt-4">
                <x-form.select
                    name="owner_id"
                    label="Owner"
                    :required="true"
                    :value="old('owner_id', $group?->owner?->id)"
                    :options="['' => ''] + $users->mapWithKeys(fn($user) => [$user->id => $user->name])->toArray()"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('owner_id')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.groups.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
