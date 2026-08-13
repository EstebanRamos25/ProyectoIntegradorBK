import React from 'react'

export function Floor({ kind, width, depth, onPointerDown, name }) {
  const color = kind === 'wood' ? '#8b5a2b' : '#cfd8dc'
  return (
    <mesh
      rotation={[-Math.PI / 2, 0, 0]}
      receiveShadow
      onPointerDown={onPointerDown}
      name={name}
    >
      <planeGeometry args={[width, depth]} />
      <meshStandardMaterial color={color} roughness={0.9} metalness={0.1} />
    </mesh>
  )
}
