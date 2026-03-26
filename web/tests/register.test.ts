import { describe, it, expect, vi, beforeEach } from 'vitest'
import { ref, computed } from 'vue'
import { mount } from '@vue/test-utils'

// ---------------------------------------------------------------------------
// Stub Nuxt globals BEFORE any component under test is imported.
// ---------------------------------------------------------------------------

vi.stubGlobal('computed', computed)
vi.stubGlobal('ref', ref)

// useState: simulate Nuxt's shared state via a plain ref per key
vi.stubGlobal('useState', <T>(_key: string, init: () => T) => ref<T>(init()))

// useApi: return a no-op stub
vi.stubGlobal('useApi', () => vi.fn())

// definePageMeta: no-op in tests
vi.stubGlobal('definePageMeta', vi.fn())

// navigateTo mock — captured per test
const mockNavigateTo = vi.fn()
vi.stubGlobal('navigateTo', mockNavigateTo)

// useRoute: provide a minimal route object
vi.stubGlobal('useRoute', () => ({
  query: {},
  path: '/register',
}))

// useRouter mock
const mockRouterPush = vi.fn()
vi.stubGlobal('useRouter', () => ({
  push: mockRouterPush,
}))

// ---------------------------------------------------------------------------
// Default useAuth stub factory
// ---------------------------------------------------------------------------

const mockRegister = vi.fn()

function makeAuthStub({
  isAuthenticated = false,
  registerResult = [{ customer: { id: 1, name: 'Test', email: 'test@example.com' }, token: 'tok' }, null] as const,
}: {
  isAuthenticated?: boolean
  registerResult?: readonly [unknown, unknown]
} = {}) {
  mockRegister.mockResolvedValue(registerResult)
  vi.stubGlobal('useAuth', () => ({
    isAuthenticated: computed(() => isAuthenticated),
    register: mockRegister,
  }))
}

// ---------------------------------------------------------------------------
// Shared global stubs
// ---------------------------------------------------------------------------

const globalStubs = {
  NuxtLink: { template: '<a href="#"><slot /></a>' },
}

// ---------------------------------------------------------------------------
// Tests
// ---------------------------------------------------------------------------

