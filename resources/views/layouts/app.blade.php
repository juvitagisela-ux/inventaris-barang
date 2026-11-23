<!DOCTYPE html>
<html lang="id" class="light min-h-full bg-zinc-50">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'Inventaris Barang' }} · Inventaris Barang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles

</head>
<body class="min-h-screen bg-zinc-50 text-zinc-900 antialiased">
    <header class="border-b border-zinc-200 bg-white">
        <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
            <a href="{{ route('inventory.index') }}" class="font-semibold tracking-tight">Inventaris Barang</a>
            @auth
                <div class="flex items-center gap-3">
                    <span class="hidden text-sm text-zinc-600 sm:inline">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <flux:button type="submit" variant="ghost" size="sm">Keluar</flux:button>
                    </form>
                </div>
            @endauth
        </div>
    </header>
    <main class="mx-auto max-w-7xl px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        {{ $slot }}
    </main>
    @livewireScripts
    @fluxScripts
</body>
</html>
