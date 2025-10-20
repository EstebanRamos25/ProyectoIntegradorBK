import React, { useState } from 'react'
import { createRoot } from 'react-dom/client'
import { Canvas, useFrame } from '@react-three/fiber'
import { OrbitControls, Environment } from '@react-three/drei'

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
      <Controls floor={floor} setFloor={setFloor} />
      <Canvas shadows camera={{ position: [4, 4, 6], fov: 50 }}>
        <ambientLight intensity={0.6} />
        <directionalLight castShadow position={[5, 8, 5]} intensity={1.0} />
        <Floor kind={floor} />
        <mesh position={[0, 0.5, 0]} castShadow>
          <boxGeometry args={[1,1,1]} />
          <meshStandardMaterial color="#90caf9" />
        </mesh>
        <OrbitControls makeDefault />
        <Environment preset="city" />
      </Canvas>
    </>
  )
}

createRoot(document.getElementById('r3f-root')).render(<Demo />)
