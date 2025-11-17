import React, { useRef, useState } from 'react'
import { createRoot } from 'react-dom/client'
import { Canvas, useFrame } from '@react-three/fiber'
import { SoftShadows, Float, CameraControls, Sky, PerformanceMonitor, Loader } from '@react-three/drei'
import { easing } from 'maath'
import { Leva } from 'leva'
import { Perf } from 'r3f-perf'
// Nota: no usamos leva ni r3f-perf aún para simplificar la integración inicial
import { Room } from './Room'

function Light() {
  const ref = useRef()
  useFrame((state, delta) => {
    easing.dampE(
      ref.current.rotation,
      [(state.pointer.y * Math.PI) / 50, (state.pointer.x * Math.PI) / 20, 0],
      0.2,
      delta,
    )
  })
  return (
    <group ref={ref}>
      <directionalLight
        position={[5, 5, -8]}
        castShadow
        intensity={5}
        shadow-mapSize={2048}
        shadow-bias={-0.001}
      >
        <orthographicCamera attach="shadow-camera" args={[-8.5, 8.5, 8.5, -8.5, 0.1, 20]} />
      </directionalLight>
    </group>
  )
}

function Sphere({ color = 'hotpink', floatIntensity = 15, position = [0, 5, -8], scale = 1 }) {
  return (
    <Float floatIntensity={floatIntensity}>
      <mesh castShadow position={position} scale={scale}>
        <sphereGeometry />
        <meshBasicMaterial color={color} />
      </mesh>
    </Float>
  )
}

function RoomScene() {
  const [bad, setBad] = useState(false)
  const [showPerf, setShowPerf] = useState(true)
  const [config, setConfig] = useState({ size: 35, focus: 0.5, samples: 16, enabled: true })

  return (
    <>
      <div style={{position:'fixed',top:10,left:10,zIndex:10,display:'flex',gap:8}}>
        <button onClick={()=>window.location.href='/3d'} style={{padding:'6px 10px'}}>Ir a Demo base</button>
        <button onClick={()=>setShowPerf((v)=>!v)} style={{padding:'6px 10px'}}>Perf {showPerf?'ON':'OFF'}</button>
      </div>
      <Canvas shadows camera={{ position: [5, 2, 10], fov: 50 }}>
        {showPerf && <Perf position="top-left" />}
        <PerformanceMonitor onDecline={() => setBad(true)} />
        {config.enabled && (
          <SoftShadows
            size={config.size}
            focus={config.focus}
            samples={bad ? Math.min(6, config.samples) : config.samples}
          />
        )}
        <CameraControls makeDefault />
        <color attach="background" args={['#d0d0d0']} />
        <fog attach="fog" args={['#d0d0d0', 8, 35]} />
        <ambientLight intensity={0.4} />
        <Light />
        <Room scale={0.5} position={[0, -1, 0]} />
        <Sphere />
        <Sphere position={[2, 4, -8]} scale={0.9} />
        <Sphere position={[-2, 2, -8]} scale={0.8} />
        <Sky inclination={0.52} scale={20} />
      </Canvas>
      <Loader />
      <Leva collapsed />
    </>
  )
}

if (document.getElementById('r3f-room-root')) {
  createRoot(document.getElementById('r3f-room-root')).render(<RoomScene />)
}
