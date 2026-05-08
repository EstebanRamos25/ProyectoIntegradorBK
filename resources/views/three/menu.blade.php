<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Experiencia 3D - Escenarios</title>
    @vite(['resources/css/app.css'])
</head>
<body class="min-h-screen bg-slate-50 text-slate-900">
    <div class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Experiencia 3D</h1>
                <p class="mt-1 text-sm text-slate-600">Gestiona tus escenarios guardados y abre el editor 3D.</p>
            </div>
            <div class="flex items-center gap-3">
                @if($user)
                    @if(method_exists($user, 'inRole') && $user->inRole('client'))
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button
                                type="submit"
                                class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-900 hover:bg-slate-50"
                            >
                                Cerrar sesión
                            </button>
                        </form>
                    @endif
                    <a
                        href="{{ route('three.editor', ['new' => 1]) }}"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Crear escenario
                    </a>
                @else
                    <a
                        href="{{ url('/login') }}"
                        class="inline-flex items-center rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white hover:bg-slate-800"
                    >
                        Iniciar sesión
                    </a>
                @endif
            </div>
        </div>

        <div class="mt-8 rounded-2xl border border-slate-200 bg-white p-6">
            @if (session('status'))
                <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                    {{ session('status') }}
                </div>
            @endif
            @if(!$user)
                <div class="py-10 text-center">
                    <div class="text-lg font-semibold">Inicia sesión para ver tus proyectos</div>
                    <div class="mt-2 text-sm text-slate-600">Los escenarios se guardan por usuario, así cada cliente mantiene su propio historial.</div>
                    <a
                        href="{{ url('/login') }}"
                        class="mt-6 inline-flex items-center rounded-lg bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-700"
                    >
                        Iniciar sesión
                    </a>
                </div>
            @else
                @if($scenes->isEmpty())
                    <div class="py-14 text-center">
                        <div class="text-lg font-semibold">Aún no tienes escenarios guardados</div>
                        <div class="mt-2 text-sm text-slate-600">Crea tu primer escenario y podrás revisarlo y editarlo cuando quieras.</div>
                        <a
                            href="{{ route('three.editor', ['new' => 1]) }}"
                            target="_blank"
                            rel="noopener"
                            class="mt-8 inline-flex items-center rounded-xl bg-emerald-600 px-7 py-3 text-base font-semibold text-white hover:bg-emerald-700"
                        >
                            Crear escenario
                        </a>
                    </div>
                @else
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-sm font-semibold text-slate-700">Tus proyectos</div>
                            <div class="mt-1 text-xs text-slate-500">Selecciona uno para abrirlo en el editor 3D.</div>
                        </div>
                        <a
                            href="{{ route('three.editor', ['new' => 1]) }}"
                            target="_blank"
                            rel="noopener"
                            class="inline-flex items-center rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700"
                        >
                            Crear escenario
                        </a>
                    </div>

                    <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                        @foreach($scenes as $scene)
                            @php
                                $quote = isset($quotesByScene) ? ($quotesByScene->get($scene->id) ?? null) : null;
                            @endphp
                            <div class="rounded-xl border border-slate-200 p-4 hover:border-slate-300">
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <div class="font-semibold">{{ $scene->name }}</div>
                                        <div class="mt-1 text-xs text-slate-500">Actualizado: {{ optional($scene->updated_at)->format('d/m/Y H:i') }}</div>
                                    </div>
                                    <a
                                        href="{{ route('three.editor', ['sceneId' => $scene->id]) }}"
                                        class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                    >
                                        Abrir
                                    </a>
                                </div>

                                @if($quote)
                                    <div class="mt-4 flex flex-wrap items-center gap-2">
                                        <a
                                            href="{{ route('three.quotes.download', ['quoteId' => $quote->id]) }}"
                                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-50"
                                        >
                                            Ver cotización
                                        </a>

                                        @if($quote->status === 'sent')
                                            <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-2 text-xs font-semibold text-emerald-700">
                                                Enviada
                                            </span>
                                        @elseif($quote->status === 'sold')
                                            <span class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-2 text-xs font-semibold text-slate-700">
                                                Vendida
                                            </span>
                                        @else
                                            <form method="POST" action="{{ route('three.quotes.send', ['quoteId' => $quote->id]) }}">
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-700"
                                                >
                                                    Enviar al admin
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif
        </div>

        <div class="mt-6 text-center text-xs text-slate-500">
            Nota: el guardado y la carga de escenarios están asociados a tu usuario.
        </div>
    </div>
</body>
</html>
