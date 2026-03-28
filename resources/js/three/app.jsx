import React, { useEffect, useMemo, useState, useCallback, useRef } from 'react'
import { createRoot } from 'react-dom/client'
import { Canvas, useFrame, useThree } from '@react-three/fiber'
import { OrbitControls, Environment, SoftShadows, PerformanceMonitor, useTexture } from '@react-three/drei'
import { EffectComposer, Bloom, SSAO, ToneMapping, Vignette } from '@react-three/postprocessing'
import { DepthOfField, BrightnessContrast, HueSaturation } from '@react-three/postprocessing'
import {
  ACESFilmicToneMapping,
  SRGBColorSpace,
  Vector3,
  Object3D,
  PerspectiveCamera,
  WebGLRenderTarget,
  GridHelper,
} from 'three'
import {
  cameraConfig,
  controlsConfig,
  lightsConfig,
  postprocessingConfig,
  rendererConfig,
} from './config'

const WHITE_TEX_DATA_URL =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"><rect width="4" height="4" fill="#ffffff"/></svg>'
  )

function TexturedPieceMesh({ piece, onPointerDown }) {
  const { gl } = useThree()
  const map = useTexture(piece?.textureUrl || WHITE_TEX_DATA_URL)

  useEffect(() => {
    if (!map) return
    map.colorSpace = SRGBColorSpace
    map.wrapS = map.wrapT = 1000 // RepeatWrapping (evita importar constante extra)
    map.repeat.set(1, 1)
    map.anisotropy = Math.min(8, gl?.capabilities?.getMaxAnisotropy?.() ?? 8)
    map.needsUpdate = true
  }, [map, gl])

  return (
    <mesh
      position={piece.position}
      rotation={piece.rotation}
      scale={[piece.scaleXZ?.x ?? 1, 1, piece.scaleXZ?.z ?? 1]}
      castShadow
      receiveShadow
      onPointerDown={onPointerDown}
      name={piece.name}
    >
      <boxGeometry args={piece.size} />
      <meshStandardMaterial
        map={map}
        color={'#ffffff'}
        emissive="#000000"
        roughness={piece.roughness ?? 0.85}
        metalness={piece.metalness ?? 0.0}
      />
    </mesh>
  )
}

function Floor({ kind, width, depth }) {
  // Two simple materials: wood vs ceramic using basic colors for MVP
  const color = kind === 'wood' ? '#8b5a2b' : '#cfd8dc'
  return (
    <mesh rotation={[-Math.PI/2,0,0]} receiveShadow>
      <planeGeometry args={[width,depth,1,1]} />
      <meshStandardMaterial color={color} roughness={0.9} metalness={0.0} />
    </mesh>
  )
}

