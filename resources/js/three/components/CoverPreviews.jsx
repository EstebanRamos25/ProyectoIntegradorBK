import React, { useRef, useMemo, useEffect } from 'react'
import { useTexture } from '@react-three/drei'
import { SRGBColorSpace, Object3D } from 'three'
import { SurfaceMaterial } from './SurfaceMaterial'
import { isPointInActiveCell, normalizeGrid } from '../roomGeometry'

const WHITE_TEX_DATA_URL =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"><rect width="4" height="4" fill="#ffffff"/></svg>'
  )

/**
 * CoverPreview - Cobertura del Piso (respeta la forma personalizada de la cuadrícula).
 * @param {boolean} solid - true: acabado continuo sólido (100% opaco sin juntas abiertas). false: modo grilla con separación visible entre piezas.
 */
export function CoverPreview({
  enabled,
  piece,
  pieceSizeCm,
  roomSize,
  roomShape = 'rectangular',
  customGrid = null,
  textureUrl,
  solid = false,
  onPointerDown,
}) {
  const MAX_PREVIEW = 1600
  const instancesRef = useRef()
  const map = useTexture(textureUrl || piece?.textureUrl || WHITE_TEX_DATA_URL)

  useEffect(() => {
    if (!map) return
    map.colorSpace = SRGBColorSpace
    map.wrapS = map.wrapT = 1000
    map.repeat.set(1, 1)
    map.needsUpdate = true
  }, [map])

  const preview = useMemo(() => {
    if (!enabled || !piece || !pieceSizeCm) return null
    const baseW = (Number(pieceSizeCm.x || 60) / 100) * (piece.scaleXZ?.x ?? 1)
    const baseD = (Number(pieceSizeCm.z || 60) / 100) * (piece.scaleXZ?.z ?? 1)
    if (baseW <= 0 || baseD <= 0) return null

    const fullCols = Math.max(1, Math.ceil(roomSize.width / baseW))
    const fullRows = Math.max(1, Math.ceil(roomSize.depth / baseD))
    const fullTotal = fullCols * fullRows

    const stride = fullTotal <= MAX_PREVIEW ? 1 : Math.ceil(Math.sqrt(fullTotal / MAX_PREVIEW))
    const tileW = baseW * stride
    const tileD = baseD * stride
    const cols = Math.max(1, Math.ceil(roomSize.width / tileW))
    const rows = Math.max(1, Math.ceil(roomSize.depth / tileD))

    const cleanGrid = normalizeGrid(customGrid, roomShape)
    const placements = []

    for (let r = 0; r < rows; r++) {
      for (let c = 0; c < cols; c++) {
        if (placements.length >= MAX_PREVIEW) break
        const xStart = -roomSize.width / 2 + c * tileW
        const zStart = -roomSize.depth / 2 + r * tileD
        const actualW = Math.min(tileW, roomSize.width / 2 - xStart)
        const actualD = Math.min(tileD, roomSize.depth / 2 - zStart)
        if (actualW <= 0.01 || actualD <= 0.01) continue

        const cx = xStart + actualW / 2
        const cz = zStart + actualD / 2

        if (isPointInActiveCell(cx, cz, cleanGrid, roomSize)) {
          placements.push({
            cx,
            cz,
            w: actualW,
            d: actualD,
          })
        }
      }
    }

    if (!placements.length) return null
    return { placements }
  }, [enabled, piece, pieceSizeCm, roomSize, roomShape, customGrid])

  useEffect(() => {
    if (!instancesRef.current || !preview) return
    const inst = instancesRef.current
    const dummy = new Object3D()
    // En modo Grilla dejamos una separación clara (90%) para apreciar cada pieza; en Sólido 100% continuo
    const gapFactor = solid ? 1.0 : 0.9

    for (let i = 0; i < preview.placements.length; i++) {
      const p = preview.placements[i]
      dummy.position.set(p.cx, 0.018, p.cz)
      dummy.rotation.set(0, 0, 0)
      dummy.scale.set(p.w * gapFactor, 1, p.d * gapFactor)
      dummy.updateMatrix()
      inst.setMatrixAt(i, dummy.matrix)
    }
    inst.count = preview.placements.length
    inst.instanceMatrix.needsUpdate = true
  }, [preview, solid])

  if (!preview) return null

  const opacity = solid ? 1.0 : 0.85
  const isTransparent = !solid

  return (
    <instancedMesh
      key={`floor-inst-${preview.placements.length}-${solid ? 'solid' : 'grid'}`}
      ref={instancesRef}
      args={[null, null, preview.placements.length]}
      receiveShadow
      castShadow
      onPointerDown={onPointerDown}
      name="Piso"
    >
      <boxGeometry args={[1, 0.025, 1]} />
      <meshStandardMaterial
        key={solid ? 'floor-mat-solid' : 'floor-mat-grid'}
        map={map}
        color={'#ffffff'}
        roughness={solid ? (piece.roughness ?? 0.7) : 0.8}
        metalness={piece.metalness ?? 0.0}
        opacity={opacity}
        transparent={isTransparent}
      />
    </instancedMesh>
  )
}

