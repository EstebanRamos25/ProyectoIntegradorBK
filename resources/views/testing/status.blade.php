<x-orchid-layout>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body text-center">
                        <div id="statusIndicator" class="mb-4">
                            @if($status === 'pending')
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                                <h3 class="mt-3">Ejecutando pruebas...</h3>
                            @elseif($status === 'completed')
                                <div class="text-success display-4">
                                    <i class="icon-check"></i>
                                </div>
                                <h3 class="mt-3">¡Pruebas completadas!</h3>
                            @else
                                <div class="text-danger display-4">
                                    <i class="icon-close"></i>
                                </div>
                                <h3 class="mt-3">Error en las pruebas</h3>
                            @endif
                        </div>
                        
                        <div id="resultsPanel" class="{{ $status !== 'completed' ? 'd-none' : '' }}">
                            <a href="{{ route('testing.download', $uuid) }}" 
                               class="btn btn-lg btn-primary mb-3"
                               target="_blank">
                                <i class="icon-doc"></i> Ver reporte completo
                            </a>
                            
                            <div class="progress" style="height: 30px;">
                                <div class="progress-bar bg-success" style="width: 85%">85% Rendimiento</div>
                                <div class="progress-bar bg-warning" style="width: 10%">10% Advertencias</div>
                                <div class="progress-bar bg-danger" style="width: 5%">5% Errores</div>
                            </div>
                            
                            <div class="row mt-4">
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <i class="icon-clock display-4 text-info"></i>
                                            <h5>Tiempo ejecución</h5>
                                            <p class="display-6">2.4s</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <i class="icon-check display-4 text-success"></i>
                                            <h5>Pruebas exitosas</h5>
                                            <p class="display-6">42/45</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <i class="icon-warning display-4 text-warning"></i>
                                            <h5>Advertencias</h5>
                                            <p class="display-6">3</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-light">
                                        <div class="card-body">
                                            <i class="icon-close display-4 text-danger"></i>
                                            <h5>Errores críticos</h5>
                                            <p class="display-6">0</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    @push('scripts')
    <script>
        const checkStatus = () => {
            fetch("{{ route('testing.status', $uuid) }}", { headers: { 'Accept': 'application/json' } })
                .then(response => response.json())
                .then(data => {
                    if (data.status === 'completed') {
                        document.getElementById('statusIndicator').innerHTML = `
                            <div class="text-success display-4">
                                <i class="icon-check"></i>
                            </div>
                            <h3 class="mt-3">¡Pruebas completadas!</h3>
                        `;
                        document.getElementById('resultsPanel').classList.remove('d-none');
                    } else if (data.status === 'failed') {
                        document.getElementById('statusIndicator').innerHTML = `
                            <div class="text-danger display-4">
                                <i class="icon-close"></i>
                            </div>
                            <h3 class="mt-3">Error en las pruebas</h3>
                        `;
                    } else {
                        setTimeout(checkStatus, 3000);
                    }
                });
        }
        
        @if($status === 'pending')
        setTimeout(checkStatus, 3000);
        @endif
    </script>
    @endpush
</x-orchid-layout>