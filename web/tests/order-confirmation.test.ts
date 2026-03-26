import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref } from 'vue'

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal('definePageMeta', vi.fn())

// NuxtLink is stubbed via global stubs in mount() calls below.

// ---------------------------------------------------------------------------
// Tests for OrderConfirmation component
// ---------------------------------------------------------------------------

describe('OrderConfirmation component', () => {
  beforeEach(() => {
    vi.resetModules()
  })

  it('displays the order ID', async () => {
    const { default: OrderConfirmation } = await import('../components/OrderConfirmation.vue')
    const wrapper = mount(OrderConfirmation, {
      props: { orderId: 42, totalAmount: '99.99', createdAt: '2024-01-01T00:00:00Z' },
      global: {
        stubs: { NuxtLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    expect(wrapper.text()).toContain('42')
  })

  it('displays the order total amount', async () => {
    const { default: OrderConfirmation } = await import('../components/OrderConfirmation.vue')
    const wrapper = mount(OrderConfirmation, {
      props: { orderId: 7, totalAmount: '149.95', createdAt: '2024-01-01T00:00:00Z' },
      global: {
        stubs: { NuxtLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    expect(wrapper.text()).toContain('149.95')
  })

  it('shows an estimated delivery message', async () => {
    const { default: OrderConfirmation } = await import('../components/OrderConfirmation.vue')
    const wrapper = mount(OrderConfirmation, {
      props: { orderId: 1, totalAmount: '50.00', createdAt: '2024-01-01T00:00:00Z' },
      global: {
        stubs: { NuxtLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    expect(wrapper.text().toLowerCase()).toMatch(/delivery|business days/)
  })

  it('shows a link to /account/orders/{orderId}', async () => {
    const { default: OrderConfirmation } = await import('../components/OrderConfirmation.vue')
    const wrapper = mount(OrderConfirmation, {
      props: { orderId: 42, totalAmount: '99.99', createdAt: '2024-01-01T00:00:00Z' },
      global: {
        stubs: { NuxtLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    const links = wrapper.findAll('a')
    const orderLink = links.find((l) => l.attributes('href') === '/account/orders/42')
    expect(orderLink).toBeDefined()
  })

  it('shows a continue shopping link to /', async () => {
    const { default: OrderConfirmation } = await import('../components/OrderConfirmation.vue')
    const wrapper = mount(OrderConfirmation, {
      props: { orderId: 42, totalAmount: '99.99', createdAt: '2024-01-01T00:00:00Z' },
      global: {
        stubs: { NuxtLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    const links = wrapper.findAll('a')
    const homeLink = links.find((l) => l.attributes('href') === '/')
    expect(homeLink).toBeDefined()
  })

  it('shows a success heading or confirmation message', async () => {
    const { default: OrderConfirmation } = await import('../components/OrderConfirmation.vue')
    const wrapper = mount(OrderConfirmation, {
      props: { orderId: 1, totalAmount: '25.00', createdAt: '2024-01-01T00:00:00Z' },
      global: {
        stubs: { NuxtLink: { props: ['to'], template: '<a :href="to"><slot /></a>' } },
      },
    })

    expect(wrapper.text().toLowerCase()).toMatch(/thank you|order.*placed|success/)
  })
})
