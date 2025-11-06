import create from 'zustand'

export const useThreeStore = create((set) => ({
  scene: null,
  slots: { floor: 'Floor', walls: 'Walls' },
  materials: { floor: 'wood', walls: 'ceramic' },
  setScene: (scene) => set({ scene }),
  setMaterial: (slot, kind) => set((s) => ({ materials: { ...s.materials, [slot]: kind } })),
}))
