@props(['breadcrumbs' => []])

@if (count($breadcrumbs) > 0)
    <nav aria-label="Breadcrumb" class="mb-4 flex">
        <ol role="list" class="flex items-center space-x-4">
            @foreach ($breadcrumbs as $index => $item)
                <li>
                    @if ($index === 0 && strtolower($item['text']) === 'home')
                        <div>
                            <a href="{{ $item['url'] ?? '#' }}" class="text-gray-400 hover:text-gray-500">
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    data-slot="icon"
                                    aria-hidden="true"
                                    class="size-5 shrink-0"
                                >
                                    <path
                                        d="M9.293 2.293a1 1 0 0 1 1.414 0l7 7A1 1 0 0 1 17 11h-1v6a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-3a1 1 0 0 0-1-1H9a1 1 0 0 0-1 1v3a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1v-6H3a1 1 0 0 1-.707-1.707l7-7Z"
                                        clip-rule="evenodd"
                                        fill-rule="evenodd"
                                    />
                                </svg>
                                <span class="sr-only">{!! $item['text'] !!}</span>
                            </a>
                        </div>
                    @else
                        <div class="flex items-center">
                            @if ($index > 0)
                                <svg
                                    viewBox="0 0 20 20"
                                    fill="currentColor"
                                    data-slot="icon"
                                    aria-hidden="true"
                                    class="size-5 shrink-0 text-gray-400"
                                >
                                    <path
                                        d="M8.22 5.22a.75.75 0 0 1 1.06 0l4.25 4.25a.75.75 0 0 1 0 1.06l-4.25 4.25a.75.75 0 0 1-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 0 1 0-1.06Z"
                                        clip-rule="evenodd"
                                        fill-rule="evenodd"
                                    />
                                </svg>
                            @endif

                            @if (isset($item['active']) && $item['active'])
                                <span aria-current="page" class="ml-4 text-sm font-medium text-gray-500">
                                    {!! $item['text'] !!}
                                </span>
                            @else
                                <a
                                    href="{{ $item['url'] ?? '#' }}"
                                    class="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700"
                                >
                                    {!! $item['text'] !!}
                                </a>
                            @endif
                        </div>
                    @endif
                </li>
            @endforeach
        </ol>
    </nav>
@endif
