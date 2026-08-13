import React, { useMemo } from 'react'

/**
 * Genera spotlights que simulan luz solar entrando por cada ventana.
 * Las luces apuntan desde fuera del cuarto hacia dentro, creando haces de luz
 * realistas en el piso y paredes interiores.
 *
 * Props:
 *   windows    - array de { id, wallKey, widthCm, heightCm, sillHeightCm }
 *   roomSize   - { width, depth, height } en metros
 *   wallThickness - grosor de pared en metros
 *   timeOfDay  - 0..24 para ajustar dirección de haces
 *   intensity  - multiplicador general
 */

const MAX_SHADOW_LIGHTS = 4

export function WindowLight({ windows = [], roomSize, wallThickness = 0.12, timeOfDay = 12, intensity = 1 }) {
  const lights = useMemo(() => {
    if (!windows.length || !roomSize) return []

    // Sun azimuth angle based on time of day
    const h = ((timeOfDay % 24) + 24) % 24
    const sunAzimuth = ((h - 6) / 24) * Math.PI * 2
    const sunElevation = Math.sin(((h - 6) / 12) * Math.PI) * (Math.PI / 2.3)
    const isNight = h < 5.5 || h > 19.5

    // Don't create window lights at night
    if (isNight) return []

    return windows.map((win, idx) => {
      const wM = (win.widthCm || 160) / 100
      const hM = (win.heightCm || 120) / 100
      const sillM = (win.sillHeightCm || 90) / 100
      const centerY = sillM + hM / 2
      const wallKey = win.wallKey || 'north'

      // Calculate light position outside the window, pointing inward
      const offset = 3.0 // how far outside the light source is
      let lightPos = [0, centerY + 1.5, 0]
      let targetPos = [0, 0, 0]

      // Adjust sun direction influence based on azimuth
      const sunDirX = Math.sin(sunAzimuth) * 2
      const sunDirZ = Math.cos(sunAzimuth) * 2

      switch (wallKey) {
        case 'north':
          lightPos = [sunDirX, centerY + 2, -roomSize.depth / 2 - offset]
          targetPos = [sunDirX * 0.3, 0, -roomSize.depth / 2 + roomSize.depth * 0.3]
          break
        case 'south':
          lightPos = [sunDirX, centerY + 2, roomSize.depth / 2 + offset]
          targetPos = [sunDirX * 0.3, 0, roomSize.depth / 2 - roomSize.depth * 0.3]
          break
        case 'west':
          lightPos = [-roomSize.width / 2 - offset, centerY + 2, sunDirZ]
          targetPos = [-roomSize.width / 2 + roomSize.width * 0.3, 0, sunDirZ * 0.3]
          break
        case 'east':
          lightPos = [roomSize.width / 2 + offset, centerY + 2, sunDirZ]
          targetPos = [roomSize.width / 2 - roomSize.width * 0.3, 0, sunDirZ * 0.3]
          break
        default:
          break
      }

      // Window light angle based on window size relative to distance
      const angle = Math.atan2(Math.max(wM, hM) / 2, offset) * 1.2
      // Intensity scales with window area and sun elevation
      const elevFactor = Math.max(0.2, Math.sin(Math.max(sunElevation, 0)))
      const baseIntensity = 1.5 * (wM * hM / 2) * elevFactor * intensity

      // Warm color matching time of day
      let color = '#ffffff'
      if (h < 7 || h > 17) color = '#ffcc80'
      else if (h < 9 || h > 15) color = '#fff5e6'

      return {
        id: win.id || idx,
        position: lightPos,
        targetPos,
        angle: Math.min(angle, Math.PI / 3),
        intensity: baseIntensity,
        color,
        castShadow: idx < MAX_SHADOW_LIGHTS,
      }
    })
  }, [windows, roomSize, wallThickness, timeOfDay, intensity])

  if (!lights.length) return null

  return (
    <>
      {lights.map((l) => (
        <SpotLightWithTarget key={l.id} {...l} />
      ))}
    </>
  )
}

function SpotLightWithTarget({ position, targetPos, angle, intensity, color, castShadow }) {
  return (
    <group>
      <spotLight
        position={position}
        angle={angle}
        penumbra={0.6}
        intensity={intensity}
        color={color}
        distance={30}
        castShadow={castShadow}
        shadow-mapSize={castShadow ? [1024, 1024] : undefined}
        shadow-bias={-0.0004}
        decay={1.5}
      />
      {/* A subtle point light at the window position for soft interior glow */}
      <pointLight
        position={[
          (position[0] + targetPos[0]) / 2,
          position[1] - 0.5,
          (position[2] + targetPos[2]) / 2,
        ]}
        intensity={intensity * 0.15}
        color={color}
        distance={8}
        decay={2}
      />
    </group>
  )
}
