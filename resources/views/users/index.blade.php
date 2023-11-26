<x-app-layout>

    <Users-Manager
        :statuses="{{ collect($statuses) }}"
        :roles="{{ collect($roles) }}">
    </Users-Manager>

</x-app-layout>