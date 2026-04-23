<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>Crear cuenta • {{ config('app.name', 'Sistema') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        :root {
            --color-cream: #0B0B0C;
            --color-charcoal: rgba(255,255,255,0.96);
            --color-wood: #8B6F47;
            --color-warm-gray: rgba(255,255,255,0.70);
            --color-accent: #B8956A;
            --color-border: rgba(255,255,255,0.12);
            --surface: rgba(18,18,20,0.88);
            --surface-solid: #121214;
            --surface-glass: rgba(255,255,255,0.06);
        }

        body {
            background:
                radial-gradient(900px 520px at 10% 0%, rgba(184, 149, 106, 0.14), transparent 60%),
                radial-gradient(800px 520px at 90% 20%, rgba(139, 111, 71, 0.12), transparent 55%),
                var(--color-cream);
            color: var(--color-charcoal);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .glass {
            background: var(--surface-glass);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid var(--color-border);
        }

        .gradient-text {
            background: linear-gradient(135deg, var(--color-wood), var(--color-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="min-h-screen">
    <main class="mx-auto flex min-h-screen max-w-7xl items-center justify-center px-6 py-12">
        <div class="w-full max-w-md">
            <a href="{{ url('/') }}" class="inline-flex items-center gap-2 text-sm font-semibold" style="color: var(--color-warm-gray);">
                <span aria-hidden="true">←</span> Volver al inicio
            </a>

            <div class="mt-6 glass rounded-2xl p-8">
                <div class="mb-6">
                    <div class="text-2xl font-bold">Crear cuenta</div>
                    <div class="mt-1 text-sm" style="color: var(--color-warm-gray);">Crea tu cuenta de cliente para usar la experiencia 3D y guardar escenas.</div>
                </div>

                <form method="POST" action="{{ route('client.register') }}" class="space-y-4">
                    @csrf

                    <div>
                        <label for="name" class="block text-sm font-semibold">Nombre</label>
                        <input
                            id="name"
                            name="name"
                            type="text"
                            autocomplete="name"
                            value="{{ old('name') }}"
                            required
                            class="mt-2 w-full rounded-lg px-4 py-2.5 text-sm"
                            style="background: var(--surface-solid); border: 1px solid var(--color-border); color: var(--color-charcoal);"
                        />
                        @error('name')
                            <div class="mt-2 text-xs" style="color: rgba(255,255,255,0.75);">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-semibold">Correo</label>
                        <input
                            id="email"
                            name="email"
                            type="email"
                            autocomplete="email"
                            value="{{ old('email') }}"
                            required
                            class="mt-2 w-full rounded-lg px-4 py-2.5 text-sm"
                            style="background: var(--surface-solid); border: 1px solid var(--color-border); color: var(--color-charcoal);"
                        />
                        @error('email')
                            <div class="mt-2 text-xs" style="color: rgba(255,255,255,0.75);">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-semibold">Contraseña</label>
                        <input
                            id="password"
                            name="password"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-2 w-full rounded-lg px-4 py-2.5 text-sm"
                            style="background: var(--surface-solid); border: 1px solid var(--color-border); color: var(--color-charcoal);"
                        />
                        <div class="mt-2 text-xs" style="color: rgba(255,255,255,0.65);">Mínimo 8 caracteres.</div>
                        @error('password')
                            <div class="mt-2 text-xs" style="color: rgba(255,255,255,0.75);">{{ $message }}</div>
                        @enderror
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-semibold">Confirmar contraseña</label>
                        <input
                            id="password_confirmation"
                            name="password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            required
                            class="mt-2 w-full rounded-lg px-4 py-2.5 text-sm"
                            style="background: var(--surface-solid); border: 1px solid var(--color-border); color: var(--color-charcoal);"
                        />
                    </div>

                    <button
                        type="submit"
                        class="w-full rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-all duration-300 hover:shadow-lg"
                        style="background: linear-gradient(135deg, var(--color-wood), var(--color-accent));"
                    >
                        Crear cuenta
                    </button>
                </form>

                <div class="mt-6 text-sm" style="color: var(--color-warm-gray);">
                    ¿Ya tienes cuenta? <a href="{{ route('login') }}" class="font-semibold" style="color: var(--color-accent);">Ingresar</a>
                </div>
            </div>
        </div>
    </main>
</body>
</html>
