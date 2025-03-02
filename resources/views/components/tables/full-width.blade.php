<div class="px-4 sm:px-6 lg:px-8">

    {{-- header --}}
    <div class="sm:flex sm:items-center">
        {{-- left side text --}}
        <div class="sm:flex-auto">
            <h3>{{ $heading }}</h3>
            <p class="mt-2 text-sm text-gray-700">{{ $description }}</p>
        </div>
        {{-- right side buttons --}}
        <div class="mt-4 sm:ml-16 sm:mt-0 sm:flex-none">
            @foreach($tableActions as $action)
                <a
                    href="{{ route($action['route'], $action['params'] ?? []) }}"
                    class="rounded-md bg-carolina px-3 py-2 text-center font-semibold text-white shadow-sm hover:bg-navy focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-navy"
                >{{ $action['text'] }}</a>
            @endforeach
        </div>
    </div>

    {{-- table --}}
    <div class="mt-8 flow-root">
        <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
            <div class="inline-block min-w-full py-2 align-middle">
                <table class="min-w-full divide-y divide-gray-300">

                    {{-- table header --}}
                    <thead>
                        <tr>
                            @foreach($headers as $header)
                                <th scope="col" class="px-3 py-3.5 text-left text-sm font-semibold">{{ $header }}</th>
                            @endforeach
                        </tr>
                    </thead>

                    {{-- table body --}}
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach($rows as $row)
                            <tr>
                                {{-- row data --}}
                                @foreach($columns as $column)
                                    @if ($loop->first)
                                        <td class="whitespace-nowrap py-4 pl-4 pr-3 font-medium sm:pl-6 lg:pl-8">
                                            @renderTableData($column)
                                        </td>
                                    @else
                                        <td>
                                            @renderTableData($column)
                                        </td>
                                    @endif
                                @endforeach

                                {{-- row actions --}}
                                @if(!empty($rowActions))

                                    <td class="relative whitespace-nowrap py-4 pl-3 pr-4 text-right text-sm font-medium sm:pr-6 lg:pr-8">

                                        @foreach ($rowActions as $action)

                                            @php
                                                $routeParams = [];
                                                if($action['routeParams']) {
                                                    foreach ($action['routeParams'] as $key => $value) {
                                                        $routeParams[$key] = $row->$value;
                                                    }
                                                }
                                            @endphp 

                                            {{-- @if (!isset($action['permission']) || Auth::user()->can($action['permission'], $row))  --}}

                                                <a 
                                                    class="text-carolina hover:text-navy"
                                                    href="{{ route($action['route'], $routeParams) }}"
                                                    @if(isset($action['confirm']))
                                                        onclick="return confirm('{{ $action['confirm'] }}')"
                                                    @endif
                                                >
                                                    {{ $action['label'] }}
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