@php
    $playerCount = $member->players()->count();
    $playerLimit = $effectivePlayerLimit;
    $isManagingAnotherMember = auth()->id() !== $member->user_id;
@endphp

<x-layouts.app
    mainHeading="Create a new player"
    mainDescription="Add a new player {{ auth()->id() === $member->user_id ? 'to your roster' : 'for '.$member->user->name }} in {{ $group->name }}"
    :mainActions="[
        ['text' => 'Back', 'route' => $returnRouteName, 'params' => $returnRouteParams],
    ]"
>
    <div class="space-y-6">
        @if ($isManagingAnotherMember)
            <div class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3">
                <p class="text-sm text-gray-700">
                    Adding a player for <span class="font-semibold">{{ $member->user->name }}</span>.
                </p>
            </div>
        @endif

        {{-- Player Limit Warning --}}
        @if ($playerCount >= $playerLimit)
            <div class="rounded-lg border border-red-200 bg-red-50 p-4">
                <p class="text-sm font-semibold text-red-900">Player limit reached</p>
                <p class="mt-1 text-sm text-red-700">
                    Delete an existing player before creating a new one.
                </p>
            </div>
        @endif

        {{-- Create Player Form --}}
        <x-forms.multi-section-form
            action="{{ route($routeBaseName.'.store', ['group' => $group, 'member' => $member]) }}"
            method="POST"
        >
            <x-slot name="sections">
                <x-forms.form-section
                    title="Player details"
                    description="Choose a display name for your player. Names must be unique within your group."
                >
                    <div>
                        <x-inputs.input-label for="player_name" class="font-semibold" :value="__('Player Name')" />
                        <x-inputs.text-input
                            id="player_name"
                            name="player_name"
                            type="text"
                            class="mt-1 block w-full"
                            :value="old('player_name')"
                            placeholder="e.g., My Awesome Player"
                            required
                            autofocus
                            autocomplete="off"
                        />
                        <x-inputs.input-error class="mt-2" :messages="$errors->get('player_name')" />
                        <p class="mt-2 text-xs text-gray-500">
                            Player names must be unique across all players in {{ $group->name }}.
                        </p>
                    </div>
                </x-forms.form-section>
            </x-slot>

            <x-slot name="buttons">
                <x-buttons.cancel-button>
                    <a href="{{ route($returnRouteName, $returnRouteParams) }}">
                        {{ __('Cancel') }}
                    </a>
                </x-buttons.cancel-button>

                @if ($playerCount < $playerLimit)
                    <x-buttons.primary-button class="ms-4">
                        {{ __('Create Player') }}
                    </x-buttons.primary-button>
                @else
                    <button type="button" class="ms-4 inline-flex items-center rounded-md bg-gray-300 px-3 py-2 text-sm font-semibold text-gray-500 shadow-sm cursor-not-allowed" disabled>
                        {{ __('Player Limit Reached') }}
                    </button>
                @endif
            </x-slot>
        </x-forms.multi-section-form>
    </div>
</x-layouts.app>
