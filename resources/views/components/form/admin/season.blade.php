<form action="{{ $action }}" method="POST" class="rounded-lg bg-white p-6 shadow-md">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-inputs.input-label for="name" class="font-semibold" :value="__('Name')" />
        <x-inputs.text-input
            id="name"
            name="name"
            type="text"
            class="mt-1 block w-full"
            :value="old('name', $season?->name)"
            required
            autofocus
            autocomplete="name"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('name')" />
    </div>

    <div class="mt-4">
        <x-form.select
            name="sport"
            label="Sport"
            :required="true"
            :value="old('sport', $season?->sport)"
            :options="['' => ''] + $sports->mapWithKeys(fn($sport) => [$sport => ucfirst($sport)])->toArray()"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('sport')" />
    </div>

    <div class="mt-4">
        <x-form.select
            name="season_type"
            label="Season Type"
            :required="true"
            :value="old('season_type', $season?->season_type)"
            :options="['' => ''] + $seasonTypes->mapWithKeys(fn($type) => [$type => ucfirst($type)])->toArray()"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('season_type')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="season_start" class="font-semibold" :value="__('Season Start')" />
        <x-inputs.text-input
            id="season_start"
            name="season_start"
            type="date"
            class="mt-1 block w-full"
            :value="old('season_start', $season?->season_start)"
            required
            autocomplete="season_start"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('season_start')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="season_end" class="font-semibold" :value="__('Season End')" />
        <x-inputs.text-input
            id="season_end"
            name="season_end"
            type="date"
            class="mt-1 block w-full"
            :value="old('season_end', $season?->season_end)"
            required
            autocomplete="season_end"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('season_end')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="active" class="font-semibold" :value="__('Active')" />
        <input
            type="checkbox"
            id="active"
            name="active"
            value="1"
            {{ old('active', $season?->active) ? 'checked' : '' }}
            class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('active')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="active_date" class="font-semibold" :value="__('Active Date')" />
        <x-inputs.text-input
            id="active_date"
            name="active_date"
            type="date"
            class="mt-1 block w-full"
            :value="old('active_date', $season?->active_date)"
            autocomplete="active_date"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('active_date')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="inactive_date" class="font-semibold" :value="__('Inactive Date')" />
        <x-inputs.text-input
            id="inactive_date"
            name="inactive_date"
            type="date"
            class="mt-1 block w-full"
            :value="old('inactive_date', $season?->inactive_date)"
            autocomplete="inactive_date"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('inactive_date')" />
    </div>

    {{-- buttons --}}
    <div class="mt-4 flex items-center justify-end">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('seasons.index') }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endif
    </div>
</form>
