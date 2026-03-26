// middleware/auth.ts
// Route-level authentication guard.
//
// Apply to protected pages via definePageMeta({ middleware: 'auth' }).
// Unauthenticated visitors are redirected to /login with the intended
// destination preserved as a `redirect` query parameter.

export default defineNuxtRouteMiddleware((to) => {
    const { isAuthenticated } = useAuth();

    if (!isAuthenticated.value) {
        return navigateTo(`/login?redirect=${to.path}`);
    }
});
