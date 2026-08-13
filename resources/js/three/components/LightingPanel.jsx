import React, { useState } from 'react'

/**
 * Panel flotante de control de iluminación en la esquina inferior izquierda.
 * Controla: hora del día, intensidad, PostFX, DOF.
 * Diseño glassmorphism compacto y colapsable.
 */

const TIME_LABELS = {
  0: '🌙 Medianoche',
  3: '🌙 Madrugada',
  6: '🌅 Amanecer',
  9: '☀️ Mañana',
  12: '☀️ Mediodía',
  15: '🌤️ Tarde',
  18: '🌅 Atardecer',
  21: '🌙 Noche',
}

function getTimeLabel(h) {
  const hr = Math.round(((h % 24) + 24) % 24)
  // Find the closest label
  const keys = Object.keys(TIME_LABELS).map(Number).sort((a, b) => a - b)
  let best = keys[0]
  for (const k of keys) {
    if (hr >= k) best = k
  }
  const hh = String(Math.floor(h)).padStart(2, '0')
  const mm = String(Math.round((h % 1) * 60)).padStart(2, '0')
  return `${TIME_LABELS[best]} ${hh}:${mm}`
}

export function LightingPanel({
  timeOfDay,
  onTimeOfDayChange,
  lightIntensity,
  onLightIntensityChange,
  postFXOn,
  onPostFXToggle,
  dofOn,
  onDofToggle,
}) {
  const [isOpen, setIsOpen] = useState(true)

  return (
    <div
      onWheelCapture={(e) => e.stopPropagation()}
      style={styles.container}
    >
      {/* Toggle button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        style={styles.toggleBtn}
        title={isOpen ? 'Ocultar panel de luz' : 'Mostrar panel de luz'}
      >
        {isOpen ? '☀️ ▼' : '☀️ ▲'}
      </button>

      {isOpen && (
        <div style={styles.body}>
          {/* Time of Day Slider */}
          <div style={styles.sliderGroup}>
            <div style={styles.sliderHeader}>
              <span style={styles.label}>Hora del Día</span>
              <span style={styles.value}>{getTimeLabel(timeOfDay)}</span>
            </div>
            <input
              type="range"
              min={0}
              max={24}
              step={0.25}
              value={timeOfDay}
              onChange={(e) => onTimeOfDayChange(Number(e.target.value))}
              style={styles.rangeInput}
            />
            {/* Sun path mini visualizer */}
            <div style={styles.sunPath}>
              <div
                style={{
                  ...styles.sunDot,
                  left: `${(timeOfDay / 24) * 100}%`,
                  bottom: `${Math.max(0, Math.sin(((timeOfDay - 6) / 12) * Math.PI)) * 100}%`,
                }}
              />
              <div style={styles.horizon} />
            </div>
          </div>

          {/* Intensity Slider */}
          <div style={styles.sliderGroup}>
            <div style={styles.sliderHeader}>
              <span style={styles.label}>Intensidad</span>
              <span style={styles.value}>{(lightIntensity * 100).toFixed(0)}%</span>
            </div>
            <input
              type="range"
              min={20}
              max={200}
              step={5}
              value={Math.round(lightIntensity * 100)}
              onChange={(e) => onLightIntensityChange(Number(e.target.value) / 100)}
              style={styles.rangeInput}
            />
          </div>

          {/* Toggle Row */}
          <div style={styles.toggleRow}>
            <button
              onClick={onPostFXToggle}
              style={{
                ...styles.pillBtn,
                ...(postFXOn ? styles.pillBtnActive : {}),
              }}
            >
              ✨ Efectos {postFXOn ? 'ON' : 'OFF'}
            </button>
            <button
              onClick={onDofToggle}
              style={{
                ...styles.pillBtn,
                ...(dofOn ? styles.pillBtnActive : {}),
              }}
            >
              🔍 Enfoque {dofOn ? 'ON' : 'OFF'}
            </button>
          </div>
        </div>
      )}
    </div>
  )
}

const styles = {
  container: {
    position: 'fixed',
    bottom: 16,
    left: 16,
    zIndex: 90,
    width: 280,
    fontFamily: 'system-ui, -apple-system, sans-serif',
    color: '#fff',
  },
  toggleBtn: {
    width: '100%',
    padding: '8px 14px',
    borderRadius: '12px 12px 0 0',
    border: '1px solid rgba(255, 255, 255, 0.12)',
    borderBottom: 'none',
    background: 'rgba(15, 23, 42, 0.85)',
    backdropFilter: 'blur(16px)',
    WebkitBackdropFilter: 'blur(16px)',
    color: '#f8fafc',
    fontSize: 13,
    fontWeight: 700,
    cursor: 'pointer',
    textAlign: 'left',
    letterSpacing: 0.5,
  },
  body: {
    padding: '12px 14px 14px',
    background: 'rgba(15, 23, 42, 0.88)',
    backdropFilter: 'blur(16px)',
    WebkitBackdropFilter: 'blur(16px)',
    border: '1px solid rgba(255, 255, 255, 0.12)',
    borderTop: 'none',
    borderRadius: '0 0 14px 14px',
    boxShadow: '0 12px 30px rgba(0, 0, 0, 0.4)',
    display: 'flex',
    flexDirection: 'column',
    gap: 12,
  },
  sliderGroup: {
    display: 'flex',
    flexDirection: 'column',
    gap: 4,
  },
  sliderHeader: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    fontSize: 11,
  },
  label: {
    fontWeight: 600,
    color: '#cbd5e1',
  },
  value: {
    fontWeight: 700,
    color: '#10b981',
    fontSize: 10,
  },
  rangeInput: {
    width: '100%',
    height: 4,
    borderRadius: 2,
    appearance: 'auto',
    cursor: 'pointer',
    accentColor: '#10b981',
  },
  sunPath: {
    position: 'relative',
    width: '100%',
    height: 24,
    marginTop: 2,
    borderRadius: 6,
    background: 'linear-gradient(180deg, #1e3a5f 0%, #0f172a 100%)',
    overflow: 'hidden',
  },
  sunDot: {
    position: 'absolute',
    width: 10,
    height: 10,
    borderRadius: '50%',
    background: '#fbbf24',
    boxShadow: '0 0 8px 2px rgba(251, 191, 36, 0.6)',
    transform: 'translate(-50%, 50%)',
    transition: 'left 0.15s ease, bottom 0.15s ease',
  },
  horizon: {
    position: 'absolute',
    bottom: 0,
    left: 0,
    right: 0,
    height: 1,
    background: 'rgba(255, 255, 255, 0.2)',
  },
  toggleRow: {
    display: 'flex',
    gap: 6,
    marginTop: 2,
  },
  pillBtn: {
    flex: 1,
    padding: '6px 8px',
    borderRadius: 8,
    border: '1px solid rgba(255, 255, 255, 0.12)',
    background: 'rgba(255, 255, 255, 0.05)',
    color: '#94a3b8',
    fontSize: 11,
    fontWeight: 600,
    cursor: 'pointer',
    transition: 'all 0.2s',
  },
  pillBtnActive: {
    background: 'rgba(16, 185, 129, 0.15)',
    borderColor: '#10b981',
    color: '#10b981',
  },
}
