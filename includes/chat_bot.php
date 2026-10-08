<?php
/**
 * Smart AI chatbot for AquaFix Pro.
 * Reads real services, prices, hours, contact info from your database and config.
 * Handles typos, question variants, and replies with beautiful formatting.
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Main entry point.
 */
function chat_bot_reply(string $userMessage, ?int $userId = null): array
{
    $raw = trim($userMessage);
    $msg = mb_strtolower($raw);

    /* Normalize: remove punctuation, collapse spaces */
    $clean = preg_replace('/[^a-z0-9\s]/i', ' ', $msg);
    $clean = preg_replace('/\s+/', ' ', $clean);
    $clean = trim($clean);

    /* Typo tolerance */
    $typoMap = [
        'knole'=>'know','kno'=>'know','knoe'=>'know','nkow'=>'know',
        'servic'=>'service','servces'=>'services','servies'=>'services','servics'=>'services',
        'pric'=>'price','prise'=>'price','prices'=>'price',
        'bok'=>'book','bokking'=>'booking','bookin'=>'booking',
        'emegency'=>'emergency','emergancy'=>'emergency','urgnt'=>'urgent',
        'contct'=>'contact','cantact'=>'contact','phon'=>'phone',
        'helo'=>'hello','hellow'=>'hello','hii'=>'hi',
        'thnaks'=>'thanks','thanx'=>'thanks',
        'hous'=>'hours','huors'=>'hours',
        'availabl'=>'available','availbe'=>'available',
    ];
    $clean = str_replace(array_keys($typoMap), array_values($typoMap), $clean);

    /* ============================================================
       1. SERVICES OVERVIEW — the big "what do you offer" reply
       ============================================================ */
    if (preg_match('/\b(service|services|offer|offering|provide|what do you do|list of|things you do)\b/', $clean)) {
        return bot_reply(build_services_overview(), true);
    }

    /* ============================================================
       2. PRICING (generic)
       ============================================================ */
    if (preg_match('/\b(price|pricing|cost|charge|fee|rate|quote|estimate|how much)\b/', $clean)) {
        return bot_reply(build_pricing_overview(), true);
    }

    /* ============================================================
       3. HOURS / OPENING TIMES
       ============================================================ */
    if (preg_match('/\b(hours?|open|opening|available|when|time|schedule)\b/', $clean)) {
        return bot_reply(build_hours_message(), true);
    }

    /* ============================================================
       4. CONTACT INFO
       ============================================================ */
    if (preg_match('/\b(contact|phone|email|reach|call|number|whatsapp|address|location|where)\b/', $clean)) {
        return bot_reply(build_contact_message(), true);
    }

    /* ============================================================
       5. BOOKING
       ============================================================ */
    if (preg_match('/\b(book|booking|appointment|schedule|reserve|slot|how to book)\b/', $clean)) {
        return bot_reply(build_booking_message(), true);
    }

    /* ============================================================
       6. CANCEL / RESCHEDULE
       ============================================================ */
    if (preg_match('/\b(cancel|reschedule|postpone|change)\b/', $clean)
        && preg_match('/\b(booking|appointment)\b/', $clean)) {
        return bot_reply(
            "You can cancel a booking from your **My Bookings** page anytime — "
            . "go to your dashboard → My Bookings → Cancel.\n\n"
            . "For rescheduling, reply with your **booking number** and preferred new date, "
            . "or call " . SITE_PHONE . ". 📅", true
        );
    }

    /* ============================================================
       7. USER'S OWN BOOKINGS
       ============================================================ */
    if (preg_match('/\b(my bookings?|my appointments?|do i have|when is my)\b/', $clean)) {
        if ($userId) {
            $bookings = db_run(
                "SELECT b.*, s.title AS service_title
                 FROM bookings b LEFT JOIN services s ON s.id = b.service_id
                 WHERE b.user_id = ? ORDER BY b.created_at DESC LIMIT 5",
                [$userId]
            )->fetchAll();

            if ($bookings) {
                $lines = ["Here are your recent bookings 📋\n"];
                foreach ($bookings as $b) {
                    $date = $b['booking_date'] ? date('M j, Y', strtotime($b['booking_date'])) : 'no date';
                    $stat = ucfirst(str_replace('_', ' ', $b['status']));
                    $lines[] = "• **{$b['service_title']}** — $date ({$stat})";
                }
                $lines[] = "\nNeed to cancel or reschedule any of these?";
                return bot_reply(implode("\n", $lines), true);
            }
            return bot_reply("You don't have any bookings yet. Would you like to book? Click **Book Now** at the top. 🛠️", true);
        }
        return bot_reply("Please log in first, then I can show your bookings. Click **Login** in the top bar.", true);
    }

    /* ============================================================
       8. SPECIFIC SERVICE — match against real service titles
       ============================================================ */
    foreach (fetch_all_services() as $s) {
        $title = mb_strtolower($s['title']);
        $words = preg_split('/\s+/', preg_replace('/[^a-z ]+/i', '', $title));
        foreach ($words as $w) {
            if (mb_strlen($w) > 4 && str_contains($clean, $w)) {
                return bot_reply(
                    "**{$s['title']}** — {$s['price']}\n\n"
                    . "{$s['desc']}\n\n"
                    . "Would you like to book this? Click **Book Now** above, or call " . SITE_PHONE . ".", true
                );
            }
        }
    }

    /* ============================================================
       9. REVIEWS
       ============================================================ */
    if (preg_match('/\b(review|rating|testimonial|feedback|people say|reputation)\b/', $clean)) {
        return bot_reply(build_reviews_message(), true);
    }

    /* ============================================================
       10. EMERGENCY
       ============================================================ */
    if (preg_match('/\b(emergency|urgent|flood|burst|gas leak|flooding|asap|immediately|right now)\b/', $clean)) {
        return bot_reply(
            "🚨 **This sounds urgent — please call now:**\n\n"
            . "📞 **" . SITE_PHONE . "** (24/7 emergency line)\n"
            . "💬 WhatsApp: " . SITE_WHATSAPP . "\n\n"
            . "Our team will dispatch a technician immediately. "
            . "If this involves a **gas leak**, please leave the building and call from outside.", true
        );
    }

    /* ============================================================
       11. PACKAGES
       ============================================================ */
    if (preg_match('/\b(package|plan|subscription|maintenance)\b/', $clean)) {
        return bot_reply(build_packages_message(), true);
    }

    /* ============================================================
       12. WARRANTY
       ============================================================ */
    if (preg_match('/\b(warranty|guarantee|insured|licensed)\b/', $clean)) {
        return bot_reply(
            "Every job comes with:\n"
            . "✅ 100% Satisfaction Guarantee\n"
            . "✅ 2-Year Workmanship Warranty\n"
            . "✅ Fully licensed & insured technicians\n\n"
            . "You're completely covered. 👍", true
        );
    }

    /* ============================================================
       13. PAYMENT
       ============================================================ */
    if (preg_match('/\b(payment|pay|credit card|cash|invoice|receipt)\b/', $clean)) {
        return bot_reply(
            "We accept **cash, cards, and bank transfer**. 💳\n\n"
            . "For completed bookings, download your **PDF invoice** from **My Bookings**.", true
        );
    }

    /* ============================================================
       14. GREETING (short messages only)
       ============================================================ */
    if (strlen($clean) < 20 && preg_match('/\b(hi|hello|hey|hiya|yo|greetings)\b/', $clean)) {
        return bot_reply(build_welcome_message(), true);
    }

    /* ============================================================
       15. THANKS
       ============================================================ */
    if (preg_match('/\b(thanks|thank|appreciate|cheers)\b/', $clean)) {
        return bot_reply("You're very welcome! 😊 Anything else I can help with?", true);
    }

    /* ============================================================
       16. GOODBYE
       ============================================================ */
    if (preg_match('/\b(bye|goodbye|see you|gotta go)\b/', $clean)) {
        return bot_reply("Goodbye! 👋 Thanks for chatting with " . SITE_NAME . ". We're here 24/7. Have a great day!", true);
    }

    /* ============================================================
       FALLBACK
       ============================================================ */
    return [
        'reply'   => "Thanks for your message! 🤖 I've passed this to a human agent who will reply here shortly.\n\n"
                   . "In the meantime, you can:\n"
                   . "• 📞 Call us at " . SITE_PHONE . "\n"
                   . "• Type **'services'** to see what we offer\n"
                   . "• Type **'pricing'** to see our rates\n"
                   . "• Type **'hours'** for our working hours",
        'matched' => false,
    ];
}

