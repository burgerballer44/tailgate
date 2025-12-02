@props(['player' => null, 'group' => null, 'member' => null, 'action' => '', 'method' => 'POST'])

<form action="{{ $action }}" method="POST" class="rounded-lg bg-white p-6 shadow-md">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

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

    {{-- buttons --}}
    <div class="mt-4 flex items-center justify-end">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('groups.members.players.index', [$group, $member]) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endif
    </div>
</form>
