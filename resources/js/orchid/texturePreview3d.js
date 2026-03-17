import * as THREE from 'three'
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js'

/**
 * Monta un preview 3D (placa/cubo delgado) con una textura.
 * Retorna una función cleanup.
 */
export async function mountTexturePreview3d({ canvas, imageUrl }) {
  if (!canvas) throw new Error('canvas requerido')
  if (!imageUrl) throw new Error('imageUrl requerido')

  const renderer = new THREE.WebGLRenderer({
    canvas,
    antialias: true,
    alpha: true,
    preserveDrawingBuffer: false,
  })
  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2))
  renderer.outputColorSpace = THREE.SRGBColorSpace

  const scene = new THREE.Scene()
  scene.background = new THREE.Color(0x0b1220)

  const camera = new THREE.PerspectiveCamera(45, 1, 0.1, 100)
  camera.position.set(1.8, 1.2, 1.8)

  const controls = new OrbitControls(camera, renderer.domElement)
  controls.enableDamping = true
  controls.dampingFactor = 0.08
  controls.enablePan = false
  controls.minDistance = 1.2
  controls.maxDistance = 4.0

  const ambient = new THREE.AmbientLight(0xffffff, 0.75)
  scene.add(ambient)

  const dir = new THREE.DirectionalLight(0xffffff, 1.25)
  dir.position.set(3, 4, 2)
  scene.add(dir)

  // “Placa”: caja delgada
  const geometry = new THREE.BoxGeometry(1.6, 0.08, 1.0)

  const loader = new THREE.TextureLoader()
  const texture = await new Promise((resolve, reject) => {
    loader.load(
      imageUrl,
      (t) => resolve(t),
      undefined,
      (err) => reject(err)
    )
  })
  texture.colorSpace = THREE.SRGBColorSpace
  texture.wrapS = THREE.RepeatWrapping
  texture.wrapT = THREE.RepeatWrapping
  texture.repeat.set(1, 1)
  texture.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy?.() ?? 8)

  const material = new THREE.MeshStandardMaterial({
    map: texture,
    roughness: 0.65,
    metalness: 0.05,
  })
  const mesh = new THREE.Mesh(geometry, material)
  mesh.rotation.y = Math.PI * 0.15
  scene.add(mesh)

  // Base suave para referencia
  const base = new THREE.Mesh(
    new THREE.CircleGeometry(1.35, 48),
    new THREE.MeshStandardMaterial({ color: 0x111827, roughness: 0.95, metalness: 0.0 })
  )
  base.rotation.x = -Math.PI / 2
  base.position.y = -0.45
  scene.add(base)

  let raf = 0
  let disposed = false

  const resize = () => {
    if (disposed) return
    const parent = canvas.parentElement
    const w = Math.max(1, parent?.clientWidth || 640)
    const h = Math.max(1, parent?.clientHeight || 480)
    renderer.setSize(w, h, false)
    camera.aspect = w / h
    camera.updateProjectionMatrix()
  }

  const ro = new ResizeObserver(() => resize())
  if (canvas.parentElement) ro.observe(canvas.parentElement)
  resize()

  const tick = () => {
    if (disposed) return
    controls.update()
    renderer.render(scene, camera)
    raf = requestAnimationFrame(tick)
  }
  tick()

  return () => {
    disposed = true
    cancelAnimationFrame(raf)
    ro.disconnect()
    controls.dispose()
    geometry.dispose()
    material.dispose()
    texture.dispose()
    base.geometry.dispose()
    base.material.dispose()
    renderer.dispose()
  }
}
