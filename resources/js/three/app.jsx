import React, { useEffect, useMemo, useState, useCallback, useRef } from 'react'
import { createRoot } from 'react-dom/client'
import { Canvas, useFrame, useThree } from '@react-three/fiber'
import { OrbitControls, Environment, SoftShadows, PerformanceMonitor, useTexture } from '@react-three/drei'
// Effects re-exported from components/PostFX.jsx
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

import { TexturedPieceMesh } from './components/TexturedPieceMesh'
import { SurfaceMaterial } from './components/SurfaceMaterial'
import { Floor } from './components/Floor'
import { Controls } from './components/Controls'
import { RendererSetup, PostFX } from './components/PostFX'
import { CoverPreview, WallCoverPreview } from './components/CoverPreviews'
import { RightSidebar } from './components/RightSidebar'
import { DynamicSpot } from './components/DynamicSpot'
import { SunLight, getEnvironmentPreset } from './components/SunLight'
import { WindowLight } from './components/WindowLight'
import { LightingPanel } from './components/LightingPanel'
import { useRecommendations } from './hooks/useRecommendations'
import { ToastProvider, useToast } from './components/Toast'
import { WindowMesh } from './components/WindowMesh'
import {
  createPresetGrid,
  normalizeGrid,
  computeFloorMetrics,
  computeWallSegments,
} from './roomGeometry'

const WHITE_TEX_DATA_URL =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"><rect width="4" height="4" fill="#ffffff"/></svg>'
  )

function getMaterialDimsCm(material) {
  const dims = material?.piece_dimensions_cm
  const defaultW = material?.kind === 'plank' ? 120 : 60
  const defaultD = material?.kind === 'plank' ? 20 : 60
  const w = Number(dims?.width || 0)
  const d = Number(dims?.depth || 0)
  return {
    width: Number.isFinite(w) && w > 0 ? w : defaultW,
    depth: Number.isFinite(d) && d > 0 ? d : defaultD,
    locked: !!dims?.locked,
  }
}

