@props([
    'method' => 'POST',
    'action' => null,
])

<form
    method="{{ $method }}"
    action="{{ $action }}"
    {{ $attributes->merge(['class' => 'space-y-12']) }}
>
    @csrf
    {{ $sections }}

    <div class="mt-6 flex items-center justify-end gap-x-6">
        {{ $buttons }}
    </div>
</form>
