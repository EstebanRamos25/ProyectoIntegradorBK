import React, { Suspense } from 'react'
import { useGLTF } from '@react-three/drei'

// SceneLoader: loads a GLB and exposes named nodes for slotting materials
export function SceneLoader({ url, onReady }) {
  const { scene, nodes, materials } = useGLTF(url)
  React.useEffect(() => {
    onReady && onReady({ scene, nodes, materials })
  }, [onReady, scene, nodes, materials])
  return <primitive object={scene} />
}

useGLTF.preload('/storage/3d/demo-room.glb')
