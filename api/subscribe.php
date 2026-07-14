<?php
/**
 * POST /api/subscribe.php
 *
 * Accepts JSON: {"email": "...", "src": "...", "_gotcha": "..."}
 * Always responds with JSON. Errors go to the log file, never to output.
 *
 *  - email validated with filter_var and stored lowercase, deduped by UNIQUE
 *  - honeypot: a non-empty _gotcha returns {"ok":true} but stores nothing
 *  - rate limit: max 5 requests/minute per visitor hash -> HTTP 429
 */

declare(strict_types=1);

require __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

/** Send a JSON response and stop. */
function respond(int $status, array $payload): void
{
    http_response_code($status);
    echo json_encode($payload);
    exit;
}

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        respond(405, ['ok' => false, 'error' => 'method_not_allowed']);
    }

    // Prefer JSON body; fall back to a classic form POST.
    $raw  = file_get_contents('php://input');
    $data = json_decode((string) $raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }

    $email  = trim((string) ($data['email']   ?? ''));
    $src    = hl_normalize_src($data['src']    ?? 'direct');
    $gotcha = trim((string) ($data['_gotcha']  ?? ''));

    $db      = hl_db();
    $visitor = hl_visitor_hash();

    // Rate limit first, so nothing can hammer the database.
    if (!hl_rate_ok($db, $visitor)) {
        respond(429, ['ok' => false, 'error' => 'rate_limited']);
    }

    // Honeypot: look successful to the bot, but store nothing.
    if ($gotcha !== '') {
        respond(200, ['ok' => true]);
    }

    // Validate + normalize the email.
    $email = strtolower($email);
    if ($email === '' || strlen($email) > 254 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        respond(422, ['ok' => false, 'error' => 'invalid_email']);
    }

    // Insert; a duplicate is silently ignored so the UI can still show success.
    $db->prepare('INSERT OR IGNORE INTO signups (email, source) VALUES (?, ?)')
       ->execute([$email, $src]);

    respond(200, ['ok' => true]);

} catch (Throwable $e) {
    hl_log('subscribe failed: ' . $e->getMessage());
    respond(500, ['ok' => false, 'error' => 'server_error']);
}
