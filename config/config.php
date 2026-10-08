<?php
/**
 * Site-wide configuration and helper functions.
 */

define('SITE_NAME', 'AquaFix Pro');
define('SITE_TAGLINE', 'Premium Plumbing Solutions');
define('SITE_PHONE', '055 866 5790');
define('SITE_WHATSAPP', '053 717 3421');
define('SITE_EMAIL', 'info@aquafixpro.com');
define('SITE_ADDRESS', '123 Main Street, Springfield, IL 62704');
define('SITE_HOURS', 'Mon–Fri: 7am–7pm • 24/7 Emergency Service');

define('ADMIN_USER', 'admin');
define('ADMIN_PASS', 'aquafix2024');

/** Escape output to prevent XSS. */
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

/** Redirect helper. */
function redirect($path) {
    header("Location: $path");
    exit;
}

/** Return JSON response and stop. */
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

/** Start session if not already started. */
function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

/**
 * Services list — used across Services, Home, and Booking pages.
 */
function get_services() {
    return [
        ['slug' => 'leak-detection', 'icon' => 'droplet', 'title' => 'Leak Detection', 'short' => 'Advanced acoustic & thermal leak detection', 'desc' => 'Using cutting-edge acoustic and thermal imaging technology, we pinpoint hidden leaks behind walls, under floors, and underground — minimizing disruption and repair costs.', 'price' => 'From $89'],
        ['slug' => 'drain-cleaning', 'icon' => 'waves', 'title' => 'Drain Cleaning', 'short' => 'Hydro-jetting & snaking for clear drains', 'desc' => 'From stubborn kitchen sinks to main sewer lines, our hydro-jetting and snaking services restore full flow and prevent recurring clogs.', 'price' => 'From $129'],
        ['slug' => 'water-heater', 'icon' => 'flame', 'title' => 'Water Heater Repair & Installation', 'short' => 'Tank, tankless, and hybrid systems', 'desc' => 'Repair, replace, or upgrade your water heater. We service all major brands and install energy-efficient tankless and hybrid systems.', 'price' => 'From $399'],
        ['slug' => 'pipe-repair', 'icon' => 'git-branch', 'title' => 'Pipe Repair & Replacement', 'short' => 'Burst, frozen, or corroded pipe fixes', 'desc' => 'Fast response for burst pipes, frozen lines, and corroded plumbing. We use trenchless techniques when possible to minimize yard damage.', 'price' => 'From $199'],
        ['slug' => 'fixture-install', 'icon' => 'shower-head', 'title' => 'Fixture Installation', 'short' => 'Faucets, sinks, toilets, showers & more', 'desc' => 'Upgrade your kitchen or bath with professional installation of faucets, sinks, toilets, shower systems, and garbage disposals.', 'price' => 'From $79'],
        ['slug' => 'sewer-service', 'icon' => 'alert-triangle', 'title' => 'Sewer Line Service', 'short' => 'Camera inspection & line repair', 'desc' => 'Comprehensive sewer line diagnostics with camera inspection, root removal, and full line replacement using trenchless technology.', 'price' => 'From $249'],
        ['slug' => 'emergency', 'icon' => 'siren', 'title' => '24/7 Emergency Plumbing', 'short' => 'Rapid response for urgent issues', 'desc' => 'Plumbing emergencies do not wait. Our certified technicians are on call 24 hours a day, 7 days a week for burst pipes, overflows, and gas leaks.', 'price' => 'Call for rate'],
        ['slug' => 'backflow', 'icon' => 'shield-check', 'title' => 'Backflow Testing & Prevention', 'short' => 'Certified annual testing & installation', 'desc' => 'Keep your water supply safe with certified backflow testing, repair, and prevention device installation — required for commercial properties.', 'price' => 'From $149'],
        ['slug' => 'gas-line', 'icon' => 'flame', 'title' => 'Gas Line Repair', 'short' => 'Safe gas pipe installation & leak repair', 'desc' => 'Licensed gas line installation, leak detection, and repair for stoves, fireplaces, outdoor grills, and whole-home systems.', 'price' => 'From $179'],
        ['slug' => 'water-filtration', 'icon' => 'sparkles', 'title' => 'Water Filtration Systems', 'short' => 'Whole-home & under-sink filtration', 'desc' => 'Improve water quality with whole-house filtration, reverse osmosis, and water softener systems — installed and maintained by experts.', 'price' => 'From $599'],
        ['slug' => 'sump-pump', 'icon' => 'git-branch', 'title' => 'Sump Pump Service', 'short' => 'Installation, repair & battery backup', 'desc' => 'Protect your basement from flooding with sump pump installation, repair, and battery backup systems for power-outage protection.', 'price' => 'From $349'],
        ['slug' => 'repiping', 'icon' => 'git-branch', 'title' => 'Whole-Home Repiping', 'short' => 'PEX & copper repiping specialists', 'desc' => 'Replace old, corroded galvanized or polybutylene pipes with durable PEX or copper. Minimal wall damage, lifetime warranty on materials.', 'price' => 'From $2,999'],
    ];
}

