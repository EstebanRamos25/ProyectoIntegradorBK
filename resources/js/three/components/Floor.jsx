import React, { useMemo } from 'react'
import * as THREE from 'three'

export function Floor({ kind, width, depth, roomShape = 'rectangular', onPointerDown, name }) {
  const color = kind === 'wood' ? '#8b5a2b' : '#cfd8dc'

  const geometry = useMemo(() => {
    if (roomShape === 'rectangular' || roomShape === 'open_loft') {
      return new THREE.PlaneGeometry(width, depth)
    }

    const shape = new THREE.Shape()
    const hw = width / 2
    const hd = depth / 2

    // Local coordinates for PlaneGeometry oriented flat (rotation-x = -PI/2):
    // Local X = World X
    // Local Y = -World Z
    // North edge: World Z = -hd => Local Y = hd
    // South edge: World Z = hd => Local Y = -hd
    // West edge: World X = -hw => Local X = -hw
    // East edge: World X = hw => Local X = hw

    if (roomShape === 'l_shape') {
      // Exclude South-East (Local X > 0, Local Y < 0)
      shape.moveTo(-hw, -hd) // South-West
      shape.lineTo(0, -hd)   // South-Middle
      shape.lineTo(0, 0)     // Inner corner
      shape.lineTo(hw, 0)    // East-Middle
      shape.lineTo(hw, hd)   // North-East
      shape.lineTo(-hw, hd)  // North-West
      shape.lineTo(-hw, -hd) // Cierra
    } else if (roomShape === 'u_shape') {
      // Exclude South-Middle ( -w/4 < X < w/4, Y < 0)
      shape.moveTo(-hw, -hd)      // South-West
      shape.lineTo(-width/4, -hd) // South Left Inner
      shape.lineTo(-width/4, 0)   // Inner Left corner
      shape.lineTo(width/4, 0)    // Inner Right corner
      shape.lineTo(width/4, -hd)  // South Right Inner
      shape.lineTo(hw, -hd)       // South-East
      shape.lineTo(hw, hd)        // North-East
      shape.lineTo(-hw, hd)       // North-West
      shape.lineTo(-hw, -hd)      // Cierra
    } else if (roomShape === 't_shape') {
      // Exclude South-West and South-East (X < -w/4 or X > w/4, Y < 0)
      shape.moveTo(-width/4, -hd) // South-Middle Left
      shape.lineTo(width/4, -hd)  // South-Middle Right
      shape.lineTo(width/4, 0)    // Inner East V
      shape.lineTo(hw, 0)         // Inner East H
      shape.lineTo(hw, hd)        // North-East
      shape.lineTo(-hw, hd)       // North-West
      shape.lineTo(-hw, 0)        // Inner West H
      shape.lineTo(-width/4, 0)   // Inner West V
      shape.lineTo(-width/4, -hd) // Cierra
    } else {
      shape.moveTo(-hw, -hd)
      shape.lineTo(hw, -hd)
      shape.lineTo(hw, hd)
      shape.lineTo(-hw, hd)
      shape.lineTo(-hw, -hd)
    }

    return new THREE.ShapeGeometry(shape)
  }, [roomShape, width, depth])

  return (
    <mesh
      rotation={[-Math.PI / 2, 0, 0]}
      receiveShadow
      onPointerDown={onPointerDown}
      name={name}
      geometry={geometry}
    >
      <meshStandardMaterial color={color} roughness={0.9} metalness={0.1} />
    </mesh>
  )
}
