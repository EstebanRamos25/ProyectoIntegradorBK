export const GRID_SIZE = 10

/**
 * Genera una matriz 10x10 (1 = celda activa de cuarto, 0 = vacío)
 * a partir de una plantilla predefinida.
 */
export function createPresetGrid(shape = 'rectangular') {
  const grid = Array.from({ length: GRID_SIZE }, () => Array(GRID_SIZE).fill(1))

  if (shape === 'l_shape') {
    // Recorta el cuadrante Sur-Este (filas 5..9, columnas 5..9)
    for (let r = 5; r < GRID_SIZE; r++) {
      for (let c = 5; c < GRID_SIZE; c++) {
        grid[r][c] = 0
      }
    }
  } else if (shape === 'u_shape') {
    // Recorta el centro-sur (filas 5..9, columnas 3..6)
    for (let r = 5; r < GRID_SIZE; r++) {
      for (let c = 3; c <= 6; c++) {
        grid[r][c] = 0
      }
    }
  } else if (shape === 't_shape') {
    // Recorta esquinas Sur-Oeste y Sur-Este (filas 5..9, cols 0..2 y 7..9)
    for (let r = 5; r < GRID_SIZE; r++) {
      for (let c = 0; c <= 2; c++) grid[r][c] = 0
      for (let c = 7; c < GRID_SIZE; c++) grid[r][c] = 0
    }
  } else if (shape === 'corridor') {
    // Pasillo / Rectángulo alargado central (filas 2..7, todas las columnas)
    for (let r = 0; r < GRID_SIZE; r++) {
      for (let c = 0; c < GRID_SIZE; c++) {
        grid[r][c] = r >= 2 && r <= 7 ? 1 : 0
      }
    }
  }

  return grid
}

/**
 * Normaliza y valida una matriz de cuadrícula 10x10.
 * Si no es válida o está completamente vacía, devuelve el preset indicado.
 */
export function normalizeGrid(rawGrid, fallbackShape = 'rectangular') {
  if (!Array.isArray(rawGrid) || rawGrid.length !== GRID_SIZE) {
    return createPresetGrid(fallbackShape)
  }

  let activeCount = 0
  const clean = rawGrid.map((row) => {
    if (!Array.isArray(row) || row.length !== GRID_SIZE) {
      return Array(GRID_SIZE).fill(1)
    }
    return row.map((cell) => {
      const val = cell ? 1 : 0
      if (val === 1) activeCount++
      return val
    })
  })

  if (activeCount === 0) {
    return createPresetGrid(fallbackShape)
  }

  return clean
}

/**
 * Determina si un punto en coordenadas del mundo (x, z) cae dentro de una celda activa.
 */
export function isPointInActiveCell(x, z, grid, roomSize) {
  const cleanGrid = normalizeGrid(grid)
  const w = Number(roomSize?.width || 10)
  const d = Number(roomSize?.depth || 10)
  if (w <= 0 || d <= 0) return false

  const normX = (x + w / 2) / w
  const normZ = (z + d / 2) / d

  if (normX < 0 || normX > 1 || normZ < 0 || normZ > 1) return false

  const col = Math.min(GRID_SIZE - 1, Math.max(0, Math.floor(normX * GRID_SIZE)))
  const row = Math.min(GRID_SIZE - 1, Math.max(0, Math.floor(normZ * GRID_SIZE)))

  return cleanGrid[row]?.[col] === 1
}

/**
 * Calcula las celdas activas y las métricas exactas de área de piso.
 */
