<form action="{{ $action }}" method="POST" class="rounded-lg bg-white p-6 shadow-md">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <x-form.select
            name="home_team_id"
            label="Home Team"
            :required="true"
            :value="old('home_team_id', $game?->home_team_id)"
            :options="['' => ''] + $teams->pluck('name', 'id')->toArray()"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('home_team_id')" />
    </div>

    <div class="mt-4">
        <x-form.select
            name="away_team_id"
            label="Away Team"
            :required="true"
            :value="old('away_team_id', $game?->away_team_id)"
            :options="['' => ''] + $teams->pluck('name', 'id')->toArray()"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('away_team_id')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="home_team_score" class="font-semibold" :value="__('Home Team Score')" />
        <x-inputs.text-input
            id="home_team_score"
            name="home_team_score"
            type="number"
            class="mt-1 block w-full"
            :value="old('home_team_score', $game?->home_team_score)"
            min="0"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('home_team_score')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="away_team_score" class="font-semibold" :value="__('Away Team Score')" />
        <x-inputs.text-input
            id="away_team_score"
            name="away_team_score"
            type="number"
            class="mt-1 block w-full"
            :value="old('away_team_score', $game?->away_team_score)"
            min="0"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('away_team_score')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="start_date" class="font-semibold" :value="__('Start Date')" />
        <x-inputs.text-input
            id="start_date"
            name="start_date"
            type="text"
            class="mt-1 block w-full"
            :value="old('start_date', $game?->start_date)"
            required
            placeholder="e.g., 2024-10-01 or TBD"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('start_date')" />
    </div>

    <div class="mt-4">
        <x-inputs.input-label for="start_time" class="font-semibold" :value="__('Start Time')" />
        <x-inputs.text-input
            id="start_time"
            name="start_time"
            type="text"
            class="mt-1 block w-full"
            :value="old('start_time', $game?->start_time)"
            required
            placeholder="e.g., 7:00 PM or TBD"
        />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('start_time')" />
    </div>

    {{-- buttons --}}
    <div class="mt-4 flex items-center justify-end">
        @isset($buttons)
            {{ $buttons }}
        @else
            <x-buttons.cancel-button>
                <a href="{{ route('seasons.games.index', $season) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        @endif
    </div>
</form>
