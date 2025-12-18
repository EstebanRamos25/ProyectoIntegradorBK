import React, { useEffect, useMemo, useState, useCallback, useRef } from 'react'
import { createRoot } from 'react-dom/client'
import { Canvas, useFrame, useThree } from '@react-three/fiber'
import { OrbitControls, Environment, SoftShadows, PerformanceMonitor } from '@react-three/drei'
import { EffectComposer, Bloom, SSAO, ToneMapping, Vignette } from '@react-three/postprocessing'
import { DepthOfField, BrightnessContrast, HueSaturation } from '@react-three/postprocessing'
import { ACESFilmicToneMapping, SRGBColorSpace, Vector3, Object3D } from 'three'
import {
  cameraConfig,
  controlsConfig,
  lightsConfig,
  postprocessingConfig,
  rendererConfig,
} from './config'

function Floor({ kind }) {
  // Two simple materials: wood vs ceramic using basic colors for MVP
  const color = kind === 'wood' ? '#8b5a2b' : '#cfd8dc'
  return (
    <mesh rotation={[-Math.PI/2,0,0]} receiveShadow>
      <planeGeometry args={[10,10,1,1]} />
      <meshStandardMaterial color={color} roughness={0.9} metalness={0.0} />
    </mesh>
  )
}

function Controls({
  floor,
  setFloor,
  paletteKind,
  setPaletteKind,
  onAdd,
  canAdd,
  addLabel,
  coverage,
  onCover,
  onToggleCover,
  coverEnabled,
}) {
  return (
    <div style={{position:'fixed', top:12, left:12, zIndex:10, width:260, background:'rgba(0,0,0,.65)', color:'#fff', padding:'12px', borderRadius:10, fontFamily:'system-ui,Arial,sans-serif'}}>
      <div style={{fontWeight:700, marginBottom:10}}>Catálogo</div>

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Elemento</div>
      <div style={{display:'flex', gap:8, marginBottom:10}}>
        <button
          onClick={() => setPaletteKind('tile')}
          style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid #333', background: paletteKind === 'tile' ? '#1f6feb' : '#222', color:'#fff', cursor:'pointer'}}
        >
          Cerámica
        </button>
        <button
          onClick={() => setPaletteKind('plank')}
          style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid #333', background: paletteKind === 'plank' ? '#1f6feb' : '#222', color:'#fff', cursor:'pointer'}}
        >
          Madera
        </button>
      </div>

      <button
        onClick={onAdd}
        disabled={!canAdd}
        style={{width:'100%', padding:'10px 10px', borderRadius:8, border:'none', background: canAdd ? '#16a34a' : '#374151', color:'#fff', cursor: canAdd ? 'pointer' : 'not-allowed'}}
      >
        {addLabel}
      </button>

      <div style={{height:10}} />

      <button
        onClick={onCover}
        disabled={!coverage?.canCompute}
        style={{width:'100%', padding:'10px 10px', borderRadius:8, border:'none', background: coverage?.canCompute ? '#f59e0b' : '#374151', color:'#111827', cursor: coverage?.canCompute ? 'pointer' : 'not-allowed'}}
        title={!coverage?.canCompute ? 'Agrega una pieza para calcular cobertura' : 'Calcula cuántas piezas cubren el piso'}
      >
        Cubrir piso (calcular)
      </button>

      {coverage?.canCompute && coverage?.computed && (
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
            Preview limita a 800 piezas por rendimiento.
          </div>
        </div>
      )}

      <div style={{height:10}} />

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Piso</div>
      <div style={{display:'flex', gap:8}}>
        <button onClick={() => setFloor('wood')} style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid #333', background: floor === 'wood' ? '#444' : '#222', color:'#fff', cursor:'pointer'}}>Madera</button>
        <button onClick={() => setFloor('ceramic')} style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid #333', background: floor === 'ceramic' ? '#444' : '#222', color:'#fff', cursor:'pointer'}}>Cerámica</button>
      </div>
    </div>
  )
}