function Controls({
  floor,
  setFloor,
  materials,
  materialsLoading,
  materialsError,
  selectedMaterialId,
  onSelectMaterial,
  onAdd,
  canAdd,
  addLabel,
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
  roomSizeCm,
  onRoomSizeChange,
  quoteSummary,
  onGenerateQuote,
  quoteLoading,
  inventoryStatus,
}) {
  const selectedMaterial = useMemo(
    () => materials?.find((m) => String(m.id) === String(selectedMaterialId)) ?? null,
    [materials, selectedMaterialId]
  )

  return (
    <div style={{position:'fixed', top:12, left:12, zIndex:10, width:260, background:'rgba(0,0,0,.65)', color:'#fff', padding:'12px', borderRadius:10, fontFamily:'system-ui,Arial,sans-serif'}}>
      <div style={{fontWeight:700, marginBottom:10}}>Catálogo</div>

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

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Material (desde Productos)</div>
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
              value={selectedMaterialId ?? ''}
              onChange={(e) => onSelectMaterial(e.target.value ? Number(e.target.value) : null)}
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
                {selectedMaterial.piece_dimensions_cm?.locked && (
                  <>
                    <br />
                    Formato: {Number(selectedMaterial.piece_dimensions_cm.width).toFixed(0)}×{Number(selectedMaterial.piece_dimensions_cm.depth).toFixed(0)} cm
                  </>
                )}
              </div>
            )}
          </>
        )}
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
            Preview usa hasta 800 instancias (muestreo automático en cuartos grandes).
          </div>
        </div>
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

      <div style={{fontSize:12, opacity:0.85, marginBottom:6}}>Piso</div>
      <div style={{display:'flex', gap:8}}>
        <button onClick={() => setFloor('wood')} style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid #333', background: floor === 'wood' ? '#444' : '#222', color:'#fff', cursor:'pointer'}}>Madera</button>
        <button onClick={() => setFloor('ceramic')} style={{flex:1, padding:'8px 10px', borderRadius:8, border:'1px solid #333', background: floor === 'ceramic' ? '#444' : '#222', color:'#fff', cursor:'pointer'}}>Cerámica</button>
      </div>

      <div style={{height:10}} />

      <button
        onClick={onGenerateQuote}
        disabled={!quoteSummary?.ready || quoteLoading || inventoryStatus?.canFulfill === false}
        style={{width:'100%', padding:'10px 10px', borderRadius:8, border:'none', background: quoteSummary?.ready && !quoteLoading && inventoryStatus?.canFulfill !== false ? '#2563eb' : '#374151', color:'#fff', cursor: quoteSummary?.ready && !quoteLoading && inventoryStatus?.canFulfill !== false ? 'pointer' : 'not-allowed'}}
        title={
          !quoteSummary?.ready
            ? 'Agrega una pieza para cotizar el escenario'
            : (inventoryStatus?.canFulfill === false ? 'Stock insuficiente según inventario' : 'Genera un PDF con la cotización estimada')
        }
      >
        {quoteLoading ? 'Generando PDF...' : 'Generar cotización PDF'}
      </button>

      {quoteSummary?.ready && (
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
  const [materials, setMaterials] = useState([])
  const [materialsLoading, setMaterialsLoading] = useState(true)
  const [materialsError, setMaterialsError] = useState(null)
  const [selectedMaterialId, setSelectedMaterialId] = useState(null)
  const [pieces, setPieces] = useState([])
  const [coverage, setCoverage] = useState({ canCompute: false, computed: false })
  const [coverEnabled, setCoverEnabled] = useState(false)
  const [roomSizeCm, setRoomSizeCm] = useState({ width: 1000, depth: 1000, height: 300 })
  const [quoteLoading, setQuoteLoading] = useState(false)
  const [scenes, setScenes] = useState([])
  const [scenesLoading, setScenesLoading] = useState(true)
  const [scenesError, setScenesError] = useState(null)
  const [selectedSceneId, setSelectedSceneId] = useState(null)
  const [sceneName, setSceneName] = useState('')
  const pendingSceneDataRef = useRef(null)
  const autoLoadedSceneRef = useRef(false)
  const nextId = useRef(1)
  const lastEmissive = useRef({ obj: null, color: null })
  const controlsRef = useRef(null)
  const r3fRef = useRef({ gl: null, scene: null })

  const currencyFormatter = useMemo(
    () => new Intl.NumberFormat('es-BO', { style: 'currency', currency: 'BOB', maximumFractionDigits: 0 }),
    []
  )

  const roomSize = useMemo(
    () => ({
      width: roomSizeCm.width / 100,
      depth: roomSizeCm.depth / 100,
      height: roomSizeCm.height / 100,
    }),
    [roomSizeCm]
  )

  const wallThickness = 0.2

  const roomLimits = useMemo(
    () => ({
      minX: -roomSize.width / 2,
      maxX: roomSize.width / 2,
      minZ: -roomSize.depth / 2,
      maxZ: roomSize.depth / 2,
      y: 0.5,
    }),
    [roomSize]
  )

  useEffect(() => {
    let alive = true
    async function loadMaterials() {
      setMaterialsLoading(true)
      setMaterialsError(null)
      try {
        const resp = await fetch('/3d/materials', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
        })
        if (!resp.ok) throw new Error(`HTTP ${resp.status}`)
        const data = await resp.json()
        const items = Array.isArray(data?.items) ? data.items : []
        if (!alive) return
        setMaterials(items)
        setSelectedMaterialId((prev) => {
          if (prev && items.some((m) => Number(m.id) === Number(prev))) return prev
          return items[0]?.id ?? null
        })
      } catch (err) {
        if (!alive) return
        console.error(err)
        setMaterials([])
        setSelectedMaterialId(null)
        setMaterialsError('No se pudo cargar el catálogo desde Productos.')
      } finally {
        if (alive) setMaterialsLoading(false)
      }
    }
    loadMaterials()
    return () => {
      alive = false
    }
  }, [])

  useEffect(() => {
    let alive = true
    async function loadScenes() {
      setScenesLoading(true)
      setScenesError(null)
      try {
        const resp = await fetch('/3d/scenes', {
          method: 'GET',
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
        })

        if (resp.status === 401 || resp.status === 419) {
          if (!alive) return
          setScenes([])
          setScenesError('Inicia sesión para guardar y cargar escenarios.')
          return
        }

        if (!resp.ok) throw new Error(`HTTP ${resp.status}`)
        const data = await resp.json()
        const items = Array.isArray(data?.items) ? data.items : []
        if (!alive) return
        setScenes(items)
      } catch (err) {
        if (!alive) return
        console.error(err)
        setScenes([])
        setScenesError('No se pudieron cargar los escenarios guardados.')
      } finally {
        if (alive) setScenesLoading(false)
      }
    }

    loadScenes()
    return () => {
      alive = false
    }
  }, [])

  const selectedMaterial = useMemo(
    () => materials.find((m) => Number(m.id) === Number(selectedMaterialId)) ?? null,
    [materials, selectedMaterialId]
  )

  const buildScenePayload = useCallback(() => {
    return {
      version: 1,
      floor,
      roomSizeCm,
      selectedMaterialId,
      coverEnabled,
      pieces: pieces.map((p) => ({
        kind: p.kind,
        material_id: p?.material?.id ?? null,
        position: p.position,
        rotation: p.rotation,
        size: p.size,
        scaleXZ: p.scaleXZ,
        lockSizeXZ: !!p.lockSizeXZ,
      })),
    }
  }, [floor, roomSizeCm, selectedMaterialId, coverEnabled, pieces])

  const resetSceneState = useCallback(() => {
    setSelectedSceneId(null)
    setSceneName('')
    setPieces([])
    setSelected(null)
    setCoverage({ canCompute: false, computed: false })
    setCoverEnabled(false)
    window.localStorage.removeItem('three.lastSceneId')
  }, [])

  const hydrateScene = useCallback((sceneData) => {
    if (!sceneData || typeof sceneData !== 'object') return
    setFloor(sceneData.floor === 'ceramic' ? 'ceramic' : 'wood')

    if (sceneData.roomSizeCm && typeof sceneData.roomSizeCm === 'object') {
      setRoomSizeCm((prev) => ({
        ...prev,
        width: Number(sceneData.roomSizeCm.width) || prev.width,
        depth: Number(sceneData.roomSizeCm.depth) || prev.depth,
        height: Number(sceneData.roomSizeCm.height) || prev.height,
      }))
    }

    if (sceneData.selectedMaterialId != null) {
      setSelectedMaterialId(Number(sceneData.selectedMaterialId))
    }

    setCoverEnabled(!!sceneData.coverEnabled)

    const nextPieces = Array.isArray(sceneData.pieces) ? sceneData.pieces : []
    if (!nextPieces.length) {
      setPieces([])
      setSelected(null)
      setCoverage({ canCompute: false, computed: false })
      return
    }

    // (MVP) el editor actualmente maneja 1 pieza: cargamos la primera.
    const raw = nextPieces[0]
    const material = materials.find((m) => Number(m.id) === Number(raw?.material_id)) ?? selectedMaterial
    const id = nextId.current++
    const piece = {
      id,
      kind: raw?.kind === 'plank' ? 'plank' : 'tile',
      material: material ?? null,
      name: `Pieza-${id}`,
      position: Array.isArray(raw?.position) ? raw.position : [0, roomLimits.y, 0],
      rotation: Array.isArray(raw?.rotation) ? raw.rotation : [0, 0, 0],
      size: Array.isArray(raw?.size) ? raw.size : (material?.kind === 'plank' ? [1.8, 0.12, 0.5] : [1.0, 0.12, 1.0]),
      scaleXZ: raw?.scaleXZ && typeof raw.scaleXZ === 'object' ? raw.scaleXZ : { x: 1, z: 1 },
      lockSizeXZ: !!raw?.lockSizeXZ,
      textureUrl: material?.image_url || WHITE_TEX_DATA_URL,
      roughness: material?.kind === 'plank' ? 0.75 : 0.65,
      metalness: 0.0,
    }
    setPieces([piece])
    setSelected(null)
    setCoverage({ canCompute: false, computed: false })
  }, [materials, selectedMaterial, roomLimits.y])

  // Si se seleccionó un escenario antes de cargar materiales, diferimos la hidratación.
  useEffect(() => {
    const pending = pendingSceneDataRef.current
    if (!pending) return
    if (!materialsLoading) {
      pendingSceneDataRef.current = null
      hydrateScene(pending)
    }
  }, [materialsLoading, hydrateScene])

  const loadSceneById = useCallback(async (sceneId) => {
    if (!sceneId) {
      setSelectedSceneId(null)
      window.localStorage.removeItem('three.lastSceneId')
      return
    }

    try {
      const resp = await fetch(`/3d/scenes/${sceneId}`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })

      if (resp.status === 401 || resp.status === 419) {
        setScenesError('Inicia sesión para cargar escenarios.')
        return
      }

      if (!resp.ok) throw new Error(`HTTP ${resp.status}`)
      const data = await resp.json()
      const item = data?.item
      setSelectedSceneId(Number(item?.id) || null)
      setSceneName(String(item?.name || ''))
      window.localStorage.setItem('three.lastSceneId', String(item?.id || ''))

      if (materialsLoading) {
        pendingSceneDataRef.current = item?.data ?? null
      } else {
        hydrateScene(item?.data ?? null)
      }
    } catch (err) {
      console.error(err)
      window.alert('No se pudo cargar el escenario.')
    }
  }, [materialsLoading, hydrateScene])

  useEffect(() => {
    const params = new URLSearchParams(window.location.search)
    const isNew = params.get('new') === '1'
    const sceneIdParam = params.get('sceneId')
    const requestedSceneId = sceneIdParam ? Number(sceneIdParam) : null

    if (isNew) {
      autoLoadedSceneRef.current = true
      resetSceneState()
      return
    }

    if (requestedSceneId && Number.isFinite(requestedSceneId) && requestedSceneId > 0) {
      autoLoadedSceneRef.current = true
      loadSceneById(requestedSceneId)
    }
  }, [loadSceneById, resetSceneState])

  useEffect(() => {
    if (autoLoadedSceneRef.current) return
    if (scenesLoading) return
    if (scenesError) return
    if (!scenes?.length) return

    const lastId = Number(window.localStorage.getItem('three.lastSceneId') || '')
    if (!Number.isFinite(lastId) || lastId <= 0) return
    if (!scenes.some((s) => Number(s.id) === lastId)) return

    autoLoadedSceneRef.current = true
    loadSceneById(lastId)
  }, [scenesLoading, scenesError, scenes, loadSceneById])

  const createNewScene = useCallback(() => {
    const ok = window.confirm('Esto creará un escenario nuevo y limpiará la escena actual. ¿Continuar?')
    if (!ok) return
    resetSceneState()
  }, [resetSceneState])

  const saveScene = useCallback(async () => {
    if (scenesError) {
      window.alert('Inicia sesión para guardar escenarios.')
      return
    }

    const name = String(sceneName || '').trim()
    if (!name) {
      window.alert('Escribe un nombre para el escenario antes de guardar.')
      return
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
    const payload = { name, data: buildScenePayload() }

    try {
      const isUpdate = !!selectedSceneId
      const url = isUpdate ? `/3d/scenes/${selectedSceneId}` : '/3d/scenes'
      const method = isUpdate ? 'PUT' : 'POST'
      const resp = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken ?? '',
        },
        body: JSON.stringify(payload),
      })

      if (resp.status === 401 || resp.status === 419) {
        window.alert('Inicia sesión para guardar escenarios.')
        return
      }

      if (!resp.ok) {
        const errBody = await resp.json().catch(() => null)
        const msg = errBody?.message || 'No se pudo guardar el escenario.'
        throw new Error(msg)
      }

      const data = await resp.json()
      const item = data?.item
      const savedId = Number(item?.id) || null
      if (savedId) {
        setSelectedSceneId(savedId)
        window.localStorage.setItem('three.lastSceneId', String(savedId))
      }

      // refrescar lista
      const listResp = await fetch('/3d/scenes', {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })
      if (listResp.ok) {
        const listData = await listResp.json()
        setScenes(Array.isArray(listData?.items) ? listData.items : [])
      }

      window.alert('Escenario guardado.')
    } catch (err) {
      console.error(err)
      window.alert(String(err?.message || 'No se pudo guardar el escenario.'))
    }
  }, [sceneName, selectedSceneId, buildScenePayload, scenesError])

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

  const quotePiece = useMemo(() => selectedPiece ?? pieces[0] ?? null, [selectedPiece, pieces])

  const inventoryStatus = useMemo(() => {
    const material = quotePiece?.material ?? selectedMaterial
    if (!material) return { canCompute: false }

    const floorAreaM2 = (roomSizeCm.width / 100) * (roomSizeCm.depth / 100)
    const m2PerBox = Number(material?.packaging?.m2_per_box || 0)
    const boxesAvailableTotal = Number(material?.inventory?.boxes_available_total ?? NaN)

    if (!Number.isFinite(floorAreaM2) || floorAreaM2 <= 0) return { canCompute: false }
    if (!Number.isFinite(m2PerBox) || m2PerBox <= 0) {
      return {
        canCompute: false,
        reason: 'Falta m² por caja en el producto para calcular cajas requeridas.',
      }
    }

    const boxesRequired = Math.max(1, Math.ceil(floorAreaM2 / m2PerBox))

    if (!Number.isFinite(boxesAvailableTotal)) {
      return {
        canCompute: true,
        boxesRequired,
        boxesAvailableTotal: null,
        missingBoxes: null,
        canFulfill: null,
        canSingleLot: null,
      }
    }

    const lots = Array.isArray(material?.inventory?.lots) ? material.inventory.lots : []
    const bestLot = lots
      .map((l) => ({ lot_code: l?.lot_code ?? null, boxes: Number(l?.boxes_available || 0) }))
      .sort((a, b) => b.boxes - a.boxes)[0]
    const bestLotBoxes = bestLot ? Number(bestLot.boxes || 0) : 0

    const missingBoxes = Math.max(0, boxesRequired - boxesAvailableTotal)
    const canFulfill = boxesAvailableTotal >= boxesRequired
    const canSingleLot = bestLotBoxes >= boxesRequired

    return {
      canCompute: true,
      boxesRequired,
      boxesAvailableTotal,
      missingBoxes,
      canFulfill,
      canSingleLot,
      bestLotCode: bestLot?.lot_code ?? null,
    }
  }, [quotePiece, selectedMaterial, roomSizeCm])

  const captureTopDownSnapshot = useCallback(() => {
    const gl = r3fRef.current?.gl
    const scene = r3fRef.current?.scene
    if (!gl || !scene) return null

    const w = 1024
    const h = 1024

    const far = Math.max(roomSize.width, roomSize.depth, roomSize.height) * 10 + 50
    const fov = 45
    const cam = new PerspectiveCamera(fov, 1, 0.1, far)

    const halfW = roomSize.width / 2
    const halfD = roomSize.depth / 2
    const radius = Math.sqrt(halfW * halfW + halfD * halfD)
    const dist = radius / Math.tan((fov * Math.PI) / 360)

    // Vista superior inclinada (muestra piso + paredes). El offset en Z evita colinealidad perfecta.
    cam.position.set(0, dist * 1.15, dist * 0.95)
    cam.up.set(0, 1, 0)
    cam.lookAt(0, 0, 0)
    cam.updateProjectionMatrix()
    cam.updateMatrixWorld()

    // Grid temporal para el snapshot (no afecta UI).
    const gridSize = Math.max(roomSize.width, roomSize.depth)
    const divisions = Math.max(10, Math.min(80, Math.round(gridSize / 0.25)))
    const grid = new GridHelper(gridSize, divisions, 0x334155, 0x94a3b8)
    grid.position.set(0, 0.01, 0)
    scene.add(grid)

    const target = new WebGLRenderTarget(w, h)
    const prevTarget = gl.getRenderTarget()
    const prevXrEnabled = gl.xr.enabled
    gl.xr.enabled = false

    gl.setRenderTarget(target)
    gl.clear()
    gl.render(scene, cam)

    const pixels = new Uint8Array(w * h * 4)
    gl.readRenderTargetPixels(target, 0, 0, w, h, pixels)

    gl.setRenderTarget(prevTarget)
    gl.xr.enabled = prevXrEnabled
    target.dispose()

    scene.remove(grid)

    const canvas = document.createElement('canvas')
    canvas.width = w
    canvas.height = h
    const ctx = canvas.getContext('2d')
    if (!ctx) return null

    const imageData = ctx.createImageData(w, h)
    // WebGL entrega los píxeles con origen abajo-izquierda; invertimos Y para PNG.
    for (let row = 0; row < h; row++) {
      const srcStart = (h - row - 1) * w * 4
      const destStart = row * w * 4
      imageData.data.set(pixels.subarray(srcStart, srcStart + w * 4), destStart)
    }
    ctx.putImageData(imageData, 0, 0)
    return canvas.toDataURL('image/png')
  }, [roomSize])

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
      const minX = roomLimits.minX + halfX
      const maxX = roomLimits.maxX - halfX
      const minZ = roomLimits.minZ + halfZ
      const maxZ = roomLimits.maxZ - halfZ
      return {
        x: minX > maxX ? 0 : Math.min(maxX, Math.max(minX, pos.x)),
        z: minZ > maxZ ? 0 : Math.min(maxZ, Math.max(minZ, pos.z)),
      }
    },
    [roomLimits]
  )

  const updateRoomSize = useCallback((axis, value) => {
    const limits = axis === 'height'
      ? { min: 200, max: 800 }
      : { min: 200, max: 3000 }

    setRoomSizeCm((prev) => {
      const nextValue = Math.round(Math.min(limits.max, Math.max(limits.min, Number(value) || prev[axis])))
      return { ...prev, [axis]: nextValue }
    })
  }, [])

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

  const quoteSizeCm = useMemo(() => {
    if (!quotePiece) return null
    const base = quotePiece.size
    const sx = quotePiece.scaleXZ?.x ?? 1
    const sz = quotePiece.scaleXZ?.z ?? 1
    return {
      x: metersToCm(base[0] * sx),
      z: metersToCm(base[2] * sz),
      y: metersToCm(base[1] ?? 0.12),
    }
  }, [quotePiece, metersToCm])

  const coverageData = useMemo(() => {
    if (!selectedPiece || !selectedSizeCm) return { canCompute: false, computed: false }
    const pieceM = {
      x: selectedSizeCm.x / 100,
      z: selectedSizeCm.z / 100,
    }
    const pieceAreaM2 = pieceM.x * pieceM.z
    const floorAreaM2 = roomSize.width * roomSize.depth
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
  }, [selectedPiece, selectedSizeCm, roomSize])

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
      if (selectedPiece.lockSizeXZ) return
      const cmClamped = Math.max(10, Math.min(400, cmValue))
      const meters = cmToMeters(cmClamped)
      const base = selectedPiece.size

      // Convertir centímetros absolutos a escala relativa sobre la geometría base
      const nextScale = axis === 'x'
        ? { x: meters / base[0], z: selectedPiece.scaleXZ?.z ?? 1 }
        : { x: selectedPiece.scaleXZ?.x ?? 1, z: meters / base[2] }

      setPieces((prev) => {
        const next = prev.map((p) => {
          if (p.id !== selectedPiece.id) return p
          const currentPos = selected && selected.name === p.name
            ? { x: selected.position.x, z: selected.position.z }
            : { x: p.position[0], z: p.position[2] }
          const updatedPiece = { ...p, scaleXZ: nextScale }
          const clamped = clampPosForPiece(currentPos, updatedPiece)
          return { ...updatedPiece, position: [clamped.x, roomLimits.y, clamped.z] }
        })
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
      const minX = roomLimits.minX + halfX
      const maxX = roomLimits.maxX - halfX
      const minZ = roomLimits.minZ + halfZ
      const maxZ = roomLimits.maxZ - halfZ
      const nextX = minX > maxX ? 0 : Math.min(maxX, Math.max(minX, selected.position.x + dx * step))
      const nextZ = minZ > maxZ ? 0 : Math.min(maxZ, Math.max(minZ, selected.position.z + dz * step))
      selected.position.set(nextX, roomLimits.y, nextZ)
      if (selectedPiece?.id != null) {
        setPieces((prev) => prev.map((p) => (
          p.id === selectedPiece.id ? { ...p, position: [nextX, roomLimits.y, nextZ] } : p
        )))
      }
    },
    [selected, isMovable, roomLimits, selectedPiece]
  )

  const quoteSummary = useMemo(() => {
    if (!quotePiece || !quoteSizeCm || !quotePiece?.material) {
      return { ready: false }
    }

    const material = quotePiece.material

    const floorAreaM2 = roomSize.width * roomSize.depth
    const pieceAreaM2 = (quoteSizeCm.x / 100) * (quoteSizeCm.z / 100)
    const estimatedUnits = Math.max(1, Math.ceil(floorAreaM2 / Math.max(pieceAreaM2, 0.0001)))
    const unitPriceM2 = Number(material.price_per_m2 || 0)
    const total = floorAreaM2 * unitPriceM2

    return {
      ready: true,
      itemLabel: material.name,
      floorAreaM2,
      pieceAreaM2,
      estimatedUnits,
      unitPriceM2,
      total,
      floorAreaLabel: `${floorAreaM2.toFixed(2)} m²`,
      unitPriceLabel: currencyFormatter.format(unitPriceM2),
      totalLabel: currencyFormatter.format(total),
    }
  }, [quotePiece, quoteSizeCm, roomSize, currencyFormatter])

  const hasPiece = pieces.length > 0
  const canAdd = !materialsLoading && !materialsError && !!selectedMaterial
  const addLabel = hasPiece ? 'Reemplazar pieza' : 'Agregar pieza'

  const addOnePiece = useCallback(() => {
    if (!selectedMaterial) return
    if (hasPiece) {
      const current = pieces[0]
      const currentLabel = current?.material?.name ? current.material.name : 'actual'
      const nextLabel = selectedMaterial.name
      const ok = window.confirm(
        `Ya existe una pieza (${currentLabel}).\n\nSi continúas, se borrará y se agregará: ${nextLabel}.\n\n¿Deseas reemplazarla?`
      )
      if (!ok) return
    }
    const id = nextId.current++

    const dims = selectedMaterial?.piece_dimensions_cm
    const hasFixedDims = !!(dims && dims.locked && Number(dims.width) > 0 && Number(dims.depth) > 0)
    const fixedSize = hasFixedDims
      ? [Number(dims.width) / 100, 0.12, Number(dims.depth) / 100]
      : null

    const piece = {
      id,
      kind: selectedMaterial.kind,
      material: selectedMaterial,
      name: `Pieza-${id}`,
      position: [0, roomLimits.y, 0],
      rotation: [0, 0, 0],
      size: fixedSize ?? (selectedMaterial.kind === 'plank' ? [1.8, 0.12, 0.5] : [1.0, 0.12, 1.0]),
      scaleXZ: { x: 1, z: 1 },
      lockSizeXZ: !!fixedSize,
      textureUrl: selectedMaterial.image_url,
      roughness: selectedMaterial.kind === 'plank' ? 0.75 : 0.65,
      metalness: 0.0,
    }
    setPieces([piece])
    // Selección y enfoque: se asigna tras mount por referencia del evento de click.
    setSelected(null)
  }, [selectedMaterial, hasPiece, pieces, roomLimits.y])

  const handleGenerateQuote = useCallback(async () => {
    if (!quoteSummary?.ready || !quotePiece || !quoteSizeCm || quoteLoading) return

    if (inventoryStatus?.canCompute && inventoryStatus?.canFulfill === false) {
      window.alert(
        `Stock insuficiente para cotizar en base al inventario.\n\n` +
          `Requiere: ${inventoryStatus.boxesRequired} cajas\n` +
          `Disponibles: ${inventoryStatus.boxesAvailableTotal} cajas\n` +
          `Faltan: ${inventoryStatus.missingBoxes} cajas\n\n` +
          `Registra un ingreso de inventario (nuevo lote) o ajusta el tamaño del cuarto.`
      )
      return
    }

    setQuoteLoading(true)

    try {
      let snapshotTop = null
      try {
        snapshotTop = captureTopDownSnapshot()
      } catch (err) {
        console.warn('No se pudo capturar la vista superior para el PDF.', err)
        snapshotTop = null
      }

      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
      const response = await fetch('/3d/quotation', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/pdf',
          'X-CSRF-TOKEN': csrfToken ?? '',
        },
        body: JSON.stringify({
          material_id: quotePiece?.material?.id ?? null,
          scene_name: 'Cotización desde escena 3D',
          floor_kind: floor,
          snapshot_top_png_data_url: snapshotTop,
          room: {
            width_cm: roomSizeCm.width,
            depth_cm: roomSizeCm.depth,
            height_cm: roomSizeCm.height,
          },
          piece: {
            kind: quotePiece.kind,
            width_cm: quoteSizeCm.x,
            depth_cm: quoteSizeCm.z,
            height_cm: quoteSizeCm.y,
          },
        }),
      })

      if (!response.ok) {
        throw new Error(`No se pudo generar la cotización (${response.status})`)
      }

      const blob = await response.blob()
      const url = URL.createObjectURL(blob)
      const link = document.createElement('a')
      const disposition = response.headers.get('Content-Disposition') || ''
      const filenameMatch = disposition.match(/filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/i)
      const filename = filenameMatch ? filenameMatch[1].replace(/['"]/g, '').trim() : 'cotizacion-escena-3d.pdf'

      link.href = url
      link.download = filename
      document.body.appendChild(link)
      link.click()
      link.remove()

      window.setTimeout(() => URL.revokeObjectURL(url), 1500)
    } catch (error) {
      console.error(error)
      window.alert('No se pudo generar la cotización PDF. Revisa la configuración actual e inténtalo de nuevo.')
    } finally {
      setQuoteLoading(false)
    }
  }, [quoteSummary, quotePiece, quoteSizeCm, quoteLoading, floor, roomSizeCm, inventoryStatus, captureTopDownSnapshot])

  useEffect(() => {
    if (selected && isMovable(selected) && selectedPiece) {
      const clamped = clampPosForPiece({ x: selected.position.x, z: selected.position.z }, selectedPiece)
      selected.position.set(clamped.x, roomLimits.y, clamped.z)
    }

    setPieces((prev) => {
      let changed = false
      const next = prev.map((piece) => {
        const currentPos = selected && selected.name === piece.name
          ? { x: selected.position.x, z: selected.position.z }
          : { x: piece.position[0], z: piece.position[2] }
        const clamped = clampPosForPiece(currentPos, piece)
        const nextPosition = [clamped.x, roomLimits.y, clamped.z]
        if (
          piece.position[0] !== nextPosition[0] ||
          piece.position[1] !== nextPosition[1] ||
          piece.position[2] !== nextPosition[2]
        ) {
          changed = true
          return { ...piece, position: nextPosition }
        }
        return piece
      })

      return changed ? next : prev
    })
  }, [roomLimits, clampPosForPiece, selected, selectedPiece, isMovable])

  const eastOpeningDepth = useMemo(() => {
    const desired = roomSize.depth * 0.45
    return Math.min(Math.max(desired, 1.8), Math.max(roomSize.depth - 1.2, 1.8))
  }, [roomSize.depth])

  const eastSideDepth = useMemo(
    () => Math.max((roomSize.depth - eastOpeningDepth) / 2, 0.5),
    [roomSize.depth, eastOpeningDepth]
  )

  const eastLintelHeight = useMemo(
    () => Math.min(0.55, Math.max(0.4, roomSize.height * 0.18)),
    [roomSize.height]
  )

  const eastColumnHeight = useMemo(
    () => Math.max(roomSize.height - eastLintelHeight, 0.8),
    [roomSize.height, eastLintelHeight]
  )

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
        materials={materials}
        materialsLoading={materialsLoading}
        materialsError={materialsError}
        selectedMaterialId={selectedMaterialId}
        onSelectMaterial={setSelectedMaterialId}
        onAdd={addOnePiece}
        canAdd={canAdd}
        addLabel={addLabel}
        scenes={scenes}
        scenesLoading={scenesLoading}
        scenesError={scenesError}
        selectedSceneId={selectedSceneId}
        sceneName={sceneName}
        onSceneNameChange={setSceneName}
        onCreateNewScene={createNewScene}
        onSaveScene={saveScene}
        onLoadScene={loadSceneById}
        coverage={{ ...coverage, ...coverageData, canCompute: coverageData.canCompute, computed: coverage.computed }}
        onCover={handleCoverCompute}
        onToggleCover={toggleCover}
        coverEnabled={coverEnabled}
        roomSizeCm={roomSizeCm}
        onRoomSizeChange={updateRoomSize}
        quoteSummary={quoteSummary}
        onGenerateQuote={handleGenerateQuote}
        quoteLoading={quoteLoading}
        inventoryStatus={inventoryStatus}
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
              {selectedPiece.lockSizeXZ && (
                <div style={{fontSize:11, opacity:0.75, marginBottom:8, lineHeight:1.25}}>
                  Tamaño fijo según el formato del producto.
                </div>
              )}
              <label style={{display:'block', fontSize:12, opacity:0.9, marginBottom:8}}>
                Largo (X): {selectedSizeCm?.x ?? 0} cm
                <input
                  type="range"
                  min={10}
                  max={400}
                  step={1}
                  value={selectedSizeCm?.x ?? 100}
                  onChange={(e) => updateSelectedSizeCm('x', Number(e.target.value))}
                  disabled={!!selectedPiece.lockSizeXZ}
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
                  disabled={!!selectedPiece.lockSizeXZ}
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
        onCreated={(state) => {
          r3fRef.current = { gl: state.gl, scene: state.scene }
        }}
      >
        <RendererSetup />

        {/* Degrada SoftShadows si el rendimiento cae */}
        <PerformanceMonitor onDecline={() => setSoftShadowsBad(true)} onIncline={() => setSoftShadowsBad(false)} />
        <SoftShadows size={35} focus={0.5} samples={softShadowsBad ? 6 : 16} />

        <color attach="background" args={['#d0d0d0']} />

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

        <Floor kind={floor} width={roomSize.width} depth={roomSize.depth} />

        {/* Preview de cobertura (instanced, liviano) */}
        <CoverPreview
          enabled={coverEnabled && coverage?.computed}
          piece={pieces[0]}
          pieceSizeCm={coverageData?.computed ? { x: coverageData.pieceCmX, z: coverageData.pieceCmZ } : null}
          roomSize={roomSize}
          textureUrl={pieces[0]?.textureUrl}
        />

        {/* Piezas dinámicas (inicia vacío) */}
        {pieces.map((p) => (
          <TexturedPieceMesh key={p.id} piece={p} onPointerDown={handleSelect} />
        ))}
        {/* Paredes adaptables al tamaño configurado del cuarto */}
        {/* Pared norte */}
        <mesh position={[0, roomSize.height / 2, -roomSize.depth / 2]} castShadow onPointerDown={handleSelect} name="Pared-Norte">
          <boxGeometry args={[roomSize.width, roomSize.height, wallThickness]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared sur */}
        <mesh position={[0, roomSize.height / 2, roomSize.depth / 2]} castShadow onPointerDown={handleSelect} name="Pared-Sur">
          <boxGeometry args={[roomSize.width, roomSize.height, wallThickness]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared oeste */}
        <mesh position={[-roomSize.width / 2, roomSize.height / 2, 0]} castShadow onPointerDown={handleSelect} name="Pared-Oeste">
          <boxGeometry args={[wallThickness, roomSize.height, roomSize.depth]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared este (con ventana simple hueco central) */}
        {/* Simplificación: dos columnas y travesaño arriba dejando hueco */}
        <group name="Pared-Este">
          <mesh position={[roomSize.width / 2, roomSize.height - eastLintelHeight / 2, 0]} castShadow onPointerDown={handleSelect} name="Marco-Este-Arriba">
            <boxGeometry args={[wallThickness, eastLintelHeight, roomSize.depth]} />
            <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
          </mesh>
          <mesh position={[roomSize.width / 2, eastColumnHeight / 2, -(eastOpeningDepth / 2 + eastSideDepth / 2)]} castShadow onPointerDown={handleSelect} name="Columna-Este-1">
            <boxGeometry args={[wallThickness, eastColumnHeight, eastSideDepth]} />
            <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
          </mesh>
          <mesh position={[roomSize.width / 2, eastColumnHeight / 2, eastOpeningDepth / 2 + eastSideDepth / 2]} castShadow onPointerDown={handleSelect} name="Columna-Este-2">
            <boxGeometry args={[wallThickness, eastColumnHeight, eastSideDepth]} />
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

