<x-layouts.app
    mainHeading="Teams"
    mainDescription="A list of all the teams including their designation, mascot and sport."
    :mainActions="[
        ['text' => 'Add Team', 'route' => 'teams.create'],
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
        <x-form.query-search label="Search by designation or mascot" :error="$errors->get('q')" />

        <x-form.select
            name="sport"
            label="Sport"
            :value="old('sport', request()->input('sport'))"
            :options="['' => 'All Sports'] + $sports->mapWithKeys(fn($sport) => [$sport => ucfirst($sport)])->toArray()"
        />
    </x-form.query-filters>

    {{-- table --}}
    <x-tables.full-width
        heading="Teams"
        description="A list of all the teams including their designation, mascot and sport."
        :tableActions="[
            ['route' => 'teams.create', 'text' => 'Add Team']
        ]"
        :headers="['Designation', 'Mascot', 'Sports', 'Created', 'Actions']"
        :rows="$teams"
        :columns="['designation', 'mascot', 'sports_string', 'created_at']"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'teams.show',
                'routeParams' => ['team' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'teams.edit',
                'routeParams' => ['team' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'teams.destroy',
                'routeParams' => ['team' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this team?'
            ]
        ]"
    ></x-tables.full-width>
</x-layouts.app>
