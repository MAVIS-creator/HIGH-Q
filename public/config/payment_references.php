<?php
/**
 * Centralized Payment Reference Generator
 * Generates unique payment references based on registration type
 */

/**
 * Generate a unique payment reference with custom prefix based on registration type
 * 
 * @param string $type Registration type (postutme, jamb, regular, tutor, admin, etc.)
 * @return string Generated payment reference
 */
function generatePaymentReference($type = 'regular') {
    $timestamp = date('YmdHis');
    $random = strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
    
    // Map registration types to prefixes
    $prefixes = [
        'postutme' => 'JAP',     // JAMB/POST-UTME
        'jamb' => 'JAP',          // JAMB
        'regular' => 'REG',       // Regular registration
        'tutor' => 'TUT',         // Tutor services
        'admin' => 'ADM',         // Admin-created payment
        'course' => 'CRS',        // Course enrollment
        'exam' => 'EXM',          // Exam registration
        'receipt' => 'RCP',       // Receipt upload
        'default' => 'PAY'        // Default fallback
    ];
    
    $prefix = $prefixes[$type] ?? $prefixes['default'];
    
    return "{$prefix}-{$timestamp}-{$random}";
}

/**
 * Get prefix for a registration type (for display purposes)
 * 
 * @param string $type Registration type
 * @return array Array with prefix and description
 */
function getPaymentReferenceInfo($type = 'regular') {
    $info = [
        'postutme' => ['prefix' => 'JAP', 'name' => 'JAMB/POST-UTME Registration'],
        'jamb' => ['prefix' => 'JAP', 'name' => 'JAMB Registration'],
        'regular' => ['prefix' => 'REG', 'name' => 'Regular Registration'],
        'tutor' => ['prefix' => 'TUT', 'name' => 'Tutor Services'],
        'admin' => ['prefix' => 'ADM', 'name' => 'Administrative Payment'],
        'course' => ['prefix' => 'CRS', 'name' => 'Course Enrollment'],
        'exam' => ['prefix' => 'EXM', 'name' => 'Exam Registration'],
        'receipt' => ['prefix' => 'RCP', 'name' => 'Receipt Upload'],
        'default' => ['prefix' => 'PAY', 'name' => 'General Payment']
    ];
    
    return $info[$type] ?? $info['default'];
}

/**
 * Parse payment reference to extract type and metadata
 * 
 * @param string $reference Payment reference
 * @return array Array with type, timestamp, and random code
 */
function parsePaymentReference($reference) {
    $parts = explode('-', $reference);
    if (count($parts) >= 3) {
        return [
            'prefix' => $parts[0],
            'timestamp' => $parts[1] ?? null,
            'code' => $parts[2] ?? null,
            'formatted_date' => isset($parts[1]) ? date('M j, Y g:i A', strtotime($parts[1])) : null
        ];
    }
    return null;
}
