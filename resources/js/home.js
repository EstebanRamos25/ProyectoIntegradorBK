import { mountTexturePreview3d } from './orchid/texturePreview3d.js'

const productCards = Array.from(document.querySelectorAll('[data-home-product-card]'))
const canvas = document.querySelector('[data-home-3d-canvas]')
const titleEl = document.querySelector('[data-home-3d-title]')
const metaEl = document.querySelector('[data-home-3d-meta]')
const emptyEl = document.querySelector('[data-home-3d-empty]')

if (canvas && productCards.length > 0) {
  let cleanup = null

  const selectProduct = async (card) => {
    const imageUrl = card?.dataset.homeProductImage
    const name = card?.dataset.homeProductName || 'Producto'
    const category = card?.dataset.homeProductCategory || ''

    if (!imageUrl) return

    productCards.forEach((item) => item.classList.toggle('is-active', item === card))

    if (titleEl) titleEl.textContent = name
    if (metaEl) metaEl.textContent = category ? category : 'Visualización 3D interactiva'
    if (emptyEl) emptyEl.classList.add('hidden')

    if (cleanup) {
      cleanup()
      cleanup = null
    }

    cleanup = await mountTexturePreview3d({ canvas, imageUrl })
  }

  const firstCard = productCards.find((card) => card.dataset.homeProductImage)
  if (firstCard) {
    selectProduct(firstCard)
  }

  productCards.forEach((card) => {
    card.addEventListener('click', () => selectProduct(card))
  })
}
