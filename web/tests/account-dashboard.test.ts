import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { ref, computed } from 'vue'

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal('computed', computed)

// useNuxtApp: throw so composable falls back gracefully
vi.stubGlobal('useNuxtApp', () => {
  throw new Error('outside Nuxt context')
})

vi.stubGlobal('useRuntimeConfig', () => ({
  public: { apiBaseUrl: 'http://localhost:8000' },
}))

// useState: simulate Nuxt's shared state via a plain ref per key
vi.stubGlobal('useState', <T>(_key: string, init: () => T) => ref<T>(init()))

// useApi: return a no-op fetch stub (overridden per-test via useAuth stub)
vi.stubGlobal('useApi', () => vi.fn())

// navigateTo: stub for redirect assertions
const mockNavigateTo = vi.fn()
vi.stubGlobal('navigateTo', mockNavigateTo)

// definePageMeta: no-op in test env
vi.stubGlobal('definePageMeta', vi.fn())

// ---------------------------------------------------------------------------
// Shared stubs
// ---------------------------------------------------------------------------

const globalStubs = {
  NuxtLink: { template: '<a><slot /></a>' },
}

// ---------------------------------------------------------------------------
// Test fixture data
// ---------------------------------------------------------------------------

const mockCustomer = {
  id: 1,
  name: 'Alice Smith',
  first_name: 'Alice',
  last_name: 'Smith',
  email: 'alice@example.com',
  phone: '+1-555-0100',
}

// ---------------------------------------------------------------------------
// Helper: build a useAuth stub
// ---------------------------------------------------------------------------

function makeAuthStub(
  userValue: typeof mockCustomer | null = mockCustomer,
  apiMock = vi.fn(),
) {
  return () => ({
    user: ref(userValue),
    isAuthenticated: computed(() => userValue !== null),
    logout: vi.fn(),
    _api: apiMock,
  })
}

// ---------------------------------------------------------------------------
// Tests: Account Dashboard page
// ---------------------------------------------------------------------------

