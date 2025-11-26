<form action="{{ $action }}" method="POST" class="rounded-lg bg-white p-6 shadow-md">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @php
        // get the sports associated with the team for checking the checkboxes
        $teamSports = $team?->sports
            ->pluck('sport')
            ->pluck('value')
            ->toArray();
    @endphp

    <div>
        <x-inputs.input-label for="designation" class="font-semibold" :value="__('Designation')" />
        <x-inputs.text-input
            id="designation"
            name="designation"
            type="text"
            class="mt-1 block w-full"
            :value="old('designation', $team?->designation)"
            required
            autofocus
            autocomplete="designation"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('designation')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="mascot" class="font-semibold" :value="__('Mascot')" />
        <x-inputs.text-input
            id="mascot"
            name="mascot"
            type="text"
            class="mt-1 block w-full"
            :value="old('mascot', $team?->mascot)"
            required
            autocomplete="mascot"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('mascot')" />
    </div>

    <div class="mt-4">
        <label for="sports" class="block font-semibold">Sports</label>
        <div class="mt-2 space-y-2">
            @foreach ($sports as $sport)
                <div class="flex items-center">
                    <input
                        type="checkbox"
                        id="sport_{{ $sport }}"
                        name="sports[]"
                        value="{{ $sport }}"
                        {{ in_array($sport, old('sports', $teamSports ?? [])) ? 'checked' : '' }}
                        class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                    />
                    <label for="sport_{{ $sport }}" class="ml-2 text-sm">{{ ucfirst($sport) }}</label>
                </div>
            @endforeach
        </div>
        <x-inputs.input-error class="mt-2" :messages="$errors->get('sports')" />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('sports.*')" />
    </div>

    {{-- buttons --}}
    <div class="mt-4 flex items-center justify-end">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('teams.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endif
    </div>
</form>
