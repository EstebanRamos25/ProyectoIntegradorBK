import React from 'react'
import { DoubleSide } from 'three'

/**
 * Renderiza el marco de ventana 3D y el panel de vidrio translúcido.
 * El cristal es DoubleSide para que sea visible desde dentro y desde fuera.
 * Incluye un plano luminoso exterior para simular la luz del cielo.
 */
export function WindowMesh({
  position = [0, 0, 0],
  rotation = [0, 0, 0],
  widthM = 1.6,
  heightM = 1.2,
  depthM = 0.14,
  onPointerDown,
}) {
  const frameThickness = 0.06
  const glassThickness = 0.015

  // Frame color: dark aluminum
  const frameColor = '#2d3748'
  const frameRoughness = 0.4
  const frameMetalness = 0.85

  return (
    <group position={position} rotation={rotation} onPointerDown={onPointerDown} name="Ventana-3D">
      {/* Panel de Cristal Translúcido - DoubleSide para visibilidad exterior */}
      <mesh position={[0, 0, 0]} castShadow={false} receiveShadow>
        <boxGeometry args={[widthM - frameThickness * 2, heightM - frameThickness * 2, glassThickness]} />
        <meshPhysicalMaterial
          color="#b3d9ff"
          transparent
          opacity={0.3}
          roughness={0.05}
          metalness={0.1}
          transmission={0.6}
          thickness={0.05}
          ior={1.5}
          envMapIntensity={1.0}
          side={DoubleSide}
        />
      </mesh>

      {/* Plano luminoso exterior: simula la luz del cielo detrás de la ventana */}
      <mesh position={[0, 0, -depthM * 0.8]}>
        <planeGeometry args={[widthM - frameThickness * 2, heightM - frameThickness * 2]} />
        <meshBasicMaterial
          color="#a8d8ff"
          transparent
          opacity={0.25}
          side={DoubleSide}
        />
      </mesh>

      {/* Marco de Ventana - Travesaño Superior */}
      <mesh position={[0, heightM / 2 - frameThickness / 2, 0]} castShadow receiveShadow>
        <boxGeometry args={[widthM, frameThickness, depthM]} />
        <meshStandardMaterial color={frameColor} roughness={frameRoughness} metalness={frameMetalness} side={DoubleSide} />
      </mesh>

      {/* Marco de Ventana - Travesaño Inferior */}
      <mesh position={[0, -heightM / 2 + frameThickness / 2, 0]} castShadow receiveShadow>
        <boxGeometry args={[widthM, frameThickness, depthM]} />
        <meshStandardMaterial color={frameColor} roughness={frameRoughness} metalness={frameMetalness} side={DoubleSide} />
      </mesh>

      {/* Marco de Ventana - Columna Izquierda */}
      <mesh position={[-widthM / 2 + frameThickness / 2, 0, 0]} castShadow receiveShadow>
        <boxGeometry args={[frameThickness, heightM, depthM]} />
        <meshStandardMaterial color={frameColor} roughness={frameRoughness} metalness={frameMetalness} side={DoubleSide} />
      </mesh>

      {/* Marco de Ventana - Columna Derecha */}
      <mesh position={[widthM / 2 - frameThickness / 2, 0, 0]} castShadow receiveShadow>
        <boxGeometry args={[frameThickness, heightM, depthM]} />
        <meshStandardMaterial color={frameColor} roughness={frameRoughness} metalness={frameMetalness} side={DoubleSide} />
      </mesh>

      {/* Cruceta central (divisor horizontal) */}
      <mesh position={[0, 0, 0]} castShadow receiveShadow>
        <boxGeometry args={[widthM - frameThickness * 2, frameThickness * 0.6, depthM * 0.5]} />
        <meshStandardMaterial color={frameColor} roughness={frameRoughness} metalness={frameMetalness} side={DoubleSide} />
      </mesh>
    </group>
  )
}
