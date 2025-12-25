<x-layouts.app
    mainHeading="Team: {{ $team->designation }} ({{ $team->mascot }})"
    mainDescription="Details for team including designation, mascot, and sport."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'admin.teams.index'],
        ['text' => 'Edit Team', 'route' => 'admin.teams.edit', 'params' => ['team' => $team]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Teams', 'url' => route('admin.teams.index')],
            ['text' => $team->designation, 'active' => true],
        ]"
    />
    <x-model-viewer
        :fields="[
            [
                'label' => 'Organization',
                'value' => $team->organization,
            ],
            [
                'label' => 'Designation',
                'value' => $team->designation,
            ],
            [
                'label' => 'Mascot',
                'value' => $team->mascot,
            ],
            [
                'label' => 'Type',
                'value' => $team->type,
            ],
            [
                'label' => 'Sports',
                'value' => $team->sports_string,
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