function RendererSetup() {
  const { gl } = useThree()
  // Set renderer defaults once (R3F handles resizing)
  React.useEffect(() => {
    gl.outputColorSpace = SRGBColorSpace
    gl.toneMapping = ACESFilmicToneMapping
    gl.toneMappingExposure = postprocessingConfig.toneMapping.exposure
    gl.physicallyCorrectLights = rendererConfig.physicallyCorrectLights
    gl.shadowMap.enabled = true
  }, [gl])
  return null
}

// (PostFX definido más abajo con DOF/grading)

function Demo() {
  const [floor, setFloor] = useState('wood')
  const [selected, setSelected] = useState(null)
  const [postFXOn, setPostFXOn] = useState(true)
  const [softShadowsBad, setSoftShadowsBad] = useState(false)
  const [dofOn, setDofOn] = useState(true)
  const [paletteKind, setPaletteKind] = useState('tile')
  const [pieces, setPieces] = useState([])
  const [coverage, setCoverage] = useState({ canCompute: false, computed: false })
  const [coverEnabled, setCoverEnabled] = useState(false)
  const nextId = useRef(1)
  const lastEmissive = useRef({ obj: null, color: null })
  const controlsRef = useRef(null)

  const clearSelectionVisual = useCallback(() => {
    if (lastEmissive.current.obj && lastEmissive.current.obj.material?.emissive) {
      lastEmissive.current.obj.material.emissive.set(lastEmissive.current.color)
      lastEmissive.current.obj = null
    }
  }, [])

  const handlePointerMissed = useCallback(() => {
    clearSelectionVisual()
    setSelected(null)
  }, [clearSelectionVisual])

  const isMovable = useCallback((obj) => {
    if (!obj) return false
    // Solo piezas (no paredes ni grupo de pared)
    return typeof obj.name === 'string' && obj.name.startsWith('Pieza-')
  }, [])

  // Límites del cuarto: piso 10x10 centrado en 0,0 (plano XZ)
  // Los cubos son de 1x1x1, así que el centro no debe pasar ±4.5
  const roomLimits = useMemo(() => ({ min: -4.5, max: 4.5, y: 0.5 }), [])

  const selectedPieceId = useMemo(() => {
    if (!selected?.name?.startsWith('Pieza-')) return null
    const idStr = selected.name.replace('Pieza-', '')
    const idNum = Number(idStr)
    return Number.isFinite(idNum) ? idNum : null
  }, [selected])

  const selectedPiece = useMemo(() => {
    if (selectedPieceId == null) return null
    return pieces.find((p) => p.id === selectedPieceId) ?? null
  }, [pieces, selectedPieceId])

  const updateSelectedScale = useCallback(
    (axis, value) => {
      if (!selectedPiece) return
      const v = Math.max(0.5, Math.min(3.0, value))
      setPieces((prev) =>
        prev.map((p) =>
          p.id === selectedPiece.id
            ? { ...p, scaleXZ: { ...p.scaleXZ, [axis]: v } }
            : p
        )
      )
    },
    [selectedPiece]
  )

  const clampPosForPiece = useCallback(
    (pos, piece) => {
      const base = piece?.size ?? [1, 1, 1]
      const sx = piece?.scaleXZ?.x ?? 1
      const sz = piece?.scaleXZ?.z ?? 1
      const halfX = (base[0] * sx) / 2
      const halfZ = (base[2] * sz) / 2
      const minX = roomLimits.min + halfX
      const maxX = roomLimits.max - halfX
      const minZ = roomLimits.min + halfZ
      const maxZ = roomLimits.max - halfZ
      return {
        x: Math.min(maxX, Math.max(minX, pos.x)),
        z: Math.min(maxZ, Math.max(minZ, pos.z)),
      }
    },
    [roomLimits]
  )

  const metersToCm = useCallback((m) => Math.round(m * 100), [])
  const cmToMeters = useCallback((cm) => cm / 100, [])

  const selectedSizeCm = useMemo(() => {
    if (!selectedPiece) return null
    const base = selectedPiece.size
    const sx = selectedPiece.scaleXZ?.x ?? 1
    const sz = selectedPiece.scaleXZ?.z ?? 1
    return {
      x: metersToCm(base[0] * sx),
      z: metersToCm(base[2] * sz),
    }
  }, [selectedPiece, metersToCm])

  const coverageData = useMemo(() => {
    if (!selectedPiece || !selectedSizeCm) return { canCompute: false, computed: false }
    const pieceM = {
      x: selectedSizeCm.x / 100,
      z: selectedSizeCm.z / 100,
    }
    const pieceAreaM2 = pieceM.x * pieceM.z
    const floorAreaM2 = 10 * 10
    if (!Number.isFinite(pieceAreaM2) || pieceAreaM2 <= 0) return { canCompute: false, computed: false }

    const count = Math.ceil(floorAreaM2 / pieceAreaM2)
    return {
      canCompute: true,
      computed: true,
      pieceCmX: selectedSizeCm.x,
      pieceCmZ: selectedSizeCm.z,
      pieceAreaM2,
      floorAreaM2,
      count,
    }
  }, [selectedPiece, selectedSizeCm])

  const handleCoverCompute = useCallback(() => {
    setCoverage(coverageData)
    setCoverEnabled(true)
  }, [coverageData])

  const toggleCover = useCallback(() => {
    setCoverEnabled((v) => !v)
  }, [])

  const updateSelectedSizeCm = useCallback(
    (axis, cmValue) => {
      if (!selectedPiece) return
      const cmClamped = Math.max(10, Math.min(400, cmValue))
      const meters = cmToMeters(cmClamped)
      const base = selectedPiece.size

      // Convertir centímetros absolutos a escala relativa sobre la geometría base
      const nextScale = axis === 'x'
        ? { x: meters / base[0], z: selectedPiece.scaleXZ?.z ?? 1 }
        : { x: selectedPiece.scaleXZ?.x ?? 1, z: meters / base[2] }

      setPieces((prev) => {
        const next = prev.map((p) => (p.id === selectedPiece.id ? { ...p, scaleXZ: nextScale } : p))
        const updated = next.find((p) => p.id === selectedPiece.id)
        if (updated && selected && selected.name === updated.name) {
          const clamped = clampPosForPiece({ x: selected.position.x, z: selected.position.z }, updated)
          selected.position.set(clamped.x, roomLimits.y, clamped.z)
        }
        return next
      })
    },
    [selectedPiece, cmToMeters, clampPosForPiece, selected, roomLimits.y]
  )

  const nudgeSelected = useCallback(
    (dx, dz, fast = false) => {
      if (!selected || !isMovable(selected)) return
      const step = fast ? 0.25 : 0.1
      const base = selectedPiece?.size ?? [1, 1, 1]
      const sx = selectedPiece?.scaleXZ?.x ?? 1
      const sz = selectedPiece?.scaleXZ?.z ?? 1
      const halfX = (base[0] * sx) / 2
      const halfZ = (base[2] * sz) / 2
      const minX = roomLimits.min + halfX
      const maxX = roomLimits.max - halfX
      const minZ = roomLimits.min + halfZ
      const maxZ = roomLimits.max - halfZ
      const nextX = Math.min(maxX, Math.max(minX, selected.position.x + dx * step))
      const nextZ = Math.min(maxZ, Math.max(minZ, selected.position.z + dz * step))
      selected.position.set(nextX, roomLimits.y, nextZ)
    },
    [selected, isMovable, roomLimits, selectedPiece]
  )

  const pieceCatalog = useMemo(
    () => ({
      tile: {
        label: 'Cerámica',
        size: [1.0, 0.12, 1.0],
        color: '#cfd8dc',
        roughness: 0.85,
        metalness: 0.0,
      },
      plank: {
        label: 'Madera',
        size: [1.8, 0.12, 0.5],
        color: '#8b5a2b',
        roughness: 0.9,
        metalness: 0.0,
      },
    }),
    []
  )

  const hasPiece = pieces.length > 0
  const canAdd = true
  const addLabel = hasPiece ? 'Reemplazar pieza' : 'Agregar pieza'

  const addOnePiece = useCallback(() => {
    if (hasPiece) {
      const current = pieces[0]
      const currentLabel = current?.kind ? pieceCatalog[current.kind]?.label : 'actual'
      const nextLabel = pieceCatalog[paletteKind]?.label
      const ok = window.confirm(
        `Ya existe una pieza (${currentLabel}).\n\nSi continúas, se borrará y se agregará: ${nextLabel}.\n\n¿Deseas reemplazarla?`
      )
      if (!ok) return
    }
    const def = pieceCatalog[paletteKind]
    const id = nextId.current++
    const piece = {
      id,
      kind: paletteKind,
      name: `Pieza-${id}`,
      position: [0, roomLimits.y, 0],
      rotation: [0, 0, 0],
      size: def.size,
      scaleXZ: { x: 1, z: 1 },
      color: def.color,
      roughness: def.roughness,
      metalness: def.metalness,
    }
    setPieces([piece])
    // Selección y enfoque: se asigna tras mount por referencia del evento de click.
    setSelected(null)
  }, [hasPiece, pieces, pieceCatalog, paletteKind, roomLimits.y])

  // Teclado: flechas para mover, Shift acelera, Esc deselecciona
  useEffect(() => {
    function onKeyDown(ev) {
      if (!selected) return
      const fast = ev.shiftKey
      switch (ev.key) {
        case 'ArrowUp':
          ev.preventDefault()
          nudgeSelected(0, -1, fast)
          break
        case 'ArrowDown':
          ev.preventDefault()
          nudgeSelected(0, 1, fast)
          break
        case 'ArrowLeft':
          ev.preventDefault()
          nudgeSelected(-1, 0, fast)
          break
        case 'ArrowRight':
          ev.preventDefault()
          nudgeSelected(1, 0, fast)
          break
        case 'Escape':
          ev.preventDefault()
          handlePointerMissed()
          break
        default:
          break
      }
    }

    window.addEventListener('keydown', onKeyDown, { passive: false })
    return () => window.removeEventListener('keydown', onKeyDown)
  }, [selected, nudgeSelected, handlePointerMissed])

  const animateCameraTo = useCallback((obj) => {
    const controls = controlsRef.current
    const cam = controls?.object
    if (!controls || !cam || !obj) return
    const startPos = cam.position.clone()
    const endPos = obj.position.clone().add(new Vector3(2, 2, 2))
    const startTarget = controls.target.clone()
    const endTarget = obj.position.clone()
    const duration = 0.8
    let t = 0
    function step() {
      t += 1/60
      const alpha = Math.min(t / duration, 1)
      cam.position.lerpVectors(startPos, endPos, alpha)
      controls.target.lerpVectors(startTarget, endTarget, alpha)
      controls.update()
      if (alpha < 1) requestAnimationFrame(step)
    }
    requestAnimationFrame(step)
  }, [])

  const resetCamera = useCallback(() => {
    const controls = controlsRef.current
    const cam = controls?.object
    if (!controls || !cam) return
    controls.target.set(0,0,0)
    cam.position.set(...cameraConfig.position)
    controls.update()
    handlePointerMissed()
  }, [handlePointerMissed])

  const handleSelect = useCallback((e) => {
    if (e.button !== 0) return // solo click izquierdo
    e.stopPropagation()
    const obj = e.object
    if (!obj.material) return
    clearSelectionVisual()
    if (obj.material.emissive) {
      lastEmissive.current = { obj, color: obj.material.emissive.clone() }
      obj.material.emissive.set('#222222')
    }
    setSelected(obj)
    animateCameraTo(obj)
  }, [clearSelectionVisual, animateCameraTo])

  return (
    <>
      <Controls
        floor={floor}
        setFloor={setFloor}
        paletteKind={paletteKind}
        setPaletteKind={setPaletteKind}
        onAdd={addOnePiece}
        canAdd={canAdd}
        addLabel={addLabel}
        coverage={{ ...coverage, ...coverageData, canCompute: coverageData.canCompute, computed: coverage.computed }}
        onCover={handleCoverCompute}
        onToggleCover={toggleCover}
        coverEnabled={coverEnabled}
      />
      <div style={{position:'fixed', top:12, right:12, zIndex:12, background:'rgba(0,0,0,.6)', color:'#fff', padding:'10px 12px', borderRadius:8, fontFamily:'system-ui,Arial,sans-serif'}}>
        <div style={{display:'flex', gap:8}}>
          <button onClick={() => setPostFXOn(v => !v)} style={{padding:'6px 10px'}}>
            PostFX {postFXOn ? 'ON' : 'OFF'}
          </button>
          <button onClick={() => setDofOn(v => !v)} style={{padding:'6px 10px'}}>
            DOF {dofOn ? 'ON' : 'OFF'}
          </button>
        </div>
      </div>
      {selected && (
        <div style={{position:'fixed', top:62, right:12, zIndex:11, background:'rgba(0,0,0,.75)', color:'#fff', padding:'10px 12px', borderRadius:8, fontFamily:'system-ui,Arial,sans-serif', width:240}}>
          <div style={{fontWeight:'600', marginBottom:8}}>Objeto seleccionado</div>
          {!isMovable(selected) && (
            <div style={{fontSize:12, opacity:0.8, marginBottom:8}}>
              Solo las piezas se pueden mover.
            </div>
          )}
          <div style={{display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:6, marginBottom:10}}>
            {['#90caf9','#ffb74d','#81c784','#ce93d8'].map(c => (
              <button key={c} onClick={()=>{ if(selected.material){ selected.material.color.set(c) } }} style={{width:32,height:32,borderRadius:4,border:'1px solid #333', background:c, cursor:'pointer'}} />
            ))}
          </div>

          {isMovable(selected) && selectedPiece && (
            <div style={{marginBottom:10}}>
              <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Tamaño (cm, sin altura)</div>
              <label style={{display:'block', fontSize:12, opacity:0.9, marginBottom:8}}>
                Largo (X): {selectedSizeCm?.x ?? 0} cm
                <input
                  type="range"
                  min={10}
                  max={400}
                  step={1}
                  value={selectedSizeCm?.x ?? 100}
                  onChange={(e) => updateSelectedSizeCm('x', Number(e.target.value))}
                  style={{width:'100%'}}
                />
              </label>
              <label style={{display:'block', fontSize:12, opacity:0.9}}>
                Ancho (Z): {selectedSizeCm?.z ?? 0} cm
                <input
                  type="range"
                  min={10}
                  max={400}
                  step={1}
                  value={selectedSizeCm?.z ?? 100}
                  onChange={(e) => updateSelectedSizeCm('z', Number(e.target.value))}
                  style={{width:'100%'}}
                />
              </label>
            </div>
          )}
          {isMovable(selected) && (
            <div style={{fontSize:12, opacity:0.85, marginBottom:10, lineHeight:1.25}}>
              Flechas: mover<br />
              Shift: más rápido<br />
              Esc: quitar selección
            </div>
          )}
          <div style={{display:'flex', gap:6}}>
            <button onClick={handlePointerMissed} style={{flex:1, padding:'6px 10px', background:'#444', color:'#fff', border:'none', borderRadius:4}}>Quitar</button>
            <button onClick={resetCamera} style={{flex:1, padding:'6px 10px', background:'#1976d2', color:'#fff', border:'none', borderRadius:4}}>Reset</button>
          </div>
        </div>
      )}
      <Canvas
        shadows
        dpr={rendererConfig.dpr}
        camera={{ position: cameraConfig.position, fov: cameraConfig.fov }}
        onPointerMissed={handlePointerMissed}
      >
        <RendererSetup />

        {/* Degrada SoftShadows si el rendimiento cae */}
        <PerformanceMonitor onDecline={() => setSoftShadowsBad(true)} onIncline={() => setSoftShadowsBad(false)} />
        <SoftShadows size={35} focus={0.5} samples={softShadowsBad ? 6 : 16} />

        <color attach="background" args={['#d0d0d0']} />
        <fog attach="fog" args={['#d0d0d0', 8, 28]} />

        {/* Iluminación más cinematográfica: key/fill/rim */}
        <ambientLight intensity={0.25} />
        <directionalLight
          castShadow
          position={[6, 10, 6]}
          intensity={2.4}
          shadow-mapSize={2048}
          shadow-bias={-0.00035}
        />
        <directionalLight position={[-6, 4, -2]} intensity={0.7} color={'#d7e8ff'} />
        <directionalLight position={[0, 3, -7]} intensity={0.9} color={'#fff1d6'} />

        {/* Spotlight dinámico siguiendo selección */}
        <DynamicSpot selected={selected} />

        {/* Postprocesado */}
        <PostFX enabled={postFXOn && postprocessingConfig.enabled} dofOn={dofOn} selected={selected} />

        <Floor kind={floor} />

        {/* Preview de cobertura (instanced, liviano) */}
        <CoverPreview
          enabled={coverEnabled && coverage?.computed}
          piece={pieces[0]}
          pieceSizeCm={coverageData?.computed ? { x: coverageData.pieceCmX, z: coverageData.pieceCmZ } : null}
        />

        {/* Piezas dinámicas (inicia vacío) */}
        {pieces.map((p) => (
          <mesh
            key={p.id}
            position={p.position}
            rotation={p.rotation}
            scale={[p.scaleXZ?.x ?? 1, 1, p.scaleXZ?.z ?? 1]}
            castShadow
            receiveShadow
            onPointerDown={handleSelect}
            name={p.name}
          >
            <boxGeometry args={p.size} />
            <meshStandardMaterial
              color={p.color}
              emissive="#000000"
              roughness={p.roughness}
              metalness={p.metalness}
            />
          </mesh>
        ))}
        {/* Paredes (10x10 piso, centrado). Altura 3. Grosor 0.2 */}
        {/* Pared norte */}
        <mesh position={[0,1.5,-5]} castShadow onPointerDown={handleSelect} name="Pared-Norte">
          <boxGeometry args={[10,3,0.2]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared sur */}
        <mesh position={[0,1.5,5]} castShadow onPointerDown={handleSelect} name="Pared-Sur">
          <boxGeometry args={[10,3,0.2]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared oeste */}
        <mesh position={[-5,1.5,0]} castShadow onPointerDown={handleSelect} name="Pared-Oeste">
          <boxGeometry args={[0.2,3,10]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared este (con ventana simple hueco central) */}
        {/* Simplificación: dos columnas y travesaño arriba dejando hueco */}
        <group name="Pared-Este">
          <mesh position={[5,1.5,0]} castShadow onPointerDown={handleSelect} name="Marco-Este-Arriba">
            <boxGeometry args={[0.2,0.5,10]} />
            <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
          </mesh>
          <mesh position={[5,1.0,-4]} castShadow onPointerDown={handleSelect} name="Columna-Este-1">
            <boxGeometry args={[0.2,2,2]} />
            <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
          </mesh>
          <mesh position={[5,1.0,4]} castShadow onPointerDown={handleSelect} name="Columna-Este-2">
            <boxGeometry args={[0.2,2,2]} />
            <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
          </mesh>
        </group>
  <OrbitControls ref={controlsRef} makeDefault
          enableDamping={controlsConfig.enableDamping}
          dampingFactor={controlsConfig.dampingFactor}
          rotateSpeed={controlsConfig.rotateSpeed}
          zoomSpeed={controlsConfig.zoomSpeed}
          panSpeed={controlsConfig.panSpeed}
          minDistance={controlsConfig.minDistance}
          maxDistance={controlsConfig.maxDistance}
          minPolarAngle={controlsConfig.minPolarAngle}
          maxPolarAngle={controlsConfig.maxPolarAngle}
        />
        <Environment preset="city" />
      </Canvas>
    </>
  )
}

