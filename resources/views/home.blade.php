<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistema') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <style>
        :root {
            --color-cream: #FDFBF7;
            --color-charcoal: #2C2C2C;
            --color-wood: #8B6F47;
            --color-warm-gray: #A39A94;
            --color-sand: #D2B48C;
            --color-clay: #9B7653;
            --color-accent: #B8956A;
        }
        
        body {
            background-color: var(--color-cream);
            color: var(--color-charcoal);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .gradient-text {
            background: linear-gradient(135deg, var(--color-wood), var(--color-accent));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
    </style>
</head>
<body class="min-h-screen" style="background-color: var(--color-cream);"">
    @php
        $prefix = trim(config('platform.prefix', '/admin'), '/');
        $adminUrl = '/' . $prefix;
        $loginUrl = $adminUrl . '/login';
    @endphp

    <header class="border-b" style="border-color: #E8DED6; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-lg" style="background: linear-gradient(135deg, var(--color-wood), var(--color-accent));"></div>
                <div class="leading-snug">
                    <div class="text-lg font-semibold" style="color: var(--color-charcoal);">{{ config('app.name', 'Cerámica & Pisos') }}</div>
                    <div class="text-xs" style="color: var(--color-warm-gray);">Materiales Premium • Diseño & Calidad</div>
                </div>
            </div>

            <a
                href="{{ $loginUrl }}"
                class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-all duration-300 hover:shadow-lg"
                style="background: linear-gradient(135deg, var(--color-wood), var(--color-accent));"
            >
                Ingresar
            </a>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-16">
        <!-- Hero Section -->
        <section class="mb-20 max-w-4xl">
            <h1 class="text-5xl font-bold leading-tight" style="color: var(--color-charcoal);">
                Descubre nuestra <span class="gradient-text">colección premium</span> de cerámicas y pisos
            </h1>
            <p class="mt-6 text-lg leading-relaxed" style="color: var(--color-warm-gray);">
                Explora una selección cuidadosa de materiales de alta calidad. Visualiza en 3D, diseña tus espacios y solicita cotizaciones directamente desde nuestro catálogo interactivo.
            </p>
        </section>

        <!-- Products Preview -->
        <section class="mb-20">
            <h2 class="mb-8 text-3xl font-semibold" style="color: var(--color-charcoal);">Nuestros servicios</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Panel Administrativo -->
                <div class="group rounded-2xl border p-8 transition-all duration-300 hover:shadow-lg" style="border-color: #E8DED6; background: white;">
                    <div class="mb-4 inline-block rounded-lg p-3" style="background: rgba(139, 111, 71, 0.08);">
                        <svg class="h-6 w-6" style="color: var(--color-wood);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                    </div>
                    <div class="mb-3 text-lg font-semibold" style="color: var(--color-charcoal);">Panel Administrativo</div>
                    <div class="mb-5 text-sm leading-relaxed" style="color: var(--color-warm-gray);">
                        Acceso completo al catálogo, gestión de inventario, proyectos, promociones y reportes de ventas.
                    </div>
                    <a href="{{ $adminUrl }}" class="inline-flex items-center gap-2 text-sm font-semibold transition-all duration-300" style="color: var(--color-wood);">
                        Acceder al panel
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- Demo 3D Básica -->
                <div class="group rounded-2xl border p-8 transition-all duration-300 hover:shadow-lg" style="border-color: #E8DED6; background: white;">
                    <div class="mb-4 inline-block rounded-lg p-3" style="background: rgba(184, 149, 106, 0.08);">
                        <svg class="h-6 w-6" style="color: var(--color-accent);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mb-3 text-lg font-semibold" style="color: var(--color-charcoal);">Visualización 3D</div>
                    <div class="mb-5 text-sm leading-relaxed" style="color: var(--color-warm-gray);">
                        Explora materiales en un entorno tridimensional interactivo. Visualiza texturas y colores con detalle.
                    </div>
                    <a href="{{ route('three.demo') }}" class="inline-flex items-center gap-2 text-sm font-semibold transition-all duration-300" style="color: var(--color-accent);">
                        Abrir visualizador
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- Demo Diseño de Espacios -->
                <div class="group rounded-2xl border p-8 transition-all duration-300 hover:shadow-lg" style="border-color: #E8DED6; background: white;">
                    <div class="mb-4 inline-block rounded-lg p-3" style="background: rgba(155, 118, 83, 0.08);">
                        <svg class="h-6 w-6" style="color: var(--color-clay);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                        </svg>
                    </div>
                    <div class="mb-3 text-lg font-semibold" style="color: var(--color-charcoal);">Diseñador de Espacios</div>
                    <div class="mb-5 text-sm leading-relaxed" style="color: var(--color-warm-gray);">
                        Crea y personaliza espacios. Configura dimensiones, materiales y obtén cotizaciones en PDF automáticas.
                    </div>
                    <a href="{{ route('three.room') }}" class="inline-flex items-center gap-2 text-sm font-semibold transition-all duration-300" style="color: var(--color-clay);">
                        Diseñar ahora
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- Features Section -->
        <section class="mb-20 rounded-2xl p-12" style="background: linear-gradient(135deg, var(--color-wood) 0%, var(--color-accent) 100%); color: white;">
            <h2 class="mb-12 text-3xl font-semibold">Por qué elegirnos</h2>
            <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                <div>
                    <div class="mb-3 text-2xl font-bold opacity-90">✓</div>
                    <div class="mb-2 font-semibold">Catálogo Curado</div>
                    <div class="text-sm opacity-90">Selección premium de cerámicas y pisos de madera de la más alta calidad.</div>
                </div>
                <div>
                    <div class="mb-3 text-2xl font-bold opacity-90">✓</div>
                    <div class="mb-2 font-semibold">Visualización 3D</div>
                    <div class="text-sm opacity-90">Tecnología avanzada para visualizar materiales antes de comprar.</div>
                </div>
                <div>
                    <div class="mb-3 text-2xl font-bold opacity-90">✓</div>
                    <div class="mb-2 font-semibold">Cotizaciones Rápidas</div>
                    <div class="text-sm opacity-90">Genera presupuestos en PDF con cálculos automáticos y precisos.</div>
                </div>
                <div>
                    <div class="mb-3 text-2xl font-bold opacity-90">✓</div>
                    <div class="mb-2 font-semibold">Diseño Personalizado</div>
                    <div class="text-sm opacity-90">Herramientas para diseñar y adaptar espacios a tus necesidades.</div>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section class="rounded-2xl border-2 p-12 text-center" style="border-color: #E8DED6;">
            <h2 class="mb-4 text-2xl font-semibold" style="color: var(--color-charcoal);">Comienza tu proyecto</h2>
            <p class="mb-8 text-base" style="color: var(--color-warm-gray);">
                Inicia sesión en el panel administrativo para acceder a todas las funcionalidades de gestión y cotización.
            </p>
            <a
                href="{{ $loginUrl }}"
                class="inline-block rounded-lg px-8 py-3 text-base font-semibold text-white transition-all duration-300 hover:shadow-lg"
                style="background: linear-gradient(135deg, var(--color-wood), var(--color-accent));"
            >
                Ingresar al Sistema
            </a>
        </section>
    </main>

    <footer class="border-t mt-20" style="border-color: #E8DED6; background: white;">
        <div class="mx-auto max-w-7xl px-6 py-8 text-center text-sm" style="color: var(--color-warm-gray);">
            <p>{{ config('app.name', 'Cerámica & Pisos Premium') }} • © {{ now()->format('Y') }}</p>
            <p class="mt-2 text-xs">Materiales de calidad para espacios extraordinarios</p>
        </div>
    </footer>
</body>
</html>
