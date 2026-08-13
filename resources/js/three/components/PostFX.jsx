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

export function PostFX({ enabled, dofOn }) {
  if (!enabled) return null
  const pp = postprocessingConfig
  return (
    <EffectComposer multisampling={0}>
      {pp.ssao.enabled && (
        <SSAO radius={pp.ssao.radius} intensity={pp.ssao.intensity} />
      )}
      {pp.bloom.enabled && (
        <Bloom
          intensity={pp.bloom.intensity}
          luminanceThreshold={pp.bloom.luminanceThreshold}
          luminanceSmoothing={pp.bloom.luminanceSmoothing}
        />
      )}
      <BrightnessContrast brightness={0.02} contrast={0.08} />
      <HueSaturation hue={0.0} saturation={0.06} />
      {dofOn && (
        <DepthOfField
          focusDistance={0.02}
          focalLength={0.05}
          bokehScale={2.0}
        />
      )}
      {pp.toneMapping.enabled && (
        <ToneMapping
          mode={ACESFilmicToneMapping}
          exposure={pp.toneMapping.exposure}
        />
      )}
      {pp.vignette.enabled && (
        <Vignette offset={pp.vignette.offset} darkness={pp.vignette.darkness} />
      )}
    </EffectComposer>
  )
}
