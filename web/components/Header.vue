<template>
    <header class="site-header">
        <div class="site-header__inner">
            <!-- Brand / Home -->
            <NuxtLink to="/" class="site-header__brand">Filament Shop</NuxtLink>

            <!-- Primary navigation -->
            <nav class="site-header__nav">
                <NuxtLink to="/">Home</NuxtLink>
                <NuxtLink to="/categories">Categories</NuxtLink>
                <NuxtLink to="/cart" class="site-header__cart">
                    Cart
                    <span v-if="itemCount > 0" class="site-header__cart-count">{{
                        itemCount
                    }}</span>
                    <span v-else class="site-header__cart-count">0</span>
                </NuxtLink>
            </nav>

            <!-- Localization controls -->
            <div class="site-header__locale">
                <select
                    class="site-header__select"
                    :value="language"
                    aria-label="Language"
                    @change="
                        setLanguage(
                            ($event.target as HTMLSelectElement).value as 'en' | 'fr' | 'es'
                        )
                    "
                >
                    <option v-for="lang in availableLanguages" :key="lang" :value="lang">
                        {{ lang.toUpperCase() }}
                    </option>
                </select>

                <select
                    class="site-header__select"
                    :value="currency"
                    aria-label="Currency"
                    @change="
                        setCurrency(
                            ($event.target as HTMLSelectElement).value as 'USD' | 'EUR' | 'GBP'
                        )
                    "
                >
                    <option v-for="curr in availableCurrencies" :key="curr" :value="curr">
                        {{ curr }}
                    </option>
                </select>
            </div>

            <!-- Auth controls -->
            <div class="site-header__auth">
                <template v-if="isAuthenticated">
                    <NuxtLink to="/account">My Account</NuxtLink>
                    <button class="site-header__logout" @click="logout">Logout</button>
                </template>
                <template v-else>
                    <NuxtLink to="/login">Login</NuxtLink>
                    <NuxtLink to="/register">Register</NuxtLink>
                </template>
            </div>
        </div>
    </header>
</template>

<script setup lang="ts">
const { isAuthenticated, logout } = useAuth();
const { itemCount } = useCart();
const { language, currency, availableLanguages, availableCurrencies, setLanguage, setCurrency } =
    useLocalization();
</script>

<style scoped>
.site-header {
    position: sticky;
    top: 0;
    z-index: 100;
    background: #1a1a2e;
    color: #fff;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

.site-header__inner {
    display: flex;
    align-items: center;
    gap: 1.5rem;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0.75rem 1.5rem;
}

.site-header__brand {
    font-size: 1.25rem;
    font-weight: 700;
    color: #e94560;
    text-decoration: none;
    margin-right: auto;
}

.site-header__nav {
    display: flex;
    gap: 1.25rem;
}

.site-header__nav a,
.site-header__auth a {
    color: #ccc;
    text-decoration: none;
    font-size: 0.95rem;
    transition: color 0.15s;
}

.site-header__nav a:hover,
.site-header__auth a:hover {
    color: #fff;
}

.site-header__cart {
    display: flex;
    align-items: center;
    gap: 0.35rem;
}

.site-header__cart-count {
    background: #e94560;
    color: #fff;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 600;
    min-width: 1.25rem;
    height: 1.25rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    padding: 0 0.3rem;
}

.site-header__locale {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.site-header__select {
    background: #16213e;
    border: 1px solid #444;
    color: #ccc;
    border-radius: 4px;
    padding: 0.25rem 0.4rem;
    font-size: 0.85rem;
    cursor: pointer;
}

.site-header__select:hover {
    border-color: #888;
    color: #fff;
}

.site-header__auth {
    display: flex;
    align-items: center;
    gap: 1rem;
}

.site-header__logout {
    background: transparent;
    border: 1px solid #e94560;
    color: #e94560;
    border-radius: 4px;
    padding: 0.3rem 0.75rem;
    font-size: 0.9rem;
    cursor: pointer;
    transition:
        background 0.15s,
        color 0.15s;
}

.site-header__logout:hover {
    background: #e94560;
    color: #fff;
}
</style>
