import React, { useMemo } from 'react'

export function Controls({
  activeSurface,
  onActiveSurfaceChange,
  floor,
  setFloor,
  materials,
  materialsLoading,
  materialsError,
  selectedMaterialId,
  onSelectMaterial,
  selectedWallMaterialId,
  onSelectWallMaterial,
  recommendations,
  recommendationsLoading,
  recommendationsError,
  onUseRecommendation,
  wallCoverage,
  onAdd,
  canAdd,
  addLabel,
  onAddWalls,
  canAddWalls,
  addLabelWalls,
  scenes,
  scenesLoading,
  scenesError,
  selectedSceneId,
  sceneName,
  onSceneNameChange,
  onCreateNewScene,
  onSaveScene,
  onLoadScene,
  coverage,
  onCover,
  onToggleCover,
  coverEnabled,
  onCoverWalls,
  onToggleCoverWalls,
  wallCoverEnabled,
  roomSizeCm,
  onRoomSizeChange,
  quoteSummary,
  onGenerateQuote,
  quoteLoading,
  inventoryStatus,
}) {
  const activeMaterialId = activeSurface === 'walls' ? selectedWallMaterialId : selectedMaterialId
  const selectedMaterial = useMemo(
    () => materials?.find((m) => String(m.id) === String(activeMaterialId)) ?? null,
    [materials, activeMaterialId]
  )

  return (
    <div
      onWheelCapture={(e) => e.stopPropagation()}
      style={{
        position: 'fixed',
        top: 12,
        left: 12,
        zIndex: 10,
        width: 260,
        maxHeight: 'calc(100vh - 24px)',
        overflowY: 'auto',
        overflowX: 'hidden',
        overscrollBehavior: 'contain',
        WebkitOverflowScrolling: 'touch',
        background: 'rgba(0,0,0,.65)',
        color: '#fff',
        padding: '12px',
        borderRadius: 10,
        fontFamily: 'system-ui,Arial,sans-serif',
      }}
    >
      <div style={{fontWeight:700, marginBottom:10}}>Catálogo</div>

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Superficie</div>
      <div style={{display:'flex', gap:8, marginBottom:10}}>
        <button
          onClick={() => onActiveSurfaceChange('floor')}
          style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid rgba(255,255,255,.12)', background: activeSurface === 'floor' ? '#1f6feb' : '#111827', color:'#fff', cursor:'pointer'}}
          title="Seleccionar piso"
        >
          Piso
        </button>
        <button
          onClick={() => onActiveSurfaceChange('walls')}
          style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid rgba(255,255,255,.12)', background: activeSurface === 'walls' ? '#1f6feb' : '#111827', color:'#fff', cursor:'pointer'}}
          title="Seleccionar paredes"
        >
          Paredes
        </button>
      </div>
      <div style={{fontSize:11, opacity:0.72, marginTop:-6, marginBottom:10, lineHeight:1.25}}>
        Tip: haz click en el piso o una pared para hacer zoom.
      </div>

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Escenarios (por usuario)</div>
      <div style={{padding:'10px', background:'rgba(255,255,255,.06)', border:'1px solid rgba(255,255,255,.08)', borderRadius:10, marginBottom:10}}>
        {scenesLoading && (
          <div style={{fontSize:12, opacity:0.85}}>Cargando escenarios...</div>
        )}

        {!scenesLoading && scenesError && (
          <div style={{fontSize:12, opacity:0.9, lineHeight:1.3}}>
            {scenesError}
          </div>
        )}

        {!scenesLoading && !scenesError && (
          <>
            <select
              value={selectedSceneId ?? ''}
              onChange={(e) => onLoadScene(e.target.value ? Number(e.target.value) : null)}
              style={{width:'100%', padding:'8px 10px', borderRadius:8, border:'1px solid rgba(255,255,255,.14)', background:'#111827', color:'#fff'}}
              title={selectedSceneId ? 'Escena cargada' : 'Selecciona una escena guardada'}
            >
              <option value="">(Sin escena cargada)</option>
              {scenes.map((s) => (
                <option key={s.id} value={s.id}>
                  {s.name}
                </option>
              ))}
            </select>

            <div style={{height:8}} />

            <input
              value={sceneName}
              onChange={(e) => onSceneNameChange(e.target.value)}
              placeholder="Nombre del escenario"
              style={{width:'100%', padding:'8px 10px', borderRadius:8, border:'1px solid rgba(255,255,255,.14)', background:'#0b1220', color:'#fff'}}
            />

            <div style={{display:'flex', gap:8, marginTop:8}}>
              <button
                onClick={onCreateNewScene}
                style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid rgba(255,255,255,.12)', background:'#111827', color:'#fff', cursor:'pointer'}}
                title="Crear un escenario nuevo"
              >
                Nuevo
              </button>
              <button
                onClick={onSaveScene}
                style={{flex:1, padding:'8px 10px', borderRadius:8, border:'none', background:'#16a34a', color:'#fff', cursor:'pointer'}}
                title="Guardar este escenario"
              >
                Guardar
              </button>
            </div>
            <div style={{fontSize:11, opacity:0.75, marginTop:8, lineHeight:1.25}}>
              Requiere inicio de sesión. Cada usuario ve solo sus escenarios.
            </div>
          </>
        )}
      </div>

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>
        Material (desde Productos) · {activeSurface === 'walls' ? 'Paredes' : 'Piso'}
      </div>
      <div style={{padding:'10px', background:'rgba(255,255,255,.06)', border:'1px solid rgba(255,255,255,.08)', borderRadius:10, marginBottom:10}}>
        {materialsLoading && (
          <div style={{fontSize:12, opacity:0.85}}>Cargando materiales...</div>
        )}
        {!materialsLoading && (materialsError || !materials?.length) && (
          <div style={{fontSize:12, opacity:0.9, lineHeight:1.3}}>
            No hay materiales disponibles desde Productos.
            <div style={{fontSize:11, opacity:0.75, marginTop:6}}>
              Sube imágenes a productos para habilitarlos en Experiencia 3D.
            </div>
          </div>
        )}

        {!materialsLoading && !!materials?.length && (
          <>
            <select
              value={activeMaterialId ?? ''}
              onChange={(e) => {
                const next = e.target.value ? Number(e.target.value) : null
                if (activeSurface === 'walls') {
                  onSelectWallMaterial(next)
                } else {
                  onSelectMaterial(next)
                }
              }}
              style={{width:'100%', padding:'8px 10px', borderRadius:8, border:'1px solid rgba(255,255,255,.14)', background:'#111827', color:'#fff'}}
            >
              {materials.map((m) => (
                <option key={m.id} value={m.id}>
                  {m.name}
                </option>
              ))}
            </select>
            {selectedMaterial && (
              <div style={{fontSize:11, opacity:0.75, marginTop:8, lineHeight:1.3}}>
                Tipo: {selectedMaterial.kind === 'plank' ? 'Madera' : 'Cerámica'}
                <br />
                Precio ref. m²: {Number(selectedMaterial.price_per_m2 || 0).toFixed(0)} Bs
                {!!(Number(selectedMaterial.piece_dimensions_cm?.width || 0) > 0 && Number(selectedMaterial.piece_dimensions_cm?.depth || 0) > 0) && (
                  <>
                    <br />
                    {selectedMaterial.piece_dimensions_cm?.locked ? 'Formato fijo' : 'Formato'}: {Number(selectedMaterial.piece_dimensions_cm.width).toFixed(0)}×{Number(selectedMaterial.piece_dimensions_cm.depth).toFixed(0)} cm
                  </>
                )}
              </div>
            )}
          </>
        )}
      </div>

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Recomendados</div>
      <div style={{padding:'10px', background:'rgba(255,255,255,.06)', border:'1px solid rgba(255,255,255,.08)', borderRadius:10, marginBottom:10}}>
        {!selectedSceneId && (
          <div style={{fontSize:12, opacity:0.9, lineHeight:1.3}}>
            Carga una escena para ver recomendaciones.
          </div>
        )}

        {!!selectedSceneId && recommendationsLoading && (
          <div style={{fontSize:12, opacity:0.85}}>Calculando recomendaciones...</div>
        )}

        {!!selectedSceneId && !recommendationsLoading && recommendationsError && (
          <div style={{fontSize:12, opacity:0.9, lineHeight:1.3}}>
            {recommendationsError}
          </div>
        )}

        {!!selectedSceneId && !recommendationsLoading && !recommendationsError && (!recommendations?.length) && (
          <div style={{fontSize:12, opacity:0.9, lineHeight:1.3}}>
            Sin recomendaciones por ahora.
          </div>
        )}

        {!!selectedSceneId && !recommendationsLoading && !recommendationsError && !!recommendations?.length && (
          <div style={{display:'flex', flexDirection:'column', gap:8}}>
            {recommendations.map((r) => (
              <button
                key={r.product_id}
                onClick={() => onUseRecommendation?.(Number(r.product_id))}
                style={{
                  textAlign:'left',
                  padding:'8px 10px',
                  borderRadius:8,
                  border:'1px solid rgba(255,255,255,.12)',
                  background:'#0b1220',
                  color:'#fff',
                  cursor:'pointer',
                }}
                title={`Stock: ${Number(r.stock_boxes_available ?? 0)} cajas`}
              >
                <div style={{fontWeight:700, fontSize:12}}>{r.name}</div>
                <div style={{fontSize:11, opacity:0.75, marginTop:2, lineHeight:1.25}}>
                  {r.categoria_name ? `Categoría: ${r.categoria_name} · ` : ''}Stock: {Number(r.stock_boxes_available ?? 0)} cajas
                </div>
              </button>
            ))}
          </div>
        )}
      </div>

      <button
        onClick={activeSurface === 'walls' ? onAddWalls : onAdd}
        disabled={activeSurface === 'walls' ? !canAddWalls : !canAdd}
        style={{
          width:'100%',
          padding:'10px 10px',
          borderRadius:8,
          border:'none',
          background: (activeSurface === 'walls' ? canAddWalls : canAdd) ? '#16a34a' : '#374151',
          color:'#fff',
          cursor: (activeSurface === 'walls' ? canAddWalls : canAdd) ? 'pointer' : 'not-allowed'
        }}
      >
        {activeSurface === 'walls' ? addLabelWalls : addLabel}
      </button>

      <div style={{height:10}} />

      {activeSurface === 'floor' ? (
        <button
          onClick={onCover}
          disabled={!coverage?.canCompute}
          style={{width:'100%', padding:'10px 10px', borderRadius:8, border:'none', background: coverage?.canCompute ? '#f59e0b' : '#374151', color:'#111827', cursor: coverage?.canCompute ? 'pointer' : 'not-allowed'}}
          title={!coverage?.canCompute ? 'Agrega una pieza para calcular cobertura' : 'Calcula cuántas piezas cubren el piso'}
        >
          Cubrir piso (calcular)
        </button>
      ) : (
        <button
          onClick={onCoverWalls}
          disabled={!wallCoverage?.canCompute}
          style={{width:'100%', padding:'10px 10px', borderRadius:8, border:'none', background: wallCoverage?.canCompute ? '#f59e0b' : '#374151', color:'#111827', cursor: wallCoverage?.canCompute ? 'pointer' : 'not-allowed'}}
          title={!wallCoverage?.canCompute ? 'Agrega una pieza para calcular cobertura' : 'Calcula cuántas piezas cubren las paredes'}
        >
          Cubrir paredes (calcular)
        </button>
      )}

      {activeSurface === 'floor' && coverage?.canCompute && coverage?.computed && (
        <div style={{marginTop:10, padding:'10px', background:'rgba(255,255,255,.06)', border:'1px solid rgba(255,255,255,.08)', borderRadius:10}}>
          <div style={{fontWeight:700, marginBottom:6}}>Cobertura</div>
          <div style={{fontSize:12, opacity:0.9, lineHeight:1.35}}>
            Pieza: {coverage.pieceCmX}×{coverage.pieceCmZ} cm
            <br />
            Área pieza: {coverage.pieceAreaM2.toFixed(3)} m²
            <br />
            Piso: {coverage.floorAreaM2.toFixed(0)} m²
            <br />
            Cantidad aprox.: {coverage.count} uds
          </div>
          <div style={{display:'flex', gap:8, marginTop:10}}>
            <button
              onClick={onToggleCover}
              style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid rgba(255,255,255,.12)', background: coverEnabled ? '#1f6feb' : '#111827', color:'#fff', cursor:'pointer'}}
            >
              Preview {coverEnabled ? 'ON' : 'OFF'}
            </button>
          </div>
          <div style={{fontSize:11, opacity:0.75, marginTop:8}}>
            Preview usa hasta 800 instancias (muestreo automático en cuartos grandes).
          </div>
        </div>
      )}

      {activeSurface === 'walls' && (
        <>
          <div style={{padding:'10px', background:'rgba(255,255,255,.06)', border:'1px solid rgba(255,255,255,.08)', borderRadius:10, marginBottom:10}}>
            <div style={{fontWeight:700, marginBottom:6}}>Cobertura paredes</div>
            {!wallCoverage?.canCompute && (
              <div style={{fontSize:12, opacity:0.9, lineHeight:1.35}}>
                Agrega una pieza para estimar cobertura.
              </div>
            )}
            {wallCoverage?.canCompute && (
              <div style={{fontSize:12, opacity:0.92, lineHeight:1.4}}>
                Área paredes: {wallCoverage.wallAreaM2.toFixed(2)} m²
                <br />
                Área pieza: {wallCoverage.pieceAreaM2.toFixed(4)} m²
                <br />
                Cantidad aprox.: {wallCoverage.estimatedUnits} uds
              </div>
            )}
            {wallCoverage?.canCompute && wallCoverage?.computed && (
              <div style={{display:'flex', gap:8, marginTop:10}}>
                <button
                  onClick={onToggleCoverWalls}
                  style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid rgba(255,255,255,.12)', background: wallCoverEnabled ? '#1f6feb' : '#111827', color:'#fff', cursor:'pointer'}}
                >
                  Preview {wallCoverEnabled ? 'ON' : 'OFF'}
                </button>
              </div>
            )}
            <div style={{fontSize:11, opacity:0.72, marginTop:8}}>
              Se incluye en el PDF como referencia (no calcula precio para paredes).
            </div>
          </div>
        </>
      )}

      <div style={{height:10}} />

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Escenario / cuarto</div>
      <div style={{padding:'10px', background:'rgba(255,255,255,.06)', border:'1px solid rgba(255,255,255,.08)', borderRadius:10, marginBottom:10}}>
        <label style={{display:'block', fontSize:12, opacity:0.92, marginBottom:8}}>
          Ancho (X): {roomSizeCm.width} cm
          <input
            type="range"
            min={200}
            max={3000}
            step={10}
            value={roomSizeCm.width}
            onChange={(e) => onRoomSizeChange('width', Number(e.target.value))}
            style={{width:'100%'}}
          />
        </label>
        <label style={{display:'block', fontSize:12, opacity:0.92, marginBottom:8}}>
          Largo (Z): {roomSizeCm.depth} cm
          <input
            type="range"
            min={200}
            max={3000}
            step={10}
            value={roomSizeCm.depth}
            onChange={(e) => onRoomSizeChange('depth', Number(e.target.value))}
            style={{width:'100%'}}
          />
        </label>
        <label style={{display:'block', fontSize:12, opacity:0.92}}>
          Alto: {roomSizeCm.height} cm
          <input
            type="range"
            min={200}
            max={800}
            step={10}
            value={roomSizeCm.height}
            onChange={(e) => onRoomSizeChange('height', Number(e.target.value))}
            style={{width:'100%'}}
          />
        </label>
        <div style={{fontSize:11, opacity:0.75, marginTop:8}}>
          Área del piso: {((roomSizeCm.width / 100) * (roomSizeCm.depth / 100)).toFixed(2)} m²
        </div>

        {inventoryStatus?.canCompute && (
          <div
            style={{
              marginTop:10,
              padding:'10px',
              borderRadius:10,
              border:'1px solid rgba(255,255,255,.10)',
              background:
                inventoryStatus.canFulfill === false
                  ? 'rgba(239,68,68,.18)'
                  : 'rgba(34,197,94,.12)',
            }}
          >
            <div style={{fontWeight:700, marginBottom:6}}>Inventario (cajas)</div>
            <div style={{fontSize:12, opacity:0.92, lineHeight:1.35}}>
              Requiere: {inventoryStatus.boxesRequired} cajas
              <br />
              Disponibles: {inventoryStatus.boxesAvailableTotal == null ? '—' : `${inventoryStatus.boxesAvailableTotal} cajas`}
              {inventoryStatus.missingBoxes != null && inventoryStatus.missingBoxes > 0 && (
                <>
                  <br />
                  Faltan: {inventoryStatus.missingBoxes} cajas
                </>
              )}
              {inventoryStatus.canSingleLot === false && (
                <>
                  <br />
                  Nota: no alcanza en un solo lote (podría mezclar lotes).
                </>
              )}
            </div>
            {inventoryStatus.canFulfill === false && (
              <div style={{fontSize:11, opacity:0.85, marginTop:8}}>
                Sugerencia: registra un ingreso de inventario (nuevo lote) antes de cotizar.
              </div>
            )}
          </div>
        )}

        {inventoryStatus?.reason && (
          <div style={{fontSize:11, opacity:0.75, marginTop:10, lineHeight:1.25}}>
            Inventario: {inventoryStatus.reason}
          </div>
        )}
      </div>

      {activeSurface === 'floor' && (
        <>
          <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Piso</div>
          <div style={{display:'flex', gap:8}}>
            <button onClick={() => setFloor('wood')} style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid #333', background: floor === 'wood' ? '#444' : '#222', color:'#fff', cursor:'pointer'}}>Madera</button>
            <button onClick={() => setFloor('ceramic')} style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid #333', background: floor === 'ceramic' ? '#444' : '#222', color:'#fff', cursor:'pointer'}}>Cerámica</button>
          </div>
        </>
      )}

      <div style={{height:10}} />

      <button
        onClick={onGenerateQuote}
        disabled={!quoteSummary?.ready || quoteLoading}
        style={{width:'100%', padding:'10px 10px', borderRadius:8, border:'none', background: quoteSummary?.ready && !quoteLoading ? '#2563eb' : '#374151', color:'#fff', cursor: quoteSummary?.ready && !quoteLoading ? 'pointer' : 'not-allowed'}}
        title={
          !quoteSummary?.ready
            ? 'Agrega una pieza para cotizar el escenario'
            : 'Genera un PDF con la cotización estimada'
        }
      >
        {quoteLoading ? 'Generando PDF...' : 'Generar cotización PDF'}
      </button>

      <div style={{fontSize:11, opacity:0.72, marginTop:8, lineHeight:1.25}}>
        El PDF de cotización se genera sin descontar inventario. La venta confirmada es la que descuenta stock.
      </div>

      {activeSurface === 'floor' && quoteSummary?.ready && (
        <div style={{marginTop:10, padding:'10px', background:'rgba(255,255,255,.06)', border:'1px solid rgba(255,255,255,.08)', borderRadius:10}}>
          <div style={{fontWeight:700, marginBottom:6}}>Resumen de cotización</div>
          <div style={{fontSize:12, opacity:0.92, lineHeight:1.4}}>
            Elemento: {quoteSummary.itemLabel}
            <br />
            Área: {quoteSummary.floorAreaLabel}
            <br />
            Precio por m²: {quoteSummary.unitPriceLabel}
            <br />
            Total estimado: {quoteSummary.totalLabel}
          </div>
          <div style={{fontSize:11, opacity:0.72, marginTop:8}}>
            Precio por m² desde Productos. Stock por cajas desde Inventario (si está registrado).
          </div>
        </div>
      )}
    </div>
  )
}
