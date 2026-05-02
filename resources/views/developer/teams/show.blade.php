<x-layouts.app
    mainHeading="Team: {{ $team->designation }} ({{ $team->mascot }})"
    mainDescription="Details for team including designation, mascot, and sport."
    :mainActions="[
        ['text' => 'Back to Teams', 'route' => 'developer.teams.index'],
        ['text' => 'Edit Team', 'route' => 'developer.teams.edit', 'params' => ['team' => $team]],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Teams', 'url' => route('developer.teams.index')],
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
                'label' => 'Conference',
                'value' => $team->conference,
            ],
            [
                'label' => 'Abbreviation',
                'value' => $team->abbreviation,
            ],
            [
                'label' => 'Color',
                'value' => $team->color,
            ],
            [
                'label' => 'Alternate Color',
                'value' => $team->alternate_color,
            ],
            [
                'label' => 'Logos',
                'value' => is_array($team->logos) ? implode(', ', $team->logos) : null,
            ],
            [
                'label' => 'Social Media',
                'value' => is_array($team->social_media)
                    ? collect($team->social_media)
                        ->map(fn ($item) => ($item['label'] ?? 'Link') . ': ' . ($item['url'] ?? ''))
                        ->join(', ')
                    : null,
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