function PostFX({ enabled, dofOn, selected }) {
  if (!enabled) return null
  const pp = postprocessingConfig
  return (
    <EffectComposer multisampling={0}>
      {pp.ssao.enabled && (
        <SSAO radius={pp.ssao.radius} intensity={pp.ssao.intensity} />
      )}
      {pp.bloom.enabled && (
        <Bloom
          intensity={pp.bloom.intensity}
          luminanceThreshold={pp.bloom.luminanceThreshold}
          luminanceSmoothing={pp.bloom.luminanceSmoothing}
        />
      )}

      {/* Color grading sutil para look "roomScene" */}
      <BrightnessContrast brightness={0.02} contrast={0.08} />
      <HueSaturation hue={0.0} saturation={0.06} />

      {/* DOF: leve desenfoque en fondo para profundidad */}
      {dofOn && (
        <DepthOfField
          focusDistance={0.02}
          focalLength={0.05}
          bokehScale={2.0}
        />
      )}

      {pp.toneMapping.enabled && (
        <ToneMapping
          mode={pp.toneMapping.mode === 'ACES_FILMIC' ? ACESFilmicToneMapping : ACESFilmicToneMapping}
          exposure={pp.toneMapping.exposure}
        />
      )}
      {pp.vignette.enabled && (
        <Vignette offset={pp.vignette.offset} darkness={pp.vignette.darkness} />
      )}
    </EffectComposer>
  )
}

