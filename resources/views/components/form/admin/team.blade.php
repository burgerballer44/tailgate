<form action="{{ $action }}" method="POST" class="rounded-lg bg-white p-6 shadow-md">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

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
        <x-form.select
            name="sport"
            label="Sport"
            :required="true"
            :value="old('sport', $team?->sport)"
            :options="['' => ''] + collect($sports)->mapWithKeys(fn($sport) => [$sport => ucfirst($sport)])->toArray()"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('sport')" />
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
