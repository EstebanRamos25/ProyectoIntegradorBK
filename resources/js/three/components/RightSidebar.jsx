import React, { Suspense, useState } from 'react'
import { Canvas } from '@react-three/fiber'
import { OrbitControls, Environment, ContactShadows, useTexture } from '@react-three/drei'
import { SunLight, getEnvironmentPreset } from './SunLight'
import * as THREE from 'three'

/**
 * Visor 3D miniatura para materiales
 */
function MiniViewer({ textureUrl, roughness, metalness, timeOfDay, intensity }) {
  const map = useTexture(textureUrl || 'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+ip1sAAAAASUVORK5CYII=')
  map.colorSpace = THREE.SRGBColorSpace
  map.wrapS = map.wrapT = THREE.RepeatWrapping
  map.needsUpdate = true

  return (
    <>
      <color attach="background" args={['#0f172a']} />
      
      {/* Luces del visor que imitan la hora del día pero siempre visibles */}
      <SunLight timeOfDay={timeOfDay} intensity={intensity * 1.5} />
      <ambientLight intensity={0.4} />

      <mesh position={[0, -0.2, 0]} castShadow receiveShadow>
        <boxGeometry args={[2.5, 0.4, 2.5]} />
        <meshStandardMaterial 
          map={map}
          color={map ? '#ffffff' : '#475569'}
          roughness={roughness}
          metalness={metalness}
        />
      </mesh>

      <ContactShadows position={[0, -0.4, 0]} opacity={0.7} scale={5} blur={1.5} far={1} />
      <OrbitControls autoRotate autoRotateSpeed={1.5} enableZoom={false} minPolarAngle={0.5} maxPolarAngle={Math.PI/2.1} />
      <Environment preset={getEnvironmentPreset(timeOfDay)} background={false} />
    </>
  )
}

/**
 * Barra lateral derecha que se muestra cuando hay un material seleccionado.
 * Muestra el visor 3D, detalles del producto y la calculadora de cobertura.
 */
