import { useTexture } from '@react-three/drei'
import * as THREE from 'three'

// Minimal material library for floor/walls using PBR textures
// Expect files under /storage/3d/textures/{name}/
export function useFloorMaterial(kind = 'wood') {
  if (kind === 'wood') {
    const map = useTexture('/storage/3d/textures/wood/albedo.jpg')
    map.wrapS = map.wrapT = THREE.RepeatWrapping
    map.repeat.set(2, 2)
    return <meshStandardMaterial map={map} roughness={0.7} metalness={0.0} />
  }
  if (kind === 'ceramic') {
    const map = useTexture('/storage/3d/textures/ceramic/albedo.jpg')
    map.wrapS = map.wrapT = THREE.RepeatWrapping
    map.repeat.set(3, 3)
    return <meshStandardMaterial map={map} roughness={0.4} metalness={0.0} />
  }
  // fallback color
  return <meshStandardMaterial color={kind === 'wood' ? '#8b5a2b' : '#27beffff'} />
}