function CoverPreview({ enabled, piece, pieceSizeCm }) {
  const MAX_PREVIEW = 800
  const instancesRef = useRef()

  const preview = useMemo(() => {
    if (!enabled || !piece || !pieceSizeCm) return null
    const pieceW = pieceSizeCm.x / 100
    const pieceD = pieceSizeCm.z / 100
    if (pieceW <= 0 || pieceD <= 0) return null

    // Piso 10x10 centrado en 0,0 -> rango [-5,5] en X/Z
    const cols = Math.max(1, Math.floor(10 / pieceW))
    const rows = Math.max(1, Math.floor(10 / pieceD))
    const total = Math.min(cols * rows, MAX_PREVIEW)
    return { pieceW, pieceD, cols, rows, total }
  }, [enabled, piece, pieceSizeCm])

  useEffect(() => {
    if (!instancesRef.current || !preview) return
    const inst = instancesRef.current
    const dummy = new Object3D()
    let i = 0
    for (let r = 0; r < preview.rows; r++) {
      for (let c = 0; c < preview.cols; c++) {
        if (i >= preview.total) break
        const x = -5 + preview.pieceW / 2 + c * preview.pieceW
        const z = -5 + preview.pieceD / 2 + r * preview.pieceD
        dummy.position.set(x, 0.5, z)
        dummy.rotation.set(0, 0, 0)
        dummy.scale.set(piece.scaleXZ?.x ?? 1, 1, piece.scaleXZ?.z ?? 1)
        dummy.updateMatrix()
        inst.setMatrixAt(i, dummy.matrix)
        i++
      }
      if (i >= preview.total) break
    }
    inst.instanceMatrix.needsUpdate = true
  }, [preview, piece])

  if (!preview) return null
  return (
    <instancedMesh ref={instancesRef} args={[null, null, preview.total]} receiveShadow castShadow>
      <boxGeometry args={piece.size} />
      <meshStandardMaterial
        color={piece.color}
        roughness={piece.roughness}
        metalness={piece.metalness}
        opacity={0.55}
        transparent
      />
    </instancedMesh>
  )
}

