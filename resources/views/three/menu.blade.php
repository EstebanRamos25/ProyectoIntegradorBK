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
    @php
        $isAdmin = !empty($isAdmin);
        $clientScenes = isset($clientScenes) ? $clientScenes : collect();
        $uniqueClients = $clientScenes
            ->map(fn ($s) => $s->user)
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();
    @endphp

    <div class="mx-auto max-w-5xl px-6 py-10">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl font-semibold">Experiencia 3D</h1>
                <p class="mt-1 text-sm text-slate-600">
                    @if($isAdmin)
                        Gestiona tus propios escenarios 3D y supervisa los proyectos creados por los clientes.
                    @else
                        Gestiona tus escenarios guardados y abre el editor 3D.
                    @endif
                </p>
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
                        + Crear escenario
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

            @if (session('error'))
                <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" style="border-color:#fecaca;background-color:#fef2f2;color:#991b1b;">
                    {{ session('error') }}
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
                @if($isAdmin)
                    <!-- Barra de Pestañas para Administrador -->
                    <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 pb-4">
                        <div class="flex flex-wrap items-center gap-2">
                            <button
                                type="button"
                                id="tab-btn-own"
                                onclick="switchThreeMenuTab('own')"
                                class="inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition"
                            >
                                <span>Mis proyectos</span>
                                <span class="rounded-full bg-white/20 px-2 py-0.5 text-xs font-bold">{{ $scenes->count() }}</span>
                            </button>
                            <button
                                type="button"
                                id="tab-btn-clients"
                                onclick="switchThreeMenuTab('clients')"
                                class="inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition"
                            >
                                <span>Proyectos de clientes</span>
                                <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs font-bold text-slate-700">{{ $clientScenes->count() }}</span>
                            </button>
                        </div>
                        <div class="text-xs text-slate-500">
                            Vista de Administrador
                        </div>
                    </div>
                @endif

                <!-- SECCIÓN 1: MIS PROYECTOS -->
                <div id="tab-panel-own">
                    @if($scenes->isEmpty())
                        <div class="py-14 text-center">
                            <div class="text-lg font-semibold">Aún no tienes escenarios propios guardados</div>
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
                                <div class="mt-1 text-xs text-slate-500">Selecciona uno para abrirlo en el editor 3D. Puedes eliminar proyectos que aún no hayan sido enviados al administrador.</div>
                            </div>
                        </div>

                        <div class="mt-5 grid grid-cols-1 gap-3 sm:grid-cols-2">
                            @foreach($scenes as $scene)
                                @php
                                    $quote = isset($quotesByScene) ? ($quotesByScene->get($scene->id) ?? null) : null;
                                    $canDelete = ((int) $scene->user_id === (int) $user->id)
                                        && (!$quote || !in_array($quote->status, ['sent', 'sold'], true));
                                @endphp
                                <div class="flex flex-col justify-between rounded-xl border border-slate-200 p-4 hover:border-slate-300">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="font-semibold text-slate-900">{{ $scene->name }}</div>
                                            <div class="mt-1 text-xs text-slate-500">Actualizado: {{ optional($scene->updated_at)->format('d/m/Y H:i') }}</div>
                                        </div>
                                        <a
                                            href="{{ route('three.editor', ['sceneId' => $scene->id]) }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                        >
                                            Abrir
                                        </a>
                                    </div>

                                    <div class="mt-4 flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                                        <div class="flex flex-wrap items-center gap-2">
                                            @if($quote)
                                                <a
                                                    href="{{ route('three.quotes.download', ['quoteId' => $quote->id]) }}"
                                                    target="_blank"
                                                    rel="noopener"
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
                                            @else
                                                <span class="inline-flex items-center rounded-lg bg-slate-100 px-2.5 py-1.5 text-xs text-slate-500">
                                                    Sin cotización aún
                                                </span>
                                            @endif
                                        </div>

                                        @if($canDelete)
                                            <form
                                                method="POST"
                                                action="{{ route('three.scenes.destroy', ['sceneId' => $scene->id]) }}"
                                                onsubmit="return confirm('¿Estás seguro de que deseas eliminar el proyecto «{{ addslashes($scene->name) }}»? Esta acción no se puede deshacer.');"
                                            >
                                                @csrf
                                                @method('DELETE')
                                                <button
                                                    type="submit"
                                                    class="inline-flex items-center rounded-lg border px-3 py-2 text-xs font-semibold transition"
                                                    style="border-color:#fecaca;background-color:#fef2f2;color:#dc2626;"
                                                    onmouseover="this.style.backgroundColor='#fee2e2'"
                                                    onmouseout="this.style.backgroundColor='#fef2f2'"
                                                    title="Eliminar proyecto"
                                                >
                                                    Eliminar
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- SECCIÓN 2: PROYECTOS DE CLIENTES (Solo Admin) -->
                @if($isAdmin)
                    <div id="tab-panel-clients" style="display: none;">
                        <div class="flex flex-col gap-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <div class="text-sm font-semibold text-slate-700">Proyectos de clientes</div>
                                    <div class="mt-1 text-xs text-slate-500">Visualiza los escenarios diseñados por los clientes y revisa sus cotizaciones.</div>
                                </div>
                                <div id="clients-count-label" class="text-xs font-semibold text-slate-600">
                                    Mostrando {{ $clientScenes->count() }} de {{ $clientScenes->count() }} proyectos
                                </div>
                            </div>

                            <!-- Barra de Filtros -->
                            <div class="grid grid-cols-1 gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3.5 sm:grid-cols-3">
                                <div>
                                    <label for="filter-search" class="block text-xs font-semibold text-slate-600 mb-1">Buscar proyecto o cliente</label>
                                    <input
                                        type="text"
                                        id="filter-search"
                                        oninput="filterClientScenes()"
                                        placeholder="Nombre del proyecto, cliente o correo..."
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-slate-500 focus:outline-none"
                                    >
                                </div>

                                <div>
                                    <label for="filter-client" class="block text-xs font-semibold text-slate-600 mb-1">Filtrar por cliente</label>
                                    <select
                                        id="filter-client"
                                        onchange="filterClientScenes()"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-slate-500 focus:outline-none"
                                    >
                                        <option value="all">Todos los clientes ({{ $uniqueClients->count() }})</option>
                                        @foreach($uniqueClients as $clientUser)
                                            <option value="{{ $clientUser->id }}">
                                                {{ $clientUser->name }} ({{ $clientUser->email }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="filter-status" class="block text-xs font-semibold text-slate-600 mb-1">Estado de cotización</label>
                                    <select
                                        id="filter-status"
                                        onchange="filterClientScenes()"
                                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs text-slate-900 focus:border-slate-500 focus:outline-none"
                                    >
                                        <option value="all">Todos los estados</option>
                                        <option value="sent">Enviada al admin</option>
                                        <option value="draft">En borrador (no enviada)</option>
                                        <option value="none">Sin cotización</option>
                                        <option value="sold">Vendida</option>
                                    </select>
                                </div>
                            </div>

                            @if($clientScenes->isEmpty())
                                <div class="py-12 text-center">
                                    <div class="text-base font-semibold text-slate-700">Ningún cliente ha guardado proyectos todavía</div>
                                    <div class="mt-1 text-xs text-slate-500">Cuando los clientes creen escenarios en el editor 3D, aparecerán listados aquí.</div>
                                </div>
                            @else
                                <div id="client-scenes-grid" class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-2">
                                    @foreach($clientScenes as $scene)
                                        @php
                                            $quote = isset($quotesByScene) ? ($quotesByScene->get($scene->id) ?? null) : null;
                                            $quoteStatus = $quote ? (string) $quote->status : 'none';
                                            $clientName = optional($scene->user)->name ?? ('Usuario #' . $scene->user_id);
                                            $clientEmail = optional($scene->user)->email ?? '';
                                            $searchText = mb_strtolower($scene->name . ' ' . $clientName . ' ' . $clientEmail);
                                        @endphp
                                        <div
                                            class="client-scene-card flex flex-col justify-between rounded-xl border border-slate-200 p-4 hover:border-slate-300"
                                            data-search="{{ e($searchText) }}"
                                            data-client-id="{{ $scene->user_id }}"
                                            data-quote-status="{{ $quoteStatus }}"
                                        >
                                            <div>
                                                <div class="flex items-start justify-between gap-3">
                                                    <div>
                                                        <div class="font-semibold text-slate-900">{{ $scene->name }}</div>
                                                        <div class="mt-1 text-xs text-slate-600">
                                                            <span class="font-medium text-slate-800">Cliente:</span> {{ $clientName }}
                                                            @if($clientEmail)
                                                                <span class="text-slate-400">({{ $clientEmail }})</span>
                                                            @endif
                                                        </div>
                                                        <div class="mt-1 text-xs text-slate-500">
                                                            Actualizado: {{ optional($scene->updated_at)->format('d/m/Y H:i') }}
                                                        </div>
                                                    </div>
                                                    <a
                                                        href="{{ route('three.editor', ['sceneId' => $scene->id]) }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="inline-flex shrink-0 items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold text-white hover:bg-slate-800"
                                                    >
                                                        Abrir
                                                    </a>
                                                </div>
                                            </div>

                                            <div class="mt-4 flex flex-wrap items-center justify-between gap-2 pt-2 border-t border-slate-100">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    @if($quote && $quote->pdf_path)
                                                        <a
                                                            href="{{ route('three.quotes.download', ['quoteId' => $quote->id]) }}"
                                                            target="_blank"
                                                            rel="noopener"
                                                            class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold text-slate-900 hover:bg-slate-50"
                                                        >
                                                            Ver cotización
                                                        </a>
                                                    @endif

                                                    @if($quoteStatus === 'sent')
                                                        <span class="inline-flex items-center rounded-lg bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                                                            Enviada al admin
                                                        </span>
                                                    @elseif($quoteStatus === 'sold')
                                                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-700">
                                                            Vendida
                                                        </span>
                                                    @elseif($quoteStatus === 'draft')
                                                        <span class="inline-flex items-center rounded-lg px-3 py-1.5 text-xs font-semibold" style="background-color:#fef3c7;color:#92400e;">
                                                            Borrador (No enviada)
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs text-slate-500">
                                                            Sin cotización
                                                        </span>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <div id="client-scenes-empty-filter" class="py-10 text-center" style="display: none;">
                                    <div class="text-sm font-semibold text-slate-700">No se encontraron proyectos con esos filtros</div>
                                    <div class="mt-1 text-xs text-slate-500">Intenta cambiar el cliente, estado o término de búsqueda.</div>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endif
        </div>

        <div class="mt-6 text-center text-xs text-slate-500">
            Nota: el guardado y la carga de escenarios están asociados a cada usuario.
        </div>
    </div>

    @if($isAdmin)
    <script>
        function switchThreeMenuTab(tab) {
            var ownPanel = document.getElementById('tab-panel-own');
            var clientsPanel = document.getElementById('tab-panel-clients');
            var ownBtn = document.getElementById('tab-btn-own');
            var clientsBtn = document.getElementById('tab-btn-clients');

            if (!ownPanel || !clientsPanel || !ownBtn || !clientsBtn) return;

            var activeClass = 'inline-flex items-center gap-2 rounded-lg bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition';
            var inactiveClass = 'inline-flex items-center gap-2 rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50 transition';

            if (tab === 'clients') {
                ownPanel.style.display = 'none';
                clientsPanel.style.display = 'block';
                ownBtn.className = inactiveClass;
                clientsBtn.className = activeClass;
            } else {
                ownPanel.style.display = 'block';
                clientsPanel.style.display = 'none';
                ownBtn.className = activeClass;
                clientsBtn.className = inactiveClass;
            }
        }

        function filterClientScenes() {
            var searchInput = document.getElementById('filter-search');
            var clientSelect = document.getElementById('filter-client');
            var statusSelect = document.getElementById('filter-status');
            var cards = document.querySelectorAll('.client-scene-card');
            var countLabel = document.getElementById('clients-count-label');
            var emptyFilterMsg = document.getElementById('client-scenes-empty-filter');

            var query = (searchInput ? searchInput.value : '').toLowerCase().trim();
            var selectedClient = clientSelect ? clientSelect.value : 'all';
            var selectedStatus = statusSelect ? statusSelect.value : 'all';

            var total = cards.length;
            var visible = 0;

            cards.forEach(function (card) {
                var cardSearch = (card.getAttribute('data-search') || '').toLowerCase();
                var cardClient = card.getAttribute('data-client-id') || '';
                var cardStatus = card.getAttribute('data-quote-status') || 'none';

                var matchesSearch = !query || cardSearch.indexOf(query) !== -1;
                var matchesClient = (selectedClient === 'all') || (cardClient === selectedClient);
                var matchesStatus = (selectedStatus === 'all') || (cardStatus === selectedStatus);

                if (matchesSearch && matchesClient && matchesStatus) {
                    card.style.display = '';
                    visible++;
                } else {
                    card.style.display = 'none';
                }
            });

            if (countLabel) {
                countLabel.textContent = 'Mostrando ' + visible + ' de ' + total + ' proyectos';
            }

            if (emptyFilterMsg) {
                emptyFilterMsg.style.display = (visible === 0 && total > 0) ? 'block' : 'none';
            }
        }
    </script>
    @endif
</body>
</html>