export function computeFloorMetrics(grid, roomSize) {
  const cleanGrid = normalizeGrid(grid)
  const w = Number(roomSize?.width || 10)
  const d = Number(roomSize?.depth || 10)
  const cellW = w / GRID_SIZE
  const cellD = d / GRID_SIZE

  const activeCells = []
  for (let r = 0; r < GRID_SIZE; r++) {
    for (let c = 0; c < GRID_SIZE; c++) {
      if (cleanGrid[r][c] === 1) {
        const cx = -w / 2 + (c + 0.5) * cellW
        const cz = -d / 2 + (r + 0.5) * cellD
        activeCells.push({ r, c, cx, cz, width: cellW, depth: cellD })
      }
    }
  }

  const totalCells = GRID_SIZE * GRID_SIZE
  const activeRatio = activeCells.length / totalCells
  const floorAreaM2 = activeCells.length * cellW * cellD

  return {
    cellW,
    cellD,
    activeCells,
    activeCount: activeCells.length,
    activeRatio,
    floorAreaM2,
  }
}

/**
 * Genera todos los segmentos de pared perimetrales fusionando bordes colineales contiguos,
 * más los muros internos adicionales definidos por el usuario.
 *
 * @param {number[][]} grid Matriz 10x10
 * @param {{width:number, depth:number, height:number}} roomSize Dimensiones en metros
 * @param {string} roomShape Identificador de forma ('open_loft' omite pared sur exterior)
 * @param {Array} interiorWalls Muros divisorios internos [{id, axis, row, col, lengthCells}]
 */
