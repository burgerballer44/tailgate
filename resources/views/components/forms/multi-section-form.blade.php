@props([
    'method' => 'POST',
    'action' => null,
])

@php
    $httpMethod = strtoupper($method);
    $formMethod = in_array($httpMethod, ['GET', 'POST']) ? $httpMethod : 'POST';
@endphp

<form
    method="{{ $formMethod }}"
    action="{{ $action }}"
    {{ $attributes->merge(['class' => 'rounded-lg bg-white p-6 shadow-md space-y-12']) }}
>
    @csrf
    @if ($formMethod !== $httpMethod)
        @method($method)
    @endif
    {{ $sections }}

    <div class="mt-6 flex items-center justify-end gap-x-6">
        {{ $buttons }}
    </div>
</form>
