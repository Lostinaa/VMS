<div
    x-data="{ theme: localStorage.getItem('theme') || 'system' }"
    x-init="$watch('theme', (val) => {
        localStorage.setItem('theme', val);
        $dispatch('theme-changed', val);
    })"
    class="flex items-center gap-1 me-2"
>
    {{-- Light --}}
    <button
        x-on:click="theme = 'light'"
        x-bind:class="theme === 'light' ? 'text-primary-500 bg-primary-500/10' : 'text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400'"
        class="rounded-lg p-2 transition"
        title="Light mode"
    >
        <x-heroicon-m-sun class="h-5 w-5" />
    </button>

    {{-- Dark --}}
    <button
        x-on:click="theme = 'dark'"
        x-bind:class="theme === 'dark' ? 'text-primary-500 bg-primary-500/10' : 'text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400'"
        class="rounded-lg p-2 transition"
        title="Dark mode"
    >
        <x-heroicon-m-moon class="h-5 w-5" />
    </button>

    {{-- System --}}
    <button
        x-on:click="theme = 'system'"
        x-bind:class="theme === 'system' ? 'text-primary-500 bg-primary-500/10' : 'text-gray-400 hover:text-gray-500 dark:text-gray-500 dark:hover:text-gray-400'"
        class="rounded-lg p-2 transition"
        title="System default"
    >
        <x-heroicon-m-computer-desktop class="h-5 w-5" />
    </button>
</div>
