<x-layouts.app
    mainHeading="Seasons"
    mainDescription="A list of all the seasons including their name, sport, season type, and dates."
    :mainActions="[
        ['text' => 'Add Season', 'route' => 'developer.seasons.create'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Seasons', 'active' => true],
        ]"
    />
    {{-- query --}}
    <x-form.query-filters>
        <x-form.query-search label="Search by name" :error="$errors->get('q')" />

        <x-form.select
            name="sport"
            label="Sport"
            :value="old('sport', request()->input('sport'))"
            placeholder="All Sports"
            :options="$sports->mapWithKeys(fn($sport) => [$sport => ucfirst($sport)])->toArray()"
        />

        <x-form.select
            name="season_type"
            label="Season Type"
            :value="old('season_type', request()->input('season_type'))"
            placeholder="All Types"
            :options="$seasonTypes->mapWithKeys(fn($type) => [$type => ucfirst($type)])->toArray()"
        />
    </x-form.query-filters>

    {{-- table --}}
    <x-tables.full-width
        heading="Seasons"
        description="A list of all the seasons including their name, sport, season type, and status."
        :tableActions="[
            ['route' => 'developer.seasons.create', 'text' => 'Add Season']
        ]"
        :headers="['Name', 'Sport', 'Season Type', 'Active', 'Created', 'Actions']"
        :rows="$seasons"
        :columns="[
            'name',
            'sport_html_entity',
            'season_type',
            'active_html_entity',
            fn ($row) => $row->created_at?->format('Y-m-d H:i:a'),
        ]"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'developer.seasons.show',
                'routeParams' => ['season' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'developer.seasons.edit',
                'routeParams' => ['season' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'developer.seasons.destroy',
                'routeParams' => ['season' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this season?'
            ]
        ]"
    ></x-tables.full-width>

    <div class="mt-4">
        {{ $seasons->links() }}
    </div>
</x-layouts.app>
