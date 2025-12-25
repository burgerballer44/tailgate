<x-layouts.app
    mainHeading="Teams"
    mainDescription="A list of all the teams including their organization, designation, mascot and sport."
    :mainActions="[
        ['text' => 'Add Team', 'route' => 'admin.teams.create'],
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
        <x-form.query-search label="Search by organization, designation, mascot" :error="$errors->get('q')" />

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
        description="A list of all the teams including their organization, designation, mascot and sport."
        :tableActions="[
            ['route' => 'admin.teams.create', 'text' => 'Add Team']
        ]"
        :headers="['Organization', 'Designation', 'Mascot', 'Type', 'Sports', 'Created', 'Actions']"
        :rows="$teams"
        :columns="['organization', 'designation', 'mascot', 'type', 'sports_string', 'created_at']"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'admin.teams.show',
                'routeParams' => ['team' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'admin.teams.edit',
                'routeParams' => ['team' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'admin.teams.destroy',
                'routeParams' => ['team' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this team?'
            ]
        ]"
    ></x-tables.full-width>

    <div class="mt-4">
        {{ $teams->links() }}
    </div>
</x-layouts.app>
