<!DOCTYPE html>
<html lang="en" class="h-full bg-slate-50" data-themeable>
<head>
    {{-- Apply the saved theme before first paint (no light flash in dark mode). --}}
    <script>
        (() => { try { const t = localStorage.getItem('theme') || 'system';
            document.documentElement.classList.toggle('dark', t === 'dark' || (t === 'system' && matchMedia('(prefers-color-scheme: dark)').matches));
        } catch (e) {} })();
    </script>
    @include('partials.head')
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="h-full bg-slate-50 font-sans text-slate-800 antialiased" x-data="{ nav: false }" @keydown.escape.window="nav = false">
@php
    $brand = \App\Models\Setting::allValues();
    $user = auth()->user();
    $initials = fn (string $name) => \Illuminate\Support\Str::of($name)->explode(' ')->filter()->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))->take(2)->join('');
    $links = [
        ['route' => 'admin.proposals.index', 'match' => 'admin.proposals.*', 'label' => 'Proposals', 'icon' => 'M9 12h6m-6 4h6M7 4h7l5 5v11a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1V5a1 1 0 0 1 1-1Z'],
        ['route' => 'admin.clients', 'match' => 'admin.clients', 'label' => 'Clients', 'icon' => 'M17 20h5v-2a4 4 0 0 0-5-3.87M9 20H2v-2a4 4 0 0 1 5-3.87m10-4a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z'],
        ['route' => 'admin.templates', 'match' => 'admin.templates*', 'label' => 'Templates', 'icon' => 'M4 5h16M4 12h16M4 19h10'],
        ['route' => 'admin.case-studies', 'match' => 'admin.case-studies', 'label' => 'Case Studies', 'icon' => 'M3 17l6-6 4 4 8-8M14 7h7v7'],
        ['route' => 'admin.services', 'match' => 'admin.services', 'label' => 'Services', 'icon' => 'M20 7 12 3 4 7m16 0-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4'],
        ['route' => 'admin.settings', 'match' => 'admin.settings', 'label' => 'Branding', 'icon' => 'M12 15a3 3 0 1 0 0-6 3 3 0 0 0 0 6Zm7.4-3a7.4 7.4 0 0 0-.1-1.2l2-1.6-2-3.4-2.4 1a7.5 7.5 0 0 0-2-1.2L14.5 3h-5l-.4 2.6a7.5 7.5 0 0 0-2 1.2l-2.4-1-2 3.4 2 1.6a7.4 7.4 0 0 0 0 2.4l-2 1.6 2 3.4 2.4-1a7.5 7.5 0 0 0 2 1.2l.4 2.6h5l.4-2.6a7.5 7.5 0 0 0 2-1.2l2.4 1 2-3.4-2-1.6c.1-.4.1-.8.1-1.2Z'],
    ];
    // Breadcrumb parent for sub-pages: [label, route]
    $parent = match (true) {
        request()->routeIs('admin.proposals.create', 'admin.proposals.edit') => ['Proposals', 'admin.proposals.index'],
        request()->routeIs('admin.templates.*') => ['Templates', 'admin.templates'],
        default => null,
    };
    $pageTitle = $title ?? '';
@endphp

<a href="#main" class="sr-only z-50 rounded bg-white px-3 py-2 text-sm font-semibold focus:not-sr-only focus:fixed focus:top-2 focus:left-2">Skip to content</a>

{{-- Mobile overlay --}}
<div x-show="nav" x-cloak x-transition.opacity @click="nav = false" class="fixed inset-0 z-30 bg-black/40 lg:hidden"></div>