export function WallPiecePreview({ piece, roomSize, wallThickness, activeWallKey, onPointerDown }) {
  if (!piece) return null
  const tileT = 0.04
  const w = Number(piece.width_cm || 60) / 100
  const h = Number(piece.depth_cm || 60) / 100
  if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) return null

  const eps = 0.004
  const halfT = tileT / 2

  const placement = useMemo(() => {
    switch (activeWallKey) {
      case 'south':
        return {
          position: [0, roomSize.height / 2, roomSize.depth / 2 - wallThickness / 2 - halfT - eps],
          rotation: [0, Math.PI, 0],
        }
      case 'west':
        return {
          position: [-roomSize.width / 2 + wallThickness / 2 + halfT + eps, roomSize.height / 2, 0],
          rotation: [0, Math.PI / 2, 0],
        }
      case 'east':
        return {
          position: [roomSize.width / 2 - wallThickness / 2 - halfT - eps, roomSize.height / 2, 0],
          rotation: [0, -Math.PI / 2, 0],
        }
      case 'north':
      default:
        return {
          position: [0, roomSize.height / 2, -roomSize.depth / 2 + wallThickness / 2 + halfT + eps],
          rotation: [0, 0, 0],
        }
    }
  }, [activeWallKey, roomSize, wallThickness, halfT])

  return (
    <mesh
      name={piece.name}
      position={placement.position}
      rotation={placement.rotation}
      castShadow
      receiveShadow
      onPointerDown={onPointerDown}
    >
      <boxGeometry args={[w, h, tileT]} />
      <SurfaceMaterial textureUrl={piece.textureUrl} repeat={[1, 1]} opacity={0.95} />
    </mesh>
  )
}

/**
 * WallCoverPreview - Cobertura de Paredes adaptable a todos los segmentos del cuarto personalizado.
 * @param {boolean} solid - true: sólido opaco continuo. false: grilla con juntas visibles entre baldosas.
 */
