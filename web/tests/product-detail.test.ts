import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref, computed } from 'vue'

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal('computed', computed)

vi.stubGlobal('useState', <T>(_key: string, init: () => T) => ref<T>(init()))

vi.stubGlobal('useApi', () => vi.fn())

const mockCreateError = vi.fn((opts: { statusCode: number; statusMessage?: string }) => {
  const err = new Error(opts.statusMessage ?? String(opts.statusCode))
  ;(err as unknown as Record<string, unknown>).statusCode = opts.statusCode
  return err
})
vi.stubGlobal('createError', mockCreateError)

// ---------------------------------------------------------------------------
// Fixtures
// ---------------------------------------------------------------------------

const makeVariant = (id: number, attrs: Record<string, string>, stock = 5) => ({
  id,
  sku: `SKU-${id}`,
  price: '29.99',
  stock_quantity: stock,
  attributes: attrs,
})

const makeProduct = (overrides: Record<string, unknown> = {}) => ({
  id: 1,
  name: 'PLA Filament',
  slug: 'pla-filament',
  description: 'High quality PLA filament for 3D printing.',
  price: '19.99',
  images: ['https://example.com/image1.jpg', 'https://example.com/image2.jpg'],
  variants: [
    makeVariant(10, { color: 'Red', size: '1kg' }, 5),
    makeVariant(11, { color: 'Blue', size: '1kg' }, 0),
  ],
  attributes: { material: 'PLA' },
  reviews: [
    { id: 1, rating: 5, comment: 'Excellent filament!', customer_name: 'Alice' },
    { id: 2, rating: 4, comment: 'Good value.', customer_name: 'Bob' },
  ],
  ...overrides,
})

// ---------------------------------------------------------------------------
// Default stubs
// ---------------------------------------------------------------------------

const globalStubs = {
  NuxtLink: { template: '<a><slot /></a>' },
}

// ---------------------------------------------------------------------------
// Helper: stub useProducts and useCart and useRoute for each test
// ---------------------------------------------------------------------------

function setupStubs({
  product = makeProduct(),
  fetchProductBySlug = vi.fn().mockResolvedValue(product),
  addItem = vi.fn().mockResolvedValue(undefined),
  slug = 'pla-filament',
  error = ref<string | null>(null),
  user = ref<{ name: string } | null>(null),
}: {
  product?: ReturnType<typeof makeProduct> | null
  fetchProductBySlug?: ReturnType<typeof vi.fn>
  addItem?: ReturnType<typeof vi.fn>
  slug?: string
  error?: ReturnType<typeof ref<string | null>>
  user?: ReturnType<typeof ref<{ name: string } | null>>
} = {}) {
  vi.stubGlobal('useRoute', () => ({
    params: { slug },
  }))

  vi.stubGlobal('useProducts', () => ({
    fetchProductBySlug,
    currentProduct: ref(product),
    error,
  }))

  vi.stubGlobal('useCart', () => ({
    addItem,
    cart: ref(null),
    itemCount: computed(() => 0),
  }))

  vi.stubGlobal('useAuth', () => ({
    user,
    isAuthenticated: computed(() => user.value !== null),
    logout: vi.fn(),
  }))
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('Product detail page ([slug].vue)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    vi.resetModules()
    mockCreateError.mockClear()
  })

  // ── Slug used to fetch product ───────────────────────────────────────────

  it('calls fetchProductBySlug with the route slug on mount', async () => {
    const fetchProductBySlug = vi.fn().mockResolvedValue(makeProduct())
    setupStubs({ fetchProductBySlug, slug: 'pla-filament' })

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    mount(ProductDetailPage, { global: { stubs: globalStubs } })

    // Wait for the async onMounted to complete
    await new Promise((r) => setTimeout(r, 0))

    expect(fetchProductBySlug).toHaveBeenCalledWith('pla-filament')
  })

  // ── Product info displayed ───────────────────────────────────────────────

  it('displays the product name and description', async () => {
    setupStubs()

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('PLA Filament')
    expect(wrapper.text()).toContain('High quality PLA filament for 3D printing.')
  })

  // ── Image gallery ────────────────────────────────────────────────────────

  it('renders product images in a gallery', async () => {
    setupStubs()

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))
    await wrapper.vm.$nextTick()

    const images = wrapper.findAll('img')
    expect(images.length).toBeGreaterThan(0)
  })

  // ── Variant selector ─────────────────────────────────────────────────────

  it('renders variant selector options from product variants', async () => {
    setupStubs()

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))
    await wrapper.vm.$nextTick()

    // Should show a select or radio inputs for variants
    const hasSelect = wrapper.find('select').exists()
    const hasOptions = wrapper.findAll('option').length > 0
    expect(hasSelect || hasOptions).toBe(true)
  })

  // ── Add to cart ──────────────────────────────────────────────────────────

  it('calls addItem with selected variantId and quantity when Add to Cart is clicked', async () => {
    const addItem = vi.fn().mockResolvedValue(undefined)
    setupStubs({ addItem })

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))
    await wrapper.vm.$nextTick()

    // Select a variant (the first in-stock one) via the select element
    const select = wrapper.find('select')
    if (select.exists()) {
      await select.setValue('10') // variant id 10 (Red, in stock)
      await wrapper.vm.$nextTick()
    }

    const addToCartBtn = wrapper.find('[data-testid="add-to-cart"]')
    expect(addToCartBtn.exists()).toBe(true)
    await addToCartBtn.trigger('click')
    await new Promise((r) => setTimeout(r, 0))

    expect(addItem).toHaveBeenCalledWith(10, 1)
  })

  it('disables Add to Cart button when no variant is selected', async () => {
    setupStubs()

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))
    await wrapper.vm.$nextTick()

    const addToCartBtn = wrapper.find('[data-testid="add-to-cart"]')
    expect(addToCartBtn.exists()).toBe(true)
    // Button should be disabled when no variant selected
    expect(addToCartBtn.attributes('disabled')).toBeDefined()
  })

  // ── Reviews section ──────────────────────────────────────────────────────

  it('displays approved reviews with rating and comment', async () => {
    setupStubs()

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('Excellent filament!')
    expect(wrapper.text()).toContain('Good value.')
    expect(wrapper.text()).toContain('Alice')
  })

  it('shows "No reviews yet" when product has no reviews', async () => {
    setupStubs({ product: makeProduct({ reviews: [] }) })

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    const wrapper = mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))
    await wrapper.vm.$nextTick()

    expect(wrapper.text()).toContain('No reviews yet')
  })

  // ── 404 handling ─────────────────────────────────────────────────────────

  it('calls createError with statusCode 404 when product is not found', async () => {
    setupStubs({
      product: null,
      fetchProductBySlug: vi.fn().mockResolvedValue(null),
    })

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))
    await new Promise((r) => setTimeout(r, 0))

    expect(mockCreateError).toHaveBeenCalledWith(
      expect.objectContaining({ statusCode: 404 }),
    )
  })

  it('shows error message when product is not found (404)', async () => {
    const errorRef = ref<string | null>('Product not found')
    setupStubs({
      product: null,
      fetchProductBySlug: vi.fn().mockResolvedValue(null),
      error: errorRef,
    })

    const { default: ProductDetailPage } = await import('../pages/products/[slug].vue')
    mount(ProductDetailPage, { global: { stubs: globalStubs } })

    await new Promise((r) => setTimeout(r, 0))

    // createError should be thrown when product is null
    expect(mockCreateError).toHaveBeenCalledWith(
      expect.objectContaining({ statusCode: 404 }),
    )
  })
})
