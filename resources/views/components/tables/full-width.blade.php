<div class="px-4 sm:px-6 lg:px-8">
    {{-- table --}}
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle">
                <table class="min-w-full divide-y divide-gray-300">
                    {{-- table header --}}
                    <thead>
                        <tr>
                            @foreach ($headers as $header)
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    {{-- table body --}}
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($rows as $row)
                            <tr>
                                {{-- row data --}}
                                @foreach ($columns as $column)
                                    @if ($loop->first)
                                        <td class="py-4 pr-3 pl-4 font-medium whitespace-nowrap sm:pl-6 lg:pl-8">
                                            @renderTableData($column)
                                        </td>
                                    @else
                                        <td>
                                            @renderTableData($column)
                                        </td>
                                    @endif
                                @endforeach

                                {{-- row actions --}}
                                @if (! empty($rowActions))
                                    <td
                                        class="relative py-4 pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-6 lg:pr-8"
                                    >
                                        @foreach ($rowActions as $action)
                                            @php
                                                $routeParams = [];
                                                if ($action["routeParams"]) {
                                                    foreach ($action["routeParams"] as $key => $value) {
                                                        $routeParams[$key] = $row->$value;
                                                    }
                                                }
                                            @endphp

                                            {{-- @if (!isset($action['permission']) || Auth::user()->can($action['permission'], $row)) --}}

                                            <a
                                                class="text-carolina hover:text-navy"
                                                href="{{ route($action["route"], $routeParams) }}"
                                                @if (isset($action["confirm"]))
                                                    onclick="return confirm('{{ $action["confirm"] }}')"
                                                @endif
                                            >
                                                {{ $action["label"] }}
                                            </a>

                                            {{-- @endif --}}
                                        @endforeach
                                    </td>
                                @endif
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
