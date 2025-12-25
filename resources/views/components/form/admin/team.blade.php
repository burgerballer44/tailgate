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
        <x-inputs.input-label for="organization" class="font-semibold" :value="__('Organization')" />
        <x-inputs.text-input
            id="organization"
            name="organization"
            type="text"
            class="mt-1 block w-full"
            :value="old('organization', $team?->organization)"
            required
            autofocus
            autocomplete="organization"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('organization')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="designation" class="font-semibold" :value="__('Designation')" />
        <x-inputs.text-input
            id="designation"
            name="designation"
            type="text"
            class="mt-1 block w-full"
            :value="old('designation', $team?->designation)"
            required
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
            autocomplete="mascot"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('mascot')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="type" class="font-semibold" :value="__('Type')" />
        <select
            id="type"
            name="type"
            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
            required
        >
            <option value="">Select Type</option>
            @foreach (['College', 'Professional'] as $typeOption)
                <option value="{{ $typeOption }}" {{ old('type', $team?->type) === $typeOption ? 'selected' : '' }}>
                    {{ $typeOption }}
                </option>
            @endforeach
        </select>
        <x-inputs.input-error class="mt-2" :messages="$errors->get('type')" />
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
