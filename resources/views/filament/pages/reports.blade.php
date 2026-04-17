<x-filament-panels::page>
    {{-- Date Filters & Export --}}
    <x-filament::section>
        <div class="flex flex-col sm:flex-row items-end gap-4">
            <div class="w-full sm:w-auto">
                <x-filament::input.wrapper>
                    <x-slot name="prefix">From</x-slot>
                    <input type="date" wire:model.live="dateFrom" class="fi-input block w-full rounded-lg border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none transition duration-75 focus:ring-0 dark:text-white">
                </x-filament::input.wrapper>
            </div>
            <div class="w-full sm:w-auto">
                <x-filament::input.wrapper>
                    <x-slot name="prefix">To</x-slot>
                    <input type="date" wire:model.live="dateTo" class="fi-input block w-full rounded-lg border-none bg-transparent px-3 py-1.5 text-sm text-gray-950 outline-none transition duration-75 focus:ring-0 dark:text-white">
                </x-filament::input.wrapper>
            </div>
            <div class="flex gap-2 ml-auto">
                <x-filament::button wire:click="exportCsv" color="success" icon="heroicon-m-arrow-down-tray" size="sm">
                    CSV
                </x-filament::button>
                <x-filament::button wire:click="exportExcel" color="info" icon="heroicon-m-table-cells" size="sm">
                    Excel
                </x-filament::button>
                <x-filament::button wire:click="exportPdf" color="danger" icon="heroicon-m-document" size="sm">
                    PDF
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- Native Filament Table --}}
    {{ $this->table }}
</x-filament-panels::page>
