@php($brand = \App\Models\Setting::allValues())
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) && $title ? $title.' · ' : '' }}{{ $brand['agency_name'] }}</title>
@fonts
@vite(['resources/css/app.css', 'resources/js/app.js'])
@livewireStyles
<style>
    :root {
        --color-brand: {{ preg_match('/^#[0-9a-fA-F]{3,8}$/', $brand['brand_primary']) ? $brand['brand_primary'] : '#1a1a2e' }};
        --color-brand-accent: {{ preg_match('/^#[0-9a-fA-F]{3,8}$/', $brand['brand_accent']) ? $brand['brand_accent'] : '#7c5cff' }};
        --color-brand-highlight: {{ preg_match('/^#[0-9a-fA-F]{3,8}$/', $brand['brand_highlight']) ? $brand['brand_highlight'] : '#e6499b' }};
    }
</style>