describe('Register page', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    // Default: not authenticated, register succeeds
    makeAuthStub()
    vi.resetModules()
  })

  // ── Field rendering ────────────────────────────────────────────────────────

  it('renders name, email, password, and password-confirmation fields', async () => {
    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    expect(wrapper.find('input[name="name"]').exists()).toBe(true)
    expect(wrapper.find('input[name="email"]').exists()).toBe(true)
    expect(wrapper.find('input[name="password"]').exists()).toBe(true)
    expect(wrapper.find('input[name="password_confirmation"]').exists()).toBe(true)
  })

  it('renders a submit button', async () => {
    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    expect(wrapper.find('button[type="submit"]').exists()).toBe(true)
  })

  it('renders a login link', async () => {
    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    expect(wrapper.text()).toContain('Login')
  })

  // ── Password confirmation client-side validation ───────────────────────────

  it('shows inline error and does NOT call register when passwords do not match', async () => {
    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    await wrapper.find('input[name="name"]').setValue('Alice')
    await wrapper.find('input[name="email"]').setValue('alice@example.com')
    await wrapper.find('input[name="password"]').setValue('password123')
    await wrapper.find('input[name="password_confirmation"]').setValue('different')

    await wrapper.find('form').trigger('submit.prevent')
    await wrapper.vm.$nextTick()

    expect(mockRegister).not.toHaveBeenCalled()
    expect(wrapper.text()).toMatch(/password.*match|match.*password/i)
  })

  // ── Successful submission ──────────────────────────────────────────────────

  it('calls useAuth().register() with correct arguments on valid submit', async () => {
    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    await wrapper.find('input[name="name"]').setValue('Alice Smith')
    await wrapper.find('input[name="email"]').setValue('alice@example.com')
    await wrapper.find('input[name="password"]').setValue('password123')
    await wrapper.find('input[name="password_confirmation"]').setValue('password123')

    await wrapper.find('form').trigger('submit.prevent')
    await wrapper.vm.$nextTick()

    expect(mockRegister).toHaveBeenCalledWith(
      'Alice Smith',
      'alice@example.com',
      'password123',
      'password123',
    )
  })

  it('redirects to homepage on successful registration', async () => {
    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    await wrapper.find('input[name="name"]').setValue('Alice Smith')
    await wrapper.find('input[name="email"]').setValue('alice@example.com')
    await wrapper.find('input[name="password"]').setValue('password123')
    await wrapper.find('input[name="password_confirmation"]').setValue('password123')

    await wrapper.find('form').trigger('submit.prevent')
    // Wait for async register to resolve
    await new Promise(resolve => setTimeout(resolve, 0))
    await wrapper.vm.$nextTick()

    expect(mockNavigateTo).toHaveBeenCalledWith('/')
  })

  it('resets form fields on successful submission', async () => {
    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    await wrapper.find('input[name="name"]').setValue('Alice Smith')
    await wrapper.find('input[name="email"]').setValue('alice@example.com')
    await wrapper.find('input[name="password"]').setValue('password123')
    await wrapper.find('input[name="password_confirmation"]').setValue('password123')

    await wrapper.find('form').trigger('submit.prevent')
    await new Promise(resolve => setTimeout(resolve, 0))
    await wrapper.vm.$nextTick()

    expect((wrapper.find('input[name="name"]').element as HTMLInputElement).value).toBe('')
    expect((wrapper.find('input[name="email"]').element as HTMLInputElement).value).toBe('')
    expect((wrapper.find('input[name="password"]').element as HTMLInputElement).value).toBe('')
    expect(
      (wrapper.find('input[name="password_confirmation"]').element as HTMLInputElement).value,
    ).toBe('')
  })

  // ── API error display ──────────────────────────────────────────────────────

  it('displays error message on API failure', async () => {
    makeAuthStub({
      registerResult: [null, { message: 'Email already in use' }] as const,
    })

    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    await wrapper.find('input[name="name"]').setValue('Alice Smith')
    await wrapper.find('input[name="email"]').setValue('alice@example.com')
    await wrapper.find('input[name="password"]').setValue('password123')
    await wrapper.find('input[name="password_confirmation"]').setValue('password123')

    await wrapper.find('form').trigger('submit.prevent')
    await new Promise(resolve => setTimeout(resolve, 0))
    await wrapper.vm.$nextTick()

    expect(mockNavigateTo).not.toHaveBeenCalled()
    expect(wrapper.text()).toMatch(/error|failed|already|invalid/i)
  })

  // ── Loading state ──────────────────────────────────────────────────────────

  it('disables submit button while API call is in progress', async () => {
    // Simulate slow register
    let resolveRegister!: (value: unknown) => void
    mockRegister.mockReturnValue(
      new Promise(resolve => {
        resolveRegister = resolve
      }),
    )

    const { default: RegisterPage } = await import('../pages/register.vue')
    const wrapper = mount(RegisterPage, { global: { stubs: globalStubs } })

    await wrapper.find('input[name="name"]').setValue('Alice Smith')
    await wrapper.find('input[name="email"]').setValue('alice@example.com')
    await wrapper.find('input[name="password"]').setValue('password123')
    await wrapper.find('input[name="password_confirmation"]').setValue('password123')

    wrapper.find('form').trigger('submit.prevent')
    await wrapper.vm.$nextTick()

    const submitBtn = wrapper.find('button[type="submit"]')
    expect(submitBtn.attributes('disabled')).toBeDefined()

    // Clean up
    resolveRegister([{ customer: { id: 1, name: 'Alice', email: 'alice@example.com' }, token: 'tok' }, null])
  })

  // ── Already authenticated redirect ─────────────────────────────────────────

  it('redirects to homepage if user is already authenticated', async () => {
    makeAuthStub({ isAuthenticated: true })

    const { default: RegisterPage } = await import('../pages/register.vue')
    mount(RegisterPage, { global: { stubs: globalStubs } })
    await new Promise(resolve => setTimeout(resolve, 0))

    expect(mockNavigateTo).toHaveBeenCalledWith('/')
  })
})
