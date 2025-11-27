<x-layouts.app
    mainHeading="Season: {{ $season->name }}"
    mainDescription="Details for season including name, sport, season type, and dates."
    :mainActions="[
        ['text' => 'Back to Seasons', 'route' => 'seasons.index'],
        ['text' => 'Edit Season', 'route' => 'seasons.edit', 'params' => ['season' => $season]],
    ]"
>
    <x-model-viewer
        :fields="[
            [
                'label' => 'Name',
                'value' => $season->name,
            ],
            [
                'label' => 'Sport',
                'value' => $season->sport,
            ],
            [
                'label' => 'Season Type',
                'value' => $season->season_type,
            ],
            [
                'label' => 'Season Start',
                'value' => $season->season_start,
            ],
            [
                'label' => 'Season End',
                'value' => $season->season_end,
            ],
            [
                'label' => 'Created At',
                'value' => $season->created_at->format('F j, Y, g:i a'),
            ],
            [
                'label' => 'Updated At',
                'value' => $season->updated_at->format('F j, Y, g:i a'),
            ],
        ]"
    />
</x-layouts.app>
