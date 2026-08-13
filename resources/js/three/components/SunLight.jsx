import React, { useMemo, useRef } from 'react'
import { useFrame } from '@react-three/fiber'
import { Vector3 } from 'three'

/**
 * Simula un sol realista que se puede rotar con un slider de "hora del día".
 * Produce una directionalLight (key) + hemisphereLight (cielo/suelo).
 * El color y la intensidad cambian según la hora: amanecer dorado, mediodía brillante, atardecer ámbar, noche azul.
 *
 * Props:
 *   timeOfDay - 0..24 (float). 6 = amanecer, 12 = mediodía, 18 = atardecer, 0/24 = medianoche
 *   intensity - multiplicador general (default 1)
 */

// Pre-computed color keyframes (hour → {sun, sky, ground, sunIntensity, ambientIntensity})
const TIME_PRESETS = [
  // hour, sunHex, skyHex, groundHex, sunIntensity, ambientIntensity
  [0,  '#1a1a40', '#0a0a20', '#050510', 0.0,  0.08],  // midnight
  [5,  '#2d1b4e', '#1a1040', '#0a0820', 0.05, 0.10],  // pre-dawn
  [6,  '#ff8c42', '#ff6b35', '#3d2b1f', 0.6,  0.20],  // sunrise
  [7,  '#ffaa5c', '#87CEEB', '#5a4a3a', 1.4,  0.30],  // early morning
  [9,  '#fff5e6', '#87CEEB', '#8a7a6a', 2.0,  0.35],  // morning
  [12, '#ffffff', '#87CEEB', '#8a8a7a', 2.6,  0.40],  // noon
  [15, '#fff8e1', '#87CEEB', '#8a7a6a', 2.2,  0.38],  // afternoon
  [17, '#ffcc80', '#ff9e80', '#5a4a3a', 1.6,  0.30],  // late afternoon
  [18, '#ff7043', '#ff5722', '#3d2b1f', 0.8,  0.22],  // sunset
  [19, '#4a3060', '#2d1b4e', '#1a1020', 0.2,  0.12],  // dusk
  [21, '#1a1a40', '#0a0a20', '#050510', 0.0,  0.08],  // night
  [24, '#1a1a40', '#0a0a20', '#050510', 0.0,  0.08],  // midnight wrap
]

function hexToRgb(hex) {
  const v = parseInt(hex.replace('#', ''), 16)
  return [(v >> 16) & 0xff, (v >> 8) & 0xff, v & 0xff]
}

function lerpColor(hex1, hex2, t) {
  const [r1, g1, b1] = hexToRgb(hex1)
  const [r2, g2, b2] = hexToRgb(hex2)
  const r = Math.round(r1 + (r2 - r1) * t)
  const g = Math.round(g1 + (g2 - g1) * t)
  const b = Math.round(b1 + (b2 - b1) * t)
  return `#${((r << 16) | (g << 8) | b).toString(16).padStart(6, '0')}`
}

function lerp(a, b, t) {
  return a + (b - a) * t
}

function getTimePreset(hour) {
  const h = ((hour % 24) + 24) % 24
  let i = 0
  for (; i < TIME_PRESETS.length - 1; i++) {
    if (h < TIME_PRESETS[i + 1][0]) break
  }
  const [h0, sun0, sky0, gnd0, si0, ai0] = TIME_PRESETS[i]
  const [h1, sun1, sky1, gnd1, si1, ai1] = TIME_PRESETS[Math.min(i + 1, TIME_PRESETS.length - 1)]
  const range = h1 - h0 || 1
  const t = Math.max(0, Math.min(1, (h - h0) / range))

  return {
    sunColor: lerpColor(sun0, sun1, t),
    skyColor: lerpColor(sky0, sky1, t),
    groundColor: lerpColor(gnd0, gnd1, t),
    sunIntensity: lerp(si0, si1, t),
    ambientIntensity: lerp(ai0, ai1, t),
  }
}

function getSunPosition(hour, radius = 15) {
  // Map hour to angle: 6h = east horizon (0°), 12h = zenith (90°), 18h = west horizon (180°)
  const h = ((hour % 24) + 24) % 24
  const elevation = Math.sin(((h - 6) / 12) * Math.PI) * (Math.PI / 2.3)
  const azimuth = ((h - 6) / 24) * Math.PI * 2

  const y = Math.max(0.5, radius * Math.sin(Math.max(elevation, 0.05)))
  const xz = radius * Math.cos(Math.max(elevation, 0.05))
  const x = xz * Math.sin(azimuth)
  const z = xz * Math.cos(azimuth)

  return [x, y, z]
}

export function SunLight({ timeOfDay = 12, intensity = 1 }) {
  const dirRef = useRef()
  const hemiRef = useRef()

  const preset = useMemo(() => getTimePreset(timeOfDay), [timeOfDay])
  const sunPos = useMemo(() => getSunPosition(timeOfDay), [timeOfDay])

  // Smooth transition via lerp each frame
  useFrame(() => {
    if (dirRef.current) {
      const target = new Vector3(...sunPos)
      dirRef.current.position.lerp(target, 0.08)
    }
  })

  const finalSunIntensity = preset.sunIntensity * intensity
  const finalAmbientIntensity = preset.ambientIntensity * intensity

  return (
    <>
      {/* Key light (sol) */}
      <directionalLight
        ref={dirRef}
        castShadow
        position={sunPos}
        intensity={finalSunIntensity}
        color={preset.sunColor}
        shadow-mapSize={[2048, 2048]}
        shadow-bias={-0.0003}
        shadow-camera-left={-15}
        shadow-camera-right={15}
        shadow-camera-top={15}
        shadow-camera-bottom={-15}
        shadow-camera-near={0.5}
        shadow-camera-far={50}
      />

      {/* Hemisphere light for ambient sky/ground bounce */}
      <hemisphereLight
        ref={hemiRef}
        intensity={finalAmbientIntensity}
        color={preset.skyColor}
        groundColor={preset.groundColor}
      />

      {/* Subtle fill from opposite side */}
      <directionalLight
        position={[-sunPos[0] * 0.5, sunPos[1] * 0.3, -sunPos[2] * 0.5]}
        intensity={finalSunIntensity * 0.15}
        color={preset.skyColor}
      />
    </>
  )
}

// Export for use by Environment preset selector
// Valid drei presets: apartment, city, dawn, forest, lobby, night, park, studio, sunset, warehouse
export function getEnvironmentPreset(timeOfDay) {
  const h = ((timeOfDay % 24) + 24) % 24
  if (h >= 5 && h < 7) return 'dawn'
  if (h >= 7 && h < 10) return 'forest'
  if (h >= 10 && h < 15) return 'park'
  if (h >= 15 && h < 18) return 'sunset'
  if (h >= 18 && h < 20) return 'sunset'
  return 'night'
}