export function RightSidebar({
  previewFloor,
  previewWall,
  installedFloor,
  installedWall,
  floorCoverage,
  wallCoverage,
  coverSolid,
  onToggleCoverSolid,
  timeOfDay,
  lightIntensity,
  onApplyFloor,
  onApplyWall,
  onCancelPreviewFloor,
  onCancelPreviewWall,
  onRemoveFloor,
  onRemoveWall,
  activeSurface
}) {
  const [isCollapsed, setIsCollapsed] = useState(false)
  const currencyFormatter = new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB' })

  // Render a single material block (used for preview and installed items)
  const renderMaterialBlock = (mat, label, actionButtons, covData, isWallCoverage) => {
    if (!mat) return null
    const price = mat.price_per_m2 ? Number(mat.price_per_m2) : 0
    const roughness = mat.kind === 'plank' ? 0.75 : 0.65

    return (
      <div style={styles.blockContainer}>
        <div style={styles.blockLabel}>{label}</div>
        <div style={{ ...styles.viewerBox, height: 140 }}>
          <Canvas shadows camera={{ position: [2.5, 2, 2.5], fov: 45 }}>
            <Suspense fallback={null}>
              <MiniViewer textureUrl={mat.image_url} roughness={roughness} metalness={0.0} timeOfDay={timeOfDay} intensity={lightIntensity} />
            </Suspense>
          </Canvas>
        </div>
        <div style={styles.blockBody}>
          <div style={styles.header}>
            <div style={styles.title}>{mat.name}</div>
            <div style={styles.price}>{currencyFormatter.format(price)} / m²</div>
          </div>
          {mat.piece_dimensions_cm && (
            <div style={styles.detailsRow}>
              <span>Dimensiones:</span>
              <strong>{mat.piece_dimensions_cm.width}x{mat.piece_dimensions_cm.depth} cm</strong>
            </div>
          )}

          {/* Coverage integrated in the block */}
          {covData?.computed && (
            <div style={{ marginTop: 12, padding: 12, background: 'rgba(255,255,255,0.04)', borderRadius: 8 }}>
              <div style={styles.coverageHeader}>
                <span>📊 Cobertura</span>
                {/* Solamente mostramos el botón de grilla si es la superficie activa para no cruzar el estado global */}
                {((isWallCoverage && activeSurface === 'walls') || (!isWallCoverage && activeSurface === 'floor')) && (
                  <button onClick={onToggleCoverSolid} style={{...styles.toggleBtn, ...(coverSolid ? styles.toggleBtnSolid : {})}}>
                    {coverSolid ? '🧱 Sólido' : '🔲 Grilla'}
                  </button>
                )}
              </div>
              <div style={styles.coverageBox}>
                <div style={styles.infoRow}><span>Área total:</span><strong>{(isWallCoverage ? covData.wallAreaM2 : covData.floorAreaM2)?.toFixed(2)} m²</strong></div>
                <div style={styles.infoRow}><span>Área por pieza:</span><strong>{covData.pieceAreaM2?.toFixed(4)} m²</strong></div>
                <div style={styles.highlightBox}>
                  <div style={styles.highlightTitle}>Total Piezas Estimadas</div>
                  <div style={styles.highlightValue}>{isWallCoverage ? covData.estimatedUnits : covData.count} <span style={{fontSize: 14}}>unidades</span></div>
                </div>
              </div>
            </div>
          )}

          {actionButtons && <div style={styles.actionRow}>{actionButtons}</div>}
        </div>
      </div>
    )
  }

  const hasAnyContent = previewFloor || installedFloor || previewWall || installedWall
  if (!hasAnyContent) return null

  if (isCollapsed) {
    return (
      <button style={styles.btnExpand} onClick={() => setIsCollapsed(false)}>
        {'<'}
      </button>
    )
  }

  return (
    <div style={styles.container}>
      <div style={styles.topHeader}>
        <span style={{ fontWeight: 600 }}>Materiales en Escena</span>
        <button style={styles.btnCollapse} onClick={() => setIsCollapsed(true)}>{'>'}</button>
      </div>

      <div style={styles.installedScroll}>
        {/* FLOOR SECTION */}
        {previewFloor ? renderMaterialBlock(
          previewFloor, 
          'Previsualizando Piso', 
          <>
            <button style={styles.btnCancel} onClick={onCancelPreviewFloor}>Cancelar</button>
            <button style={styles.btnApply} onClick={onApplyFloor}>Aplicar al Piso</button>
          </>,
          floorCoverage,
          false
        ) : installedFloor ? renderMaterialBlock(
          installedFloor, 
          'Piso Instalado', 
          <button style={styles.btnRemove} onClick={onRemoveFloor}>Quitar del Piso</button>,
          floorCoverage,
          false
        ) : null}

        {/* WALL SECTION */}
        {previewWall ? renderMaterialBlock(
          previewWall, 
          'Previsualizando Paredes', 
          <>
            <button style={styles.btnCancel} onClick={onCancelPreviewWall}>Cancelar</button>
            <button style={styles.btnApply} onClick={onApplyWall}>Aplicar a Paredes</button>
          </>,
          wallCoverage,
          true
        ) : installedWall ? renderMaterialBlock(
          installedWall, 
          'Paredes Instaladas', 
          <button style={styles.btnRemove} onClick={onRemoveWall}>Quitar de Paredes</button>,
          wallCoverage,
          true
        ) : null}
      </div>
    </div>
  )
}

