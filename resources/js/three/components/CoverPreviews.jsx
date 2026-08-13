import React, { useRef, useMemo, useEffect } from 'react'
import { useTexture } from '@react-three/drei'
import { SRGBColorSpace, Object3D } from 'three'
import { SurfaceMaterial } from './SurfaceMaterial'

const WHITE_TEX_DATA_URL =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"><rect width="4" height="4" fill="#ffffff"/></svg>'
  )

export function CoverPreview({ enabled, piece, pieceSizeCm, roomSize, textureUrl }) {
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

export function WallPiecePreview({ piece, roomSize, wallThickness, activeWallKey, onPointerDown }) {
  if (!piece) return null
  const tileT = 0.06
  const w = Number(piece.width_cm || 0) / 100
  const h = Number(piece.depth_cm || 0) / 100
  if (!Number.isFinite(w) || !Number.isFinite(h) || w <= 0 || h <= 0) return null

  const eps = 0.002
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

export function WallCoverPreview({ enabled, piece, roomSize, wallThickness, textureUrl }) {
  const MAX_PREVIEW = 800
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
    if (!enabled || !piece) return null
    const pieceW = Number(piece.width_cm || 0) / 100
    const pieceH = Number(piece.depth_cm || 0) / 100
    if (!Number.isFinite(pieceW) || !Number.isFinite(pieceH) || pieceW <= 0 || pieceH <= 0) return null

    const colsW = Math.max(1, Math.ceil(roomSize.width / pieceW))
    const colsD = Math.max(1, Math.ceil(roomSize.depth / pieceW))
    const rows = Math.max(1, Math.ceil(roomSize.height / pieceH))
    const fullTotal = 2 * (colsW * rows) + 2 * (colsD * rows)

    const stride = fullTotal <= MAX_PREVIEW ? 1 : Math.ceil(Math.sqrt(fullTotal / MAX_PREVIEW))
    const tileW = pieceW * stride
    const tileH = pieceH * stride
    const colsW2 = Math.max(1, Math.ceil(colsW / stride))
    const colsD2 = Math.max(1, Math.ceil(colsD / stride))
    const rows2 = Math.max(1, Math.ceil(rows / stride))
    const total = Math.min(MAX_PREVIEW, 2 * (colsW2 * rows2) + 2 * (colsD2 * rows2))
    return { pieceW, pieceH, tileW, tileH, colsW2, colsD2, rows2, stride, total }
  }, [enabled, piece, roomSize])

  useEffect(() => {
    if (!instancesRef.current || !preview) return
    const inst = instancesRef.current
    const dummy = new Object3D()

    const tileT = 0.06
    const eps = 0.002
    const halfT = tileT / 2
    let i = 0

    const walls = [
      { key: 'north', cols: preview.colsW2 },
      { key: 'south', cols: preview.colsW2 },
      { key: 'west', cols: preview.colsD2 },
      { key: 'east', cols: preview.colsD2 },
    ]

    for (const w of walls) {
      for (let r = 0; r < preview.rows2; r++) {
        for (let c = 0; c < w.cols; c++) {
          if (i >= preview.total) break

          let px = 0, py = 0, pz = 0
          let ry = 0

          py = preview.tileH / 2 + r * preview.tileH
          
          if (w.key === 'north' || w.key === 'south') {
            px = -roomSize.width / 2 + preview.tileW / 2 + c * preview.tileW
            if (w.key === 'north') {
              pz = -roomSize.depth / 2 + wallThickness / 2 + halfT + eps
              ry = 0
            } else {
              pz = roomSize.depth / 2 - wallThickness / 2 - halfT - eps
              ry = Math.PI
            }
          } else {
            pz = -roomSize.depth / 2 + preview.tileW / 2 + c * preview.tileW
            if (w.key === 'west') {
              px = -roomSize.width / 2 + wallThickness / 2 + halfT + eps
              ry = Math.PI / 2
            } else {
              px = roomSize.width / 2 - wallThickness / 2 - halfT - eps
              ry = -Math.PI / 2
            }
          }

          dummy.position.set(px, py, pz)
          dummy.rotation.set(0, ry, 0)
          
          const sx = preview.stride
          const sy = preview.stride
          dummy.scale.set(sx, sy, 1)
          
          dummy.updateMatrix()
          inst.setMatrixAt(i, dummy.matrix)
          i++
        }
      }
    }
    inst.instanceMatrix.needsUpdate = true
  }, [preview, piece, roomSize, wallThickness])

  if (!preview) return null

  return (
    <instancedMesh ref={instancesRef} args={[null, null, preview.total]} receiveShadow castShadow>
      <boxGeometry args={[preview.pieceW, preview.pieceH, 0.06]} />
      <meshStandardMaterial
        map={map}
        color={'#ffffff'}
        roughness={0.75}
        metalness={0.0}
        opacity={0.55}
        transparent
      />
    </instancedMesh>
  )
}