export function WallCoverPreview({
  enabled,
  piece,
  roomSize,
  wallThickness,
  wallSegments = [],
  wallHeightFactor = 1.0,
  hiddenWallIds = [],
  textureUrl,
  solid = false,
  onPointerDown,
}) {
  const MAX_PREVIEW = 2000
  const instancesRef = useRef()
  const map = useTexture((textureUrl || piece?.textureUrl) ?? WHITE_TEX_DATA_URL)

  useEffect(() => {
    if (!map) return
    map.colorSpace = SRGBColorSpace
    map.wrapS = map.wrapT = 1000
    map.repeat.set(1, 1)
    map.needsUpdate = true
  }, [map])

  const preview = useMemo(() => {
    if (!enabled || !piece || wallHeightFactor <= 0.01) return null
    const baseW = Number(piece.width_cm || 60) / 100
    const baseH = Number(piece.depth_cm || 60) / 100
    if (!Number.isFinite(baseW) || !Number.isFinite(baseH) || baseW <= 0 || baseH <= 0) return null

    const visibleSegments = (wallSegments || []).filter(
      (s) => !hiddenWallIds.includes(s.id)
    )
    if (!visibleSegments.length) return null

    const effectiveHeight = roomSize.height * wallHeightFactor
    const estimatedRaw = visibleSegments.reduce((acc, s) => {
      const c = Math.max(1, Math.ceil(s.length / baseW))
      const r = Math.max(1, Math.ceil(effectiveHeight / baseH))
      return acc + c * r * (s.isInterior ? 2 : 1)
    }, 0)

    const stride = estimatedRaw <= MAX_PREVIEW ? 1 : Math.ceil(Math.sqrt(estimatedRaw / MAX_PREVIEW))
    const tileW = baseW * stride
    const tileH = baseH * stride

    const tileT = 0.022
    const eps = 0.005
    const offsetFromWallCenter = wallThickness / 2 + tileT / 2 + eps

    const placements = []

    for (const seg of visibleSegments) {
      const cols = Math.max(1, Math.ceil(seg.length / tileW))
      const rows = Math.max(1, Math.ceil(effectiveHeight / tileH))
      const faces = seg.isInterior ? [1, -1] : [1]

      for (const faceSign of faces) {
        for (let r = 0; r < rows; r++) {
          for (let c = 0; c < cols; c++) {
            if (placements.length >= MAX_PREVIEW) break

            const localAlongStart = -seg.length / 2 + c * tileW
            const localYStart = r * tileH
            const actualW = Math.min(tileW, seg.length / 2 - localAlongStart)
            const actualH = Math.min(tileH, effectiveHeight - localYStart)
            if (actualW <= 0.01 || actualH <= 0.01) continue

            const localAlongCenter = localAlongStart + actualW / 2
            const py = localYStart + actualH / 2

            let px = seg.cx
            let pz = seg.cz
            let ry = 0

            if (seg.axis === 'x') {
              px = seg.cx + localAlongCenter
              pz = seg.cz + seg.normalZ * faceSign * offsetFromWallCenter
              ry = seg.normalZ * faceSign > 0 ? 0 : Math.PI
            } else {
              pz = seg.cz + localAlongCenter
              px = seg.cx + seg.normalX * faceSign * offsetFromWallCenter
              ry = seg.normalX * faceSign > 0 ? Math.PI / 2 : -Math.PI / 2
            }

            placements.push({
              px,
              py,
              pz,
              ry,
              w: actualW,
              h: actualH,
              t: tileT,
            })
          }
        }
      }
    }

    if (!placements.length) return null
    return { placements }
  }, [enabled, piece, roomSize, wallThickness, wallSegments, wallHeightFactor, hiddenWallIds])

  useEffect(() => {
    if (!instancesRef.current || !preview) return
    const inst = instancesRef.current
    const dummy = new Object3D()
    // En modo Grilla dejamos junta visible (90%); en modo Sólido 100% continuo
    const gapFactor = solid ? 1.0 : 0.9

    for (let i = 0; i < preview.placements.length; i++) {
      const p = preview.placements[i]
      dummy.position.set(p.px, p.py, p.pz)
      dummy.rotation.set(0, p.ry, 0)
      dummy.scale.set(p.w * gapFactor, p.h * gapFactor, p.t)
      dummy.updateMatrix()
      inst.setMatrixAt(i, dummy.matrix)
    }
    inst.count = preview.placements.length
    inst.instanceMatrix.needsUpdate = true
  }, [preview, solid])

  if (!preview) return null

  const opacity = solid ? 1.0 : 0.85
  const isTransparent = !solid

  return (
    <instancedMesh
      key={`wall-inst-${preview.placements.length}-${solid ? 'solid' : 'grid'}`}
      ref={instancesRef}
      args={[null, null, preview.placements.length]}
      receiveShadow
      castShadow
      onPointerDown={onPointerDown}
      name="Pared-Revestimiento"
    >
      <boxGeometry args={[1, 1, 1]} />
      <meshStandardMaterial
        key={solid ? 'wall-mat-solid' : 'wall-mat-grid'}
        map={map}
        color={'#ffffff'}
        roughness={solid ? 0.68 : 0.78}
        metalness={0.0}
        opacity={opacity}
        transparent={isTransparent}
      />
    </instancedMesh>
  )
}
