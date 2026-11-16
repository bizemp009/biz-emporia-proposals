<!DOCTYPE html>
<html lang="en" class="scroll-smooth bg-slate-50">
<head>
    @include('partials.head')
    {{-- Proposal links are unlisted: keep them out of search engines. --}}
    <meta name="robots" content="noindex, nofollow, noarchive">
    <meta name="referrer" content="no-referrer">
</head>
<body class="font-sans text-slate-800 antialiased">
    {{ $slot }}
    @include('partials.toasts')
    @livewireScriptConfig
</body>
</html>