function CoverPreview({ enabled, piece, pieceSizeCm, roomSize, textureUrl }) {
  const MAX_PREVIEW = 800
  const instancesRef = useRef()
  const map = useTexture(textureUrl || WHITE_TEX_DATA_URL)

  useEffect(() => {
    if (!map) return
    map.colorSpace = SRGBColorSpace
    map.wrapS = map.wrapT = 1000
    map.repeat.set(1, 1)
    map.needsUpdate = true
  }, [map])

  const preview = useMemo(() => {
    if (!enabled || !piece || !pieceSizeCm) return null
    const pieceW = pieceSizeCm.x / 100
    const pieceD = pieceSizeCm.z / 100
    if (pieceW <= 0 || pieceD <= 0) return null

    const fullCols = Math.max(1, Math.ceil(roomSize.width / pieceW))
    const fullRows = Math.max(1, Math.ceil(roomSize.depth / pieceD))
    const fullTotal = fullCols * fullRows

    // Si excede MAX_PREVIEW, agrupamos piezas (stride) para seguir cubriendo todo el piso.
    const stride = fullTotal <= MAX_PREVIEW ? 1 : Math.ceil(Math.sqrt(fullTotal / MAX_PREVIEW))
    const cols = Math.max(1, Math.ceil(fullCols / stride))
    const rows = Math.max(1, Math.ceil(fullRows / stride))
    const tileW = pieceW * stride
    const tileD = pieceD * stride
    const total = Math.min(cols * rows, MAX_PREVIEW)
    return { cols, rows, total, stride, tileW, tileD }
  }, [enabled, piece, pieceSizeCm, roomSize])

  useEffect(() => {
    if (!instancesRef.current || !preview) return
    const inst = instancesRef.current
    const dummy = new Object3D()
    let i = 0
    for (let r = 0; r < preview.rows; r++) {
      for (let c = 0; c < preview.cols; c++) {
        if (i >= preview.total) break
        const x = -roomSize.width / 2 + preview.tileW / 2 + c * preview.tileW
        const z = -roomSize.depth / 2 + preview.tileD / 2 + r * preview.tileD
        dummy.position.set(x, 0.5, z)
        dummy.rotation.set(0, 0, 0)
        const sx = (piece.scaleXZ?.x ?? 1) * (preview.stride ?? 1)
        const sz = (piece.scaleXZ?.z ?? 1) * (preview.stride ?? 1)
        dummy.scale.set(sx, 1, sz)
        dummy.updateMatrix()
        inst.setMatrixAt(i, dummy.matrix)
        i++
      }
      if (i >= preview.total) break
    }
    inst.instanceMatrix.needsUpdate = true
  }, [preview, piece, roomSize])

  if (!preview) return null
  return (
    <instancedMesh ref={instancesRef} args={[null, null, preview.total]} receiveShadow castShadow>
      <boxGeometry args={piece.size} />
      <meshStandardMaterial
        map={map}
        color={'#ffffff'}
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
