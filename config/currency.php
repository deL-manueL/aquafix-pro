
<?php
/**
 * Currency handling — base currency is USD.
 * All prices stored in DB as USD. Conversion happens on display.
 *
 * Rates last updated: 08 October 2026
 * Source: Live rates from Google Finance
 */

if (session_status() === PHP_SESSION_NONE) session_start();

/* ---------- Available currencies with live conversion rates ---------- */
function currency_list(): array {
    return [
        'USD' => ['symbol' => '$', 'name' => 'US Dollar',       'rate' => 1.00],
        'GHS' => ['symbol' => '₵', 'name' => 'Ghana Cedi',      'rate' => 11.81],
        'EUR' => ['symbol' => '€', 'name' => 'Euro',            'rate' => 0.89],
        'GBP' => ['symbol' => '£', 'name' => 'British Pound',   'rate' => 0.76],
        'NGN' => ['symbol' => '₦', 'name' => 'Nigerian Naira',  'rate' => 1329.34],
    ];
}

/* ---------- Current currency (from session, defaults to USD) ---------- */
function current_currency(): string {
    $code = strtoupper($_SESSION['currency'] ?? 'USD');
    return array_key_exists($code, currency_list()) ? $code : 'USD';
}

function set_currency(string $code): bool {
    $code = strtoupper($code);
    if (!array_key_exists($code, currency_list())) return false;
    $_SESSION['currency'] = $code;
    return true;
}

function currency_symbol(?string $code = null): string {
    $code = $code ?? current_currency();
    return currency_list()[$code]['symbol'] ?? '$';
}

function currency_rate(?string $code = null): float {
    $code = $code ?? current_currency();
    return currency_list()[$code]['rate'] ?? 1.00;
}

/* ---------- Main conversion helper ---------- */
function format_price(float $amountUsd, ?string $forceCurrency = null): string {
    $code = $forceCurrency ?? current_currency();
    $rate = currency_rate($code);
    $sym  = currency_symbol($code);
    $converted = $amountUsd * $rate;

    /* Naira — no decimals (large numbers) */
    $decimals = in_array($code, ['NGN'], true) ? 0 : 2;

    return $sym . number_format($converted, $decimals);
}

/* ---------- Extract numeric value from a price string ---------- */
/**
 * Given a price string like "From $89" or "$2,999" or "Call for rate",
 * returns a float. Returns 0 for "Call for rate" style strings.
 */
function extract_service_price(string $priceStr): float {
    if (preg_match('/\$([\d,]+(?:\.\d{2})?)/', $priceStr, $m)) {
        return (float) str_replace(',', '', $m[1]);
    }
    return 0.0;
}

/**
 * Format a service price for display — extracts the USD number,
 * converts to current currency, keeps "From" prefix if present.
 */
function format_service_price(string $priceStr): string {
    $amount = extract_service_price($priceStr);
    $hasFrom = stripos($priceStr, 'from') !== false;

    if ($amount <= 0) {
        /* No number found (like "Call for rate") — return as-is */
        return $priceStr;
    }

    return ($hasFrom ? 'From ' : '') . format_price($amount);
}