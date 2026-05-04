@props(['player' => null, 'group' => null, 'member' => null, 'action' => '', 'method' => 'POST'])

<x-forms.multi-section-form :action="$action" :method="$method">
    <x-slot name="sections">
        <x-forms.form-section
            title="Player details"
            description="Enter the display name for this player."
        >
            <div>
                <x-inputs.input-label for="player_name" class="font-semibold" :value="__('Player Name')" />
                <x-inputs.text-input
                    id="player_name"
                    name="player_name"
                    type="text"
                    class="mt-1 block w-full"
                    :value="old('player_name', $player?->player_name)"
                    required
                    autofocus
                    autocomplete="player_name"
                />
                <x-inputs.input-error class="mt-2" :messages="$errors->get('player_name')" />
            </div>
        </x-forms.form-section>
    </x-slot>

    <x-slot name="buttons">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('developer.groups.members.players.index', [$group, $member]) }}">
                    {{ __('Cancel') }}
                </a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endisset
    </x-slot>
</x-forms.multi-section-form>
