import React, { useRef } from 'react'
import { useFrame } from '@react-three/fiber'

export function DynamicSpot({ selected }) {
  const ref = useRef()
  const targetRef = useRef()
  useFrame((_, dt) => {
    if (!ref.current || !targetRef.current) return
    const spot = ref.current
    const tgt = targetRef.current
    const basePos = selected ? selected.position : { x:0, y:0, z:0 }
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
