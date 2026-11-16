<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    @include('partials.head', ['title' => 'Sign in'])
    <meta name="robots" content="noindex, nofollow">
</head>
<body class="flex min-h-full items-center justify-center bg-gradient-to-br from-brand to-brand-accent px-4 py-12 font-sans antialiased">
    @php($brand = \App\Models\Setting::allValues())
    <div class="w-full max-w-sm">
        <div class="mb-6 text-center text-white">
            <div class="text-2xl font-bold">{{ $brand['agency_name'] }}</div>
            <div class="text-sm text-white/70">Proposal Studio · Admin</div>
        </div>
        <form method="POST" action="{{ url('/login') }}" class="card space-y-4 p-6">
            @csrf
            <div>
                <label class="label" for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus class="input" autocomplete="username">
                @error('email') <p class="error">{{ $message }}</p> @enderror
            </div>
            <div>
                <label class="label" for="password">Password</label>
                <input id="password" name="password" type="password" required class="input" autocomplete="current-password">
            </div>
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="remember" class="rounded border-slate-300"> Remember me
            </label>
            <button class="btn-primary w-full">Sign in</button>
        </form>
    </div>
    @livewireScriptConfig
</body>
</html>