// Componente spotlight dinámico
function DynamicSpot({ selected }) {
  const ref = useRef()
  const targetRef = useRef()
  useFrame((_, dt) => {
    if (!ref.current || !targetRef.current) return
    const spot = ref.current
    const tgt = targetRef.current
    const basePos = selected ? selected.position : { x:0, y:0, z:0 }
    // Interpolar posición de la luz encima del objeto seleccionado
    const desired = { x: basePos.x + 2.5, y: basePos.y + 4, z: basePos.z + 2.5 }
    spot.position.lerp(desired, Math.min(dt*2, 1))
    tgt.position.lerp(basePos, Math.min(dt*2, 1))
    spot.target.updateMatrixWorld()
  })
  return (
    <>
      <spotLight
        ref={ref}
        angle={0.45}
        penumbra={0.4}
        intensity={selected ? 1.8 : 1.2}
        distance={25}
        castShadow
        color={selected ? '#ffffff' : '#f0f0f0'}
        position={[2.5,4,2.5]}
        target={targetRef.current}
      />
      <object3D ref={targetRef} position={[0,0,0]} />
    </>
  )
}

createRoot(document.getElementById('r3f-root')).render(<Demo />)

// Where do I change things?
// - Camera/Lights/Controls: resources/js/three/config.js (cameraConfig, lightsConfig, controlsConfig)
// - Loading 3D models: we’ll add a SceneLoader component (resources/js/three/loaders/SceneLoader.jsx)
// - Materials library & textures: resources/js/three/materials/library.js
// - Global state (current scene/material selections): resources/js/three/store.js