const styles = {
  container: {
    position: 'fixed',
    top: 62,
    right: 12,
    width: 320,
    maxHeight: 'calc(100vh - 80px)',
    background: 'rgba(15, 23, 42, 0.85)',
    backdropFilter: 'blur(16px)',
    WebkitBackdropFilter: 'blur(16px)',
    border: '1px solid rgba(255, 255, 255, 0.12)',
    borderRadius: 14,
    color: '#fff',
    fontFamily: 'system-ui, -apple-system, sans-serif',
    zIndex: 50,
    display: 'flex',
    flexDirection: 'column',
    boxShadow: '0 20px 40px rgba(0,0,0,0.5)',
  },
  btnCollapse: {
    background: 'rgba(255,255,255,0.1)',
    border: 'none',
    color: '#fff',
    width: 24,
    height: 24,
    borderRadius: 4,
    cursor: 'pointer',
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: 14,
  },
  btnExpand: {
    position: 'fixed',
    top: 62,
    right: 0,
    background: 'rgba(15, 23, 42, 0.85)',
    backdropFilter: 'blur(16px)',
    WebkitBackdropFilter: 'blur(16px)',
    border: '1px solid rgba(255, 255, 255, 0.12)',
    borderRight: 'none',
    borderRadius: '8px 0 0 8px',
    color: '#fff',
    width: 32,
    height: 48,
    cursor: 'pointer',
    zIndex: 50,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    fontSize: 16,
  },
  topHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    padding: '12px 16px',
    borderBottom: '1px solid rgba(255, 255, 255, 0.12)',
    background: 'rgba(0,0,0,0.2)',
  },
  installedScroll: {
    overflowY: 'auto',
    maxHeight: 'calc(100vh - 80px)',
    display: 'flex',
    flexDirection: 'column',
  },
  installedHeader: {
    padding: '16px 16px 8px',
    fontSize: 15,
    fontWeight: 700,
    color: '#e2e8f0',
    borderBottom: '1px solid rgba(255, 255, 255, 0.12)',
  },
  blockContainer: {
    borderBottom: '1px solid rgba(255, 255, 255, 0.12)',
  },
  blockLabel: {
    position: 'absolute',
    background: 'rgba(0,0,0,0.6)',
    padding: '4px 8px',
    borderRadius: '0 0 8px 0',
    fontSize: 11,
    fontWeight: 600,
    color: '#34d399',
    zIndex: 2,
  },
  viewerBox: {
    width: '100%',
    background: '#0f172a',
    cursor: 'grab',
  },
  blockBody: {
    padding: 12,
  },
  actionRow: {
    display: 'flex',
    gap: 8,
    marginTop: 12,
  },
  btnApply: {
    flex: 1,
    padding: '8px',
    borderRadius: 8,
    background: '#10b981',
    color: '#fff',
    border: 'none',
    fontWeight: 600,
    cursor: 'pointer',
    fontSize: 13,
  },
  btnCancel: {
    flex: 1,
    padding: '8px',
    borderRadius: 8,
    background: '#334155',
    color: '#fff',
    border: 'none',
    fontWeight: 600,
    cursor: 'pointer',
    fontSize: 13,
  },
  btnRemove: {
    width: '100%',
    padding: '8px',
    borderRadius: 8,
    background: 'transparent',
    color: '#ef4444',
    border: '1px solid rgba(239, 68, 68, 0.4)',
    fontWeight: 600,
    cursor: 'pointer',
    fontSize: 13,
    transition: 'background 0.2s',
  },
  body: {
    padding: 16,
    overflowY: 'auto',
  },
  header: {
    marginBottom: 12,
  },
  title: {
    fontSize: 18,
    fontWeight: 700,
    color: '#f8fafc',
    marginBottom: 2,
    lineHeight: 1.2,
  },
  price: {
    fontSize: 14,
    color: '#10b981',
    fontWeight: 600,
  },
  detailsRow: {
    display: 'flex',
    justifyContent: 'space-between',
    fontSize: 12,
    color: '#94a3b8',
    marginBottom: 6,
  },
  divider: {
    border: 'none',
    borderTop: '1px solid rgba(255, 255, 255, 0.12)',
    margin: '16px 0',
  },
  coverageHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    fontSize: 14,
    fontWeight: 600,
    color: '#e2e8f0',
    marginBottom: 12,
  },
  toggleBtn: {
    padding: '4px 10px',
    borderRadius: 8,
    border: 'none',
    background: '#334155',
    color: '#cbd5e1',
    fontSize: 11,
    fontWeight: 700,
    cursor: 'pointer',
    transition: 'background 0.2s',
  },
  toggleBtnSolid: {
    background: '#10b981',
    color: '#fff',
  },
  coverageBox: {
    display: 'flex',
    flexDirection: 'column',
    gap: 8,
  },
  infoRow: {
    display: 'flex',
    justifyContent: 'space-between',
    fontSize: 13,
    color: '#cbd5e1',
  },
  highlightBox: {
    marginTop: 8,
    background: 'rgba(16, 185, 129, 0.1)',
    border: '1px solid rgba(16, 185, 129, 0.3)',
    borderRadius: 8,
    padding: 12,
    textAlign: 'center',
  },
  highlightTitle: {
    fontSize: 12,
    color: '#34d399',
    textTransform: 'uppercase',
    letterSpacing: 0.5,
    fontWeight: 600,
    marginBottom: 4,
  },
  highlightValue: {
    fontSize: 28,
    fontWeight: 800,
    color: '#10b981',
    lineHeight: 1,
  },
  emptyCoverage: {
    fontSize: 12,
    color: '#fbbf24',
    background: 'rgba(251, 191, 36, 0.1)',
    padding: 10,
    borderRadius: 8,
    textAlign: 'center',
  },
}