/* ============================================================
   FORMATTERS — the pretty reply builders
   ============================================================ */

function build_services_overview(): string {
    $services = fetch_all_services();

    /* Group by category */
    $groups = [
        'reparaciones'  => ['label' => '🔧 Repairs & Fixes',       'items' => []],
        'water_heaters' => ['label' => '🔥 Water Heaters',          'items' => []],
        'installations' => ['label' => '🛠️ Installations & Upgrades', 'items' => []],
        'emergency'     => ['label' => '🚨 Emergency & Specialized','items' => []],
        'general'       => ['label' => '🔩 Other Services',         'items' => []],
    ];

    foreach ($services as $s) {
        $cat = $s['category'] ?? 'general';
        if (!isset($groups[$cat])) $cat = 'general';
        $groups[$cat]['items'][] = $s;
    }

    $out  = "Here's everything we do at **" . SITE_NAME . "** 👇\n\n";
    $any  = false;
    foreach ($groups as $g) {
        if (!$g['items']) continue;
        $any = true;
        $out .= $g['label'] . "\n";
        foreach ($g['items'] as $s) {
            $out .= "   • " . $s['title'] . " — **" . $s['price'] . "**\n";
        }
        $out .= "\n";
    }
    if (!$any) return "We offer plumbing services for homes and businesses. What do you need help with?";

    $out .= "🕐 **Hours:** " . SITE_HOURS . "\n";
    $out .= "📞 **Book:** " . SITE_PHONE . "\n";
    $out .= "🌐 **Online:** Click 'Book Now' in the top menu\n\n";
    $out .= "Which service would you like to know more about?";

    return $out;
}

