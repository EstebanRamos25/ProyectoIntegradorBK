import { useState, useCallback, useEffect, useRef } from 'react'

export function useRecommendations(selectedSceneId, selectedMaterialId, selectedWallMaterialId, trackEvent) {
  const [recommendations, setRecommendations] = useState([])
  const [recommendationsLoading, setRecommendationsLoading] = useState(false)
  const [recommendationsError, setRecommendationsError] = useState(null)
  const recoDebounceRef = useRef(null)

  const fetchRecommendations = useCallback(async () => {
    if (!selectedSceneId) {
      setRecommendations([])
      setRecommendationsError(null)
      setRecommendationsLoading(false)
      return
    }

    setRecommendationsLoading(true)
    setRecommendationsError(null)

    try {
      const resp = await fetch(`/3d/recommendations?scene_id=${Number(selectedSceneId)}&limit=6`, {
        method: 'GET',
        credentials: 'same-origin',
        headers: { Accept: 'application/json' },
      })

      if (resp.status === 401 || resp.status === 419) {
        setRecommendations([])
        setRecommendationsError('Inicia sesión para ver recomendaciones.')
        return
      }

      if (!resp.ok) {
        throw new Error(`HTTP ${resp.status}`)
      }

      const data = await resp.json()
      const items = Array.isArray(data?.items) ? data.items : []
      setRecommendations(items)

      if (items.length) {
        trackEvent('recommendations_shown', { meta: { count: items.length } })
      }
    } catch (e) {
      console.error(e)
      setRecommendations([])
      setRecommendationsError('No se pudieron cargar recomendaciones.')
    } finally {
      setRecommendationsLoading(false)
    }
  }, [selectedSceneId, trackEvent])

  useEffect(() => {
    fetchRecommendations()
  }, [fetchRecommendations])

  useEffect(() => {
    if (!selectedSceneId) return
    if (recoDebounceRef.current) window.clearTimeout(recoDebounceRef.current)
    recoDebounceRef.current = window.setTimeout(() => {
      fetchRecommendations()
    }, 900)
    return () => {
      if (recoDebounceRef.current) window.clearTimeout(recoDebounceRef.current)
    }
  }, [selectedSceneId, selectedMaterialId, selectedWallMaterialId, fetchRecommendations])

  return {
    recommendations,
    recommendationsLoading,
    recommendationsError
  }
}
