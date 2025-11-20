import React, { useState, useCallback, useRef } from 'react'
import { createRoot } from 'react-dom/client'
import { Canvas, useFrame } from '@react-three/fiber'
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
  const [selected, setSelected] = useState(null)
  const lastEmissive = useRef({ obj: null, color: null })
  const controlsRef = useRef(null)

  const clearSelectionVisual = useCallback(() => {
    if (lastEmissive.current.obj && lastEmissive.current.obj.material?.emissive) {
      lastEmissive.current.obj.material.emissive.set(lastEmissive.current.color)
      lastEmissive.current.obj = null
    }
  }, [])

  const handlePointerMissed = useCallback(() => {
    clearSelectionVisual()
    setSelected(null)
  }, [clearSelectionVisual])

  const animateCameraTo = useCallback((obj) => {
    const controls = controlsRef.current
    const cam = controls?.object
    if (!controls || !cam || !obj) return
    const startPos = cam.position.clone()
    const endPos = obj.position.clone().add({ x: 2, y: 2, z: 2 })
    const startTarget = controls.target.clone()
    const endTarget = obj.position.clone()
    const duration = 0.8
    let t = 0
    function step() {
      t += 1/60
      const alpha = Math.min(t / duration, 1)
      cam.position.lerpVectors(startPos, endPos, alpha)
      controls.target.lerpVectors(startTarget, endTarget, alpha)
      controls.update()
      if (alpha < 1) requestAnimationFrame(step)
    }
    requestAnimationFrame(step)
  }, [])

  const resetCamera = useCallback(() => {
    const controls = controlsRef.current
    const cam = controls?.object
    if (!controls || !cam) return
    controls.target.set(0,0,0)
    cam.position.set(...cameraConfig.position)
    controls.update()
    handlePointerMissed()
  }, [handlePointerMissed])

  const handleSelect = useCallback((e) => {
    if (e.button !== 0) return // solo click izquierdo
    e.stopPropagation()
    const obj = e.object
    if (!obj.material) return
    clearSelectionVisual()
    if (obj.material.emissive) {
      lastEmissive.current = { obj, color: obj.material.emissive.clone() }
      obj.material.emissive.set('#222222')
    }
    setSelected(obj)
    animateCameraTo(obj)
  }, [clearSelectionVisual, animateCameraTo])

  return (
    <>
      <Controls floor={floor} setFloor={setFloor} />
      {selected && (
        <div style={{position:'fixed', top:70, left:12, zIndex:11, background:'rgba(0,0,0,.75)', color:'#fff', padding:'10px 12px', borderRadius:8, fontFamily:'system-ui,Arial,sans-serif', width:190}}>
          <div style={{fontWeight:'600', marginBottom:8}}>Objeto seleccionado</div>
          <div style={{display:'grid', gridTemplateColumns:'repeat(4,1fr)', gap:6, marginBottom:10}}>
            {['#90caf9','#ffb74d','#81c784','#ce93d8'].map(c => (
              <button key={c} onClick={()=>{ if(selected.material){ selected.material.color.set(c) } }} style={{width:32,height:32,borderRadius:4,border:'1px solid #333', background:c, cursor:'pointer'}} />
            ))}
          </div>
          <div style={{display:'flex', gap:6}}>
            <button onClick={handlePointerMissed} style={{flex:1, padding:'6px 10px', background:'#444', color:'#fff', border:'none', borderRadius:4}}>Quitar</button>
            <button onClick={resetCamera} style={{flex:1, padding:'6px 10px', background:'#1976d2', color:'#fff', border:'none', borderRadius:4}}>Reset</button>
          </div>
        </div>
      )}
      <Canvas shadows camera={{ position: cameraConfig.position, fov: cameraConfig.fov }} onPointerMissed={handlePointerMissed}>
        <ambientLight intensity={lightsConfig.ambient.intensity} />
        <hemisphereLight args={[0xffffff, 0x444444, 0.5]} />
        <directionalLight castShadow position={lightsConfig.directional.position} intensity={lightsConfig.directional.intensity} />
        {/* Spotlight dinámico siguiendo selección */}
        <DynamicSpot selected={selected} />
        <Floor kind={floor} />
        {/* Cubo principal */}
        <mesh position={[0, 0.5, 0]} castShadow onPointerDown={handleSelect} name="Cubo-1">
          <boxGeometry args={[1,1,1]} />
          <meshStandardMaterial color="#90caf9" emissive="#000000" />
        </mesh>
        {/* Cubos adicionales */}
        {[
          { pos: [2,0.5,0], color:'#ffb74d', name:'Cubo-2' },
          { pos: [-2,0.5,0], color:'#81c784', name:'Cubo-3' },
          { pos: [0,0.5,2], color:'#ce93d8', name:'Cubo-4' },
          { pos: [0,0.5,-2], color:'#ffd54f', name:'Cubo-5' },
        ].map(c => (
          <mesh key={c.name} position={c.pos} castShadow onPointerDown={handleSelect} name={c.name}>
            <boxGeometry args={[1,1,1]} />
            <meshStandardMaterial color={c.color} emissive="#000000" />
          </mesh>
        ))}
        {/* Paredes (10x10 piso, centrado). Altura 3. Grosor 0.2 */}
        {/* Pared norte */}
        <mesh position={[0,1.5,-5]} castShadow onPointerDown={handleSelect} name="Pared-Norte">
          <boxGeometry args={[10,3,0.2]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared sur */}
        <mesh position={[0,1.5,5]} castShadow onPointerDown={handleSelect} name="Pared-Sur">
          <boxGeometry args={[10,3,0.2]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared oeste */}
        <mesh position={[-5,1.5,0]} castShadow onPointerDown={handleSelect} name="Pared-Oeste">
          <boxGeometry args={[0.2,3,10]} />
          <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
        </mesh>
        {/* Pared este (con ventana simple hueco central) */}
        {/* Simplificación: dos columnas y travesaño arriba dejando hueco */}
        <group name="Pared-Este">
          <mesh position={[5,1.5,0]} castShadow onPointerDown={handleSelect} name="Marco-Este-Arriba">
            <boxGeometry args={[0.2,0.5,10]} />
            <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
          </mesh>
          <mesh position={[5,1.0,-4]} castShadow onPointerDown={handleSelect} name="Columna-Este-1">
            <boxGeometry args={[0.2,2,2]} />
            <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
          </mesh>
          <mesh position={[5,1.0,4]} castShadow onPointerDown={handleSelect} name="Columna-Este-2">
            <boxGeometry args={[0.2,2,2]} />
            <meshStandardMaterial color="#e0e0e0" emissive="#000000" />
          </mesh>
        </group>
  <OrbitControls ref={controlsRef} makeDefault
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

// Componente spotlight dinámico
function DynamicSpot({ selected }) {
  const ref = useRef()
  const targetRef = useRef()
  useFrame((_, dt) => {
    if (!ref.current || !targetRef.current) return
    const spot = ref.current
    const tgt = targetRef.current
    const basePos = selected ? selected.position : { x:0, y:0, z:0 }
    // Interpolar posición de la luz encima del objeto seleccionado
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

createRoot(document.getElementById('r3f-root')).render(<Demo />)

// Where do I change things?
// - Camera/Lights/Controls: resources/js/three/config.js (cameraConfig, lightsConfig, controlsConfig)
// - Loading 3D models: we’ll add a SceneLoader component (resources/js/three/loaders/SceneLoader.jsx)
// - Materials library & textures: resources/js/three/materials/library.js
// - Global state (current scene/material selections): resources/js/three/store.js