function build_pricing_overview(): string {
    $services = fetch_all_services();
    $out = "Here are our starting prices 💰\n\n";

    foreach (array_slice($services, 0, 8) as $s) {
        $out .= "• " . $s['title'] . " — **" . $s['price'] . "**\n";
    }
    $out .= "\n💡 All quotes are upfront and flat-rate — no surprises.\n";
    $out .= "Tell me your issue and I'll point you to the right service, or type **'services'** for the full list.";
    return $out;
}

function build_hours_message(): string {
    $out  = "🕐 **Our working hours:**\n\n";
    $out .= "• **Monday – Friday:** 7:00 AM – 7:00 PM\n";
    $out .= "• **Saturday:** 8:00 AM – 5:00 PM\n";
    $out .= "• **Sunday:** Emergency calls only\n";
    $out .= "• **🚨 Emergency line:** **24/7**\n\n";
    $out .= "📞 " . SITE_PHONE . "\n";
    $out .= "You can book online anytime — just click **Book Now** in the top menu.";
    return $out;
}

function build_contact_message(): string {
    return "Here's how to reach us 📞\n\n"
         . "• **Phone:** " . SITE_PHONE . " (24/7)\n"
         . "• **WhatsApp:** " . SITE_WHATSAPP . "\n"
         . "• **Email:** " . SITE_EMAIL . "\n"
         . "• **Address:** " . SITE_ADDRESS . "\n\n"
         . "Which works best for you?";
}

function build_booking_message(): string {
    return "You can book with us in 3 easy ways:\n\n"
         . "1️⃣ **Online** — Click 'Book Now' in the top menu (fastest, 2 minutes)\n"
         . "2️⃣ **Phone** — Call " . SITE_PHONE . "\n"
         . "3️⃣ **WhatsApp** — Message " . SITE_WHATSAPP . "\n\n"
         . "Your booking is confirmed by our team within 1 business day. Which method works best?";
}

function build_reviews_message(): string {
    try {
        $stats = db_run("SELECT ROUND(AVG(rating),1) avg, COUNT(*) total FROM reviews")->fetch();
        $recent = db_run(
            "SELECT r.rating, r.comment, u.name
             FROM reviews r INNER JOIN users u ON u.id = r.user_id
             ORDER BY r.created_at DESC LIMIT 1"
        )->fetch();

        $avg = $stats['avg'] ?? 0;
        $total = (int)($stats['total'] ?? 0);

        if ($total > 0) {
            $out = "⭐ We have **$avg / 5** from $total review" . ($total !== 1 ? 's' : '') . "\n\n";
            if ($recent) {
                $out .= "Here's one from **{$recent['name']}**:\n";
                $out .= "\"{$recent['comment']}\"\n\n";
            }
            $out .= "Want to book and see for yourself? Just type **'book'**.";
            return $out;
        }
    } catch (Exception $e) {}
    return "We're proud of our **100% satisfaction guarantee** — every job is backed by our 2-year workmanship warranty. ⭐";
}

function build_packages_message(): string {
    try {
        $pkgs = db_run("SELECT title, price, price_suffix, subtitle FROM packages WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6")->fetchAll();
        if ($pkgs) {
            $out = "We offer these packages 🎁\n\n";
            foreach ($pkgs as $p) {
                $suffix = $p['price_suffix'] ?? '';
                $out .= "• **{$p['title']}** — $" . number_format((float)$p['price'], 2) . $suffix . "\n";
                if (!empty($p['subtitle'])) $out .= "   _" . $p['subtitle'] . "_\n";
            }
            $out .= "\nCheck the **Packages** page for full details, or ask me about a specific one!";
            return $out;
        }
    } catch (Exception $e) {}
    return "We offer maintenance plans and one-time packages. Check our **Packages** page!";
}

function build_welcome_message(): string {
    return "Hello! 👋 Welcome to **" . SITE_NAME . "**. I'm AQUA, your virtual assistant.\n\n"
         . "I can help with:\n"
         . "• 🔧 Our services & pricing\n"
         . "• 📅 Booking appointments\n"
         . "• 🚨 Emergency help (24/7)\n"
         . "• 📋 Your bookings\n"
         . "• 📞 Contact info\n\n"
         . "Try typing **'services'** or **'pricing'** to see what we offer!";
}

/* ============================================================
   HELPERS
   ============================================================ */

function bot_reply(string $text, bool $matched = true): array {
    return ['reply' => $text, 'matched' => $matched];
}

function fetch_all_services(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    try {
        $rows = db_run(
            "SELECT title, price, description AS `desc`, category FROM services
             WHERE is_active = 1 ORDER BY category, id ASC"
        )->fetchAll();
        if ($rows) { $cache = $rows; return $cache; }
    } catch (Exception $e) {}

    /* Fallback if category column missing or DB fails */
    $cache = [];
    foreach (get_services() as $s) {
        $cache[] = [
            'title'    => $s['title'],
            'price'    => $s['price'],
            'desc'     => $s['desc'],
            'category' => 'general',
        ];
    }
    return $cache;
}