// Componentes extraidos a components/
function Demo() {
  const { toast, confirm } = useToast()
  const [activeSurface, setActiveSurface] = useState('floor')
  const [activeWallKey, setActiveWallKey] = useState('north')
  const [floor, setFloor] = useState('wood')
  const [selected, setSelected] = useState(null)
  const [postFXOn, setPostFXOn] = useState(true)
  const [softShadowsBad, setSoftShadowsBad] = useState(false)
  const [dofOn, setDofOn] = useState(false)
  const [timeOfDay, setTimeOfDay] = useState(10)
  const [lightIntensity, setLightIntensity] = useState(1.0)
  const [floorCoverSolid, setFloorCoverSolid] = useState(false)
  const [wallCoverSolid, setWallCoverSolid] = useState(false)
  const [materials, setMaterials] = useState([])
  const [materialsLoading, setMaterialsLoading] = useState(true)
  const [materialsError, setMaterialsError] = useState(null)
  const [inventoryLive, setInventoryLive] = useState({})
  const [selectedMaterialId, setSelectedMaterialId] = useState(null)
  const [selectedWallMaterialId, setSelectedWallMaterialId] = useState(null)
  const [pieces, setPieces] = useState([])
  const [wallPieces, setWallPieces] = useState([])
  const [roomSizeCm, setRoomSizeCm] = useState({ width: 1000, depth: 1000, height: 300 })
  const [roomShape, setRoomShape] = useState('rectangular')
  const [customGrid, setCustomGrid] = useState(() => createPresetGrid('rectangular'))
  const [wallViewMode, setWallViewMode] = useState('full') // 'full' | 'half' | 'hidden'
  const [hiddenWallIds, setHiddenWallIds] = useState([])
  const [interiorWalls, setInteriorWalls] = useState([])
  const [windows, setWindows] = useState([])

  const handleRoomShapeChange = useCallback((nextShape) => {
    setRoomShape(nextShape)
    setCustomGrid(createPresetGrid(nextShape))
    setHiddenWallIds([])
  }, [])

  const handleCustomGridChange = useCallback((nextGrid) => {
    setCustomGrid(normalizeGrid(nextGrid, 'rectangular'))
    setRoomShape('custom')
  }, [])

  const addInteriorWall = useCallback(() => {
    const id = Date.now() + Math.floor(Math.random() * 1000)
    setInteriorWalls((prev) => [
      ...prev,
      {
        id,
        axis: 'x',
        row: 5,
        col: 5,
        lengthCells: 4,
      },
    ])
  }, [])

  const updateInteriorWall = useCallback((id, patch) => {
    setInteriorWalls((prev) =>
      prev.map((iw) => (iw.id === id ? { ...iw, ...patch } : iw))
    )
  }, [])

  const removeInteriorWall = useCallback((id) => {
    setInteriorWalls((prev) => prev.filter((iw) => iw.id !== id))
  }, [])

  const addWindow = useCallback((wallKey) => {
    const id = Date.now() + Math.floor(Math.random() * 1000)
    setWindows((prev) => [
      ...prev,
      {
        id,
        wallKey: wallKey || activeWallKey || 'north',
        widthCm: 160,
        heightCm: 120,
        sillHeightCm: 90,
      },
    ])
  }, [activeWallKey])

  const updateWindow = useCallback((id, patch) => {
    setWindows((prev) =>
      prev.map((w) => (w.id === id ? { ...w, ...patch } : w))
    )
  }, [])

  const removeWindow = useCallback((id) => {
    setWindows((prev) => prev.filter((w) => w.id !== id))
  }, [])
  const [quoteLoading, setQuoteLoading] = useState(false)
  const [scenes, setScenes] = useState([])
  const [scenesLoading, setScenesLoading] = useState(true)
  const [scenesError, setScenesError] = useState(null)
  const [selectedSceneId, setSelectedSceneId] = useState(null)
  const [sceneName, setSceneName] = useState('')

  const nextId = useRef(1)
  const lastEmissive = useRef({ obj: null, color: null })
  const controlsRef = useRef(null)
  const r3fRef = useRef({ gl: null, scene: null })
  const initialUrlHandledRef = useRef(false)

  const trackEvent = useCallback(async (eventType, { productId = null, value = null, meta = null } = {}) => {
    if (!selectedSceneId) return
    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')

    try {
      await fetch('/3d/events', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          Accept: 'application/json',
          'X-CSRF-TOKEN': csrfToken ?? '',
        },
        body: JSON.stringify({
          scene_id: Number(selectedSceneId),
          event_type: String(eventType || ''),
          product_id: productId ? Number(productId) : null,
          value: value !== null && value !== undefined ? Number(value) : null,
        }),
      })
    } catch (err) {
      console.warn('No se pudo registrar telemetría ML', err)
    }
  }, [selectedSceneId])

  const { recommendations, recommendationsLoading, recommendationsError } = useRecommendations(selectedSceneId, selectedMaterialId, selectedWallMaterialId, trackEvent)
  const pendingSceneDataRef = useRef(null)
  const autoLoadedSceneRef = useRef(false)

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

  const floorMetrics = useMemo(
    () => computeFloorMetrics(customGrid, roomSize),
    [customGrid, roomSize]
  )

  const wallGeometry = useMemo(
    () => computeWallSegments(customGrid, roomSize, roomShape, interiorWalls),
    [customGrid, roomSize, roomShape, interiorWalls]
  )

  const wallHeightFactor = useMemo(() => {
    if (wallViewMode === 'hidden') return 0
    if (wallViewMode === 'half') return 0.42
    return 1.0
  }, [wallViewMode])

  const wallThickness = 0.12

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
        setSelectedWallMaterialId((prev) => {
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
    () => materials?.find((m) => String(m.id) === String(selectedMaterialId)) ?? null,
    [materials, selectedMaterialId]
  )
  const selectedWallMaterial = useMemo(
    () => materials?.find((m) => String(m.id) === String(selectedWallMaterialId)) ?? null,
    [materials, selectedWallMaterialId]
  )

  const [previewFloorMaterial, setPreviewFloorMaterial] = useState(null)
  const [previewWallMaterial, setPreviewWallMaterial] = useState(null)

  const handleSelectMaterial = useCallback((id) => {
    setSelectedMaterialId(id)
    const mat = materials?.find((m) => String(m.id) === String(id)) ?? null
    setPreviewFloorMaterial(mat)
    setActiveSurface('floor')
  }, [materials])

  const handleSelectWallMaterial = useCallback((id) => {
    setSelectedWallMaterialId(id)
    const mat = materials?.find((m) => String(m.id) === String(id)) ?? null
    setPreviewWallMaterial(mat)
    setActiveSurface('walls')
  }, [materials])

  const floorPiece = useMemo(() => pieces[0] ?? null, [pieces])
  const wallPiece = useMemo(() => wallPieces[0] ?? null, [wallPieces])

  const activeFloorPiece = useMemo(() => {
    if (previewFloorMaterial) {
      const dims = getMaterialDimsCm(previewFloorMaterial)
      return {
        id: 'preview-floor',
        kind: previewFloorMaterial.kind === 'plank' ? 'plank' : 'tile',
        material: previewFloorMaterial,
        size: [dims.width / 100, 0.12, dims.depth / 100],
        scaleXZ: { x: 1, z: 1 },
        textureUrl: previewFloorMaterial.image_url,
        roughness: previewFloorMaterial.kind === 'plank' ? 0.75 : 0.65,
        metalness: 0.0,
      }
    }
    return floorPiece
  }, [previewFloorMaterial, floorPiece])

  const activeWallPiece = useMemo(() => {
    if (previewWallMaterial) {
      const dims = getMaterialDimsCm(previewWallMaterial)
      return {
        id: 'preview-wall',
        kind: previewWallMaterial.kind === 'plank' ? 'plank' : 'tile',
        material: previewWallMaterial,
        width_cm: dims.width,
        depth_cm: dims.depth,
        textureUrl: previewWallMaterial.image_url,
        roughness: previewWallMaterial.kind === 'plank' ? 0.75 : 0.65,
        metalness: 0.0,
      }
    }
    return wallPiece
  }, [previewWallMaterial, wallPiece])

  const handleUseRecommendation = useCallback((productId) => {
    if (!productId) return
    if (activeSurface === 'walls') {
      handleSelectWallMaterial(productId)
    } else {
      handleSelectMaterial(productId)
    }
    trackEvent('recommendation_click', { productId, meta: { surface: activeSurface, wall: activeWallKey } })
  }, [activeSurface, activeWallKey, trackEvent, handleSelectWallMaterial, handleSelectMaterial])

  const wallCoverageData = useMemo(() => {
    if (!activeWallPiece) return { canCompute: false, computed: false }
    const wallAreaM2 = wallGeometry.totalWallAreaM2
    if (!Number.isFinite(wallAreaM2) || wallAreaM2 <= 0) return { canCompute: false, computed: false }

    const dims = getMaterialDimsCm(activeWallPiece.material)
    const wCm = Number(activeWallPiece.width_cm || dims.width || 60)
    const dCm = Number(activeWallPiece.depth_cm || dims.depth || 60)
    if (!Number.isFinite(wCm) || !Number.isFinite(dCm) || wCm <= 0 || dCm <= 0) {
      return { canCompute: false, computed: false }
    }

    const pieceAreaM2 = (wCm / 100) * (dCm / 100)
    if (!Number.isFinite(pieceAreaM2) || pieceAreaM2 <= 0) return { canCompute: false, computed: false }
    const estimatedUnits = Math.max(1, Math.ceil(wallAreaM2 / Math.max(pieceAreaM2, 0.0001)))
    return {
      canCompute: true,
      computed: true,
      wallAreaM2,
      pieceAreaM2,
      estimatedUnits,
      pieceCmX: wCm,
      pieceCmZ: dCm,
    }
  }, [activeWallPiece, wallGeometry.totalWallAreaM2])

  const buildScenePayload = useCallback(() => {
    return {
      version: 2,
      floor,
      roomShape,
      customGrid,
      interiorWalls,
      wallViewMode,
      floorCoverSolid,
      wallCoverSolid,
      windows,
      roomSizeCm,
      selectedMaterialId,
      selectedWallMaterialId,

      wall: wallPiece ? {
        material_id: wallPiece?.material?.id ?? null,
        width_cm: wallPiece.width_cm,
        depth_cm: wallPiece.depth_cm,
        activeWallKey,
      } : null,
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
  }, [
    floor,
    roomShape,
    customGrid,
    interiorWalls,
    wallViewMode,
    floorCoverSolid,
    wallCoverSolid,
    windows,
    roomSizeCm,
    selectedMaterialId,
    selectedWallMaterialId,
    pieces,
    wallPiece,
    activeWallKey,
  ])

  const resetSceneState = useCallback(() => {
    setSelectedSceneId(null)
    setSceneName('')
    setPieces([])
    setWallPieces([])
    setPreviewFloorMaterial(null)
    setPreviewWallMaterial(null)
    setWindows([])
    setInteriorWalls([])
    setHiddenWallIds([])
    setWallViewMode('full')
    setRoomShape('rectangular')
    setCustomGrid(createPresetGrid('rectangular'))
    setSelected(null)

    window.localStorage.removeItem('three.lastSceneId')
  }, [])

  const hydrateScene = useCallback((sceneData) => {
    if (!sceneData || typeof sceneData !== 'object') return
    setFloor(sceneData.floor === 'ceramic' ? 'ceramic' : 'wood')
    setPreviewFloorMaterial(null)
    setPreviewWallMaterial(null)

    const loadedShape = sceneData.roomShape || 'rectangular'
    setRoomShape(loadedShape)
    setCustomGrid(normalizeGrid(sceneData.customGrid, loadedShape))
    setInteriorWalls(Array.isArray(sceneData.interiorWalls) ? sceneData.interiorWalls : [])
    setWallViewMode(sceneData.wallViewMode || 'full')
    setHiddenWallIds([])

    if (typeof sceneData.floorCoverSolid === 'boolean') {
      setFloorCoverSolid(sceneData.floorCoverSolid)
    }
    if (typeof sceneData.wallCoverSolid === 'boolean') {
      setWallCoverSolid(sceneData.wallCoverSolid)
    }

    if (Array.isArray(sceneData.windows)) {
      setWindows(sceneData.windows)
    } else if (sceneData.windowConfig?.enabled) {
      setWindows([
        {
          id: 1,
          wallKey: 'north',
          widthCm: Number(sceneData.windowConfig.widthCm) || 160,
          heightCm: Number(sceneData.windowConfig.heightCm) || 120,
          sillHeightCm: Number(sceneData.windowConfig.sillHeightCm) || 90,
        },
      ])
    } else {
      setWindows([])
    }

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

    if (sceneData.selectedWallMaterialId != null) {
      setSelectedWallMaterialId(Number(sceneData.selectedWallMaterialId))
    }

    const wallData = sceneData.wall && typeof sceneData.wall === 'object' ? sceneData.wall : null
    if (wallData) {
      if (wallData.activeWallKey) {
        setActiveWallKey(String(wallData.activeWallKey))
      }

      const wallMaterial = materials.find((m) => Number(m.id) === Number(wallData.material_id)) ?? null
      const dims = getMaterialDimsCm(wallMaterial)
      const wCm = Number(wallData.width_cm || dims.width || 60)
      const dCm = Number(wallData.depth_cm || dims.depth || 60)
      if (wallMaterial && wCm > 0 && dCm > 0) {
        const id = nextId.current++
        setWallPieces([
          {
            id,
            kind: wallMaterial.kind === 'plank' ? 'plank' : 'tile',
            material: wallMaterial,
            name: `ParedPieza-${id}`,
            width_cm: wCm,
            depth_cm: dCm,
            wallKey: String(wallData.activeWallKey || 'north'),
            textureUrl: wallMaterial.image_url || WHITE_TEX_DATA_URL,
          },
        ])
      } else {
        setWallPieces([])
      }
    } else {
      setWallPieces([])
    }

    const nextPieces = Array.isArray(sceneData.pieces) ? sceneData.pieces : []
    if (!nextPieces.length) {
      setPieces([])
      setSelected(null)
      return
    }

    const raw = nextPieces[0]
    const material = materials.find((m) => Number(m.id) === Number(raw?.material_id)) ?? null
    const dims = getMaterialDimsCm(material)
    const id = nextId.current++
    const piece = {
      id,
      kind: raw?.kind === 'plank' || material?.kind === 'plank' ? 'plank' : 'tile',
      material: material ?? null,
      name: `Pieza-${id}`,
      position: Array.isArray(raw?.position) ? raw.position : [0, roomLimits.y, 0],
      rotation: Array.isArray(raw?.rotation) ? raw.rotation : [0, 0, 0],
      size: Array.isArray(raw?.size) ? raw.size : [dims.width / 100, 0.12, dims.depth / 100],
      scaleXZ: raw?.scaleXZ && typeof raw.scaleXZ === 'object' ? raw.scaleXZ : { x: 1, z: 1 },
      lockSizeXZ: !!raw?.lockSizeXZ,
      textureUrl: material?.image_url || WHITE_TEX_DATA_URL,
      roughness: material?.kind === 'plank' ? 0.75 : 0.65,
      metalness: 0.0,
    }
    setPieces([piece])
    setSelected(null)
  }, [materials, roomLimits.y])

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
      toast('No se pudo cargar el escenario.', { type: 'error' })
    }
  }, [materialsLoading, hydrateScene, toast])

  useEffect(() => {
    if (initialUrlHandledRef.current) return
    initialUrlHandledRef.current = true

    const params = new URLSearchParams(window.location.search)
    const isNew = params.get('new') === '1'
    const sceneIdParam = params.get('sceneId')
    const requestedSceneId = sceneIdParam ? Number(sceneIdParam) : null

    if (isNew) {
      autoLoadedSceneRef.current = true
      resetSceneState()
      if (window.history?.replaceState) {
        window.history.replaceState({}, document.title, window.location.pathname)
      }
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

  const createNewScene = useCallback(async () => {
    const ok = await confirm('Esto creará un escenario nuevo y limpiará la escena actual. ¿Continuar?')
    if (!ok) return
    resetSceneState()
  }, [resetSceneState])

  const saveScene = useCallback(async () => {
    if (scenesError) {
      toast('Inicia sesión para guardar escenarios.', { type: 'warning' })
      return null
    }

    const name = String(sceneName || '').trim()
    if (!name) {
      toast('Escribe un nombre para el escenario antes de guardar.', { type: 'warning' })
      return null
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
        toast('Inicia sesión para guardar escenarios.', { type: 'warning' })
        return null
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

      toast('Escenario guardado correctamente.', { type: 'success' })
      return savedId
    } catch (err) {
      console.error(err)
      toast(String(err?.message || 'No se pudo guardar el escenario.'), { type: 'error' })
      return null
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

  const quotePiece = useMemo(() => activeFloorPiece, [activeFloorPiece])

  const hasValidPieceDims = useCallback((material) => {
    return !!material
  }, [])

  const inventoryStatus = useMemo(() => {
    const material = quotePiece?.material ?? activeWallPiece?.material ?? selectedMaterial
    if (!material) return { canCompute: false }

    const floorAreaM2 = floorMetrics.floorAreaM2
    const m2PerBox = Number(material?.packaging?.m2_per_box || 0)
    const liveInv = inventoryLive && material?.id != null ? inventoryLive[String(material.id)] : null
    const rawBoxes = liveInv?.boxes_available_total ?? material?.inventory?.boxes_available_total
    const boxesAvailableTotal = Number(rawBoxes ?? NaN)

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

    const rawLots = Array.isArray(liveInv?.lots)
      ? liveInv.lots
      : (Array.isArray(material?.inventory?.lots) ? material.inventory.lots : [])
    const lots = rawLots
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
  }, [quotePiece, activeWallPiece, selectedMaterial, floorMetrics.floorAreaM2, inventoryLive])

  const quoteProductId = quotePiece?.material?.id ?? null
  const wallProductId = activeWallPiece?.material?.id ?? null

  const liveInventoryProductIds = useMemo(() => {
    const ids = new Set()
    if (selectedMaterialId) ids.add(Number(selectedMaterialId))
    if (selectedWallMaterialId) ids.add(Number(selectedWallMaterialId))
    if (quoteProductId) ids.add(Number(quoteProductId))
    if (wallProductId) ids.add(Number(wallProductId))
    return Array.from(ids)
      .filter((v) => Number.isFinite(v) && v > 0)
      .slice(0, 25)
  }, [selectedMaterialId, selectedWallMaterialId, quoteProductId, wallProductId])

  useEffect(() => {
    if (!liveInventoryProductIds.length) return

    let alive = true
    let timer = null

    async function fetchSnapshot() {
      try {
        const qs = encodeURIComponent(liveInventoryProductIds.join(','))
        const resp = await fetch(`/3d/inventory?product_ids=${qs}`, {
          method: 'GET',
          credentials: 'same-origin',
          headers: { Accept: 'application/json' },
        })
        if (!resp.ok) return
        const data = await resp.json().catch(() => null)
        const items = Array.isArray(data?.items) ? data.items : []
        if (!alive) return
        setInventoryLive((prev) => {
          const next = { ...(prev || {}) }
          for (const it of items) {
            const pid = it?.product_id
            if (pid == null) continue
            const inv = it?.inventory
            if (!inv || typeof inv !== 'object') continue
            next[String(pid)] = inv
          }
          return next
        })
      } catch (err) {
        // Silencioso: la UI sigue con el stock del catálogo inicial.
        console.warn('No se pudo refrescar inventario en vivo.', err)
      }
    }

    fetchSnapshot()
    timer = window.setInterval(fetchSnapshot, 10_000)

    return () => {
      alive = false
      if (timer) window.clearInterval(timer)
    }
  }, [liveInventoryProductIds])

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
    if (!quotePiece || !quoteSizeCm) return { canCompute: false, computed: false }
    const pieceM = {
      x: quoteSizeCm.x / 100,
      z: quoteSizeCm.z / 100,
    }
    const pieceAreaM2 = pieceM.x * pieceM.z
    const floorAreaM2 = floorMetrics.floorAreaM2
    if (!Number.isFinite(pieceAreaM2) || pieceAreaM2 <= 0) return { canCompute: false, computed: false }

    const count = Math.ceil(floorAreaM2 / pieceAreaM2)
    return {
      canCompute: true,
      computed: true,
      pieceCmX: quoteSizeCm.x,
      pieceCmZ: quoteSizeCm.z,
      pieceAreaM2,
      floorAreaM2,
      count,
    }
  }, [quotePiece, quoteSizeCm, floorMetrics.floorAreaM2])



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
    const hasFloor = !!(quotePiece && quoteSizeCm && quotePiece?.material)
    const hasWall = !!(activeWallPiece && activeWallPiece?.material && wallCoverageData?.computed)

    if (!hasFloor && !hasWall) {
      return { ready: false }
    }

    const floorAreaM2 = floorMetrics.floorAreaM2
    let floorTotal = 0
    let floorUnitPriceM2 = 0
    let pieceAreaM2 = 0
    let estimatedUnits = 0
    let itemLabel = ''

    if (hasFloor) {
      const material = quotePiece.material
      itemLabel = material.name
      pieceAreaM2 = (quoteSizeCm.x / 100) * (quoteSizeCm.z / 100)
      estimatedUnits = Math.max(1, Math.ceil(floorAreaM2 / Math.max(pieceAreaM2, 0.0001)))
      floorUnitPriceM2 = Number(material.price_per_m2 || 0)
      floorTotal = floorAreaM2 * floorUnitPriceM2
    }

    let wallTotal = 0
    let wallItemLabel = ''
    const wallAreaM2 = wallGeometry.totalWallAreaM2

    if (hasWall) {
      const wallMat = activeWallPiece.material
      wallItemLabel = wallMat.name
      const wallUnitPriceM2 = Number(wallMat.price_per_m2 || 0)
      wallTotal = wallAreaM2 * wallUnitPriceM2
    }

    const total = floorTotal + wallTotal

    return {
      ready: true,
      hasFloor,
      hasWall,
      itemLabel: itemLabel || wallItemLabel,
      wallItemLabel,
      floorAreaM2,
      wallAreaM2,
      pieceAreaM2,
      estimatedUnits,
      unitPriceM2: floorUnitPriceM2,
      total,
      floorAreaLabel: `${floorAreaM2.toFixed(2)} m²`,
      wallAreaLabel: `${wallAreaM2.toFixed(2)} m²`,
      floorSubtotalLabel: currencyFormatter.format(floorTotal),
      wallSubtotalLabel: currencyFormatter.format(wallTotal),
      unitPriceLabel: currencyFormatter.format(floorUnitPriceM2),
      totalLabel: currencyFormatter.format(total),
    }
  }, [quotePiece, quoteSizeCm, activeWallPiece, wallCoverageData, floorMetrics.floorAreaM2, wallGeometry.totalWallAreaM2, currencyFormatter])

  const hasPiece = pieces.length > 0
  const canAdd = !materialsLoading && !materialsError && !!selectedMaterial
  const addLabel = hasPiece ? 'Reemplazar pieza' : 'Agregar pieza'

  const addOnePiece = useCallback((materialOverride = null) => {
    const targetMaterial = (materialOverride && materialOverride.id) ? materialOverride : selectedMaterial
    if (!targetMaterial) return false

    const id = nextId.current++
    const dims = getMaterialDimsCm(targetMaterial)
    const useDimsSize = [dims.width / 100, 0.12, dims.depth / 100]

    const piece = {
      id,
      kind: targetMaterial.kind === 'plank' ? 'plank' : 'tile',
      material: targetMaterial,
      name: `Pieza-${id}`,
      position: [0, roomLimits.y, 0],
      rotation: [0, 0, 0],
      size: useDimsSize,
      scaleXZ: { x: 1, z: 1 },
      lockSizeXZ: dims.locked,
      textureUrl: targetMaterial.image_url,
      roughness: targetMaterial.kind === 'plank' ? 0.75 : 0.65,
      metalness: 0.0,
    }
    setPieces([piece])
    setSelectedMaterialId(targetMaterial.id)
    setSelected(null)
    return true
  }, [selectedMaterial, roomLimits.y])

  const hasWallPiece = wallPieces.length > 0
  const canAddWalls = !materialsLoading && !materialsError && !!selectedWallMaterial
  const addLabelWalls = hasWallPiece ? 'Reemplazar pieza' : 'Agregar pieza'

  const addOneWallPiece = useCallback((materialOverride = null) => {
    const targetMaterial = (materialOverride && materialOverride.id) ? materialOverride : selectedWallMaterial
    if (!targetMaterial) return false

    const dims = getMaterialDimsCm(targetMaterial)
    const id = nextId.current++
    const piece = {
      id,
      kind: targetMaterial.kind === 'plank' ? 'plank' : 'tile',
      material: targetMaterial,
      name: `ParedPieza-${id}`,
      width_cm: dims.width,
      depth_cm: dims.depth,
      wallKey: activeWallKey,
      textureUrl: targetMaterial.image_url || WHITE_TEX_DATA_URL,
      roughness: targetMaterial.kind === 'plank' ? 0.75 : 0.65,
      metalness: 0.0,
    }

    setWallPieces([piece])
    setSelectedWallMaterialId(targetMaterial.id)
    setSelected(null)
    return true
  }, [selectedWallMaterial, activeWallKey])

  const handleGenerateQuote = useCallback(async () => {
    if (!quoteSummary?.ready || quoteLoading) return

    let sceneId = selectedSceneId
    if (!sceneId) {
      sceneId = await saveScene()
      if (!sceneId) return
    }

    if (inventoryStatus?.canCompute && inventoryStatus?.canFulfill === false) {
      const ok = await confirm(
        `Stock insuficiente según inventario (en tiempo real).\n\n` +
        `Requiere: ${inventoryStatus.boxesRequired} cajas\n` +
        `Disponibles: ${inventoryStatus.boxesAvailableTotal} cajas\n` +
        `Faltan: ${inventoryStatus.missingBoxes} cajas\n\n` +
        `¿Deseas generar la cotización igual? (No descuenta inventario; la validación final será en Venta).`
      )
      if (!ok) return
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

      trackEvent('quote_generate', { meta: { has_stock_warning: inventoryStatus?.canCompute && inventoryStatus?.canFulfill === false } })

      // Ajustar largo efectivo al área real de la cuadrícula personalizada para que el PDF coincida en m²
      const effectiveDepthCm = Math.max(200, Math.min(3000, Math.round(roomSizeCm.depth * floorMetrics.activeRatio)))
      const fallbackPieceKind = quotePiece?.kind || activeWallPiece?.kind || 'tile'
      const fallbackWidthCm = quoteSizeCm?.x || activeWallPiece?.width_cm || 60
      const fallbackDepthCm = quoteSizeCm?.z || activeWallPiece?.depth_cm || 60
      const fallbackHeightCm = quoteSizeCm?.y || 12

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
          scene_id: sceneId ?? null,
          scene_name: String(sceneName || '').trim() || 'Escena 3D personalizada',
          floor_kind: floor,
          snapshot_top_png_data_url: snapshotTop,
          walls: {
            material_id: activeWallPiece?.material?.id ?? null,
            piece: activeWallPiece
              ? {
                kind: activeWallPiece.kind === 'plank' ? 'plank' : 'tile',
                width_cm: activeWallPiece.width_cm,
                depth_cm: activeWallPiece.depth_cm,
              }
              : null,
          },
          room: {
            width_cm: roomSizeCm.width,
            depth_cm: effectiveDepthCm,
            height_cm: roomSizeCm.height,
          },
          piece: {
            kind: fallbackPieceKind === 'plank' ? 'plank' : 'tile',
            width_cm: fallbackWidthCm,
            depth_cm: fallbackDepthCm,
            height_cm: fallbackHeightCm,
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
      toast('No se pudo generar la cotización PDF. Revisa la configuración actual e inténtalo de nuevo.', { type: 'error', duration: 5500 })
    } finally {
      setQuoteLoading(false)
    }
  }, [quoteSummary, quotePiece, quoteSizeCm, quoteLoading, floor, roomSizeCm, floorMetrics.activeRatio, inventoryStatus, captureTopDownSnapshot, activeWallPiece, sceneName, selectedSceneId, trackEvent, saveScene, confirm, toast])

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
      t += 1 / 60
      const alpha = Math.min(t / duration, 1)
      cam.position.lerpVectors(startPos, endPos, alpha)
      controls.target.lerpVectors(startTarget, endTarget, alpha)
      controls.update()
      if (alpha < 1) requestAnimationFrame(step)
    }
    requestAnimationFrame(step)
  }, [])

  const inferSurfaceFromName = useCallback((name) => {
    if (!name) return null
    if (name === 'Piso') return 'floor'
    if (name.startsWith('Pared-') || name.startsWith('Marco-') || name.startsWith('Columna-') || name.startsWith('Pared')) {
      return 'walls'
    }
    return null
  }, [])

  const inferWallKeyFromName = useCallback((name) => {
    if (!name) return null
    const n = String(name).toLowerCase()
    if (n.includes('norte')) return 'north'
    if (n.includes('sur')) return 'south'
    if (n.includes('oeste')) return 'west'
    if (n.includes('este')) return 'east'
    return null
  }, [])

  const resetCamera = useCallback(() => {
    const controls = controlsRef.current
    const cam = controls?.object
    if (!controls || !cam) return
    controls.target.set(0, 0, 0)
    cam.position.set(...cameraConfig.position)
    controls.update()
    handlePointerMissed()
  }, [handlePointerMissed])

  const handleSelect = useCallback((e) => {
    if (e.button !== 0) return // solo click izquierdo
    e.stopPropagation()
    const obj = e.object
    if (!obj.material) return

    const surface = inferSurfaceFromName(obj?.name)
    if (surface) {
      setActiveSurface(surface)
      if (surface === 'walls') {
        const wallKey = inferWallKeyFromName(obj?.name)
        if (wallKey) setActiveWallKey(wallKey)
      }
    }

    clearSelectionVisual()
    if (obj.material.emissive) {
      lastEmissive.current = { obj, color: obj.material.emissive.clone() }
      obj.material.emissive.set('#222222')
    }
    setSelected(obj)
    animateCameraTo(obj)
  }, [clearSelectionVisual, animateCameraTo, inferSurfaceFromName, inferWallKeyFromName])


  return (
    <>
      <Controls
        activeSurface={activeSurface}
        onActiveSurfaceChange={setActiveSurface}
        activeWallKey={activeWallKey}
        onActiveWallKeyChange={setActiveWallKey}
        floor={floor}
        setFloor={setFloor}
        materials={materials}
        materialsLoading={materialsLoading}
        materialsError={materialsError}
        selectedMaterialId={selectedMaterialId}
        onSelectMaterial={handleSelectMaterial}
        selectedWallMaterialId={selectedWallMaterialId}
        onSelectWallMaterial={handleSelectWallMaterial}
        recommendations={recommendations}
        recommendationsLoading={recommendationsLoading}
        recommendationsError={recommendationsError}
        onUseRecommendation={handleUseRecommendation}
        wallCoverage={wallCoverageData}
        onAddWalls={addOneWallPiece}
        canAddWalls={canAddWalls}
        addLabelWalls={addLabelWalls}

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
        coverage={coverageData}
        roomSizeCm={roomSizeCm}
        onRoomSizeChange={updateRoomSize}
        roomShape={roomShape}
        onRoomShapeChange={handleRoomShapeChange}
        customGrid={customGrid}
        onCustomGridChange={handleCustomGridChange}
        wallViewMode={wallViewMode}
        onWallViewModeChange={setWallViewMode}
        hiddenWallIds={hiddenWallIds}
        onResetHiddenWalls={() => setHiddenWallIds([])}
        interiorWalls={interiorWalls}
        onAddInteriorWall={addInteriorWall}
        onUpdateInteriorWall={updateInteriorWall}
        onRemoveInteriorWall={removeInteriorWall}
        exactFloorAreaM2={floorMetrics.floorAreaM2}
        exactWallAreaM2={wallGeometry.totalWallAreaM2}
        windows={windows}
        onAddWindow={addWindow}
        onUpdateWindow={updateWindow}
        onRemoveWindow={removeWindow}
        quoteSummary={quoteSummary}
        onGenerateQuote={handleGenerateQuote}
        quoteLoading={quoteLoading}
        inventoryStatus={inventoryStatus}
      />
      {/* Panel de Iluminación - esquina inferior izquierda */}
      <LightingPanel
        timeOfDay={timeOfDay}
        onTimeOfDayChange={setTimeOfDay}
        lightIntensity={lightIntensity}
        onLightIntensityChange={setLightIntensity}
        postFXOn={postFXOn}
        onPostFXToggle={() => setPostFXOn(v => !v)}
        dofOn={dofOn}
        onDofToggle={() => setDofOn(v => !v)}
      />

      <RightSidebar
        activeSurface={activeSurface}
        previewFloor={previewFloorMaterial}
        previewWall={previewWallMaterial}
        installedFloor={floorPiece?.material}
        installedWall={wallPiece?.material}
        floorCoverage={coverageData}
        wallCoverage={wallCoverageData}
        floorCoverSolid={floorCoverSolid}
        onToggleFloorCoverSolid={() => setFloorCoverSolid(v => !v)}
        wallCoverSolid={wallCoverSolid}
        onToggleWallCoverSolid={() => setWallCoverSolid(v => !v)}
        timeOfDay={timeOfDay}
        lightIntensity={lightIntensity}
        onApplyFloor={() => {
          const applied = addOnePiece(previewFloorMaterial)
          if (applied) {
            setPreviewFloorMaterial(null)
            toast('Material aplicado al piso.', { type: 'success' })
          }
        }}
        onApplyWall={() => {
          const applied = addOneWallPiece(previewWallMaterial)
          if (applied) {
            setPreviewWallMaterial(null)
            toast('Material aplicado a las paredes.', { type: 'success' })
          }
        }}
        onCancelPreviewFloor={() => {
          setPreviewFloorMaterial(null)
          setSelectedMaterialId(pieces[0]?.material?.id ?? null)
        }}
        onCancelPreviewWall={() => {
          setPreviewWallMaterial(null)
          setSelectedWallMaterialId(wallPieces[0]?.material?.id ?? null)
        }}
        onRemoveFloor={() => {
          setPieces([])
          setPreviewFloorMaterial(null)
          setSelectedMaterialId(null)
        }}
        onRemoveWall={() => {
          setWallPieces([])
          setPreviewWallMaterial(null)
          setSelectedWallMaterialId(null)
        }}
      />

      {selected && (
        <div style={{ position: 'fixed', top: 74, left: 390, zIndex: 80, background: 'rgba(15, 23, 42, 0.88)', backdropFilter: 'blur(12px)', border: '1px solid rgba(255,255,255,0.15)', color: '#fff', padding: '10px 14px', borderRadius: 12, fontFamily: 'system-ui,Arial,sans-serif', width: 240, boxShadow: '0 10px 25px rgba(0,0,0,0.4)' }}>
          <div style={{ fontWeight: '700', fontSize: 13, marginBottom: 4, color: '#34d399' }}>
            {selected?.userData?.wallLabel || selected?.name || 'Superficie seleccionada'}
          </div>
          <div style={{ fontSize: 11, color: '#cbd5e1', marginBottom: 10, lineHeight: 1.3 }}>
            Superficie activa: <strong>{activeSurface === 'walls' ? 'Paredes' : 'Piso'}</strong>
          </div>
          <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6 }}>
            {selected?.userData?.wallSegmentId && (
              <button
                type="button"
                onClick={() => {
                  const wid = selected.userData.wallSegmentId
                  setHiddenWallIds((prev) => (prev.includes(wid) ? prev : [...prev, wid]))
                  handlePointerMissed()
                }}
                style={{ width: '100%', padding: '6px 10px', background: '#f59e0b', color: '#0f172a', fontWeight: 700, fontSize: 11, border: 'none', borderRadius: 6, cursor: 'pointer' }}
              >
                👁️ Ocultar esta pared
              </button>
            )}
            <button onClick={handlePointerMissed} style={{ flex: 1, padding: '6px 10px', background: '#334155', color: '#fff', fontSize: 11, fontWeight: 600, border: 'none', borderRadius: 6, cursor: 'pointer' }}>Deseleccionar</button>
            <button onClick={resetCamera} style={{ flex: 1, padding: '6px 10px', background: '#0284c7', color: '#fff', fontSize: 11, fontWeight: 600, border: 'none', borderRadius: 6, cursor: 'pointer' }}>Centrar cámara</button>
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

        <color attach="background" args={['#87CEEB']} />

        {/* Sistema de iluminación solar realista */}
        <SunLight timeOfDay={timeOfDay} intensity={lightIntensity} />

        {/* Luz que entra por las ventanas */}
        <WindowLight
          windows={windows}
          roomSize={roomSize}
          wallThickness={wallThickness}
          timeOfDay={timeOfDay}
          intensity={lightIntensity}
        />

        {/* Ambient mínimo de seguridad para que nada sea negro puro */}
        <ambientLight intensity={0.08} />

        {/* Spotlight dinámico siguiendo selección */}
        <DynamicSpot selected={selected} />

        {/* Postprocesado */}
        <PostFX enabled={postFXOn && postprocessingConfig.enabled} dofOn={dofOn} selected={selected} />

        {/* Escenario y Piso (Adaptable a la cuadrícula Sandbox) */}
        <Floor
          kind={floor}
          width={roomSize.width}
          depth={roomSize.depth}
          roomShape={roomShape}
          customGrid={customGrid}
          onPointerDown={handleSelect}
          name="Piso"
        />

        {/* Cobertura de Piso (Modo Grilla o Sólido, adaptable a la cuadrícula Sandbox) */}
        <CoverPreview
          enabled={!!activeFloorPiece && coverageData?.computed}
          piece={activeFloorPiece}
          pieceSizeCm={coverageData?.computed ? { x: coverageData.pieceCmX, z: coverageData.pieceCmZ } : null}
          roomSize={roomSize}
          roomShape={roomShape}
          customGrid={customGrid}
          textureUrl={activeFloorPiece?.textureUrl}
          solid={floorCoverSolid}
          onPointerDown={handleSelect}
        />

        {/* Cobertura en Paredes (Modo Grilla o Sólido, adaptable a todos los muros del cuarto Sandbox) */}
        <WallCoverPreview
          enabled={!!activeWallPiece && wallCoverageData?.computed}
          piece={activeWallPiece}
          roomSize={roomSize}
          wallThickness={wallThickness}
          wallSegments={wallGeometry.segments}
          wallHeightFactor={wallHeightFactor}
          hiddenWallIds={hiddenWallIds}
          textureUrl={activeWallPiece?.textureUrl}
          solid={wallCoverSolid}
          onPointerDown={handleSelect}
        />

        {/* Renderizado de Ventanas 3D Múltiples */}
        {wallHeightFactor > 0.2 && windows.map((win, idx) => {
          const wM = (win.widthCm || 160) / 100
          const hM = (win.heightCm || 120) / 100
          const sillM = (win.sillHeightCm || 90) / 100
          const y = sillM + hM / 2
          const eps = 0.003
          const wallKey = win.wallKey || 'north'

          // Buscar el segmento principal correspondiente a la orientación de la ventana
          const targetSeg = wallGeometry.segments.find((s) => s.wallKey === wallKey && !hiddenWallIds.includes(s.id))
          let pos = [0, y, -roomSize.depth / 2 + wallThickness / 2 + eps]
          let rot = [0, 0, 0]

          if (targetSeg) {
            if (targetSeg.axis === 'x') {
              pos = [targetSeg.cx, y, targetSeg.cz + targetSeg.normalZ * (wallThickness / 2 + eps)]
              rot = [0, targetSeg.normalZ > 0 ? 0 : Math.PI, 0]
            } else {
              pos = [targetSeg.cx + targetSeg.normalX * (wallThickness / 2 + eps), y, targetSeg.cz]
              rot = [0, targetSeg.normalX > 0 ? Math.PI / 2 : -Math.PI / 2, 0]
            }
          }

          return (
            <WindowMesh
              key={win.id || idx}
              position={pos}
              rotation={rot}
              widthM={wM}
              heightM={hM}
              depthM={wallThickness}
              onPointerDown={handleSelect}
            />
          )
        })}

        {/* Paredes dinámicas generadas automáticamente por el Constructor Sandbox */}
        {wallHeightFactor > 0.01 &&
          wallGeometry.segments
            .filter((seg) => !hiddenWallIds.includes(seg.id))
            .map((seg) => {
              const segH = seg.height * wallHeightFactor
              const boxArgs =
                seg.axis === 'x'
                  ? [seg.length, segH, wallThickness]
                  : [wallThickness, segH, seg.length]
              const meshName = `Pared-${seg.wallKey === 'north' ? 'Norte' : seg.wallKey === 'south' ? 'Sur' : seg.wallKey === 'west' ? 'Oeste' : 'Este'}-${seg.id}`

              return (
                <mesh
                  key={seg.id}
                  position={[seg.cx, segH / 2, seg.cz]}
                  castShadow
                  receiveShadow
                  onPointerDown={handleSelect}
                  name={meshName}
                  userData={{ wallSegmentId: seg.id, wallLabel: seg.label }}
                >
                  <boxGeometry args={boxArgs} />
                  <meshStandardMaterial
                    color={seg.isInterior ? '#dbeafe' : '#e5e7eb'}
                    roughness={0.9}
                    metalness={0.0}
                    emissive="#000000"
                  />
                </mesh>
              )
            })}

        <OrbitControls
          ref={controlsRef}
          makeDefault
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
        <Environment preset={getEnvironmentPreset(timeOfDay)} background={false} />
      </Canvas>
    </>
  )
}

createRoot(document.getElementById('r3f-root')).render(
  <ToastProvider>
    <Demo />
  </ToastProvider>
)

// Where do I change things?
// - Camera/Lights/Controls: resources/js/three/config.js (cameraConfig, lightsConfig, controlsConfig)
// - Loading 3D models: we’ll add a SceneLoader component (resources/js/three/loaders/SceneLoader.jsx)
// - Materials library & textures: resources/js/three/materials/library.js
// - Global state (current scene/material selections): resources/js/three/store.js
