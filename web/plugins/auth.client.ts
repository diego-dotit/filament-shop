// This plugin runs once on the client before any route middleware,
// ensuring session is restored from localStorage before auth guards fire.
import { useCart } from "~/composables/useCart";

export default defineNuxtPlugin(async () => {
    const { restoreSession } = useAuth();
    await restoreSession();

    try {
        const { fetchCart } = useCart();
        await fetchCart();
    } catch {
        // Cart load failure must not block app startup
    }
});
