@props(['group' => null, 'teams' => collect(), 'action' => '', 'method' => 'POST'])

<form action="{{ $action }}" method="POST" class="rounded-lg bg-white p-6 shadow-md">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-form.select
            name="team_id"
            label="Team"
            :required="true"
            :value="old('team_id')"
            :options="['' => ''] + $teams->mapWithKeys(fn($team) => [$team->id => $team->display_name])->toArray()"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('team_id')" />
    </div>

    <div class="mt-4">
        <x-form.select
            name="sport"
            label="Sport scope"
            :required="false"
            :value="old('sport')"
            :options="['' => 'All sports'] + collect(\App\Models\Sport::cases())->mapWithKeys(fn ($sport) => [$sport->value => $sport->value])->toArray()"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('sport')" />
    </div>

    {{-- buttons --}}
    <div class="mt-4 flex items-center justify-end">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('groups.show', $group) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Follow Team') }}
            </x-buttons.primary-button>
        @endif
    </div>
</form>
