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
import { CoverPreview, WallPiecePreview, WallCoverPreview } from './components/CoverPreviews'
import { DynamicSpot } from './components/DynamicSpot'
import { useRecommendations } from './hooks/useRecommendations'
import { ToastProvider, useToast } from './components/Toast'
import { WindowMesh } from './components/WindowMesh'

const WHITE_TEX_DATA_URL =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"><rect width="4" height="4" fill="#ffffff"/></svg>'
  )

// Componentes extraidos a components/
function Demo() {
  const { toast, confirm } = useToast()
  const [activeSurface, setActiveSurface] = useState('floor')
  const [activeWallKey, setActiveWallKey] = useState('north')
  const [floor, setFloor] = useState('wood')
  const [selected, setSelected] = useState(null)
  const [postFXOn, setPostFXOn] = useState(true)
  const [softShadowsBad, setSoftShadowsBad] = useState(false)
  const [dofOn, setDofOn] = useState(true)
  const [materials, setMaterials] = useState([])
  const [materialsLoading, setMaterialsLoading] = useState(true)
  const [materialsError, setMaterialsError] = useState(null)
  const [inventoryLive, setInventoryLive] = useState({})
  const [selectedMaterialId, setSelectedMaterialId] = useState(null)
  const [selectedWallMaterialId, setSelectedWallMaterialId] = useState(null)
  const [pieces, setPieces] = useState([])
  const [wallPieces, setWallPieces] = useState([])
  const [coverage, setCoverage] = useState({ canCompute: false, computed: false })
  const [coverEnabled, setCoverEnabled] = useState(false)
  const [wallCoverageState, setWallCoverageState] = useState({ canCompute: false, computed: false })
  const [wallCoverEnabled, setWallCoverEnabled] = useState(false)
  const [roomSizeCm, setRoomSizeCm] = useState({ width: 1000, depth: 1000, height: 300 })
  const [roomShape, setRoomShape] = useState('rectangular')
  const [windowConfig, setWindowConfig] = useState({
    enabled: false,
    widthCm: 160,
    heightCm: 120,
    sillHeightCm: 90,
  })
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
    () => materials.find((m) => Number(m.id) === Number(selectedMaterialId)) ?? null,
    [materials, selectedMaterialId]
  )

  const selectedWallMaterial = useMemo(
    () => materials.find((m) => Number(m.id) === Number(selectedWallMaterialId)) ?? null,
    [materials, selectedWallMaterialId]
  )

  const handleSelectMaterial = useCallback((next) => {
    setSelectedMaterialId(next)
    if (next) trackEvent('material_select', { productId: next, meta: { surface: 'floor' } })
  }, [trackEvent])

  const handleSelectWallMaterial = useCallback((next) => {
    setSelectedWallMaterialId(next)
    if (next) trackEvent('wall_material_select', { productId: next, meta: { surface: 'walls', wall: activeWallKey } })
  }, [trackEvent, activeWallKey])

  const handleUseRecommendation = useCallback((productId) => {
    if (!productId) return
    if (activeSurface === 'walls') {
      setSelectedWallMaterialId(productId)
    } else {
      setSelectedMaterialId(productId)
    }
    trackEvent('recommendation_click', { productId, meta: { surface: activeSurface, wall: activeWallKey } })
  }, [activeSurface, activeWallKey, trackEvent])

  const wallPiece = useMemo(() => wallPieces[0] ?? null, [wallPieces])

  const wallCoverageData = useMemo(() => {
    if (!wallPiece) return { canCompute: false, computed: false }
    const wallAreaM2 = 2 * (roomSize.width + roomSize.depth) * roomSize.height
    if (!Number.isFinite(wallAreaM2) || wallAreaM2 <= 0) return { canCompute: false, computed: false }

    const wCm = Number(wallPiece.width_cm || 0)
    const dCm = Number(wallPiece.depth_cm || 0)
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
  }, [wallPiece, roomSize])

  const buildScenePayload = useCallback(() => {
    return {
      version: 1,
      floor,
      roomShape,
      windowConfig,
      roomSizeCm,
      selectedMaterialId,
      selectedWallMaterialId,
      coverEnabled,
      wall: wallPiece ? {
        material_id: wallPiece?.material?.id ?? null,
        width_cm: wallPiece.width_cm,
        depth_cm: wallPiece.depth_cm,
        activeWallKey,
        coverEnabled: wallCoverEnabled,
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
  }, [floor, roomSizeCm, selectedMaterialId, selectedWallMaterialId, coverEnabled, pieces, wallPiece, activeWallKey, wallCoverEnabled])

  const resetSceneState = useCallback(() => {
    setSelectedSceneId(null)
    setSceneName('')
    setPieces([])
    setWallPieces([])
    setSelected(null)
    setCoverage({ canCompute: false, computed: false })
    setCoverEnabled(false)
    setWallCoverageState({ canCompute: false, computed: false })
    setWallCoverEnabled(false)
    window.localStorage.removeItem('three.lastSceneId')
  }, [])

  const hydrateScene = useCallback((sceneData) => {
    if (!sceneData || typeof sceneData !== 'object') return
    setFloor(sceneData.floor === 'ceramic' ? 'ceramic' : 'wood')

    if (sceneData.roomShape) {
      setRoomShape(sceneData.roomShape)
    }

    if (sceneData.windowConfig && typeof sceneData.windowConfig === 'object') {
      setWindowConfig({
        enabled: !!sceneData.windowConfig.enabled,
        widthCm: Number(sceneData.windowConfig.widthCm) || 160,
        heightCm: Number(sceneData.windowConfig.heightCm) || 120,
        sillHeightCm: Number(sceneData.windowConfig.sillHeightCm) || 90,
      })
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

    setCoverEnabled(!!sceneData.coverEnabled)

    const wallData = sceneData.wall && typeof sceneData.wall === 'object' ? sceneData.wall : null
    if (wallData) {
      if (wallData.activeWallKey) {
        setActiveWallKey(String(wallData.activeWallKey))
      }
      setWallCoverEnabled(!!wallData.coverEnabled)

      const wallMaterial = materials.find((m) => Number(m.id) === Number(wallData.material_id)) ?? selectedWallMaterial
      const wCm = Number(wallData.width_cm || 0)
      const dCm = Number(wallData.depth_cm || 0)
      if (wallMaterial && wCm > 0 && dCm > 0) {
        const id = nextId.current++
        setWallPieces([
          {
            id,
            kind: wallMaterial.kind,
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
      setWallCoverEnabled(false)
      setWallCoverageState({ canCompute: false, computed: false })
    }

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
  }, [materials, selectedMaterial, selectedWallMaterial, roomLimits.y])

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

  const quotePiece = useMemo(() => selectedPiece ?? pieces[0] ?? null, [selectedPiece, pieces])

  const hasValidPieceDims = useCallback((material) => {
    const dims = material?.piece_dimensions_cm
    const w = Number(dims?.width || 0)
    const d = Number(dims?.depth || 0)
    return Number.isFinite(w) && Number.isFinite(d) && w > 0 && d > 0
  }, [])

  const inventoryStatus = useMemo(() => {
    const material = quotePiece?.material ?? selectedMaterial
    if (!material) return { canCompute: false }

    const floorAreaM2 = (roomSizeCm.width / 100) * (roomSizeCm.depth / 100)
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
  }, [quotePiece, selectedMaterial, roomSizeCm, inventoryLive])

  const quoteProductId = quotePiece?.material?.id ?? null
  const wallProductId = wallPiece?.material?.id ?? null

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
  const canAdd = !materialsLoading && !materialsError && !!selectedMaterial && hasValidPieceDims(selectedMaterial)
  const addLabel = hasPiece ? 'Reemplazar pieza' : 'Agregar pieza'

  const addOnePiece = useCallback(async () => {
    if (!selectedMaterial) return
    if (!hasValidPieceDims(selectedMaterial)) {
      toast('Este material no tiene dimensiones de pieza configuradas (ancho/largo). Actualízalo en Productos para poder usarlo.', { type: 'warning', duration: 5000 })
      return
    }
    if (hasPiece) {
      const current = pieces[0]
      const currentLabel = current?.material?.name ? current.material.name : 'actual'
      const nextLabel = selectedMaterial.name
      const ok = await confirm(
        `Ya existe una pieza (${currentLabel}).\n\nSi continúas, se borrará y se agregará: ${nextLabel}.\n\n¿Deseas reemplazarla?`
      )
      if (!ok) return
    }
    const id = nextId.current++

    const dims = selectedMaterial?.piece_dimensions_cm
    const wCm = Number(dims?.width || 0)
    const dCm = Number(dims?.depth || 0)
    const useDimsSize = [wCm / 100, 0.12, dCm / 100]
    const lockFromProduct = !!dims?.locked

    const piece = {
      id,
      kind: selectedMaterial.kind,
      material: selectedMaterial,
      name: `Pieza-${id}`,
      position: [0, roomLimits.y, 0],
      rotation: [0, 0, 0],
      size: useDimsSize,
      scaleXZ: { x: 1, z: 1 },
      lockSizeXZ: lockFromProduct,
      textureUrl: selectedMaterial.image_url,
      roughness: selectedMaterial.kind === 'plank' ? 0.75 : 0.65,
      metalness: 0.0,
    }
    setPieces([piece])
    // Selección y enfoque: se asigna tras mount por referencia del evento de click.
    setSelected(null)
  }, [selectedMaterial, hasPiece, pieces, roomLimits.y, hasValidPieceDims])

  const hasWallPiece = wallPieces.length > 0
  const canAddWalls = !materialsLoading && !materialsError && !!selectedWallMaterial && hasValidPieceDims(selectedWallMaterial)
  const addLabelWalls = hasWallPiece ? 'Reemplazar pieza' : 'Agregar pieza'

  const addOneWallPiece = useCallback(async () => {
    if (!selectedWallMaterial) return
    if (hasWallPiece) {
      const current = wallPieces[0]
      const currentLabel = current?.material?.name ? current.material.name : 'actual'
      const nextLabel = selectedWallMaterial.name
      const ok = await confirm(
        `Ya existe una pieza de pared (${currentLabel}).\n\nSi continúas, se borrará y se agregará: ${nextLabel}.\n\n¿Deseas reemplazarla?`
      )
      if (!ok) return
    }

    if (!hasValidPieceDims(selectedWallMaterial)) {
      toast('Este material no tiene dimensiones de pieza configuradas (ancho/largo). Actualízalo en Productos para poder usarlo.', { type: 'warning', duration: 5000 })
      return
    }

    const dims = selectedWallMaterial?.piece_dimensions_cm
    const wCm = Number(dims?.width || 0)
    const dCm = Number(dims?.depth || 0)

    const id = nextId.current++
    const piece = {
      id,
      kind: selectedWallMaterial.kind,
      material: selectedWallMaterial,
      name: `ParedPieza-${id}`,
      width_cm: wCm,
      depth_cm: dCm,
      wallKey: activeWallKey,
      textureUrl: selectedWallMaterial.image_url || WHITE_TEX_DATA_URL,
      roughness: selectedWallMaterial.kind === 'plank' ? 0.75 : 0.65,
      metalness: 0.0,
    }

    setWallPieces([piece])
    setWallCoverageState({ canCompute: false, computed: false })
    setWallCoverEnabled(false)
    setSelected(null)
  }, [selectedWallMaterial, hasWallPiece, wallPieces, activeWallKey, hasValidPieceDims])

  const handleCoverWallsCompute = useCallback(() => {
    if (!wallCoverageData?.canCompute) {
      toast('Agrega una pieza en pared antes de calcular cobertura.', { type: 'warning' })
      return
    }
    setWallCoverageState(wallCoverageData)
    setWallCoverEnabled(true)
  }, [wallCoverageData])

  const toggleWallCover = useCallback(() => {
    setWallCoverEnabled((v) => !v)
  }, [])

  const handleGenerateQuote = useCallback(async () => {
    if (!quoteSummary?.ready || !quotePiece || !quoteSizeCm || quoteLoading) return

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
            material_id: wallPiece?.material?.id ?? null,
            piece: wallPiece
              ? {
                  kind: wallPiece.kind,
                  width_cm: wallPiece.width_cm,
                  depth_cm: wallPiece.depth_cm,
                }
              : null,
          },
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
      toast('No se pudo generar la cotización PDF. Revisa la configuración actual e inténtalo de nuevo.', { type: 'error', duration: 5500 })
    } finally {
      setQuoteLoading(false)
    }
  }, [quoteSummary, quotePiece, quoteSizeCm, quoteLoading, floor, roomSizeCm, inventoryStatus, captureTopDownSnapshot, wallPiece, sceneName, selectedSceneId, trackEvent, saveScene])

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
        wallCoverage={{ ...wallCoverageState, ...wallCoverageData, canCompute: wallCoverageData.canCompute, computed: wallCoverageState.computed }}
        onAddWalls={addOneWallPiece}
        canAddWalls={canAddWalls}
        addLabelWalls={addLabelWalls}
        onCoverWalls={handleCoverWallsCompute}
        onToggleCoverWalls={toggleWallCover}
        wallCoverEnabled={wallCoverEnabled}
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
        roomShape={roomShape}
        onRoomShapeChange={setRoomShape}
        windowConfig={windowConfig}
        onWindowConfigChange={setWindowConfig}
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
          <div style={{fontSize:12, opacity:0.8, marginBottom:10, lineHeight:1.25}}>
            Selecciona piso/pared para cambiar materiales.
          </div>
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

        <Floor kind={floor} width={roomSize.width} depth={roomSize.depth} onPointerDown={handleSelect} name="Piso" />

        {/* Preview de cobertura (instanced, liviano) */}
        <CoverPreview
          enabled={coverEnabled && coverage?.computed}
          piece={pieces[0]}
          pieceSizeCm={coverageData?.computed ? { x: coverageData.pieceCmX, z: coverageData.pieceCmZ } : null}
          roomSize={roomSize}
          textureUrl={pieces[0]?.textureUrl}
        />

        {/* Preview: pieza centrada en la pared activa */}
        <WallPiecePreview
          piece={wallPiece}
          roomSize={roomSize}
          wallThickness={wallThickness}
          activeWallKey={activeWallKey}
          onPointerDown={handleSelect}
        />

        {/* Preview de cobertura en paredes (instanced) */}
        <WallCoverPreview
          enabled={wallCoverEnabled && wallCoverageState?.computed}
          piece={wallPiece}
          roomSize={roomSize}
          wallThickness={wallThickness}
          textureUrl={wallPiece?.textureUrl}
        />

        {/* WindowMesh 3D Paramétrica en la pared activa */}
        {windowConfig.enabled && (
          <WindowMesh
            position={
              activeWallKey === 'south'
                ? [0, ((windowConfig.sillHeightCm || 90) + (windowConfig.heightCm || 120) / 2) / 100, roomSize.depth / 2 - wallThickness / 2 - 0.002]
                : activeWallKey === 'west'
                ? [-roomSize.width / 2 + wallThickness / 2 + 0.002, ((windowConfig.sillHeightCm || 90) + (windowConfig.heightCm || 120) / 2) / 100, 0]
                : activeWallKey === 'east'
                ? [roomSize.width / 2 - wallThickness / 2 - 0.002, ((windowConfig.sillHeightCm || 90) + (windowConfig.heightCm || 120) / 2) / 100, 0]
                : [0, ((windowConfig.sillHeightCm || 90) + (windowConfig.heightCm || 120) / 2) / 100, -roomSize.depth / 2 + wallThickness / 2 + 0.002]
            }
            rotation={
              activeWallKey === 'south'
                ? [0, Math.PI, 0]
                : activeWallKey === 'west'
                ? [0, Math.PI / 2, 0]
                : activeWallKey === 'east'
                ? [0, -Math.PI / 2, 0]
                : [0, 0, 0]
            }
            widthM={(windowConfig.widthCm || 160) / 100}
            heightM={(windowConfig.heightCm || 120) / 100}
            depthM={wallThickness}
            onPointerDown={handleSelect}
          />
        )}

        {/* Piezas dinámicas (inicia vacío) */}
        {pieces.map((p) => (
          <TexturedPieceMesh key={p.id} piece={p} onPointerDown={handleSelect} />
        ))}
        {/* Paredes adaptables al tamaño configurado del cuarto */}
        {/* Pared norte */}
        <mesh position={[0, roomSize.height / 2, -roomSize.depth / 2]} castShadow onPointerDown={handleSelect} name="Pared-Norte">
          <boxGeometry args={[roomSize.width, roomSize.height, wallThickness]} />
          <meshStandardMaterial color={'#e5e7eb'} roughness={0.9} metalness={0.0} emissive="#000000" />
        </mesh>
        {/* Pared sur */}
        <mesh position={[0, roomSize.height / 2, roomSize.depth / 2]} castShadow onPointerDown={handleSelect} name="Pared-Sur">
          <boxGeometry args={[roomSize.width, roomSize.height, wallThickness]} />
          <meshStandardMaterial color={'#e5e7eb'} roughness={0.9} metalness={0.0} emissive="#000000" />
        </mesh>
        {/* Pared oeste */}
        <mesh position={[-roomSize.width / 2, roomSize.height / 2, 0]} castShadow onPointerDown={handleSelect} name="Pared-Oeste">
          <boxGeometry args={[wallThickness, roomSize.height, roomSize.depth]} />
          <meshStandardMaterial color={'#e5e7eb'} roughness={0.9} metalness={0.0} emissive="#000000" />
        </mesh>
        {/* Pared este (con ventana simple hueco central) */}
        {/* Simplificación: dos columnas y travesaño arriba dejando hueco */}
        <group name="Pared-Este">
          <mesh position={[roomSize.width / 2, roomSize.height - eastLintelHeight / 2, 0]} castShadow onPointerDown={handleSelect} name="Marco-Este-Arriba">
            <boxGeometry args={[wallThickness, eastLintelHeight, roomSize.depth]} />
            <meshStandardMaterial color={'#e5e7eb'} roughness={0.9} metalness={0.0} emissive="#000000" />
          </mesh>
          <mesh position={[roomSize.width / 2, eastColumnHeight / 2, -(eastOpeningDepth / 2 + eastSideDepth / 2)]} castShadow onPointerDown={handleSelect} name="Columna-Este-1">
            <boxGeometry args={[wallThickness, eastColumnHeight, eastSideDepth]} />
            <meshStandardMaterial color={'#e5e7eb'} roughness={0.9} metalness={0.0} emissive="#000000" />
          </mesh>
          <mesh position={[roomSize.width / 2, eastColumnHeight / 2, eastOpeningDepth / 2 + eastSideDepth / 2]} castShadow onPointerDown={handleSelect} name="Columna-Este-2">
            <boxGeometry args={[wallThickness, eastColumnHeight, eastSideDepth]} />
            <meshStandardMaterial color={'#e5e7eb'} roughness={0.9} metalness={0.0} emissive="#000000" />
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
