<x-layouts.app
    mainHeading="Team: {{ $team->designation }} ({{ $team->mascot }})"
    mainDescription="Details for team including designation, mascot, and sport."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'teams.index'],
        ['text' => 'Edit Team', 'route' => 'teams.edit', 'params' => ['team' => $team]],
    ]"
>
    <x-model-viewer
        :fields="[
            [
                'label' => 'Designation',
                'value' => $team->designation,
            ],
            [
                'label' => 'Mascot',
                'value' => $team->mascot,
            ],
            [
                'label' => 'Sport',
                'value' => $team->sport,
            ],
            [
                'label' => 'ULID',
                'value' => $team->ulid,
            ],
            [
                'label' => 'Created At',
                'value' => $team->created_at->format('F j, Y, g:i a'),
            ],
            [
                'label' => 'Updated At',
                'value' => $team->updated_at->format('F j, Y, g:i a'),
            ],
        ]"
    />
</x-layouts.app>
