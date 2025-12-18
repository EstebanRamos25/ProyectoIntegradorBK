// Centralized 3D configuration (camera, controls, lights)
export const cameraConfig = {
  position: [4, 4, 6],
  fov: 50,
}

export const controlsConfig = {
  // OrbitControls sensitivity & limits
  enableDamping: true,
  dampingFactor: 0.08,
  rotateSpeed: 0.9,
  zoomSpeed: 0.8,
  panSpeed: 0.8,
  minDistance: 1,
  maxDistance: 20,
  minPolarAngle: 0.1,   // ~ 6°
  maxPolarAngle: Math.PI / 2.1,
}

export const lightsConfig = {
  ambient: { intensity: 0.6 },
  directional: { position: [5, 8, 5], intensity: 1.0 },
}

// Renderer & postprocessing defaults for the Demo scene
export const rendererConfig = {
  dpr: [1, 2],
  // Enable physically-correct light falloff & filmic look
  physicallyCorrectLights: true,
}

export const postprocessingConfig = {
  enabled: true,
  // Mild cinematic bloom for highlights
  bloom: {
    enabled: true,
    intensity: 0.65,
    luminanceThreshold: 0.55,
    luminanceSmoothing: 0.2,
  },
  // Ambient occlusion to add contact shadows / depth
  ssao: {
    enabled: true,
    radius: 0.12,
    intensity: 18,
  },
  // Tonemapping + subtle exposure
  toneMapping: {
    enabled: true,
    mode: 'ACES_FILMIC',
    exposure: 1.05,
  },
  vignette: {
    enabled: true,
    offset: 0.15,
    darkness: 0.75,
  },
}

// Scene asset defaults (where we’ll later wire APIs)
export const assetsConfig = {
  // Base path for GLB/Textures (Vite will serve from public/ by default)
  baseUrl: '/storage/3d/',
  // Default scene and slots (later overridden by API)
  defaultScene: {
    glb: 'demo-room.glb',
    slots: { floor: 'Floor', walls: 'Walls' },
  },
}
