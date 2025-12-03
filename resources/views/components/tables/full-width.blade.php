<div class="px-4 sm:px-6 lg:px-8">
    {{-- table --}}
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle">
                <table class="min-w-full divide-y divide-gray-300">
                    {{-- table header row --}}
                    <thead>
                        <tr>
                            @foreach ($headers as $header)
                                @if ($loop->first)
                                    <th scope="col" class="py-3.5 pr-3 pl-4 text-left text-sm font-semibold">
                                        {{ $header }}
                                    </th>
                                @else
                                    <th scope="col" class="py-3.5 pr-3 text-left text-sm font-semibold">
                                        {{ $header }}
                                    </th>
                                @endif
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
                                        <td class="py-3.5 pr-3 pl-4 font-medium whitespace-nowrap">
                                            @renderTableData($column)
                                        </td>
                                    @else
                                        <td class="py-3.5 pr-3 font-medium whitespace-nowrap">
                                            @renderTableData($column)
                                        </td>
                                    @endif
                                @endforeach

                                {{-- row actions --}}
                                @if (! empty($rowActions))
                                    <td
                                        class="relative pr-4 pl-3 text-right text-sm font-medium whitespace-nowrap sm:pr-6 lg:pr-8"
                                    >
                                        {{-- reminder --}}
                                        {{-- @if (!isset($action['permission']) || Auth::user()->can($action['permission'], $row)) --}}
                                        {{-- @endif --}}

                                        @php
                                            $dropdownItems = [];
                                            foreach ($rowActions as $action) {
                                                $routeParams = [];
                                                if (isset($action['routeParams'])) {
                                                    foreach ($action['routeParams'] as $key => $value) {
                                                        // if the value is a string and matches a property on the row, use that value
                                                        if (is_string($value) && isset($row->$value)) {
                                                            $routeParams[$key] = $row->$value;
                                                        } else {
                                                            $routeParams[$key] = $value;
                                                        }
                                                    }
                                                }
                                                $item = [
                                                    'label' => $action['label'],
                                                    'href' => route($action['route'], $routeParams),
                                                ];
                                                if (isset($action['confirm'])) {
                                                    $item['confirm'] = $action['confirm'];
                                                }
                                                // add 'method' if the action requires POST/DELETE, etc.
                                                if (isset($action['type']) && $action['type'] === 'form') {
                                                    $routeSegments = explode('.', $action['route']);
                                                    $actionName = array_pop($routeSegments);
                                                    $methodMap = [
                                                        'store' => 'POST',
                                                        'update' => 'PUT',
                                                        'destroy' => 'DELETE',
                                                    ];
                                                    $item['method'] = $methodMap[$actionName] ?? 'POST';
                                                }
                                                $dropdownItems[] = $item;
                                            }
                                        @endphp

                                        <x-tables.row-actions-dropdown :items="$dropdownItems" />
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
