@props(['group' => null, 'teams' => collect(), 'availableSeasonsForFollow' => collect(), 'selectedSeasonIds' => collect(), 'action' => '', 'method' => 'POST'])

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
        <p class="text-sm font-medium text-gray-700">Seasons</p>
        <p class="mt-1 text-xs text-gray-500">Choose one or more active seasons for this team follow.</p>

        @php
            $selectedSeasonIdValues = collect(old('season_ids', $selectedSeasonIds->all() ?? []))
                ->map(fn ($seasonId) => (int) $seasonId)
                ->all();
        @endphp

        @if ($availableSeasonsForFollow->isNotEmpty())
            <div class="mt-2 grid gap-2 sm:grid-cols-2">
                @foreach ($availableSeasonsForFollow as $season)
                    <label class="flex items-start gap-2 rounded border border-gray-200 px-3 py-2 text-sm text-gray-700">
                        <input
                            type="checkbox"
                            name="season_ids[]"
                            value="{{ $season->id }}"
                            class="mt-0.5 h-4 w-4 rounded border-gray-300 text-navy focus:ring-navy"
                            {{ in_array($season->id, $selectedSeasonIdValues, true) ? 'checked' : '' }}
                        >
                        <span>{{ $season->name }}</span>
                    </label>
                @endforeach
            </div>
        @else
            <p class="mt-2 text-sm text-gray-500">No active seasons are currently available.</p>
        @endif

        <x-inputs.input-error class="mt-2" :messages="$errors->get('season_ids')" />
        <x-inputs.input-error class="mt-2" :messages="$errors->get('season_ids.*')" />
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