import React, { useEffect } from 'react'
import { useThree } from '@react-three/fiber'
import { EffectComposer, Bloom, SSAO, ToneMapping, Vignette, DepthOfField, BrightnessContrast, HueSaturation } from '@react-three/postprocessing'
import { SRGBColorSpace, ACESFilmicToneMapping } from 'three'
import { postprocessingConfig, rendererConfig } from '../config'

export function RendererSetup() {
  const { gl } = useThree()
  useEffect(() => {
    gl.outputColorSpace = SRGBColorSpace
    gl.toneMapping = ACESFilmicToneMapping
    gl.toneMappingExposure = postprocessingConfig.toneMapping.exposure
    gl.physicallyCorrectLights = rendererConfig.physicallyCorrectLights
    gl.shadowMap.enabled = true
  }, [gl])
  return null
}

/**
 * Postprocesado optimizado para CLARIDAD de materiales.
 * - SSAO muy sutil: solo para dar profundidad en esquinas, no para oscurecer.
 * - Bloom bajísimo: apenas un brillo en reflejos, sin neblina.
 * - DOF suave y opcional: para screenshots, no para trabajo diario.
 * - Sin vignette agresivo.
 * - Contrast/saturation leve para que los materiales "pop" sin distorsionar colores.
 */
export function PostFX({ enabled, dofOn }) {
  if (!enabled) return null
  return (
    <EffectComposer multisampling={0}>
      {/* SSAO sutil: solo contacto entre superficies */}
      <SSAO
        radius={0.08}
        intensity={8}
        luminanceInfluence={0.6}
        color="#000000"
      />

      {/* Bloom muy bajo: solo especularidades brillantes */}
      <Bloom
        intensity={0.25}
        luminanceThreshold={0.85}
        luminanceSmoothing={0.1}
        mipmapBlur
      />

      {/* Leve contraste/saturación para claridad de materiales */}
      <BrightnessContrast brightness={0.01} contrast={0.05} />
      <HueSaturation hue={0.0} saturation={0.04} />

      {/* DOF opcional: muy sutil, larga distancia focal */}
      {dofOn && (
        <DepthOfField
          focusDistance={0.035}
          focalLength={0.08}
          bokehScale={1.2}
        />
      )}

      {/* Tonemapping ACES para look natural */}
      <ToneMapping
        mode={ACESFilmicToneMapping}
        exposure={1.0}
      />

      {/* Vignette muy sutil */}
      <Vignette offset={0.3} darkness={0.35} />
    </EffectComposer>
  )
}
