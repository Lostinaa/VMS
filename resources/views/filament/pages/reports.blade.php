<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Date Filters & Export --}}
        <x-filament::section>
            <div class="flex flex-wrap items-end gap-4">
                <div>
                    <x-filament::input.wrapper>
                        <x-slot name="label">From</x-slot>
                        <input type="date" wire:model.live="dateFrom" class="fi-input block w-full rounded-lg border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500">
                    </x-filament::input.wrapper>
                </div>
                <div>
                    <x-filament::input.wrapper>
                        <x-slot name="label">To</x-slot>
                        <input type="date" wire:model.live="dateTo" class="fi-input block w-full rounded-lg border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none transition duration-75 placeholder:text-gray-400 focus:ring-0 dark:text-white dark:placeholder:text-gray-500">
                    </x-filament::input.wrapper>
                </div>
                <x-filament::button wire:click="exportCsv" color="success" icon="heroicon-m-arrow-down-tray">
                    Export CSV
                </x-filament::button>
                <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-m-document">
                    Export PDF
                </x-filament::button>
            </div>
        </x-filament::section>

        {{-- Visit Table --}}
        <x-filament::section heading="Visit Details">
            <div class="overflow-x-auto">
                <table class="fi-ta-table w-full table-auto divide-y divide-gray-200 text-start dark:divide-white/5">
                    <thead class="divide-y divide-gray-200 dark:divide-white/5">
                        <tr class="bg-gray-50/50 dark:bg-white/5">
                            <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">#</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Visitor</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Host</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Site</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Purpose</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Category</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Status</th>
                            <th class="fi-ta-header-cell px-3 py-3.5 text-start text-sm font-semibold text-gray-950 dark:text-white">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 whitespace-nowrap dark:divide-white/5">
                        @foreach($this->getVisitData() as $visit)
                        <tr class="fi-ta-row transition duration-75 hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $visit->id }}</td>
                            <td class="fi-ta-cell px-3 py-4 text-sm font-medium text-gray-950 dark:text-white">{{ $visit->visitor->full_name ?? '-' }}</td>
                            <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $visit->host->name ?? '-' }}</td>
                            <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $visit->site->name ?? '-' }}</td>
                            <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($visit->purpose, 25) }}</td>
                            <td class="fi-ta-cell px-3 py-4">
                                <x-filament::badge>{{ $visit->category }}</x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-3 py-4">
                                @php $c = match($visit->status) { 'pending' => 'warning', 'approved' => 'success', 'rejected' => 'danger', 'checked_in' => 'info', 'checked_out' => 'primary', default => 'gray' }; @endphp
                                <x-filament::badge :color="$c">{{ $visit->status }}</x-filament::badge>
                            </td>
                            <td class="fi-ta-cell px-3 py-4 text-sm text-gray-500 dark:text-gray-400">{{ $visit->scheduled_at?->format('M d, H:i') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