describe('Account Dashboard page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    mockNavigateTo.mockReset()
    vi.resetModules()
  })

  // ── Authentication guard ───────────────────────────────────────────────────

  it('redirects to /login when user is not authenticated', async () => {
    vi.stubGlobal('useAuth', makeAuthStub(null))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    mount(Dashboard, { global: { stubs: globalStubs } })

    expect(mockNavigateTo).toHaveBeenCalledWith('/login')
  })

  // ── Profile display ────────────────────────────────────────────────────────

  it('displays customer profile: first name, last name, email, phone', async () => {
    vi.stubGlobal('useAuth', makeAuthStub(mockCustomer))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    const wrapper = mount(Dashboard, { global: { stubs: globalStubs } })

    expect(wrapper.text()).toContain('Alice')
    expect(wrapper.text()).toContain('Smith')
    expect(wrapper.text()).toContain('alice@example.com')
    expect(wrapper.text()).toContain('+1-555-0100')
  })

  it('does not show the edit form when in display mode', async () => {
    vi.stubGlobal('useAuth', makeAuthStub(mockCustomer))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    const wrapper = mount(Dashboard, { global: { stubs: globalStubs } })

    // Form should be hidden, no submit button visible
    expect(wrapper.find('[data-testid="edit-form"]').exists()).toBe(false)
  })

  // ── Edit mode ──────────────────────────────────────────────────────────────

  it('shows edit form when Edit button is clicked', async () => {
    vi.stubGlobal('useAuth', makeAuthStub(mockCustomer))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    const wrapper = mount(Dashboard, { global: { stubs: globalStubs } })

    await wrapper.find('[data-testid="edit-btn"]').trigger('click')

    expect(wrapper.find('[data-testid="edit-form"]').exists()).toBe(true)
  })

  it('pre-fills edit form fields with current user values', async () => {
    vi.stubGlobal('useAuth', makeAuthStub(mockCustomer))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    const wrapper = mount(Dashboard, { global: { stubs: globalStubs } })

    await wrapper.find('[data-testid="edit-btn"]').trigger('click')

    expect((wrapper.find('[data-testid="input-first-name"]').element as HTMLInputElement).value).toBe('Alice')
    expect((wrapper.find('[data-testid="input-last-name"]').element as HTMLInputElement).value).toBe('Smith')
    expect((wrapper.find('[data-testid="input-email"]').element as HTMLInputElement).value).toBe('alice@example.com')
    expect((wrapper.find('[data-testid="input-phone"]').element as HTMLInputElement).value).toBe('+1-555-0100')
  })

  it('cancel button closes edit form without saving', async () => {
    vi.stubGlobal('useAuth', makeAuthStub(mockCustomer))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    const wrapper = mount(Dashboard, { global: { stubs: globalStubs } })

    // Open edit form
    await wrapper.find('[data-testid="edit-btn"]').trigger('click')
    expect(wrapper.find('[data-testid="edit-form"]').exists()).toBe(true)

    // Cancel
    await wrapper.find('[data-testid="cancel-btn"]').trigger('click')
    expect(wrapper.find('[data-testid="edit-form"]').exists()).toBe(false)
  })

  // ── Submit / API call ──────────────────────────────────────────────────────

  it('submit calls PUT /customers/me with updated form values', async () => {
    const mockApi = vi.fn().mockResolvedValueOnce({
      data: { ...mockCustomer, first_name: 'Alicia', last_name: 'Smith' },
    })
    vi.stubGlobal('useApi', () => mockApi)
    vi.stubGlobal('useAuth', makeAuthStub(mockCustomer))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    const wrapper = mount(Dashboard, { global: { stubs: globalStubs } })

    // Open edit form
    await wrapper.find('[data-testid="edit-btn"]').trigger('click')

    // Change first name
    const firstNameInput = wrapper.find('[data-testid="input-first-name"]')
    await firstNameInput.setValue('Alicia')

    // Submit
    await wrapper.find('[data-testid="edit-form"]').trigger('submit')
    // Allow async ops to settle
    await wrapper.vm.$nextTick()

    expect(mockApi).toHaveBeenCalledWith(
      '/customers/me',
      expect.objectContaining({
        method: 'PUT',
        body: expect.objectContaining({
          first_name: 'Alicia',
          last_name: 'Smith',
          email: 'alice@example.com',
          phone: '+1-555-0100',
        }),
      }),
    )
  })

  it('shows success message after successful profile update', async () => {
    const updatedCustomer = { ...mockCustomer, first_name: 'Alicia' }
    const mockApi = vi.fn().mockResolvedValueOnce({ data: updatedCustomer })
    vi.stubGlobal('useApi', () => mockApi)
    vi.stubGlobal('useAuth', makeAuthStub(mockCustomer))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    const wrapper = mount(Dashboard, { global: { stubs: globalStubs } })

    await wrapper.find('[data-testid="edit-btn"]').trigger('click')
    await wrapper.find('[data-testid="edit-form"]').trigger('submit')
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="success-msg"]').exists()).toBe(true)
  })

  it('displays validation error message on API failure', async () => {
    const apiError = {
      data: { errors: { email: ['The email has already been taken.'] } },
    }
    const mockApi = vi.fn().mockRejectedValueOnce(apiError)
    vi.stubGlobal('useApi', () => mockApi)
    vi.stubGlobal('useAuth', makeAuthStub(mockCustomer))

    const { default: Dashboard } = await import('../pages/account/dashboard.vue')
    const wrapper = mount(Dashboard, { global: { stubs: globalStubs } })

    await wrapper.find('[data-testid="edit-btn"]').trigger('click')
    await wrapper.find('[data-testid="edit-form"]').trigger('submit')
    await wrapper.vm.$nextTick()
    await wrapper.vm.$nextTick()

    expect(wrapper.find('[data-testid="error-msg"]').exists()).toBe(true)
  })
})