/** Find a single service by slug. */
function get_service($slug) {
    foreach (get_services() as $s) {
        if ($s['slug'] === $slug) return $s;
    }
    return null;
}

/** Testimonials data. */
function get_testimonials() {
    return [
        ['name' => 'Sarah Mitchell', 'location' => 'Springfield, IL', 'rating' => 5, 'text' => 'AquaFix found a slab leak three other plumbers missed. They were professional, on time, and the price was exactly what they quoted. Highly recommend!'],
        ['name' => 'James Rodriguez', 'location' => 'Riverdale, IL', 'rating' => 5, 'text' => 'Our water heater died on a Sunday morning. AquaFix had a technician at our door within the hour and a new unit installed by noon. Lifesavers.'],
        ['name' => 'Emily Chen', 'location' => 'Lakeside, IL', 'rating' => 5, 'text' => 'The repiping job was seamless — no mess, no surprises, and the crew was incredibly respectful of our home. Water pressure has never been better.'],
        ['name' => 'Michael Thompson', 'location' => 'Oakwood, IL', 'rating' => 4, 'text' => 'Great drain cleaning service. The technician explained what caused the clog and how to prevent it. Took a little longer than expected but worth it.'],
        ['name' => 'Patricia Davis', 'location' => 'Greenville, IL', 'rating' => 5, 'text' => 'I have used AquaFix for both my home and my restaurant. Their commercial team is top-notch — backflow testing, grease trap service, all handled perfectly.'],
        ['name' => 'Robert Johnson', 'location' => 'Fairfield, IL', 'rating' => 5, 'text'   => 'Installed a whole-house water filtration system. The difference in water taste and quality is incredible. Fair pricing and excellent workmanship.'],
    ];
}

/** Render an SVG icon by name (PHP-side, mirrors the JS version). */
function renderIcon($name) {
    $icons = [
        'droplet' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 2.69l5.66 5.66a8 8 0 1 1-11.31 0z"/></svg>',
        'waves' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M2 6c.6.5 1.2 1 2.5 1C7 7 7 5 9.5 5c2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 12c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/><path d="M2 18c.6.5 1.2 1 2.5 1 2.5 0 2.5-2 5-2 2.6 0 2.4 2 5 2 2.5 0 2.5-2 5-2 1.3 0 1.9.5 2.5 1"/></svg>',
        'flame' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M8.5 14.5A2.5 2.5 0 0 0 11 12c0-1.38-.5-2-1-3-1.072-2.143-.224-4.054 2-6 .5 2.5 2 4.9 4 6.5 2 1.6 3 3.5 3 5.5a7 7 0 1 1-14 0c0-1.153.433-2.294 1-3a2.5 2.5 0 0 0 2.5 2.5z"/></svg>',
        'git-branch' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="6" y1="3" x2="6" y2="15"/><circle cx="18" cy="6" r="3"/><circle cx="6" cy="18" r="3"/><path d="M18 9a9 9 0 0 1-9 9"/></svg>',
        'shower-head' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4l16 16"/><path d="M14 4a4 4 0 0 1 4 4"/><path d="M14 4v6"/><path d="M11 13l1.5 1.5"/><path d="M18 13l1.5 1.5"/></svg>',
        'alert-triangle' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'siren' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M7 18v-6a5 5 0 1 1 10 0v6"/><path d="M5 21a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1H5z"/><path d="M12 2v2"/><path d="M4 6l1.5 1.5"/><path d="M20 6l-1.5 1.5"/></svg>',
        'shield-check' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/><path d="M9 12l2 2 4-4"/></svg>',
        'sparkles' => '<svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 3l1.5 4.5L18 9l-4.5 1.5L12 15l-1.5-4.5L6 9l4.5-1.5z"/><path d="M19 16l.7 2.3L22 19l-2.3.7L19 22l-.7-2.3L16 19l2.3-.7z"/></svg>',
    ];
    return $icons[$name] ?? $icons['droplet'];
}

/** Stats for the home page counter. */
function get_stats() {
    return [
        ['value' => 15, 'suffix' => '+', 'label' => 'Years Experience'],
        ['value' => 12000, 'suffix' => '+', 'label' => 'Jobs Completed'],
        ['value' => 98, 'suffix' => '%', 'label' => 'Satisfaction Rate'],
        ['value' => 24, 'suffix' => '/7', 'label' => 'Emergency Service'],
    ];
}
