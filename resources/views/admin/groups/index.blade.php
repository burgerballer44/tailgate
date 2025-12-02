<x-layouts.app
    mainHeading="Groups"
    mainDescription="A list of all the groups including their name, owner, and limits."
    :mainActions="[
        ['text' => 'Add Group', 'route' => 'groups.create'],
    ]"
>
    <x-breadcrumb
        :breadcrumbs="[
            ['text' => 'Home', 'url' => route('dashboard')],
            ['text' => 'Groups', 'active' => true],
        ]"
    />
    {{-- query --}}
    <x-form.query-filters>
        <x-form.query-search label="Search by name" :error="$errors->get('q')" />

        <x-form.select
            name="owner_id"
            label="Owner"
            :value="old('owner_id', request()->input('owner_id'))"
            placeholder="All Owners"
            :options="$users->mapWithKeys(fn($user) => [$user->id => $user->name])->toArray()"
        />
    </x-form.query-filters>

    {{-- table --}}
    <x-tables.full-width
        heading="Groups"
        description="A list of all the groups including their name, owner, and limits."
        :tableActions="[
            ['route' => 'groups.create', 'text' => 'Add Group']
        ]"
        :headers="['Name', 'Owner', 'Member Limit', 'Player Limit', 'Created', 'Actions']"
        :rows="$groups"
        :columns="['name', 'owner.name', 'member_limit', 'player_limit', 'created_at']"
        :rowActions="[
            [
                'label' => 'Show',
                'route' => 'groups.show',
                'routeParams' => ['group' => 'ulid'],
            ],
            [
                'label' => 'Edit',
                'route' => 'groups.edit',
                'routeParams' => ['group' => 'ulid'],
            ],
            [
                'label' => 'Delete',
                'type' => 'form',
                'route' => 'groups.destroy',
                'routeParams' => ['group' => 'ulid'],
                'confirm' => 'Are you sure you want to delete this group?'
            ]
        ]"
    ></x-tables.full-width>
</x-layouts.app>
