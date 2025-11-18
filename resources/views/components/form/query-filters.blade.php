<form method="GET" class="mb-4">
    <div class="flex items-start gap-x-4">
        {{ $slot }}

        @section('submit-button')

        <div class="mt-5">
            <x-buttons.primary-button type="submit">Search</x-buttons.primary-button>
        </div>

        @show
    </div>
</form>
