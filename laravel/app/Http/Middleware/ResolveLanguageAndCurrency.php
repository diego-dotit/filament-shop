<?php

namespace App\Http\Middleware;

use App\Domains\Currency\Models\Currency;
use App\Domains\Language\Models\Language;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveLanguageAndCurrency
{
    /**
     * Handle an incoming request.
     *
     * Resolves the active Language and Currency and stores them in the request
     * attributes so downstream code can access them via:
     *   request()->attributes->get('lang')
     *   request()->attributes->get('currency')
     */
    public function handle(Request $request, Closure $next): Response
    {
        $request->attributes->set('lang', $this->resolveLanguage($request));
        $request->attributes->set('currency', $this->resolveCurrency($request));

        return $next($request);
    }

    // ── Language ─────────────────────────────────────────────────────────────

    private function resolveLanguage(Request $request): ?Language
    {
        // 1. Accept-Language header (highest priority)
        $acceptLanguage = $request->header('Accept-Language');
        if ($acceptLanguage) {
            $code     = $this->extractFirstLanguageCode($acceptLanguage);
            $language = Language::where('code', $code)->first();
            if ($language) {
                return $language;
            }
        }

        // 2. ?lang= query param
        $langParam = $request->query('lang');
        if ($langParam) {
            $language = Language::where('code', $langParam)->first();
            if ($language) {
                return $language;
            }
        }

        // 3. Default language from database
        return Language::where('is_default', true)->first();
    }

    // ── Currency ─────────────────────────────────────────────────────────────

    private function resolveCurrency(Request $request): ?Currency
    {
        // 1. Accept-Currency header (highest priority)
        $acceptCurrency = $request->header('Accept-Currency');
        if ($acceptCurrency) {
            $currency = Currency::where('code', strtoupper(trim($acceptCurrency)))->first();
            if ($currency) {
                return $currency;
            }
        }

        // 2. ?currency= query param
        $currencyParam = $request->query('currency');
        if ($currencyParam) {
            $currency = Currency::where('code', strtoupper(trim($currencyParam)))->first();
            if ($currency) {
                return $currency;
            }
        }

        // 3. Base currency from database
        return Currency::where('is_base', true)->first();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * Extract the primary language code from an Accept-Language header value.
     *
     * Examples:
     *   "en"          → "en"
     *   "en-US"       → "en"
     *   "fr;q=0.9"    → "fr"
     *   "de-AT,en;q=0.8" → "de"
     */
    private function extractFirstLanguageCode(string $header): string
    {
        // Take the first entry (before any comma)
        $first = explode(',', $header)[0];

        // Strip quality value (;q=...)
        $first = explode(';', $first)[0];

        // Strip region/script subtag (en-US → en)
        $first = explode('-', trim($first))[0];

        return strtolower(trim($first));
    }
}
