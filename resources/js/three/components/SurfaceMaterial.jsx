import React, { useEffect } from 'react'
import { useThree } from '@react-three/fiber'
import { SRGBColorSpace } from 'three'
import { useSafeTexture } from '../hooks/useSafeTexture'

export function SurfaceMaterial({ textureUrl, repeat = [1, 1], opacity = 1, transparent = false }) {
  const { gl } = useThree()
  const map = useSafeTexture(textureUrl)

  useEffect(() => {
    if (!map) return
    map.colorSpace = SRGBColorSpace
    map.wrapS = map.wrapT = 1000
    map.repeat.set(Math.max(0.01, Number(repeat?.[0] ?? 1)), Math.max(0.01, Number(repeat?.[1] ?? 1)))
    map.anisotropy = Math.min(8, gl?.capabilities?.getMaxAnisotropy?.() ?? 8)
    map.needsUpdate = true
  }, [map, gl, repeat])

  return (
    <meshStandardMaterial
      map={map}
      color={'#ffffff'}
      emissive="#000000"
      roughness={0.75}
      metalness={0.0}
      opacity={opacity}
      transparent={transparent}
    />
  )
}
