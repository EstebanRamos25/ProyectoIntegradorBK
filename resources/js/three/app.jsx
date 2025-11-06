import React, { useState } from 'react'
import { createRoot } from 'react-dom/client'
import { Canvas } from '@react-three/fiber'
import { OrbitControls, Environment } from '@react-three/drei'
import { cameraConfig, controlsConfig, lightsConfig } from './config'

function Floor({ kind }) {
  // Two simple materials: wood vs ceramic using basic colors for MVP
  const color = kind === 'wood' ? '#8b5a2b' : '#cfd8dc'
  return (
    <mesh rotation={[-Math.PI/2,0,0]} receiveShadow>
      <planeGeometry args={[10,10,1,1]} />
      <meshStandardMaterial color={color} roughness={0.9} metalness={0.0} />
    </mesh>
  )
}

function Controls({ floor, setFloor }) {
  return (
    <div style={{position:'fixed', top:12, left:12, zIndex:10, background:'rgba(0,0,0,.6)', color:'#fff', padding:'10px 12px', borderRadius:8, fontFamily:'system-ui,Arial,sans-serif'}}>
      <button onClick={() => setFloor('wood')} style={{marginRight:8, padding:'6px 10px'}}>Piso madera</button>
      <button onClick={() => setFloor('ceramic')} style={{padding:'6px 10px'}}>Piso cerámica</button>
    </div>
  )
}

function Demo() {
  const [floor, setFloor] = useState('wood')

  return (
    <>
      {/* UI: change materials here */}
      <Controls floor={floor} setFloor={setFloor} />
      <Canvas shadows camera={{ position: cameraConfig.position, fov: cameraConfig.fov }}>
        {/* Lights: edit defaults in resources/js/three/config.js */}
        <ambientLight intensity={lightsConfig.ambient.intensity} />
        <directionalLight castShadow position={lightsConfig.directional.position} intensity={lightsConfig.directional.intensity} />
        <Floor kind={floor} />
        <mesh position={[0, 0.5, 0]} castShadow>
          <boxGeometry args={[1,1,1]} />
          <meshStandardMaterial color="#90caf9" />
        </mesh>
        {/* Camera controls: sensitivity & limits live in config.js */}
        <OrbitControls makeDefault
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

createRoot(document.getElementById('r3f-root')).render(<Demo />)

// Where do I change things?
// - Camera/Lights/Controls: resources/js/three/config.js (cameraConfig, lightsConfig, controlsConfig)
// - Loading 3D models: we’ll add a SceneLoader component (resources/js/three/loaders/SceneLoader.jsx)
// - Materials library & textures: resources/js/three/materials/library.js
// - Global state (current scene/material selections): resources/js/three/store.js
