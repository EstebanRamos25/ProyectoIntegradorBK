import React, { useEffect } from 'react'
import { useThree } from '@react-three/fiber'
import { useTexture } from '@react-three/drei'
import { SRGBColorSpace } from 'three'

const WHITE_TEX_DATA_URL =
  'data:image/svg+xml;utf8,' +
  encodeURIComponent(
    '<svg xmlns="http://www.w3.org/2000/svg" width="4" height="4"><rect width="4" height="4" fill="#ffffff"/></svg>'
  )

export function TexturedPieceMesh({ piece, onPointerDown }) {
  const { gl } = useThree()
  const map = useTexture(piece?.textureUrl || WHITE_TEX_DATA_URL)

  useEffect(() => {
    if (!map) return
    map.colorSpace = SRGBColorSpace
    map.wrapS = map.wrapT = 1000 // RepeatWrapping
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
