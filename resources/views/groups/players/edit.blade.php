@php
    $isManagingAnotherMember = auth()->id() !== $member->user_id;
@endphp

<x-layouts.app
    mainHeading="Edit player"
    mainDescription="Update player details {{ $isManagingAnotherMember ? 'for '.$member->user->name.' in' : 'in' }} {{ $group->name }}"
    :mainActions="[
        ['text' => 'Back', 'route' => $returnRouteName, 'params' => $returnRouteParams],
    ]"
>
    <div class="space-y-6">
        @if ($isManagingAnotherMember)
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-sm text-gray-700">
                    Editing player for <span class="font-semibold">{{ $member->user->name }}</span>.
                </p>
            </div>
        @endif

        <x-forms.multi-section-form
            action="{{ route($routeBaseName.'.update', ['group' => $group, 'member' => $member, 'player' => $player]) }}"
            method="PUT"
        >
            <x-slot name="sections">
                <x-forms.form-section
                    title="Player details"
                    description="Choose a display name for this player."
                >
                    <div>
                        <x-inputs.input-label for="player_name" class="font-semibold" :value="__('Player Name')" />
                        <x-inputs.text-input
                            id="player_name"
                            name="player_name"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('player_name', $player->player_name)"
                            required
                            autofocus
                            autocomplete="off"
                        />
                        <x-inputs.input-error class="mt-2" :messages="$errors->get('player_name')" />
                    </div>
                </x-forms.form-section>
            </x-slot>

            <x-slot name="buttons">
                <x-buttons.cancel-button>
                    <a href="{{ route($returnRouteName, $returnRouteParams) }}">
                        {{ __('Cancel') }}
                    </a>
                </x-buttons.cancel-button>

                <x-buttons.primary-button class="ms-4">
                    {{ __('Save changes') }}
                </x-buttons.primary-button>
            </x-slot>
        </x-forms.multi-section-form>
    </div>
</x-layouts.app>
