@props([
    'items' => [],
    'align' => 'end',
    'width' => 'min-w-24',
    'buttonClass' => 'cursor-pointer px-2 text-xl text-gray-500 hover:text-gray-700 focus:outline-none',
    'menuClass' => 'origin-top-right rounded-md bg-white shadow-lg outline-1 outline-black/5 transition',
    'linkClass' => 'block px-4 py-2 text-left text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900',
])

@php
    $alignClasses = $align === 'start' ? 'left-0 origin-top-left' : 'right-0 origin-top-right';
@endphp

<div x-data="dropdown()" class="relative inline-block text-right">
    {{-- trigger --}}
    <button
        type="button"
        x-ref="button"
        @click="toggle"
        :aria-expanded="open"
        aria-haspopup="true"
        class="{{ $buttonClass }}"
    >
        &#x22EE;
        {{-- vertical ellipsis character --}}
    </button>

    {{-- menu --}}
    <div
        x-ref="menu"
        x-show="open"
        x-transition:enter="transition duration-100 ease-out"
        x-transition:enter-start="scale-95 opacity-0"
        x-transition:enter-end="scale-100 opacity-100"
        x-transition:leave="transition duration-75 ease-in"
        x-transition:leave-start="scale-100 opacity-100"
        x-transition:leave-end="scale-95 opacity-0"
        @click.outside="close"
        @keydown.escape.prevent.stop="close"
        class="{{ $width }} {{ $menuClass }} {{ $alignClasses }} absolute z-50 mt-2"
        style="display: none"
        role="menu"
        aria-orientation="vertical"
    >
        <div class="py-1">
            @foreach ($items as $item)
                @php
                    $isActive = false; // no routeIs check needed here
                    $href = $item['href'] ?? '#';
                    $action = $href;
                    $intended_method = isset($item['method']) ? strtoupper($item['method']) : 'GET';
                    $isForm = $intended_method !== 'GET';
                    $target = isset($item['target']) ? ' target="' . $item['target'] . '" rel="noopener noreferrer"' : '';
                    $onclick = isset($item['confirm']) ? 'onclick="return confirm(\'' . addslashes($item['confirm']) . '\')"' : '';
                @endphp

                @if (! $isForm)
                    <a
                        href="{{ $action }}"
                        {!! $target !!}
                        {!! $onclick !!}
                        class="{{ $linkClass }} {{ $isActive ? 'bg-gray-100 text-gray-900' : '' }}"
                        @click="close"
                        role="menuitem"
                        aria-current="{{ $isActive ? 'page' : 'false' }}"
                    >
                        {{ $item['label'] ?? 'Item' }}
                    </a>
                @else
                    <form action="{{ $action }}" method="POST" role="none">
                        @csrf
                        @if ($intended_method !== 'POST')
                            @method($intended_method)
                        @endif

                        <button
                            type="submit"
                            class="{{ $linkClass }} w-full text-left"
                            role="menuitem"
                            @click="close"
                            {!! $onclick !!}
                        >
                            {{ $item['label'] ?? 'Submit' }}
                        </button>
                    </form>
                @endif
            @endforeach
        </div>
    </div>
</div>

<script>
    function dropdown() {
        return {
            open: false,
            toggle() {
                this.open = !this.open;
            },
            close() {
                this.open = false;
            },
        };
    }
</script>
