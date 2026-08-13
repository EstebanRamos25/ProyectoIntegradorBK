import React from 'react'

/**
 * Renderiza el marco de ventana 3D y el panel de vidrio translúcido
 */
export function WindowMesh({
  position = [0, 0, 0],
  rotation = [0, 0, 0],
  widthM = 1.6,
  heightM = 1.2,
  depthM = 0.22,
  onPointerDown,
}) {
  const frameThickness = 0.08
  const glassThickness = 0.02

  return (
    <group position={position} rotation={rotation} onPointerDown={onPointerDown} name="Ventana-3D">
      {/* Panel de Cristal Translúcido */}
      <mesh position={[0, 0, 0]} castShadow receiveShadow>
        <boxGeometry args={[widthM - frameThickness, heightM - frameThickness, glassThickness]} />
        <meshStandardMaterial
          color="#93c5fd"
          transparent
          opacity={0.35}
          roughness={0.1}
          metalness={0.9}
          emissive="#1e3a8a"
          emissiveIntensity={0.15}
        />
      </mesh>

      {/* Marco de Ventana - Travesaño Superior */}
      <mesh position={[0, heightM / 2 - frameThickness / 2, 0]} castShadow receiveShadow>
        <boxGeometry args={[widthM, frameThickness, depthM + 0.02]} />
        <meshStandardMaterial color="#1e293b" roughness={0.5} metalness={0.8} />
      </mesh>

      {/* Marco de Ventana - Travesaño Inferior */}
      <mesh position={[0, -heightM / 2 + frameThickness / 2, 0]} castShadow receiveShadow>
        <boxGeometry args={[widthM, frameThickness, depthM + 0.02]} />
        <meshStandardMaterial color="#1e293b" roughness={0.5} metalness={0.8} />
      </mesh>

      {/* Marco de Ventana - Columna Izquierda */}
      <mesh position={[-widthM / 2 + frameThickness / 2, 0, 0]} castShadow receiveShadow>
        <boxGeometry args={[frameThickness, heightM, depthM + 0.02]} />
        <meshStandardMaterial color="#1e293b" roughness={0.5} metalness={0.8} />
      </mesh>

      {/* Marco de Ventana - Columna Derecha */}
      <mesh position={[widthM / 2 - frameThickness / 2, 0, 0]} castShadow receiveShadow>
        <boxGeometry args={[frameThickness, heightM, depthM + 0.02]} />
        <meshStandardMaterial color="#1e293b" roughness={0.5} metalness={0.8} />
      </mesh>
    </group>
  )
}
