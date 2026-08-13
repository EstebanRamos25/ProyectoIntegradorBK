import React, { createContext, useContext, useCallback, useRef, useState } from 'react'
import { createPortal } from 'react-dom'

// ─── Context ────────────────────────────────────────────────────────────────
const ToastContext = createContext(null)

// ─── Hook público ───────────────────────────────────────────────────────────
export function useToast() {
  const ctx = useContext(ToastContext)
  if (!ctx) throw new Error('useToast debe usarse dentro de <ToastProvider>')
  return ctx
}

// ─── Proveedor ──────────────────────────────────────────────────────────────
export function ToastProvider({ children }) {
  const [toasts, setToasts] = useState([])
  const [confirms, setConfirms] = useState([])
  const nextId = useRef(0)

  /** Muestra un toast de información, éxito, advertencia o error */
  const toast = useCallback((message, { type = 'info', duration = 3500 } = {}) => {
    const id = ++nextId.current
    setToasts((prev) => [...prev, { id, message, type }])
    setTimeout(() => setToasts((prev) => prev.filter((t) => t.id !== id)), duration)
  }, [])

  /** Muestra un diálogo de confirmación; devuelve Promise<boolean> */
  const confirm = useCallback((message) => {
    return new Promise((resolve) => {
      const id = ++nextId.current
      setConfirms((prev) => [...prev, { id, message, resolve }])
    })
  }, [])

  const resolveConfirm = (id, value) => {
    setConfirms((prev) => {
      const item = prev.find((c) => c.id === id)
      if (item) item.resolve(value)
      return prev.filter((c) => c.id !== id)
    })
  }

  return (
    <ToastContext.Provider value={{ toast, confirm }}>
      {children}

      {/* Toasts */}
      {createPortal(
        <div style={styles.toastContainer}>
          {toasts.map((t) => (
            <div key={t.id} style={{ ...styles.toast, ...styles[t.type] }}>
              <span style={styles.toastIcon}>{icons[t.type]}</span>
              <span style={styles.toastMsg}>{t.message}</span>
              <button
                style={styles.closeBtn}
                onClick={() => setToasts((prev) => prev.filter((x) => x.id !== t.id))}
              >
                ×
              </button>
            </div>
          ))}
        </div>,
        document.body
      )}

      {/* Confirm dialogs */}
      {createPortal(
        <>
          {confirms.map((c) => (
            <div key={c.id} style={styles.overlay}>
              <div style={styles.dialog}>
                <p style={styles.dialogMsg}>{c.message}</p>
                <div style={styles.dialogBtns}>
                  <button
                    style={{ ...styles.btn, ...styles.btnCancel }}
                    onClick={() => resolveConfirm(c.id, false)}
                  >
                    Cancelar
                  </button>
                  <button
                    style={{ ...styles.btn, ...styles.btnOk }}
                    onClick={() => resolveConfirm(c.id, true)}
                  >
                    Confirmar
                  </button>
                </div>
              </div>
            </div>
          ))}
        </>,
        document.body
      )}
    </ToastContext.Provider>
  )
}

const icons = {
  info: 'ℹ️',
  success: '✅',
  warning: '⚠️',
  error: '❌',
}

const styles = {
  toastContainer: {
    position: 'fixed',
    bottom: '24px',
    right: '24px',
    zIndex: 99999,
    display: 'flex',
    flexDirection: 'column',
    gap: '10px',
    maxWidth: '360px',
    pointerEvents: 'none',
  },
  toast: {
    display: 'flex',
    alignItems: 'flex-start',
    gap: '10px',
    padding: '12px 16px',
    borderRadius: '12px',
    boxShadow: '0 8px 24px rgba(0,0,0,0.35)',
    fontSize: '13px',
    lineHeight: '1.4',
    fontFamily: 'system-ui, -apple-system, sans-serif',
    backdropFilter: 'blur(12px)',
    pointerEvents: 'auto',
    animation: 'slideUp 0.25s ease',
  },
  info: {
    background: 'rgba(30, 41, 59, 0.92)',
    color: '#e2e8f0',
    border: '1px solid rgba(100,116,139,0.4)',
  },
  success: {
    background: 'rgba(5, 46, 22, 0.92)',
    color: '#bbf7d0',
    border: '1px solid rgba(34,197,94,0.35)',
  },
  warning: {
    background: 'rgba(69, 26, 3, 0.92)',
    color: '#fed7aa',
    border: '1px solid rgba(251,146,60,0.35)',
  },
  error: {
    background: 'rgba(69, 10, 10, 0.92)',
    color: '#fecaca',
    border: '1px solid rgba(239,68,68,0.35)',
  },
  toastIcon: {
    fontSize: '15px',
    flexShrink: 0,
    marginTop: '1px',
  },
  toastMsg: {
    flex: 1,
    whiteSpace: 'pre-wrap',
  },
  closeBtn: {
    background: 'transparent',
    border: 'none',
    color: 'inherit',
    cursor: 'pointer',
    fontSize: '16px',
    lineHeight: '1',
    opacity: 0.7,
    padding: '0 0 0 4px',
    flexShrink: 0,
  },
  overlay: {
    position: 'fixed',
    inset: 0,
    background: 'rgba(0,0,0,0.55)',
    zIndex: 99998,
    display: 'flex',
    alignItems: 'center',
    justifyContent: 'center',
    backdropFilter: 'blur(3px)',
  },
  dialog: {
    background: 'rgba(15, 23, 42, 0.97)',
    border: '1px solid rgba(100,116,139,0.4)',
    borderRadius: '16px',
    padding: '28px 28px 22px',
    maxWidth: '420px',
    width: '90%',
    boxShadow: '0 20px 60px rgba(0,0,0,0.6)',
    fontFamily: 'system-ui, -apple-system, sans-serif',
  },
  dialogMsg: {
    margin: '0 0 22px',
    color: '#e2e8f0',
    fontSize: '14px',
    lineHeight: '1.55',
    whiteSpace: 'pre-wrap',
  },
  dialogBtns: {
    display: 'flex',
    gap: '10px',
    justifyContent: 'flex-end',
  },
  btn: {
    padding: '9px 22px',
    borderRadius: '8px',
    border: 'none',
    cursor: 'pointer',
    fontSize: '13px',
    fontWeight: 600,
    fontFamily: 'inherit',
    transition: 'opacity 0.15s',
  },
  btnCancel: {
    background: 'rgba(100,116,139,0.25)',
    color: '#94a3b8',
  },
  btnOk: {
    background: '#10b981',
    color: '#fff',
  },
}
