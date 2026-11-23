<!DOCTYPE html>
<html lang="id" class="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Daftar · Inventaris Barang</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])

</head>
<body class="flex min-h-screen items-center justify-center bg-zinc-50 px-4 py-10">
    <main class="w-full max-w-md rounded-2xl border border-zinc-200 bg-white p-6 shadow-sm sm:p-8">
        <a href="/" class="text-sm font-medium text-zinc-500">Inventaris Barang</a>
        <h1 class="mt-5 text-2xl font-semibold tracking-tight">Buat akun</h1>
        <p class="mt-2 text-sm text-zinc-600">Daftarkan akun untuk mulai mengelola inventaris.</p>
        @if ($errors->any())
            <div role="alert" class="mt-5 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-800">{{ $errors->first() }}</div>
        @endif
        <form method="POST" action="{{ route('register.store') }}" class="mt-6 space-y-5">
            @csrf
            <flux:input name="name" label="Nama" value="{{ old('name') }}" autocomplete="name" required />
            <flux:input name="email" type="email" label="Email" value="{{ old('email') }}" autocomplete="email" required />
            <flux:input name="password" type="password" label="Kata sandi" autocomplete="new-password" required />
            <flux:input name="password_confirmation" type="password" label="Ulangi kata sandi" autocomplete="new-password" required />
            <flux:button type="submit" variant="primary" class="w-full">Daftar</flux:button>
        </form>
        <p class="mt-6 text-center text-sm text-zinc-600">Sudah punya akun? <a class="font-medium text-zinc-900 underline underline-offset-4" href="{{ route('login') }}">Masuk</a></p>
    </main>
    @fluxScripts
</body>
</html>
