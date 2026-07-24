@props([
    'group' => null,
    'users' => collect(),
    'groupPolicies' => collect(), 'action' => '', 'method' => 'POST'])

<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Group details"
            description="Configure owner, capacity limits, and other settings used during debugging."
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

            <div class="mt-4 grid gap-4 md:grid-cols-3">
                <div>
                    <x-inputs.input-label for="member_limit" class="font-semibold" :value="__('Member limit')" />
                    <x-inputs.text-input
                        id="member_limit"
                        name="member_limit"
                        type="number"
                        min="1"
                        max="50"
                        class="mt-1 block w-full"
                        :value="old('member_limit', $group?->member_limit)"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('member_limit')" />
                </div>

                <div>
                    <x-inputs.input-label for="player_limit" class="font-semibold" :value="__('Player limit')" />
                    <x-inputs.text-input
                        id="player_limit"
                        name="player_limit"
                        type="number"
                        min="1"
                        max="10"
                        class="mt-1 block w-full"
                        :value="old('player_limit', $group?->player_limit)"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('player_limit')" />
                </div>

                <div>
                    <x-inputs.input-label for="follow_limit" class="font-semibold" :value="__('Follow limit')" />
                    <x-inputs.text-input
                        id="follow_limit"
                        name="follow_limit"
                        type="number"
                        min="1"
                        max="10"
                        class="mt-1 block w-full"
                        :value="old('follow_limit', $group?->follow_limit)"
                    />
                    <x-inputs.input-error class="mt-2" :messages="$errors->get('follow_limit')" />
                </div>
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
