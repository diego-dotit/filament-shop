// composables/useAuth.ts
// Manages authentication state: login, register, logout, and session restoration.
// Uses useState for global reactive state that persists across page navigation.
//
// Usage:
//   const { user, isAuthenticated, login, register, logout } = useAuth()

// ---------------------------------------------------------------------------
// Types
// ---------------------------------------------------------------------------

export interface Customer {
  id: number
  name: string
  email: string
  [key: string]: unknown
}

/** Tuple returned by login/register: [result, null] on success or [null, error] on failure. */
export type AuthTuple<T> = [T, null] | [null, unknown]

// ---------------------------------------------------------------------------
// Composable
// ---------------------------------------------------------------------------

export function useAuth() {
  // Shared reactive state across all composable instances (Nuxt useState)
  const user = useState<Customer | null>('auth.user', () => null)
  const token = useState<string | null>('auth.token', () => null)

  // ── Computed ───────────────────────────────────────────────────────────────

  const isAuthenticated = computed<boolean>(() => user.value !== null)

  // ── API helper ─────────────────────────────────────────────────────────────

  const api = useApi()

  // ── Private helpers ────────────────────────────────────────────────────────

  /**
   * Persist the authenticated session into local state and localStorage.
   */
  function _setSession(customer: Customer, authToken: string): void {
    if (typeof window !== 'undefined') {
      localStorage.setItem('auth_token', authToken)
    }
    token.value = authToken
    user.value = customer
  }

  /**
   * Extract the customer object from either response shape returned by the API:
   *   - `{ data: { customer: {...} }, token }` (nested)
   *   - `{ data: {...}, token }` (flat)
   */
  function _extractCustomer(data: Customer | { customer?: Customer }): Customer {
    return (data as { customer?: Customer }).customer ?? (data as Customer)
  }

  // ── Actions ────────────────────────────────────────────────────────────────

  /**
   * Authenticate with email and password.
   * Stores the token + user in localStorage and reactive state.
   *
   * @returns [{ customer, token }, null] on success, or [null, error] on failure.
   */
  async function login(
    email: string,
    password: string,
  ): Promise<AuthTuple<{ customer: Customer; token: string }>> {
    try {
      const response = await api<{ data: Customer | { customer: Customer }; token: string }>(
        '/auth/login',
        { method: 'POST', body: { email, password } },
      )

      const customer = _extractCustomer(response.data)
      const authToken = response.token

      _setSession(customer, authToken)

      return [{ customer, token: authToken }, null]
    } catch (error) {
      return [null, error]
    }
  }

  /**
   * Register a new account and log the user in automatically.
   *
   * @returns [{ customer, token }, null] on success, or [null, error] on failure.
   */
  async function register(
    name: string,
    email: string,
    password: string,
    passwordConfirmation: string,
  ): Promise<AuthTuple<{ customer: Customer; token: string }>> {
    try {
      const response = await api<{ data: Customer | { customer: Customer }; token: string }>(
        '/auth/register',
        {
          method: 'POST',
          body: { name, email, password, password_confirmation: passwordConfirmation },
        },
      )

      const customer = _extractCustomer(response.data)
      const authToken = response.token

      _setSession(customer, authToken)

      return [{ customer, token: authToken }, null]
    } catch (error) {
      return [null, error]
    }
  }

  /**
   * Log out: clears token from localStorage and resets reactive state.
   */
  function logout(): void {
    if (typeof window !== 'undefined') {
      localStorage.removeItem('auth_token')
    }
    token.value = null
    user.value = null
  }

  /**
   * Restore the user session from localStorage by validating the stored token
   * via GET /auth/me.  Called automatically on composable initialisation.
   *
   * A 401 response means the token has expired — the session is cleared.
   */
  async function restoreSession(): Promise<void> {
    if (typeof window === 'undefined') return

    const storedToken = localStorage.getItem('auth_token')
    if (!storedToken) return

    try {
      // Validate first — do not expose the token via reactive state until confirmed valid
      const me = await api<Customer>('/auth/me')
      // Only set reactive state after successful validation
      token.value = storedToken
      user.value = me
    } catch (error: unknown) {
      const err = error as {
        status?: number
        statusCode?: number
        response?: { status?: number }
      }
      const status = err?.status ?? err?.statusCode ?? err?.response?.status

      if (status === 401) {
        // Token is invalid — clean up localStorage without touching reactive state
        if (typeof window !== 'undefined') {
          localStorage.removeItem('auth_token')
        }
      }
    }
  }

  // Automatically restore the session on composable initialisation.
  // The returned promise lets callers (and tests) await completion.
  const _initPromise = restoreSession()

  return {
    user,
    token,
    isAuthenticated,
    login,
    register,
    logout,
    restoreSession,
    /** @internal — exposed for testing so tests can await session restoration. */
    _initPromise,
  }
}
