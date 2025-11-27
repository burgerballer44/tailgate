<x-layouts.app
    mainHeading="Edit Season"
    mainDescription="Update the details of the season."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'seasons.index'],
        ['text' => 'View Season', 'route' => 'seasons.show', 'params' => ['season' => $season]],
    ]"
>
    <x-form.admin.season
        :action="route('seasons.update', $season)"
        :method="'PUT'"
        :season="$season"
        :sports="$sports"
        :seasonTypes="$seasonTypes"
    >
        <x-slot name="buttons">
            <x-buttons.cancel-button>
                <a href="{{ route('seasons.show', $season) }}">{{ __('Cancel') }}</a>
            </x-buttons.cancel-button>

            <x-buttons.primary-button class="ms-4">
                {{ __('Submit') }}
            </x-buttons.primary-button>
        </x-slot>
    </x-form.admin.season>
</x-layouts.app>