<aside :class="nav ? 'translate-x-0' : '-translate-x-full'" aria-label="Main navigation"
       class="fixed inset-y-0 left-0 z-40 flex w-64 flex-col bg-brand text-white transition-transform lg:translate-x-0">
    <div class="flex items-center gap-3 px-5 py-5">
        <div class="flex size-10 items-center justify-center rounded-lg bg-brand-accent text-sm font-bold">{{ $initials($brand['agency_name']) }}</div>
        <div class="min-w-0 flex-1">
            <div class="truncate font-semibold">{{ $brand['agency_name'] }}</div>
            <div class="truncate text-xs text-white/60">Proposal Studio</div>
        </div>
        <button @click="nav = false" class="rounded p-1 text-white/70 hover:text-white lg:hidden" aria-label="Close menu">
            <svg class="size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M6 6l12 12M18 6 6 18"/></svg>
        </button>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-3 py-2">
        @foreach ($links as $link)
            @php($active = request()->routeIs($link['match']))
            <a href="{{ route($link['route']) }}" wire:navigate @click="nav = false" @if ($active) aria-current="page" @endif
               class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white/60 {{ $active ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white' }}">
                <svg class="size-5 shrink-0" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="{{ $link['icon'] }}"/></svg>
                {{ $link['label'] }}
            </a>
        @endforeach
    </nav>

    <div class="border-t border-white/10 px-5 py-4 text-xs text-white/50">{{ $brand['agency_tagline'] }}</div>
</aside>

<div class="lg:pl-64">
    <header class="sticky top-0 z-20 flex h-14 items-center gap-2 border-b border-slate-200 bg-white/90 px-3 backdrop-blur sm:gap-3 sm:px-6">
        <button @click="nav = true" class="rounded p-1.5 text-slate-600 hover:bg-slate-100 lg:hidden" aria-label="Open menu">
            <svg class="size-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M4 6h16M4 12h16M4 18h16"/></svg>
        </button>

        {{-- Breadcrumbs / page title --}}
        <nav aria-label="Breadcrumb" class="min-w-0 flex-1">
            <ol class="flex min-w-0 items-center gap-1.5 text-sm">
                @if ($parent)
                    <li class="hidden shrink-0 sm:block"><a href="{{ route($parent[1]) }}" wire:navigate class="text-slate-500 hover:text-slate-800">{{ $parent[0] }}</a></li>
                    <li class="hidden text-slate-300 sm:block" aria-hidden="true">/</li>
                @endif
                <li class="truncate font-semibold text-slate-900" aria-current="page">{{ $pageTitle ?: $brand['agency_name'] }}</li>
            </ol>
        </nav>

        {{-- + New --}}
        <x-dropdown label="Create new" width="w-56" trigger-class="btn-primary px-3 py-1.5">
            <x-slot:trigger>
                <svg class="size-4" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 5v14M5 12h14"/></svg>
                <span class="hidden sm:inline">New</span>
            </x-slot:trigger>
            <a href="{{ route('admin.proposals.create') }}" wire:navigate class="menu-item" role="menuitem">Proposal</a>
            <a href="{{ route('admin.clients', ['new' => 1]) }}" wire:navigate class="menu-item" role="menuitem">Client</a>
            <a href="{{ route('admin.templates.create') }}" wire:navigate class="menu-item" role="menuitem">Template</a>
            <a href="{{ route('admin.services', ['new' => 1]) }}" wire:navigate class="menu-item" role="menuitem">Service</a>
            <a href="{{ route('admin.case-studies', ['new' => 1]) }}" wire:navigate class="menu-item" role="menuitem">Case study</a>
        </x-dropdown>

        {{-- Theme --}}
        <x-dropdown label="Theme" width="w-40" trigger-class="rounded-lg p-2 text-slate-500 hover:bg-slate-100 hover:text-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent/50">
            <x-slot:trigger>
                <svg x-show="!$store.theme.dark" class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2m0 16v2M4.9 4.9l1.4 1.4m11.4 11.4 1.4 1.4M2 12h2m16 0h2M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/></svg>
                <svg x-show="$store.theme.dark" x-cloak class="size-5" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24" aria-hidden="true"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8Z"/></svg>
            </x-slot:trigger>
            @foreach (['light' => 'Light', 'dark' => 'Dark', 'system' => 'System'] as $mode => $label)
                <button type="button" class="menu-item" role="menuitemradio" :aria-checked="($store.theme.mode === '{{ $mode }}').toString()" @click="$store.theme.set('{{ $mode }}')">
                    <span class="w-4 text-brand-accent" x-text="$store.theme.mode === '{{ $mode }}' ? '✓' : ''"></span>{{ $label }}
                </button>
            @endforeach
        </x-dropdown>

        {{-- User menu --}}
        <x-dropdown label="Account" width="w-60" trigger-class="flex items-center gap-2 rounded-full p-0.5 focus:outline-none focus-visible:ring-2 focus-visible:ring-brand-accent/50">
            <x-slot:trigger>
                <span class="flex size-8 items-center justify-center rounded-full bg-brand-accent text-xs font-bold text-white">{{ $initials($user->name) }}</span>
            </x-slot:trigger>
            <div class="border-b border-slate-100 px-3 py-2">
                <div class="truncate text-sm font-semibold text-slate-900">{{ $user->name }}</div>
                <div class="truncate text-xs text-slate-500">{{ $user->email }}</div>
            </div>
            <div class="py-1">
                <a href="{{ route('admin.profile') }}" wire:navigate class="menu-item" role="menuitem">Profile &amp; password</a>
                <a href="{{ route('admin.settings') }}" wire:navigate class="menu-item" role="menuitem">Branding &amp; defaults</a>
            </div>
            <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 pt-1">
                @csrf
                <button class="menu-item" role="menuitem">Sign out</button>
            </form>
        </x-dropdown>
    </header>

    <main id="main" class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:py-8">
        {{ $slot }}
    </main>
</div>

<x-confirm-dialog />
@include('partials.toasts')
@livewireScriptConfig
</body>
</html>
