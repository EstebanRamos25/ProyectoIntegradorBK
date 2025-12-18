@php
    /** @var \Illuminate\Support\Collection|array $featuredProducts */
    $items = collect($featuredProducts ?? []);
@endphp

<div class="bg-white rounded shadow-sm p-4">
    <div class="d-flex align-items-center justify-content-between mb-3">
        <div>
            <div class="h5 mb-1">Vistazo de productos</div>
            <div class="text-muted">Últimos productos, listos para usar en interiores.</div>
        </div>
        <a class="btn btn-link" href="{{ url('/admin/crud/list/producto-resources') }}">Ver productos</a>
    </div>

    @if($items->isEmpty())
        <div class="text-muted">Aún no hay productos con imágenes cargadas.</div>
    @else
        <div class="oi-gallery">
            @foreach($items as $p)
                <a class="oi-card" href="{{ $p['url'] ?? '#' }}" title="{{ $p['name'] ?? '' }}">
                    <div class="oi-thumb">
                        <img src="{{ $p['image'] }}" alt="{{ $p['name'] ?? 'Producto' }}" loading="lazy">
                    </div>
                    <div class="oi-meta">
                        <div class="oi-name">{{ $p['name'] ?? 'Producto' }}</div>
                        <div class="oi-sub text-muted">
                            {{ $p['brand'] ?? 'Sin marca' }}
                            @if(!empty($p['category']))
                                · {{ $p['category'] }}
                            @endif
                        </div>
                    </div>
                </a>
            @endforeach
        </div>
    @endif
</div>

<style>
    .oi-gallery{
        display:grid;
        grid-template-columns:repeat(4,minmax(0,1fr));
        gap:12px;
    }
    @media (max-width: 1200px){
        .oi-gallery{ grid-template-columns:repeat(3,minmax(0,1fr)); }
    }
    @media (max-width: 768px){
        .oi-gallery{ grid-template-columns:repeat(2,minmax(0,1fr)); }
    }
    .oi-card{
        display:flex;
        flex-direction:column;
        background:#fff;
        border:1px solid rgba(17,24,39,.08);
        border-radius:12px;
        overflow:hidden;
        text-decoration:none;
        transition:transform .12s ease, box-shadow .12s ease;
        box-shadow:0 6px 18px rgba(17,24,39,.05);
        color:inherit;
    }
    .oi-card:hover{
        transform:translateY(-2px);
        box-shadow:0 12px 24px rgba(17,24,39,.10);
        text-decoration:none;
    }
    .oi-thumb{ width:100%; height:140px; background:#f3f4f6; }
    .oi-thumb img{ width:100%; height:100%; object-fit:cover; display:block; }
    .oi-meta{ padding:10px 12px; display:flex; flex-direction:column; gap:2px; }
    .oi-name{ font-weight:600; font-size:14px; line-height:1.2; }
    .oi-sub{ font-size:12px; line-height:1.2; }
</style>
