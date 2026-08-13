import React, { useMemo, useState } from 'react'

const FALLBACK_IMAGE_SVG =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="150"><rect fill="#1e293b" width="100%" height="100%"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#64748b" font-size="14" font-family="system-ui">Sin imagen</text></svg>'
  )

export function Controls({
  activeSurface,
  onActiveSurfaceChange,
  activeWallKey = 'north',
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
  roomShape = 'rectangular',
  onRoomShapeChange,
  windows = [],
  onAddWindow,
  onUpdateWindow,
  onRemoveWindow,
  quoteSummary,
  onGenerateQuote,
  quoteLoading,
  inventoryStatus,
}) {
  const [activeTab, setActiveTab] = useState('materials') // 'materials' | 'space' | 'coverage' | 'quote'
  const [isCollapsed, setIsCollapsed] = useState(false)

  const activeMaterialId = activeSurface === 'walls' ? selectedWallMaterialId : selectedMaterialId
  const selectedMaterial = useMemo(
    () => materials?.find((m) => String(m.id) === String(activeMaterialId)) ?? null,
    [materials, activeMaterialId]
  )

  const handleSelectMaterialCard = (materialId) => {
    if (activeSurface === 'walls') {
      onSelectWallMaterial(materialId)
    } else {
      onSelectMaterial(materialId)
    }
  }

  return (
    <>
      {/* ─── TOP BAR FLOTANTE: GESTIÓN RÁPIDA DE PROYECTO ─────────────────────── */}
      <div
        onWheelCapture={(e) => e.stopPropagation()}
        style={styles.topBar}
      >
        <div style={styles.topBarLeft}>
          <span style={styles.appTitle}>CERABOL 3D</span>
          <div style={styles.divider} />
          
          {/* Surface Selector Pills */}
          <div style={styles.surfacePills}>
            <button
              onClick={() => onActiveSurfaceChange('floor')}
              style={{
                ...styles.surfaceBtn,
                ...(activeSurface === 'floor' ? styles.surfaceBtnActive : {}),
              }}
            >
              🪵 Piso
            </button>
            <button
              onClick={() => onActiveSurfaceChange('walls')}
              style={{
                ...styles.surfaceBtn,
                ...(activeSurface === 'walls' ? styles.surfaceBtnActive : {}),
              }}
            >
              🧱 Paredes
            </button>
          </div>
        </div>

        <div style={styles.topBarRight}>
          {/* Scenes dropdown */}
          {!scenesLoading && !scenesError && scenes?.length > 0 && (
            <select
              value={selectedSceneId ?? ''}
              onChange={(e) => onLoadScene(e.target.value ? Number(e.target.value) : null)}
              style={styles.topSelect}
            >
              <option value="">(Mis escenarios)</option>
              {scenes.map((s) => (
                <option key={s.id} value={s.id}>
                  📁 {s.name}
                </option>
              ))}
            </select>
          )}

          {/* Scene Name Input */}
          <input
            value={sceneName}
            onChange={(e) => onSceneNameChange(e.target.value)}
            placeholder="Nombre del proyecto..."
            style={styles.topInput}
          />

          <button onClick={onCreateNewScene} style={styles.btnSecondary} title="Crear escenario nuevo">
            ✨ Nuevo
          </button>
          
          <button onClick={onSaveScene} style={styles.btnPrimary} title="Guardar escenario actual">
            💾 Guardar
          </button>
        </div>
      </div>

      {/* ─── PANEL LATERAL FLOTANTE REDISEÑADO ────────────────────────────────── */}
      <div
        onWheelCapture={(e) => e.stopPropagation()}
        style={{
          ...styles.sidebar,
          transform: isCollapsed ? 'translateX(-100%)' : 'translateX(0)',
        }}
      >
        {/* Toggle Collapse Button */}
        <button
          onClick={() => setIsCollapsed(!isCollapsed)}
          style={{
            ...styles.collapseBtn,
            left: isCollapsed ? '320px' : '312px',
          }}
          title={isCollapsed ? 'Mostrar controles' : 'Ocultar controles'}
        >
          {isCollapsed ? '▶' : '◀'}
        </button>

        {/* Header Tabs */}
        <div style={styles.tabsHeader}>
          <button
            onClick={() => setActiveTab('materials')}
            style={{
              ...styles.tabBtn,
              ...(activeTab === 'materials' ? styles.tabBtnActive : {}),
            }}
          >
            🎨 <span style={styles.tabLabel}>Materiales</span>
          </button>
          <button
            onClick={() => setActiveTab('space')}
            style={{
              ...styles.tabBtn,
              ...(activeTab === 'space' ? styles.tabBtnActive : {}),
            }}
          >
            📐 <span style={styles.tabLabel}>Espacio</span>
          </button>
          <button
            onClick={() => setActiveTab('coverage')}
            style={{
              ...styles.tabBtn,
              ...(activeTab === 'coverage' ? styles.tabBtnActive : {}),
            }}
          >
            🧮 <span style={styles.tabLabel}>Cobertura</span>
          </button>
          <button
            onClick={() => setActiveTab('quote')}
            style={{
              ...styles.tabBtn,
              ...(activeTab === 'quote' ? styles.tabBtnActive : {}),
            }}
          >
            📄 <span style={styles.tabLabel}>Cotizar</span>
          </button>
        </div>

        {/* TAB BODY CONTAINER */}
        <div style={styles.tabBody}>
          {/* ──────── TAB 1: MATERIALES & IA ──────── */}
          {activeTab === 'materials' && (
            <div style={styles.sectionFade}>
              <div style={styles.sectionTitleRow}>
                <span style={styles.sectionTitle}>
                  Catálogo ({activeSurface === 'walls' ? 'Paredes' : 'Piso'})
                </span>
                <span style={styles.badgeInfo}>
                  {materials?.length ?? 0} disponibles
                </span>
              </div>

              {materialsLoading && (
                <div style={styles.loadingBox}>
                  <div style={styles.spinner} /> Cargando catálogo de productos...
                </div>
              )}

              {!materialsLoading && (materialsError || !materials?.length) && (
                <div style={styles.emptyBox}>
                  ⚠️ No hay materiales cargados desde Productos.
                </div>
              )}

              {/* Grid visual de tarjetas de materiales */}
              {!materialsLoading && !!materials?.length && (
                <div style={styles.materialsGrid}>
                  {materials.map((m) => {
                    const isSelected = String(m.id) === String(activeMaterialId)
                    return (
                      <div
                        key={m.id}
                        onClick={() => handleSelectMaterialCard(m.id)}
                        style={{
                          ...styles.materialCard,
                          ...(isSelected ? styles.materialCardSelected : {}),
                        }}
                      >
                        <div style={styles.cardImgContainer}>
                          <img
                            src={m.image_url || FALLBACK_IMAGE_SVG}
                            alt={m.name}
                            style={styles.cardImg}
                            onError={(e) => {
                              e.target.src = FALLBACK_IMAGE_SVG
                            }}
                          />
                          <span style={styles.kindTag}>
                            {m.kind === 'plank' ? '🪵 Madera' : '🧱 Cerámica'}
                          </span>
                          {isSelected && <span style={styles.checkBadge}>✓</span>}
                        </div>
                        <div style={styles.cardBody}>
                          <div style={styles.cardTitle}>{m.name}</div>
                          <div style={styles.cardPrice}>
                            {Number(m.price_per_m2 || 0).toFixed(0)} <small>Bs/m²</small>
                          </div>
                          {m.piece_dimensions_cm && (
                            <div style={styles.cardDims}>
                              📐 {Number(m.piece_dimensions_cm.width).toFixed(0)}×
                              {Number(m.piece_dimensions_cm.depth).toFixed(0)} cm
                            </div>
                          )}
                        </div>
                      </div>
                    )
                  })}
                </div>
              )}

              {/* SECCIÓN RECOMENDACIONES IA */}
              <div style={{ marginTop: 20 }}>
                <div style={styles.sectionTitleRow}>
                  <span style={styles.sectionTitle}>🤖 Recomendaciones IA</span>
                  <span style={styles.badgeAi}>Red Neuronal</span>
                </div>

                {!selectedSceneId && (
                  <div style={styles.aiHintBox}>
                    💡 Guarda o carga una escena para ver sugerencias personalizadas de IA.
                  </div>
                )}

                {!!selectedSceneId && recommendationsLoading && (
                  <div style={styles.loadingBox}>Calculando recomendaciones con ML...</div>
                )}

                {!!selectedSceneId && !recommendationsLoading && !!recommendations?.length && (
                  <div style={styles.recoList}>
                    {recommendations.map((r) => (
                      <div key={r.product_id} style={styles.recoCard}>
                        <div style={{ flex: 1 }}>
                          <div style={styles.recoName}>{r.name}</div>
                          <div style={styles.recoCat}>
                            {r.categoria_name ? `Categoría: ${r.categoria_name} · ` : ''}Stock:{' '}
                            <strong style={{ color: '#34d399' }}>
                              {Number(r.stock_boxes_available ?? 0)} cajas
                            </strong>
                          </div>
                        </div>
                        <button
                          onClick={() => onUseRecommendation?.(Number(r.product_id))}
                          style={styles.recoApplyBtn}
                        >
                          Usar
                        </button>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            </div>
          )}

          {/* ──────── TAB 2: ESPACIO & CUARTO ──────── */}
          {activeTab === 'space' && (
            <div style={styles.sectionFade}>
              {/* Formas del Cuarto */}
              <div style={styles.sectionTitleRow}>
                <span style={styles.sectionTitle}>Forma del Cuarto / Escena</span>
              </div>
              <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 6, marginBottom: 16 }}>
                {[
                  { id: 'rectangular', label: '⬛ Rectangular' },
                  { id: 'l_shape', label: '🔲 Forma en L' },
                  { id: 't_shape', label: '✝️ Forma en T' },
                  { id: 'u_shape', label: '⊔ Forma en U' },
                  { id: 'open_loft', label: '🛋️ Loft Abierto' },
                ].map((shape) => (
                  <button
                    key={shape.id}
                    onClick={() => onRoomShapeChange?.(shape.id)}
                    style={{
                      padding: '8px 4px',
                      borderRadius: 8,
                      border: roomShape === shape.id ? '1px solid #10b981' : '1px solid rgba(255,255,255,0.1)',
                      background: roomShape === shape.id ? '#0f172a' : '#1e293b',
                      color: roomShape === shape.id ? '#10b981' : '#94a3b8',
                      fontSize: 11,
                      fontWeight: 600,
                      cursor: 'pointer',
                      textAlign: 'center',
                    }}
                  >
                    {shape.label}
                  </button>
                ))}
              </div>

              {/* Personalización de Ventanas 3D Múltiples en Paredes */}
              <div style={{ ...styles.sectionTitleRow, borderTop: '1px solid rgba(255,255,255,0.08)', paddingTop: 12 }}>
                <span style={styles.sectionTitle}>Ventanas 3D ({windows?.length || 0})</span>
                <button
                  onClick={() => onAddWindow?.(activeWallKey)}
                  style={{
                    padding: '5px 12px',
                    borderRadius: 8,
                    border: 'none',
                    background: '#10b981',
                    color: '#fff',
                    fontSize: 11,
                    fontWeight: 700,
                    cursor: 'pointer',
                  }}
                >
                  ➕ Añadir Ventana
                </button>
              </div>

              {(!windows || windows.length === 0) ? (
                <div style={{ fontSize: 11, color: '#94a3b8', padding: 8, background: 'rgba(255,255,255,0.04)', borderRadius: 8, marginBottom: 14 }}>
                  💡 Haz clic en "Añadir Ventana" para agregar una ventana 3D en la pared activa ({activeWallKey}).
                </div>
              ) : (
                <div style={{ display: 'flex', flexDirection: 'column', gap: 10, marginBottom: 14 }}>
                  {windows.map((win, idx) => (
                    <div key={win.id || idx} style={{ background: '#1e293b', padding: 10, borderRadius: 10, border: '1px solid rgba(16,185,129,0.3)' }}>
                      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 8 }}>
                        <span style={{ fontSize: 11, fontWeight: 700, color: '#10b981' }}>
                          🪟 Ventana #{idx + 1} (Pared: {win.wallKey || 'activa'})
                        </span>
                        <button
                          onClick={() => onRemoveWindow?.(win.id)}
                          style={{
                            padding: '3px 8px',
                            borderRadius: 6,
                            border: 'none',
                            background: 'rgba(239,68,68,0.2)',
                            color: '#f87171',
                            fontSize: 10,
                            fontWeight: 700,
                            cursor: 'pointer',
                          }}
                        >
                          🗑️ Eliminar
                        </button>
                      </div>

                      <div style={styles.sliderGroup}>
                        <div style={styles.sliderHeader}>
                          <span>Ancho</span>
                          <span style={styles.sliderValue}>{win.widthCm || 160} cm</span>
                        </div>
                        <input
                          type="range"
                          min={80}
                          max={350}
                          step={10}
                          value={win.widthCm || 160}
                          onChange={(e) => onUpdateWindow?.(win.id, { widthCm: Number(e.target.value) })}
                          style={styles.rangeInput}
                        />
                      </div>

                      <div style={styles.sliderGroup}>
                        <div style={styles.sliderHeader}>
                          <span>Alto</span>
                          <span style={styles.sliderValue}>{win.heightCm || 120} cm</span>
                        </div>
                        <input
                          type="range"
                          min={60}
                          max={220}
                          step={10}
                          value={win.heightCm || 120}
                          onChange={(e) => onUpdateWindow?.(win.id, { heightCm: Number(e.target.value) })}
                          style={styles.rangeInput}
                        />
                      </div>

                      <div style={styles.sliderGroup}>
                        <div style={styles.sliderHeader}>
                          <span>Antepecho (Desde piso)</span>
                          <span style={styles.sliderValue}>{win.sillHeightCm || 90} cm</span>
                        </div>
                        <input
                          type="range"
                          min={30}
                          max={180}
                          step={5}
                          value={win.sillHeightCm || 90}
                          onChange={(e) => onUpdateWindow?.(win.id, { sillHeightCm: Number(e.target.value) })}
                          style={styles.rangeInput}
                        />
                      </div>
                    </div>
                  ))}
                </div>
              )}

              <div style={styles.sectionTitleRow}>
                <span style={styles.sectionTitle}>Dimensiones Principales</span>
              </div>

              <div style={styles.sliderGroup}>
                <div style={styles.sliderHeader}>
                  <span>Ancho (X)</span>
                  <span style={styles.sliderValue}>{roomSizeCm.width} cm</span>
                </div>
                <input
                  type="range"
                  min={200}
                  max={3000}
                  step={10}
                  value={roomSizeCm.width}
                  onChange={(e) => onRoomSizeChange('width', Number(e.target.value))}
                  style={styles.rangeInput}
                />
              </div>

              <div style={styles.sliderGroup}>
                <div style={styles.sliderHeader}>
                  <span>Largo (Z)</span>
                  <span style={styles.sliderValue}>{roomSizeCm.depth} cm</span>
                </div>
                <input
                  type="range"
                  min={200}
                  max={3000}
                  step={10}
                  value={roomSizeCm.depth}
                  onChange={(e) => onRoomSizeChange('depth', Number(e.target.value))}
                  style={styles.rangeInput}
                />
              </div>

              <div style={styles.sliderGroup}>
                <div style={styles.sliderHeader}>
                  <span>Alto (Y)</span>
                  <span style={styles.sliderValue}>{roomSizeCm.height} cm</span>
                </div>
                <input
                  type="range"
                  min={200}
                  max={800}
                  step={10}
                  value={roomSizeCm.height}
                  onChange={(e) => onRoomSizeChange('height', Number(e.target.value))}
                  style={styles.rangeInput}
                />
              </div>

              {/* Area summary badge */}
              <div style={styles.areaMetricCard}>
                <div style={styles.metricItem}>
                  <div style={styles.metricLabel}>Área del Piso</div>
                  <div style={styles.metricVal}>
                    {((roomSizeCm.width / 100) * (roomSizeCm.depth / 100)).toFixed(2)} m²
                  </div>
                </div>
                <div style={styles.metricDivider} />
                <div style={styles.metricItem}>
                  <div style={styles.metricLabel}>Área Paredes</div>
                  <div style={styles.metricVal}>
                    {(
                      2 * (roomSizeCm.width / 100) * (roomSizeCm.height / 100) +
                      2 * (roomSizeCm.depth / 100) * (roomSizeCm.height / 100)
                    ).toFixed(2)}{' '}
                    m²
                  </div>
                </div>
              </div>

              {/* Floor finish selector */}
              {activeSurface === 'floor' && (
                <div style={{ marginTop: 18 }}>
                  <div style={styles.sectionTitle}>Textura Base de Piso</div>
                  <div style={styles.finishGroup}>
                    <button
                      onClick={() => setFloor('wood')}
                      style={{
                        ...styles.finishBtn,
                        ...(floor === 'wood' ? styles.finishBtnActive : {}),
                      }}
                    >
                      🪵 Madera
                    </button>
                    <button
                      onClick={() => setFloor('ceramic')}
                      style={{
                        ...styles.finishBtn,
                        ...(floor === 'ceramic' ? styles.finishBtnActive : {}),
                      }}
                    >
                      🧱 Cerámica
                    </button>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* ──────── TAB 3: COBERTURA & CÁLCULO ──────── */}
          {activeTab === 'coverage' && (
            <div style={styles.sectionFade}>
              <div style={styles.sectionTitleRow}>
                <span style={styles.sectionTitle}>
                  Herramientas ({activeSurface === 'walls' ? 'Paredes' : 'Piso'})
                </span>
              </div>

              {/* Main Action: Add or replace piece */}
              <button
                onClick={activeSurface === 'walls' ? onAddWalls : onAdd}
                disabled={activeSurface === 'walls' ? !canAddWalls : !canAdd}
                style={{
                  ...styles.mainActionBtn,
                  opacity: (activeSurface === 'walls' ? canAddWalls : canAdd) ? 1 : 0.45,
                  cursor: (activeSurface === 'walls' ? canAddWalls : canAdd)
                    ? 'pointer'
                    : 'not-allowed',
                }}
              >
                ➕ {activeSurface === 'walls' ? addLabelWalls : addLabel}
              </button>

              <div style={{ height: 12 }} />

              {/* Coverage Compute button */}
              {activeSurface === 'floor' ? (
                <button
                  onClick={onCover}
                  disabled={!coverage?.canCompute}
                  style={{
                    ...styles.secondaryActionBtn,
                    opacity: coverage?.canCompute ? 1 : 0.45,
                    cursor: coverage?.canCompute ? 'pointer' : 'not-allowed',
                  }}
                >
                  🧮 Calcular Cobertura de Piso
                </button>
              ) : (
                <button
                  onClick={onCoverWalls}
                  disabled={!wallCoverage?.canCompute}
                  style={{
                    ...styles.secondaryActionBtn,
                    opacity: wallCoverage?.canCompute ? 1 : 0.45,
                    cursor: wallCoverage?.canCompute ? 'pointer' : 'not-allowed',
                  }}
                >
                  🧮 Calcular Cobertura de Paredes
                </button>
              )}

              {/* Floor Coverage Breakdown Box */}
              {activeSurface === 'floor' && coverage?.canCompute && coverage?.computed && (
                <div style={styles.infoCard}>
                  <div style={styles.infoCardHeader}>
                    <span>📊 Detalle de Cobertura</span>
                    <button
                      onClick={onToggleCover}
                      style={{
                        ...styles.togglePill,
                        ...(coverEnabled ? styles.togglePillActive : {}),
                      }}
                    >
                      Preview {coverEnabled ? 'ON' : 'OFF'}
                    </button>
                  </div>
                  <div style={styles.infoRow}>
                    <span>Dimensiones pieza:</span>
                    <strong>
                      {coverage.pieceCmX}×{coverage.pieceCmZ} cm
                    </strong>
                  </div>
                  <div style={styles.infoRow}>
                    <span>Área por pieza:</span>
                    <strong>{coverage.pieceAreaM2.toFixed(3)} m²</strong>
                  </div>
                  <div style={styles.infoRow}>
                    <span>Área total piso:</span>
                    <strong>{coverage.floorAreaM2.toFixed(1)} m²</strong>
                  </div>
                  <div style={styles.infoRowHighlight}>
                    <span>Piezas estimadas:</span>
                    <span style={{ fontSize: 16, color: '#10b981', fontWeight: 700 }}>
                      {coverage.count} unidades
                    </span>
                  </div>
                </div>
              )}

              {/* Wall Coverage Breakdown Box */}
              {activeSurface === 'walls' && wallCoverage?.canCompute && (
                <div style={styles.infoCard}>
                  <div style={styles.infoCardHeader}>
                    <span>📊 Cobertura en Paredes</span>
                    {wallCoverage?.computed && (
                      <button
                        onClick={onToggleCoverWalls}
                        style={{
                          ...styles.togglePill,
                          ...(wallCoverEnabled ? styles.togglePillActive : {}),
                        }}
                      >
                        Preview {wallCoverEnabled ? 'ON' : 'OFF'}
                      </button>
                    )}
                  </div>
                  <div style={styles.infoRow}>
                    <span>Área total paredes:</span>
                    <strong>{wallCoverage.wallAreaM2.toFixed(2)} m²</strong>
                  </div>
                  <div style={styles.infoRow}>
                    <span>Área por pieza:</span>
                    <strong>{wallCoverage.pieceAreaM2.toFixed(4)} m²</strong>
                  </div>
                  <div style={styles.infoRowHighlight}>
                    <span>Piezas estimadas:</span>
                    <span style={{ fontSize: 16, color: '#10b981', fontWeight: 700 }}>
                      {wallCoverage.estimatedUnits} unidades
                    </span>
                  </div>
                </div>
              )}
            </div>
          )}

          {/* ──────── TAB 4: COTIZACIÓN & INVENTARIO ──────── */}
          {activeTab === 'quote' && (
            <div style={styles.sectionFade}>
              <div style={styles.sectionTitleRow}>
                <span style={styles.sectionTitle}>Estado de Inventario y Cotización</span>
              </div>

              {/* Real-time Stock Card */}
              {inventoryStatus?.canCompute && (
                <div
                  style={{
                    ...styles.stockCard,
                    borderColor:
                      inventoryStatus.canFulfill === false
                        ? 'rgba(239, 68, 68, 0.4)'
                        : 'rgba(16, 185, 129, 0.4)',
                    background:
                      inventoryStatus.canFulfill === false
                        ? 'rgba(239, 68, 68, 0.12)'
                        : 'rgba(16, 185, 129, 0.10)',
                  }}
                >
                  <div style={styles.stockCardHeader}>
                    <span>📦 Inventario en Tiempo Real</span>
                    <span
                      style={{
                        ...styles.stockBadge,
                        background:
                          inventoryStatus.canFulfill === false ? '#ef4444' : '#10b981',
                      }}
                    >
                      {inventoryStatus.canFulfill === false ? 'Stock Insuficiente' : 'Stock OK'}
                    </span>
                  </div>
                  <div style={styles.infoRow}>
                    <span>Cajas requeridas:</span>
                    <strong>{inventoryStatus.boxesRequired} cajas</strong>
                  </div>
                  <div style={styles.infoRow}>
                    <span>Cajas disponibles:</span>
                    <strong>
                      {inventoryStatus.boxesAvailableTotal == null
                        ? '—'
                        : `${inventoryStatus.boxesAvailableTotal} cajas`}
                    </strong>
                  </div>
                  {inventoryStatus.missingBoxes != null && inventoryStatus.missingBoxes > 0 && (
                    <div style={styles.infoRowHighlight}>
                      <span>Cajas faltantes:</span>
                      <span style={{ color: '#f87171', fontWeight: 700 }}>
                        {inventoryStatus.missingBoxes} cajas
                      </span>
                    </div>
                  )}
                </div>
              )}

              {/* Quotation Summary Card */}
              {quoteSummary?.ready ? (
                <div style={styles.quoteCard}>
                  <div style={styles.quoteCardTitle}>📋 Resumen de Cotización</div>
                  <div style={styles.infoRow}>
                    <span>Producto:</span>
                    <strong>{quoteSummary.itemLabel}</strong>
                  </div>
                  <div style={styles.infoRow}>
                    <span>Superficie piso:</span>
                    <strong>{quoteSummary.floorAreaLabel}</strong>
                  </div>
                  <div style={styles.infoRow}>
                    <span>Precio ref. m²:</span>
                    <strong>{quoteSummary.unitPriceLabel}</strong>
                  </div>
                  <div style={styles.quoteTotalRow}>
                    <span>Total Estimado:</span>
                    <span style={styles.quoteTotalVal}>{quoteSummary.totalLabel}</span>
                  </div>
                </div>
              ) : (
                <div style={styles.aiHintBox}>
                  💡 Agrega una pieza en el piso para calcular el resumen de cotización.
                </div>
              )}

              <div style={{ height: 16 }} />

              {/* Generate PDF Button */}
              <button
                onClick={onGenerateQuote}
                disabled={!quoteSummary?.ready || quoteLoading}
                style={{
                  ...styles.pdfBtn,
                  opacity: quoteSummary?.ready && !quoteLoading ? 1 : 0.45,
                  cursor: quoteSummary?.ready && !quoteLoading ? 'pointer' : 'not-allowed',
                }}
              >
                📄 {quoteLoading ? 'Generando PDF...' : 'Descargar Cotización PDF'}
              </button>
            </div>
          )}
        </div>
      </div>

      {/* ─── FLOATING BOTTOM ACTION BAR (PERMANENTE) ─────────────────────────── */}
      {selectedMaterial && (
        <div style={styles.bottomBar}>
          <div style={styles.bottomBarItem}>
            <img
              src={selectedMaterial.image_url || FALLBACK_IMAGE_SVG}
              alt={selectedMaterial.name}
              style={styles.bottomThumb}
            />
            <div>
              <div style={styles.bottomMatName}>{selectedMaterial.name}</div>
              <div style={styles.bottomMatPrice}>
                {Number(selectedMaterial.price_per_m2 || 0).toFixed(0)} Bs/m²
              </div>
            </div>
          </div>

          <div style={styles.bottomBarDivider} />

          {quoteSummary?.ready && (
            <div style={styles.bottomBarRight}>
              <div style={{ textAlign: 'right' }}>
                <div style={styles.bottomTotalLabel}>Total Estimado</div>
                <div style={styles.bottomTotalVal}>{quoteSummary.totalLabel}</div>
              </div>
              <button
                onClick={onGenerateQuote}
                disabled={quoteLoading}
                style={styles.bottomPdfBtn}
              >
                📄 PDF
              </button>
            </div>
          )}
        </div>
      )}
    </>
  )
}

// ─── STYLES (GLASSMORPHISM & MODERN UI) ──────────────────────────────────────
const styles = {
  // Top Floating Navigation Bar
  topBar: {
    position: 'fixed',
    top: 14,
    left: 14,
    right: 14,
    zIndex: 90,
    height: 52,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: '0 16px',
    background: 'rgba(15, 23, 42, 0.85)',
    backdropFilter: 'blur(16px)',
    WebkitBackdropFilter: 'blur(16px)',
    border: '1px solid rgba(255, 255, 255, 0.12)',
    borderRadius: 14,
    boxShadow: '0 10px 30px rgba(0, 0, 0, 0.35)',
    color: '#fff',
    fontFamily: 'system-ui, -apple-system, sans-serif',
  },
  topBarLeft: {
    display: 'flex',
    alignItems: 'center',
    gap: 12,
  },
  topBarRight: {
    display: 'flex',
    alignItems: 'center',
    gap: 10,
  },
  appTitle: {
    fontWeight: 800,
    fontSize: 15,
    letterSpacing: 1.2,
    background: 'linear-gradient(135deg, #10b981 0%, #6366f1 100%)',
    WebkitBackgroundClip: 'text',
    WebkitTextFillColor: 'transparent',
  },
  divider: {
    width: 1,
    height: 20,
    background: 'rgba(255, 255, 255, 0.15)',
  },
  surfacePills: {
    display: 'flex',
    gap: 4,
    background: 'rgba(0, 0, 0, 0.3)',
    padding: 3,
    borderRadius: 10,
  },
  surfaceBtn: {
    padding: '5px 12px',
    borderRadius: 7,
    border: 'none',
    background: 'transparent',
    color: '#94a3b8',
    fontSize: 12,
    fontWeight: 600,
    cursor: 'pointer',
    transition: 'all 0.2s',
  },
  surfaceBtnActive: {
    background: '#10b981',
    color: '#fff',
    boxShadow: '0 2px 8px rgba(16, 185, 129, 0.3)',
  },
  topSelect: {
    padding: '6px 12px',
    borderRadius: 8,
    border: '1px solid rgba(255, 255, 255, 0.15)',
    background: '#1e293b',
    color: '#f8fafc',
    fontSize: 12,
    outline: 'none',
  },
  topInput: {
    padding: '6px 12px',
    borderRadius: 8,
    border: '1px solid rgba(255, 255, 255, 0.15)',
    background: '#0f172a',
    color: '#fff',
    fontSize: 12,
    width: 180,
    outline: 'none',
  },
  btnSecondary: {
    padding: '7px 14px',
    borderRadius: 8,
    border: '1px solid rgba(255, 255, 255, 0.15)',
    background: '#334155',
    color: '#f8fafc',
    fontSize: 12,
    fontWeight: 600,
    cursor: 'pointer',
  },
  btnPrimary: {
    padding: '7px 14px',
    borderRadius: 8,
    border: 'none',
    background: 'linear-gradient(135deg, #10b981, #059669)',
    color: '#fff',
    fontSize: 12,
    fontWeight: 600,
    cursor: 'pointer',
    boxShadow: '0 4px 12px rgba(16, 185, 129, 0.25)',
  },

  // Sidebar Floating Panel
  sidebar: {
    position: 'fixed',
    top: 76,
    left: 14,
    zIndex: 85,
    width: 310,
    maxHeight: 'calc(100vh - 150px)',
    display: 'flex',
    flexDirection: 'column',
    background: 'rgba(15, 23, 42, 0.88)',
    backdropFilter: 'blur(16px)',
    WebkitBackdropFilter: 'blur(16px)',
    border: '1px solid rgba(255, 255, 255, 0.12)',
    borderRadius: 16,
    boxShadow: '0 16px 40px rgba(0, 0, 0, 0.45)',
    color: '#fff',
    fontFamily: 'system-ui, -apple-system, sans-serif',
    transition: 'transform 0.3s cubic-bezier(0.16, 1, 0.3, 1)',
  },
  collapseBtn: {
    position: 'absolute',
    top: 14,
    zIndex: 90,
    width: 24,
    height: 36,
    borderRadius: '0 8px 8px 0',
    border: '1px solid rgba(255, 255, 255, 0.15)',
    borderLeft: 'none',
    background: '#1e293b',
    color: '#94a3b8',
    fontSize: 11,
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    transition: 'all 0.3s',
  },
  tabsHeader: {
    display: 'flex',
    borderBottom: '1px solid rgba(255, 255, 255, 0.08)',
    padding: '8px 8px 0',
  },
  tabBtn: {
    flex: 1,
    padding: '8px 4px',
    borderRadius: '8px 8px 0 0',
    border: 'none',
    background: 'transparent',
    color: '#94a3b8',
    fontSize: 11,
    fontWeight: 600,
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    gap: 4,
    transition: 'all 0.2s',
  },
  tabBtnActive: {
    background: 'rgba(255, 255, 255, 0.08)',
    color: '#10b981',
    borderBottom: '2px solid #10b981',
  },
  tabLabel: {
    whiteSpace: 'nowrap',
  },
  tabBody: {
    padding: 14,
    overflowY: 'auto',
    flex: 1,
  },
  sectionFade: {
    animation: 'fadeIn 0.2s ease',
  },
  sectionTitleRow: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: 10,
  },
  sectionTitle: {
    fontSize: 13,
    fontWeight: 700,
    color: '#f1f5f9',
  },
  badgeInfo: {
    fontSize: 10,
    padding: '2px 8px',
    borderRadius: 10,
    background: 'rgba(148, 163, 184, 0.15)',
    color: '#cbd5e1',
  },
  badgeAi: {
    fontSize: 10,
    padding: '2px 8px',
    borderRadius: 10,
    background: 'rgba(99, 102, 241, 0.2)',
    color: '#a5b4fc',
    fontWeight: 700,
  },

  // Material Cards Grid
  materialsGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(2, 1fr)',
    gap: 10,
    maxHeight: 280,
    overflowY: 'auto',
    paddingRight: 4,
  },
  materialCard: {
    background: '#1e293b',
    borderRadius: 10,
    border: '1px solid rgba(255, 255, 255, 0.08)',
    overflow: 'hidden',
    cursor: 'pointer',
    transition: 'all 0.2s ease',
  },
  materialCardSelected: {
    borderColor: '#10b981',
    boxShadow: '0 0 0 2px rgba(16, 185, 129, 0.35)',
    background: '#0f172a',
  },
  cardImgContainer: {
    position: 'relative',
    height: 75,
    width: '100%',
    background: '#0f172a',
  },
  cardImg: {
    width: '100%',
    height: '100%',
    objectFit: 'cover',
  },
  kindTag: {
    position: 'absolute',
    bottom: 4,
    left: 4,
    fontSize: 9,
    padding: '2px 6px',
    borderRadius: 4,
    background: 'rgba(0, 0, 0, 0.7)',
    color: '#fff',
  },
  checkBadge: {
    position: 'absolute',
    top: 4,
    right: 4,
    width: 18,
    height: 18,
    borderRadius: 9,
    background: '#10b981',
    color: '#fff',
    fontSize: 11,
    fontWeight: 800,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
  },
  cardBody: {
    padding: '6px 8px',
  },
  cardTitle: {
    fontSize: 11,
    fontWeight: 600,
    color: '#f8fafc',
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
  },
  cardPrice: {
    fontSize: 12,
    fontWeight: 800,
    color: '#10b981',
    marginTop: 2,
  },
  cardDims: {
    fontSize: 9,
    color: '#94a3b8',
    marginTop: 2,
  },

  // AI Recommendation list
  aiHintBox: {
    fontSize: 11,
    color: '#94a3b8',
    padding: 10,
    borderRadius: 8,
    background: 'rgba(255, 255, 255, 0.04)',
    lineHeight: 1.4,
  },
  recoList: {
    display: 'flex',
    flexDirection: 'column',
    gap: 8,
  },
  recoCard: {
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'space-between',
    padding: '8px 10px',
    background: '#1e293b',
    borderRadius: 8,
    border: '1px solid rgba(99, 102, 241, 0.2)',
  },
  recoName: {
    fontSize: 11,
    fontWeight: 700,
    color: '#f8fafc',
  },
  recoCat: {
    fontSize: 10,
    color: '#94a3b8',
    marginTop: 2,
  },
  recoApplyBtn: {
    padding: '4px 10px',
    borderRadius: 6,
    border: 'none',
    background: '#6366f1',
    color: '#fff',
    fontSize: 11,
    fontWeight: 600,
    cursor: 'pointer',
  },

  // Sliders and Metric Cards
  sliderGroup: {
    marginBottom: 12,
  },
  sliderHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    fontSize: 11,
    color: '#94a3b8',
    marginBottom: 4,
  },
  sliderValue: {
    color: '#10b981',
    fontWeight: 700,
  },
  rangeInput: {
    width: '100%',
    accentColor: '#10b981',
    cursor: 'pointer',
  },
  areaMetricCard: {
    display: 'flex',
    alignItems: 'center',
    background: '#1e293b',
    padding: 10,
    borderRadius: 10,
    border: '1px solid rgba(255, 255, 255, 0.08)',
    marginTop: 14,
  },
  metricItem: {
    flex: 1,
    textAlign: 'center',
  },
  metricLabel: {
    fontSize: 10,
    color: '#94a3b8',
    textTransform: 'uppercase',
  },
  metricVal: {
    fontSize: 14,
    fontWeight: 800,
    color: '#f8fafc',
    marginTop: 2,
  },
  metricDivider: {
    width: 1,
    height: 24,
    background: 'rgba(255, 255, 255, 0.1)',
  },
  finishGroup: {
    display: 'flex',
    gap: 8,
    marginTop: 8,
  },
  finishBtn: {
    flex: 1,
    padding: '8px',
    borderRadius: 8,
    border: '1px solid rgba(255, 255, 255, 0.1)',
    background: '#1e293b',
    color: '#94a3b8',
    fontSize: 12,
    fontWeight: 600,
    cursor: 'pointer',
  },
  finishBtnActive: {
    background: '#10b981',
    color: '#fff',
    borderColor: '#10b981',
  },

  // Actions and Buttons
  mainActionBtn: {
    width: '100%',
    padding: '10px',
    borderRadius: 10,
    border: 'none',
    background: 'linear-gradient(135deg, #10b981, #059669)',
    color: '#fff',
    fontSize: 13,
    fontWeight: 700,
    boxShadow: '0 4px 14px rgba(16, 185, 129, 0.3)',
    transition: 'all 0.2s',
  },
  secondaryActionBtn: {
    width: '100%',
    padding: '9px',
    borderRadius: 10,
    border: 'none',
    background: '#f59e0b',
    color: '#0f172a',
    fontSize: 12,
    fontWeight: 700,
  },
  infoCard: {
    marginTop: 12,
    padding: 10,
    borderRadius: 10,
    background: '#1e293b',
    border: '1px solid rgba(255, 255, 255, 0.08)',
  },
  infoCardHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    fontSize: 11,
    fontWeight: 700,
    color: '#f8fafc',
    marginBottom: 8,
  },
  togglePill: {
    padding: '3px 8px',
    borderRadius: 10,
    border: 'none',
    background: '#334155',
    color: '#cbd5e1',
    fontSize: 10,
    fontWeight: 700,
    cursor: 'pointer',
  },
  togglePillActive: {
    background: '#3b82f6',
    color: '#fff',
  },
  infoRow: {
    display: 'flex',
    justifyContent: 'space-between',
    fontSize: 11,
    color: '#94a3b8',
    marginBottom: 4,
  },
  infoRowHighlight: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    fontSize: 12,
    color: '#f8fafc',
    marginTop: 8,
    paddingTop: 6,
    borderTop: '1px dashed rgba(255, 255, 255, 0.1)',
  },

  // Stock and Quote Cards
  stockCard: {
    padding: 10,
    borderRadius: 10,
    border: '1px solid',
    marginBottom: 12,
  },
  stockCardHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    fontSize: 11,
    fontWeight: 700,
    color: '#f8fafc',
    marginBottom: 8,
  },
  stockBadge: {
    fontSize: 9,
    padding: '2px 6px',
    borderRadius: 4,
    color: '#fff',
    fontWeight: 700,
  },
  quoteCard: {
    padding: 10,
    borderRadius: 10,
    background: '#1e293b',
    border: '1px solid rgba(255, 255, 255, 0.08)',
  },
  quoteCardTitle: {
    fontSize: 12,
    fontWeight: 700,
    color: '#f8fafc',
    marginBottom: 8,
  },
  quoteTotalRow: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 10,
    paddingTop: 8,
    borderTop: '1px solid rgba(255, 255, 255, 0.1)',
    fontSize: 12,
    fontWeight: 700,
    color: '#f8fafc',
  },
  quoteTotalVal: {
    fontSize: 16,
    color: '#10b981',
    fontWeight: 800,
  },
  pdfBtn: {
    width: '100%',
    padding: '11px',
    borderRadius: 10,
    border: 'none',
    background: 'linear-gradient(135deg, #2563eb, #1d4ed8)',
    color: '#fff',
    fontSize: 13,
    fontWeight: 700,
    boxShadow: '0 4px 14px rgba(37, 99, 235, 0.3)',
  },

  // Helpers
  loadingBox: {
    fontSize: 11,
    color: '#94a3b8',
    padding: 14,
    textAlign: 'center',
  },
  emptyBox: {
    fontSize: 11,
    color: '#f87171',
    padding: 14,
    textAlign: 'center',
  },
  spinner: {
    display: 'inline-block',
    width: 12,
    height: 12,
    border: '2px solid rgba(255, 255, 255, 0.2)',
    borderTopColor: '#10b981',
    borderRadius: '50%',
    animation: 'spin 1s linear infinite',
  },

  // Permanent Floating Bottom Action Bar
  bottomBar: {
    position: 'fixed',
    bottom: 14,
    right: 14,
    zIndex: 90,
    display: 'flex',
    alignItems: 'center',
    gap: 14,
    padding: '8px 14px',
    background: 'rgba(15, 23, 42, 0.88)',
    backdropFilter: 'blur(16px)',
    WebkitBackdropFilter: 'blur(16px)',
    border: '1px solid rgba(255, 255, 255, 0.12)',
    borderRadius: 14,
    boxShadow: '0 10px 30px rgba(0, 0, 0, 0.4)',
    color: '#fff',
    fontFamily: 'system-ui, -apple-system, sans-serif',
  },
  bottomBarItem: {
    display: 'flex',
    alignItems: 'center',
    gap: 10,
  },
  bottomThumb: {
    width: 34,
    height: 34,
    borderRadius: 7,
    objectFit: 'cover',
    border: '1px solid rgba(255, 255, 255, 0.15)',
  },
  bottomMatName: {
    fontSize: 11,
    fontWeight: 700,
    color: '#f8fafc',
    maxWidth: 130,
    whiteSpace: 'nowrap',
    overflow: 'hidden',
    textOverflow: 'ellipsis',
  },
  bottomMatPrice: {
    fontSize: 10,
    color: '#10b981',
    fontWeight: 700,
  },
  bottomBarDivider: {
    width: 1,
    height: 24,
    background: 'rgba(255, 255, 255, 0.15)',
  },
  bottomBarRight: {
    display: 'flex',
    alignItems: 'center',
    gap: 12,
  },
  bottomTotalLabel: {
    fontSize: 9,
    color: '#94a3b8',
    textTransform: 'uppercase',
  },
  bottomTotalVal: {
    fontSize: 13,
    fontWeight: 800,
    color: '#10b981',
  },
  bottomPdfBtn: {
    padding: '6px 12px',
    borderRadius: 8,
    border: 'none',
    background: '#2563eb',
    color: '#fff',
    fontSize: 11,
    fontWeight: 700,
    cursor: 'pointer',
  },
}
