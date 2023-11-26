<x-app-layout>

    <Seasons-Manager
        :season-types="{{ collect($seasonTypes) }}"
        :sports="{{ collect($sports) }}">
    </Seasons-Manager>

</x-app-layout>