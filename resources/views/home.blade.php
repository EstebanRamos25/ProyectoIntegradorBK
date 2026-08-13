<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'Sistema') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js', 'resources/js/home.js'])
    
    <style>
        :root {
            --color-cream: #FDFBF7;
            --color-charcoal: #2C2C2C;
            --color-wood: #8B6F47;
            --color-warm-gray: #A39A94;
            --color-sand: #D2B48C;
            --color-clay: #9B7653;
            --color-accent: #B8956A;
            --color-border: #E8DED6;

            --surface: rgba(255,255,255,0.9);
            --surface-solid: #ffffff;
            --surface-soft: rgba(255,255,255,0.80);
            --surface-glass: rgba(255, 255, 255, 0.75);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.06);
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

        .page-bg {
            background:
                radial-gradient(900px 500px at 10% 0%, rgba(184, 149, 106, 0.18), transparent 60%),
                radial-gradient(800px 520px at 90% 20%, rgba(139, 111, 71, 0.14), transparent 55%),
                var(--color-cream);
        }

        .glass {
            background: var(--surface-glass);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid var(--color-border);
        }

        .image-tile {
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            border: 1px solid var(--color-border);
        }

        [data-home-product-card].is-active {
            border-color: rgba(184, 149, 106, 0.7) !important;
            box-shadow: 0 16px 32px rgba(139, 111, 71, 0.16);
            transform: translateY(-2px);
        }

        /* Dark premium hero (solo para la sección Hero) */
        .hero-premium {
            position: relative;
            border: 1px solid rgba(232, 222, 214, 0.25);
            background:
                radial-gradient(900px 520px at 10% 0%, rgba(184, 149, 106, 0.20), transparent 60%),
                radial-gradient(700px 520px at 90% 30%, rgba(139, 111, 71, 0.16), transparent 55%),
                linear-gradient(135deg, rgba(44, 44, 44, 0.98), rgba(44, 44, 44, 0.86));
        }

        .hero-premium .hero-pill {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: rgba(255, 255, 255, 0.88);
        }

        .hero-premium .hero-title {
            color: rgba(255, 255, 255, 0.96);
        }

        .hero-premium .hero-subtitle {
            color: rgba(255, 255, 255, 0.72);
        }

        .hero-premium .hero-secondary {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.14);
            color: rgba(255, 255, 255, 0.90);
        }

        .hero-premium .hero-card {
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border: 1px solid rgba(255, 255, 255, 0.12);
        }

        /* Premium dark global */
        body.premium-dark {
            --color-cream: #0B0B0C;
            --color-charcoal: rgba(255,255,255,0.96);
            --color-warm-gray: rgba(255,255,255,0.70);
            --color-border: rgba(255,255,255,0.12);

            --surface: rgba(18,18,20,0.88);
            --surface-solid: #121214;
            --surface-soft: rgba(255,255,255,0.08);
            --surface-glass: rgba(255,255,255,0.06);
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.55);
        }

        body.premium-dark .page-bg {
            background:
                radial-gradient(900px 500px at 10% 0%, rgba(184, 149, 106, 0.14), transparent 60%),
                radial-gradient(800px 520px at 90% 20%, rgba(139, 111, 71, 0.12), transparent 55%),
                var(--color-cream);
        }

        body.premium-dark a:hover {
            filter: brightness(1.05);
        }
    </style>
