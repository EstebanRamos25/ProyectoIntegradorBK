import React, { useMemo } from 'react'
import { GRID_SIZE, normalizeGrid } from '../roomGeometry'

export function Floor({
  kind,
  width,
  depth,
  roomShape = 'rectangular',
  customGrid = null,
  onPointerDown,
  name,
}) {
  const color = kind === 'wood' ? '#8b5a2b' : '#cfd8dc'

  const strips = useMemo(() => {
    const grid = normalizeGrid(customGrid, roomShape)
    const cellW = width / GRID_SIZE
    const cellD = depth / GRID_SIZE
    const result = []

    for (let r = 0; r < GRID_SIZE; r++) {
      let cStart = null
      for (let c = 0; c <= GRID_SIZE; c++) {
        const active = c < GRID_SIZE && grid[r][c] === 1
        if (active && cStart === null) {
          cStart = c
        } else if (!active && cStart !== null) {
          const span = c - cStart
          const stripW = span * cellW
          const cx = -width / 2 + (cStart + span / 2) * cellW
          const cz = -depth / 2 + (r + 0.5) * cellD
          result.push({
            key: `floor-r${r}-c${cStart}-${c}`,
            cx,
            cz,
            stripW,
            stripD: cellD,
          })
          cStart = null
        }
      }
    }

    return result
  }, [customGrid, roomShape, width, depth])

  return (
    <group name={name}>
      {strips.map((s) => (
        <mesh
          key={s.key}
          position={[s.cx, 0, s.cz]}
          rotation={[-Math.PI / 2, 0, 0]}
          receiveShadow
          onPointerDown={onPointerDown}
          name={name}
        >
          <planeGeometry args={[s.stripW, s.stripD]} />
          <meshStandardMaterial color={color} roughness={0.9} metalness={0.1} />
        </mesh>
      ))}
    </group>
  )
}
