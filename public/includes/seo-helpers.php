<?php
/**
 * SEO Helpers and Branded Page Title Generator
 * Include this file at the top of public pages to set SEO-optimized page titles
 */

/**
 * Generate a branded page title with the " | High Q Tutorial" suffix
 * @param string $page_name The page-specific name
 * @param bool $include_suffix Whether to append the brand suffix (default: true)
 * @return string The formatted page title
 */
function branded_page_title(string $page_name, bool $include_suffix = true): string {
    if (!$page_name) {
        return 'High Q Tutorial | Nigeria\'s Premier Educational Academy';
    }
    
    if ($include_suffix) {
        return $page_name . ' | High Q Tutorial';
    }
    
    return $page_name;
}

/**
 * Set the page title for use in the header template
 * @param string $page_name The page-specific name
 * @param bool $include_suffix Whether to append the brand suffix
 * @return void
 */
function set_page_title(string $page_name, bool $include_suffix = true): void {
    global $pageTitle;
    $pageTitle = branded_page_title($page_name, $include_suffix);
}

/**
 * Generate canonical tag for SEO
 * @param string $url Optional URL; if empty, uses current_url()
 * @return string HTML canonical tag
 */
function generate_canonical_tag(string $url = ''): string {
    if (empty($url) && function_exists('current_url')) {
        $url = current_url();
    }
    
    if (!empty($url)) {
        return '<link rel="canonical" href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '">';
    }
    
    return '';
}

/**
 * Generate robots meta tag
 * @param string $content Content for robots tag (e.g., "index, follow" or "noindex, nofollow")
 * @return string HTML robots meta tag
 */
function generate_robots_tag(string $content = 'index, follow'): string {
    if (empty($content)) {
        $content = 'index, follow';
    }
    
    return '<meta name="robots" content="' . htmlspecialchars($content, ENT_QUOTES, 'UTF-8') . '">';
}

/**
 * Check if page should be noindexed (for sensitive/admin areas)
 * @return bool True if noindex is recommended
 */
function should_noindex_page(): bool {
    $request_uri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Noindex admin areas, payment pages, temp pages
    $noindex_patterns = [
        '/admin/',
        '/auth/',
        '/login',
        '/reset_password',
        '/verify_email',
        '/payments',
        '/tmp_',
        '/_tmp',
        '/test_',
    ];
    
    foreach ($noindex_patterns as $pattern) {
        if (strpos($request_uri, $pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Generate appropriate robots meta tag based on page type
 * @return string HTML robots meta tag or empty string
 */
function auto_robots_tag(): string {
    if (should_noindex_page()) {
        return generate_robots_tag('noindex, nofollow');
    }
    
    return generate_robots_tag('index, follow');
}

/**
 * Common page title constants
 */
define('PAGE_TITLE_HOME', 'Excellence in Education | High Q Tutorial');
define('PAGE_TITLE_ABOUT', 'About Us | High Q Tutorial');
define('PAGE_TITLE_PROGRAMS', 'Educational Programs | High Q Tutorial');
define('PAGE_TITLE_EXAMS', 'Exam Preparation | High Q Tutorial');
define('PAGE_TITLE_NEWS', 'News & Blog | High Q Tutorial');
define('PAGE_TITLE_CONTACT', 'Contact Us | High Q Tutorial');
define('PAGE_TITLE_REGISTER', 'Admission & Registration | High Q Tutorial');
define('PAGE_TITLE_FIND_PATH', 'Find Your Path Quiz | High Q Tutorial');

/**
 * SEO description constants
 */
define('SEO_DESC_HOME', 'Nigeria\'s premier tutorial academy offering JAMB, WAEC, digital skills training, and international exam preparation since 2018.');
define('SEO_DESC_ABOUT', 'Learn about High Q Tutorial\'s mission to provide excellence in academic and digital education.');
define('SEO_DESC_PROGRAMS', 'Comprehensive educational programs from WAEC/GCE to JAMB, Post-UTME, Digital Skills, and International Studies.');
define('SEO_DESC_REGISTER', 'Register for your preferred educational program at High Q Tutorial. Quick, secure enrollment.');
define('SEO_DESC_FIND_PATH', 'Take our intelligent quiz to discover your perfect educational program based on your goals and learning style.');