</head>
<body class="min-h-screen page-bg premium-dark">
    @php
        $prefix = trim(config('platform.prefix', '/admin'), '/');
        $adminUrl = '/' . $prefix;
        $adminLoginUrl = $adminUrl . '/login';
        $clientLoginUrl = route('login');
        $clientRegisterUrl = route('register');
        $homeProducts = collect($homeProducts ?? []);
        $firstHomeProduct = $homeProducts->first();

        // Sube tus imágenes a: public/images/home/
        // Puedes reemplazar los archivos manteniendo estos nombres.
        $homeImages = [
            'hero' => asset('images/home/hero.jpg'),
            'reformas' => asset('images/home/reformas.jpg'),
            'construccion' => asset('images/home/construccion.jpg'),
            'interiores' => asset('images/home/interiores.png'),
            'acabados' => asset('images/home/acabados.jpg'),
        ];
    @endphp

    <header class="border-b" style="border-color: var(--color-border); background: var(--surface); box-shadow: var(--shadow-sm);">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-6 py-5">
            <div class="flex items-center gap-4">
                <div class="h-10 w-10 rounded-lg" style="background: linear-gradient(135deg, var(--color-wood), var(--color-accent));"></div>
                <div class="leading-snug">
                    <div class="text-lg font-semibold" style="color: var(--color-charcoal);">{{ config('app.name', 'Cerámica & Pisos') }}</div>
                    <div class="text-xs" style="color: var(--color-warm-gray);">Materiales Premium • Diseño & Calidad</div>
                </div>
            </div>

            <div class="flex items-center gap-3">
                @auth
                    @if(!auth()->user()->inRole('client'))
                        <a
                            href="{{ $adminUrl }}"
                            class="hidden text-sm font-semibold sm:inline-flex"
                            style="color: var(--color-warm-gray);"
                        >
                            Panel Admin
                        </a>
                    @endif
                @endauth

                <a
                    href="{{ $clientLoginUrl }}"
                    class="rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition-all duration-300 hover:shadow-lg"
                    style="background: linear-gradient(135deg, var(--color-wood), var(--color-accent));"
                >
                    Ingresar
                </a>
            </div>
        </div>
    </header>

    <main class="mx-auto max-w-7xl px-6 py-14">
        <!-- Hero Section -->
        <section class="hero-premium mb-16 grid items-center gap-10 overflow-hidden rounded-3xl p-8 sm:p-10 lg:grid-cols-12">
            <div class="lg:col-span-6">
                <div class="hero-pill inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold">
                    Catálogo premium • Visualización 3D • Cotización rápida
                </div>
                <h1 class="hero-title mt-6 text-5xl font-bold leading-tight">
                    Descubre nuestra <span class="gradient-text">colección premium</span> de cerámicas y pisos
                </h1>
                <p class="hero-subtitle mt-6 text-lg leading-relaxed">
                    Explora una selección cuidadosa de materiales de alta calidad. Visualiza en 3D, diseña tus espacios y solicita cotizaciones directamente desde nuestro catálogo interactivo.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a
                        href="{{ route('three.menu') }}"
                        class="rounded-lg px-6 py-3 text-sm font-semibold text-white transition-all duration-300 hover:shadow-lg"
                        style="background: linear-gradient(135deg, var(--color-wood), var(--color-accent));"
                    >
                        Ver en 3D
                    </a>
                    <a
                        href="{{ route('three.room') }}"
                        class="hero-secondary rounded-lg px-6 py-3 text-sm font-semibold transition-all duration-300"
                    >
                        Diseñar un espacio
                    </a>
                    @guest
                        <a
                            href="{{ $clientRegisterUrl }}"
                            class="hero-secondary rounded-lg px-6 py-3 text-sm font-semibold transition-all duration-300"
                        >
                            Crear cuenta
                        </a>
                    @endguest
                </div>
            </div>

            <div class="lg:col-span-6">
                <div class="hero-card overflow-hidden rounded-2xl p-3">
                    <div
                        class="image-tile relative aspect-[16/11] w-full overflow-hidden rounded-xl"
                        style="background-image:
                            linear-gradient(135deg, rgba(44,44,44,0.05), rgba(184,149,106,0.15)),
                            url('{{ $homeImages['hero'] }}');"
                    >
                        <div class="absolute inset-x-0 bottom-0 p-6">
                            <div class="inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold" style="background: rgba(44,44,44,0.55); border: 1px solid rgba(255,255,255,0.12); color: rgba(255,255,255,0.92);">
                                Ideas para reformas, construcción y acabados
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div class="image-tile aspect-[4/3] rounded-xl" style="background-image:
                            linear-gradient(135deg, rgba(139,111,71,0.10), rgba(184,149,106,0.12)),
                            url('{{ $homeImages['reformas'] }}');"></div>
                        <div class="image-tile aspect-[4/3] rounded-xl" style="background-image:
                            linear-gradient(135deg, rgba(155,118,83,0.10), rgba(184,149,106,0.12)),
                            url('{{ $homeImages['construccion'] }}');"></div>
                        <div class="image-tile aspect-[4/3] rounded-xl" style="background-image:
                            linear-gradient(135deg, rgba(44,44,44,0.06), rgba(139,111,71,0.10)),
                            url('{{ $homeImages['interiores'] }}');"></div>
                    </div>
                </div>
               
            </div>
        </section>

        <!-- Inspiration Gallery -->
        <section class="mb-20">
            <div class="mb-8 flex items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-semibold" style="color: var(--color-charcoal);">Inspiración para tus proyectos</h2>
                    <p class="mt-2 text-sm" style="color: var(--color-warm-gray);">Espacios listos para mostrar reformas, construcción, interiores y acabados.</p>
                </div>
                <a href="{{ $adminUrl }}" class="hidden text-sm font-semibold sm:inline-flex" style="color: var(--color-wood);">
                    Gestionar catálogo
                </a>
            </div>

            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                <div class="group overflow-hidden rounded-2xl">
                    <div class="image-tile aspect-[4/5] rounded-2xl" style="background-image:
                        linear-gradient(180deg, rgba(44,44,44,0.05), rgba(44,44,44,0.20)),
                        url('{{ $homeImages['reformas'] }}');"></div>
                    <div class="-mt-10 px-4">
                        <div class="glass rounded-xl px-4 py-3">
                            <div class="text-sm font-semibold" style="color: var(--color-charcoal);">Reformas</div>
                            <div class="text-xs" style="color: var(--color-warm-gray);">Antes / después y cambios de ambiente</div>
                        </div>
                    </div>
                </div>

                <div class="group overflow-hidden rounded-2xl">
                    <div class="image-tile aspect-[4/5] rounded-2xl" style="background-image:
                        linear-gradient(180deg, rgba(44,44,44,0.05), rgba(44,44,44,0.20)),
                        url('{{ $homeImages['construccion'] }}');"></div>
                    <div class="-mt-10 px-4">
                        <div class="glass rounded-xl px-4 py-3">
                            <div class="text-sm font-semibold" style="color: var(--color-charcoal);">Construcción</div>
                            <div class="text-xs" style="color: var(--color-warm-gray);">Obras, avances y materiales en contexto</div>
                        </div>
                    </div>
                </div>

                <div class="group overflow-hidden rounded-2xl">
                    <div class="image-tile aspect-[4/5] rounded-2xl" style="background-image:
                        linear-gradient(180deg, rgba(44,44,44,0.05), rgba(44,44,44,0.20)),
                        url('{{ $homeImages['interiores'] }}');"></div>
                    <div class="-mt-10 px-4">
                        <div class="glass rounded-xl px-4 py-3">
                            <div class="text-sm font-semibold" style="color: var(--color-charcoal);">Interiores</div>
                            <div class="text-xs" style="color: var(--color-warm-gray);">Cocinas, baños, salas y recámaras</div>
                        </div>
                    </div>
                </div>

                <div class="group overflow-hidden rounded-2xl">
                    <div class="image-tile aspect-[4/5] rounded-2xl" style="background-image:
                        linear-gradient(180deg, rgba(44,44,44,0.05), rgba(44,44,44,0.20)),
                        url('{{ $homeImages['acabados'] }}');"></div>
                    <div class="-mt-10 px-4">
                        <div class="glass rounded-xl px-4 py-3">
                            <div class="text-sm font-semibold" style="color: var(--color-charcoal);">Acabados</div>
                            <div class="text-xs" style="color: var(--color-warm-gray);">Detalles, texturas y combinaciones</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Productos -->
        <section class="mb-20">
            <div class="mb-8 flex items-end justify-between gap-6">
                <div>
                    <h2 class="text-3xl font-semibold" style="color: var(--color-charcoal);">Productos destacados</h2>
                    <p class="mt-2 text-sm" style="color: var(--color-warm-gray);">Productos reales registrados en el sistema, con vista 3D rotatable para una primera impresión más atractiva.</p>
                </div>
                <a href="{{ route('three.menu') }}" class="hidden text-sm font-semibold sm:inline-flex" style="color: var(--color-wood);">
                    Ver catálogo 3D completo
                </a>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1.05fr_0.95fr]">
                <div class="hero-card overflow-hidden rounded-3xl p-5">
                    <div class="mb-4 flex items-start justify-between gap-4">
                        <div>
                            <div class="text-xs font-semibold uppercase tracking-[0.24em]" style="color: var(--color-warm-gray);">Vista 3D interactiva</div>
                            <h3 class="mt-2 text-2xl font-semibold" data-home-3d-title style="color: var(--color-charcoal);">{{ data_get($firstHomeProduct, 'name', 'Selecciona un producto') }}</h3>
                            <p class="mt-2 text-sm" data-home-3d-meta style="color: var(--color-warm-gray);">
                                {{ data_get($firstHomeProduct, 'category', 'Arrastra para rotar y explorar texturas') }}
                            </p>
                        </div>
                        <div class="rounded-full px-4 py-2 text-xs font-semibold" style="background: rgba(184,149,106,0.14); color: var(--color-wood);">
                            Arrastra para rotar
                        </div>
                    </div>

                    <div class="relative overflow-hidden rounded-2xl border" style="border-color: var(--color-border); background: linear-gradient(180deg, rgba(255,255,255,0.78), rgba(255,255,255,0.95));">
                        <canvas data-home-3d-canvas class="block h-[420px] w-full"></canvas>
                        <div data-home-3d-empty class="absolute inset-0 flex items-center justify-center px-6 text-center text-sm font-medium" style="color: var(--color-warm-gray);">
                            @if($homeProducts->isEmpty())
                                Aún no hay productos registrados para mostrar en el visor 3D.
                            @else
                                Cargando visualización 3D del producto...
                            @endif
                        </div>
                    </div>
                </div>

                <div>
                    @if($homeProducts->isEmpty())
                        <div class="rounded-3xl border p-8 text-sm" style="border-color: var(--color-border); background: var(--surface-solid); color: var(--color-warm-gray);">
                            Aún no hay productos registrados para mostrar en el home.
                        </div>
                    @else
                        <div class="grid gap-4 sm:grid-cols-2">
                            @foreach($homeProducts->take(8) as $product)
                                <button
                                    type="button"
                                    data-home-product-card
                                    data-home-product-image="{{ $product['image'] }}"
                                    data-home-product-name="{{ $product['name'] }}"
                                    data-home-product-category="{{ $product['category'] ?: 'Sin categoría' }}"
                                    class="group overflow-hidden rounded-2xl border text-left transition-all duration-300 hover:-translate-y-1 hover:shadow-lg"
                                    style="border-color: var(--color-border); background: var(--surface-solid);"
                                >
                                    <div class="relative aspect-[4/3] overflow-hidden">
                                        <img
                                            src="{{ $product['image'] }}"
                                            alt="{{ $product['name'] }}"
                                            class="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                                            loading="lazy"
                                        >
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/40 via-transparent to-transparent"></div>
                                        <div class="absolute left-3 top-3 rounded-full px-3 py-1 text-[11px] font-semibold" style="background: rgba(11,11,12,0.72); color: rgba(255,255,255,0.92);">
                                            @if($loop->first)
                                                Vista inicial
                                            @else
                                                Toca para rotar
                                            @endif
                                        </div>
                                    </div>
                                    <div class="space-y-2 p-4">
                                        <div class="text-sm font-semibold" style="color: var(--color-charcoal);">{{ $product['name'] }}</div>
                                        <div class="text-xs uppercase tracking-[0.18em]" style="color: var(--color-warm-gray);">{{ $product['category'] ?: 'Sin categoría' }}</div>
                                        <div class="flex items-center justify-between pt-2 text-sm">
                                            <span style="color: var(--color-warm-gray);">Precio</span>
                                            <span class="font-semibold" style="color: var(--color-wood);">{{ $product['price'] !== null ? '$' . number_format((float) $product['price'], 2) : '—' }}</span>
                                        </div>
                                    </div>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <!-- Products Preview -->
        <section class="mb-20">
            <h2 class="mb-8 text-3xl font-semibold" style="color: var(--color-charcoal);">Nuestros servicios</h2>
            <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <!-- Panel Administrativo -->
                <div class="group rounded-2xl border p-8 transition-all duration-300 hover:shadow-lg" style="border-color: var(--color-border); background: var(--surface-solid);">
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
                <div class="group rounded-2xl border p-8 transition-all duration-300 hover:shadow-lg" style="border-color: var(--color-border); background: var(--surface-solid);">
                    <div class="mb-4 inline-block rounded-lg p-3" style="background: rgba(184, 149, 106, 0.08);">
                        <svg class="h-6 w-6" style="color: var(--color-accent);" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div class="mb-3 text-lg font-semibold" style="color: var(--color-charcoal);">Visualización 3D</div>
                    <div class="mb-5 text-sm leading-relaxed" style="color: var(--color-warm-gray);">
                        Explora materiales en un entorno tridimensional interactivo. Visualiza texturas y colores con detalle.
                    </div>
                    <a href="{{ route('three.menu') }}" class="inline-flex items-center gap-2 text-sm font-semibold transition-all duration-300" style="color: var(--color-accent);">
                        Abrir visualizador
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>

                <!-- Demo Diseño de Espacios -->
                <div class="group rounded-2xl border p-8 transition-all duration-300 hover:shadow-lg" style="border-color: var(--color-border); background: var(--surface-solid);">
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
        <section class="rounded-2xl border-2 p-12 text-center" style="border-color: var(--color-border); background: var(--surface-glass);">
            <h2 class="mb-4 text-2xl font-semibold" style="color: var(--color-charcoal);">Comienza tu proyecto</h2>
            <p class="mb-8 text-base" style="color: var(--color-warm-gray);">
                Crea tu cuenta para usar la experiencia 3D, guardar tus escenas y generar cotizaciones.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <a
                    href="{{ $clientRegisterUrl }}"
                    class="inline-block rounded-lg px-8 py-3 text-base font-semibold text-white transition-all duration-300 hover:shadow-lg"
                    style="background: linear-gradient(135deg, var(--color-wood), var(--color-accent));"
                >
                    Crear cuenta
                </a>
                <a
                    href="{{ $clientLoginUrl }}"
                    class="hero-secondary inline-block rounded-lg px-8 py-3 text-base font-semibold transition-all duration-300"
                >
                    Ya tengo cuenta
                </a>
            </div>
        </section>
    </main>

    <footer class="border-t mt-20" style="border-color: var(--color-border); background: var(--surface);">
        <div class="mx-auto max-w-7xl px-6 py-8 text-center text-sm" style="color: var(--color-warm-gray);">
            <p>{{ config('app.name', 'Cerámica & Pisos Premium') }} • © {{ now()->format('Y') }}</p>
            <p class="mt-2 text-xs">Materiales de calidad para espacios extraordinarios</p>
        </div>
    </footer>
</body>
</html>
