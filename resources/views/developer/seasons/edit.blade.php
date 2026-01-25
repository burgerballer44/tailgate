<x-layouts.app
    mainHeading="Edit Season"
    mainDescription="Update the details of the season."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'developer.seasons.index'],
        ['text' => 'View Season', 'route' => 'developer.seasons.show', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('developer.seasons.index')],
            ['text' => $season->name, 'url' => route('developer.seasons.show', $season)],
            ['text' => 'Edit Season', 'active' => true],
        ]"
    />
    <x-form.developer.season
        :action="route('developer.seasons.update', $season)"
        :method="'PUT'"
        :season="$season"
        :sports="$sports"
        :seasonTypes="$seasonTypes"
    >
        <x-slot name="buttons">
            <x-buttons.cancel-button>
                <a href="{{ route('developer.seasons.show', $season) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        </x-slot>
    </x-form.developer.season>
</x-layouts.app>
