<?php
/**
 * Payment Reference Generator
 * Generates unique payment reference codes with type-specific prefixes
 */

/**
 * Generate a unique payment reference with prefix
 * @param string $prefix The prefix for the reference (e.g., 'JAMB', 'WAEC', 'PUTM')
 * @return string Generated reference like "JAMB-20260126-a1b2c3"
 */
function generatePaymentReference($prefix = 'PAY') {
    // Sanitize prefix - uppercase and remove special chars
    $prefix = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', $prefix));
    
    // Generate: PREFIX-YYYYMMDD-RANDOM6
    $date = date('Ymd');
    $random = substr(bin2hex(random_bytes(3)), 0, 6);
    
    return $prefix . '-' . $date . '-' . $random;
}
