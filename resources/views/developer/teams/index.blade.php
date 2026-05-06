<x-layouts.app
    mainHeading="Teams"
    mainDescription="A list of all the teams including their organization, designation, conference and sport."
    :mainActions="[
        ['text' => 'Import Teams', 'route' => 'developer.teams.import-teams'],
        ['text' => 'Add Team', 'route' => 'developer.teams.create'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Teams', 'active' => true],
        ]"
    />
    {{-- query --}}
    <x-form.query-filters>
        <x-form.query-search label="Search by organization, designation, conference" :error="$errors->get('q')" />

        <x-form.select
            name="sport"
            label="Sport"
            :value="old('sport', request()->input('sport'))"
            :options="['' => 'All Sports'] + $sports->mapWithKeys(fn($sport) => [$sport => ucfirst($sport)])->toArray()"
        />

        <x-form.select
            name="type"
            label="Type"
            :value="old('type', request()->input('type'))"
            :options="['' => 'All Types'] + $types->mapWithKeys(fn($type) => [$type => ucfirst($type)])->toArray()"
        />
    </x-form.query-filters>

    {{-- table --}}
    <x-tables.full-width
        heading="Teams"
        description="A list of all the teams including their organization, designation, conference and sport."
        :tableActions="[
            ['route' => 'developer.teams.create', 'text' => 'Add Team']
        ]"
        :headers="['Organization', 'Designation', 'Conference', 'Type', 'Sports', 'Color', 'Logo', 'Created', 'Actions']"
        :rows="$teams"
        :columns="[
            'organization',
            'designation',
            'conference',
            'type',
            'sports_html_entities',
            'color_badge',
            'logo_badge',
            fn ($row) => $row->created_at->format('Y-m-d H:i:a'),
        ]"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'developer.teams.show',
                'routeParams' => ['team' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'developer.teams.edit',
                'routeParams' => ['team' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'developer.teams.destroy',
                'routeParams' => ['team' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this team?'
            ]
        ]"
    ></x-tables.full-width>

    <div class="mt-4">
        {{ $teams->links() }}
    </div>
</x-layouts.app>