export function computeWallSegments(grid, roomSize, roomShape = 'rectangular', interiorWalls = []) {
  const cleanGrid = normalizeGrid(grid, roomShape)
  const w = Number(roomSize?.width || 10)
  const d = Number(roomSize?.depth || 10)
  const h = Number(roomSize?.height || 3)
  const cellW = w / GRID_SIZE
  const cellD = d / GRID_SIZE

  const segments = []

  // 1. Bordes horizontales (Norte y Sur de cada celda activa)
  for (let r = 0; r < GRID_SIZE; r++) {
    // Borde Norte de la fila r
    let cStart = null
    for (let c = 0; c <= GRID_SIZE; c++) {
      const isEdge =
        c < GRID_SIZE &&
        cleanGrid[r][c] === 1 &&
        (r === 0 || cleanGrid[r - 1][c] === 0)

      if (isEdge && cStart === null) {
        cStart = c
      } else if (!isEdge && cStart !== null) {
        const span = c - cStart
        const segLen = span * cellW
        const cx = -w / 2 + (cStart + span / 2) * cellW
        const cz = -d / 2 + r * cellD
        segments.push({
          id: `wall-N-r${r}-c${cStart}-${c}`,
          wallKey: 'north',
          label: r === 0 ? 'Pared Norte' : 'Pared Interior Norte',
          axis: 'x',
          orientation: 'north', // cara interior mira hacia +Z
          cx,
          cz,
          length: segLen,
          height: h,
          normalZ: 1,
          normalX: 0,
        })
        cStart = null
      }
    }

    // Borde Sur de la fila r
    cStart = null
    for (let c = 0; c <= GRID_SIZE; c++) {
      const isEdge =
        c < GRID_SIZE &&
        cleanGrid[r][c] === 1 &&
        (r === GRID_SIZE - 1 || cleanGrid[r + 1][c] === 0)

      if (isEdge && cStart !== null && false) {
        // no-op
      }
      if (isEdge && cStart === null) {
        cStart = c
      } else if (!isEdge && cStart !== null) {
        // En modo 'open_loft' omitimos la pared sur exterior para dejar el frente abierto
        const isOuterSouth = r === GRID_SIZE - 1
        if (!(roomShape === 'open_loft' && isOuterSouth)) {
          const span = c - cStart
          const segLen = span * cellW
          const cx = -w / 2 + (cStart + span / 2) * cellW
          const cz = -d / 2 + (r + 1) * cellD
          segments.push({
            id: `wall-S-r${r}-c${cStart}-${c}`,
            wallKey: 'south',
            label: isOuterSouth ? 'Pared Sur' : 'Pared Interior Sur',
            axis: 'x',
            orientation: 'south', // cara interior mira hacia -Z
            cx,
            cz,
            length: segLen,
            height: h,
            normalZ: -1,
            normalX: 0,
          })
        }
        cStart = null
      }
    }
  }

  // 2. Bordes verticales (Oeste y Este de cada celda activa)
  for (let c = 0; c < GRID_SIZE; c++) {
    // Borde Oeste de la columna c
    let rStart = null
    for (let r = 0; r <= GRID_SIZE; r++) {
      const isEdge =
        r < GRID_SIZE &&
        cleanGrid[r][c] === 1 &&
        (c === 0 || cleanGrid[r][c - 1] === 0)

      if (isEdge && rStart === null) {
        rStart = r
      } else if (!isEdge && rStart !== null) {
        const span = r - rStart
        const segLen = span * cellD
        const cx = -w / 2 + c * cellW
        const cz = -d / 2 + (rStart + span / 2) * cellD
        segments.push({
          id: `wall-W-c${c}-r${rStart}-${r}`,
          wallKey: 'west',
          label: c === 0 ? 'Pared Oeste' : 'Pared Interior Oeste',
          axis: 'z',
          orientation: 'west', // cara interior mira hacia +X
          cx,
          cz,
          length: segLen,
          height: h,
          normalX: 1,
          normalZ: 0,
        })
        rStart = null
      }
    }

    // Borde Este de la columna c
    rStart = null
    for (let r = 0; r <= GRID_SIZE; r++) {
      const isEdge =
        r < GRID_SIZE &&
        cleanGrid[r][c] === 1 &&
        (c === GRID_SIZE - 1 || cleanGrid[r][c + 1] === 0)

      if (isEdge && rStart === null) {
        rStart = r
      } else if (!isEdge && rStart !== null) {
        const span = r - rStart
        const segLen = span * cellD
        const cx = -w / 2 + (c + 1) * cellW
        const cz = -d / 2 + (rStart + span / 2) * cellD
        segments.push({
          id: `wall-E-c${c}-r${rStart}-${r}`,
          wallKey: 'east',
          label: c === GRID_SIZE - 1 ? 'Pared Este' : 'Pared Interior Este',
          axis: 'z',
          orientation: 'east', // cara interior mira hacia -X
          cx,
          cz,
          length: segLen,
          height: h,
          normalX: -1,
          normalZ: 0,
        })
        rStart = null
      }
    }
  }

  // 3. Muros divisorios internos agregados manualmente en modo Sandbox
  if (Array.isArray(interiorWalls)) {
    interiorWalls.forEach((iw, idx) => {
      const axis = iw.axis === 'z' ? 'z' : 'x'
      const lenCells = Math.max(1, Math.min(GRID_SIZE, Number(iw.lengthCells || 3)))
      const row = Math.max(1, Math.min(GRID_SIZE - 1, Number(iw.row ?? 5)))
      const col = Math.max(1, Math.min(GRID_SIZE - 1, Number(iw.col ?? 5)))

      if (axis === 'x') {
        const segLen = lenCells * cellW
        const cx = -w / 2 + col * cellW
        const cz = -d / 2 + row * cellD
        segments.push({
          id: `interior-${iw.id || idx}`,
          wallKey: 'north',
          label: `Muro Divisorio #${idx + 1}`,
          axis: 'x',
          orientation: 'north',
          cx,
          cz,
          length: segLen,
          height: h,
          normalX: 0,
          normalZ: 1,
          isInterior: true,
        })
      } else {
        const segLen = lenCells * cellD
        const cx = -w / 2 + col * cellW
        const cz = -d / 2 + row * cellD
        segments.push({
          id: `interior-${iw.id || idx}`,
          wallKey: 'west',
          label: `Muro Divisorio #${idx + 1}`,
          axis: 'z',
          orientation: 'west',
          cx,
          cz,
          length: segLen,
          height: h,
          normalX: 1,
          normalZ: 0,
          isInterior: true,
        })
      }
    })
  }

  const totalWallAreaM2 = segments.reduce((acc, s) => acc + s.length * s.height, 0)

  return {
    segments,
    totalWallAreaM2,
  }
}

