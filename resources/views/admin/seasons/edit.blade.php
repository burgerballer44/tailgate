<x-layouts.app
    mainHeading="Edit Season"
    mainDescription="Update the details of the season."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'admin.seasons.index'],
        ['text' => 'View Season', 'route' => 'admin.seasons.show', 'params' => ['season' => $season]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'url' => route('admin.seasons.index')],
            ['text' => $season->name, 'url' => route('admin.seasons.show', $season)],
            ['text' => 'Edit Season', 'active' => true],
        ]"
    />
    <x-form.admin.season
        :action="route('admin.seasons.update', $season)"
        :method="'PUT'"
        :season="$season"
        :sports="$sports"
        :seasonTypes="$seasonTypes"
    >
        <x-slot name="buttons">
            <x-buttons.cancel-button>
                <a href="{{ route('admin.seasons.show', $season) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        </x-slot>
    </x-form.admin.season>
</x-layouts.app>
