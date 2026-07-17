<?php
/**
 * POST /api/visit.php  — visit logging beacon for the static (Next.js) client.
 *
 * The landing page is now static HTML, so the visit is logged from the client
 * on load instead of server-side in index.php. Records the same row as before:
 * timestamp, source, salted IP+UA hash, bot flag. Body: {"src": "..."}.
 *
 * Always answers 200 with JSON; failures go to the log, never to output.
 */

declare(strict_types=1);

require __DIR__ . '/../db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

try {
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false]);
        exit;
    }

    $data = json_decode((string) file_get_contents('php://input'), true);
    if (!is_array($data)) {
        $data = [];
    }

    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $src = hl_normalize_src($data['src'] ?? 'direct');

    hl_db()
        ->prepare('INSERT INTO visits (source, visitor_hash, is_bot) VALUES (?, ?, ?)')
        ->execute([$src, hl_visitor_hash(null, $ua), hl_is_bot($ua) ? 1 : 0]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    hl_log('visit beacon failed: ' . $e->getMessage());
    // Do not surface logging errors to the visitor.
    http_response_code(200);
    echo json_encode(['ok' => true]);
}
