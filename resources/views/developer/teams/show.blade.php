<x-layouts.app
    mainHeading="Team: {{ $team->designation }}"
    mainDescription="Details for team including designation and sport."
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

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <x-model-viewer
            message="Team identity"
            details="Primary name and classification fields."
            tone="info"
            :fields="[
                [
                    'label' => 'Display Name',
                    'value' => $team->display_name,
                ],
                [
                    'label' => 'Organization',
                    'value' => $team->organization,
                ],
                [
                    'label' => 'Designation',
                    'value' => $team->designation,
                ],
                [
                    'label' => 'Type',
                    'value' => $team->type,
                ],
                [
                    'label' => 'Sports',
                    'value' => $team->sports_html_entities,
                ],
                [
                    'label' => 'ULID',
                    'value' => $team->ulid,
                ],
                [
                    'label' => 'ID',
                    'value' => $team->id,
                ],
            ]"
        />

        <x-model-viewer
            message="Branding and conference"
            details="Display and branding fields used in UI integrations."
            tone="success"
            :fields="[
                [
                    'label' => 'Conference',
                    'value' => $team->conference,
                ],
                [
                    'label' => 'Abbreviation',
                    'value' => $team->abbreviation,
                ],
                [
                    'label' => 'Primary Logo',
                    'value' => $team->logo_badge,
                ],
                [
                    'label' => 'Logos',
                    'value' => is_array($team->logos) ? implode(', ', $team->logos) : null,
                ],
                [
                    'label' => 'Color',
                    'value' => $team->color_badge,
                ],
                [
                    'label' => 'Social Media',
                    'value' => is_array($team->social_media)
                        ? collect($team->social_media)
                            ->map(fn ($item) => ($item['label'] ?? 'Link') . ': ' . ($item['url'] ?? ''))
                            ->join(', ')
                        : null,
                ],
            ]"
        />

        <x-model-viewer
            message="Metadata"
            details="Timestamps and relationship counts for diagnostics."
            tone="neutral"
            :fields="[
                [
                    'label' => 'Created At',
                    'value' => $team->created_at?->format('F j, Y, g:i a') ?? 'N/A',
                ],
                [
                    'label' => 'Updated At',
                    'value' => $team->updated_at?->format('F j, Y, g:i a') ?? 'N/A',
                ],
                [
                    'label' => 'Sport Relation Count',
                    'value' => $team->sports()->count(),
                ],
            ]"
        />
    </div>
</x-layouts.app>
