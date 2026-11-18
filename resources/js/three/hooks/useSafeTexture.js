import { useState, useEffect, useMemo } from 'react'
import * as THREE from 'three'

// Cache en memoria para no recargar la misma textura múltiples veces
const textureCache = new Map()

function createFallbackWhiteTexture() {
  const data = new Uint8Array([255, 255, 255, 255])
  const tex = new THREE.DataTexture(data, 1, 1, THREE.RGBAFormat)
  tex.colorSpace = THREE.SRGBColorSpace
  tex.wrapS = THREE.RepeatWrapping
  tex.wrapT = THREE.RepeatWrapping
  tex.needsUpdate = true
  return tex
}

/**
 * Carga una textura de Three.js de forma segura SIN lanzar promesas de React Suspense.
 * Evita que React desmonte el árbol (pantalla azul/negra) al seleccionar un material en tiempo real.
 */
export function useSafeTexture(url) {
  const fallback = useMemo(() => createFallbackWhiteTexture(), [])
  const [texture, setTexture] = useState(() => {
    if (url && textureCache.has(url)) {
      return textureCache.get(url)
    }
    return fallback
  })

  useEffect(() => {
    if (!url || typeof url !== 'string') {
      setTexture(fallback)
      return
    }

    if (textureCache.has(url)) {
      setTexture(textureCache.get(url))
      return
    }

    let alive = true
    const loader = new THREE.TextureLoader()
    loader.load(
      url,
      (loadedTex) => {
        loadedTex.colorSpace = THREE.SRGBColorSpace
        loadedTex.wrapS = THREE.RepeatWrapping
        loadedTex.wrapT = THREE.RepeatWrapping
        loadedTex.needsUpdate = true
        textureCache.set(url, loadedTex)
        if (alive) {
          setTexture(loadedTex)
        }
      },
      undefined,
      (err) => {
        console.warn('No se pudo cargar textura:', url, err)
        if (alive) {
          setTexture(fallback)
        }
      }
    )

    return () => {
      alive = false
    }
  }, [url, fallback])

  return texture
}

