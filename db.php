<?php
/**
 * Shared data layer for the Herniated Lifter early-access capture system.
 *
 *  - SQLite via PDO, single file at data/app.sqlite
 *  - Schema is created automatically on first use
 *  - Privacy: the raw IP is never stored, only a salted hash of IP + User-Agent
 *  - Helpers for bot detection, source normalization and rate limiting
 *
 * Including this file produces no output.
 */

declare(strict_types=1);

// --- optional config (required only for admin.php) --------------------------
$__cfg = __DIR__ . '/config.php';
if (is_file($__cfg)) {
    require_once $__cfg;
}
// Fallback salt so the public landing page keeps working before config.php
// has been created. Once config.php defines APP_SALT, that value is used.
if (!defined('APP_SALT')) {
    define('APP_SALT', 'hl-default-salt-please-set-config');
}

const HL_DATA_DIR = __DIR__ . '/data';
const HL_DB_FILE  = __DIR__ . '/data/app.sqlite';
const HL_LOG_FILE = __DIR__ . '/data/app.log';

/**
 * Append an error to the protected log file. Never writes to output.
 */
function hl_log(string $msg): void
{
    @error_log('[' . gmdate('Y-m-d H:i:s') . '] ' . $msg . "\n", 3, HL_LOG_FILE);
}

/**
 * Shared PDO connection. Creates the database file and schema on first call.
 */
function hl_db(): PDO
{
    static $db = null;
    if ($db instanceof PDO) {
        return $db;
    }

    if (!is_dir(HL_DATA_DIR)) {
        @mkdir(HL_DATA_DIR, 0775, true);
    }

    $db = new PDO('sqlite:' . HL_DB_FILE, null, null, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_TIMEOUT            => 5,
    ]);
    // Better concurrency on shared hosting.
    $db->exec('PRAGMA journal_mode = WAL;');
    $db->exec('PRAGMA busy_timeout = 5000;');

    hl_init_schema($db);
    return $db;
}

/**
 * Create tables if they do not exist yet. All timestamps are UTC
 * (SQLite datetime('now')), so 7-day windows compare consistently.
 */
function hl_init_schema(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS visits (
            id           INTEGER PRIMARY KEY AUTOINCREMENT,
            created_at   TEXT    NOT NULL DEFAULT (datetime('now')),
            source       TEXT    NOT NULL DEFAULT 'direct',
            visitor_hash TEXT    NOT NULL,
            is_bot       INTEGER NOT NULL DEFAULT 0
        )
    ");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_visits_created ON visits(created_at)');
    $db->exec('CREATE INDEX IF NOT EXISTS idx_visits_source  ON visits(source)');

    $db->exec("
        CREATE TABLE IF NOT EXISTS signups (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            email      TEXT    NOT NULL UNIQUE,
            source     TEXT    NOT NULL DEFAULT 'direct',
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        )
    ");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_signups_created ON signups(created_at)');

    $db->exec("
        CREATE TABLE IF NOT EXISTS rate_hits (
            id         INTEGER PRIMARY KEY AUTOINCREMENT,
            visitor    TEXT    NOT NULL,
            created_at TEXT    NOT NULL DEFAULT (datetime('now'))
        )
    ");
    $db->exec('CREATE INDEX IF NOT EXISTS idx_rate_visitor ON rate_hits(visitor, created_at)');
}

/**
 * Best-effort client IP. Only ever used to build a hash — never stored raw.
 */
function hl_client_ip(): string
{
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if ($ip !== '') {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

/**
 * Salted hash of IP + User-Agent, for a rough uniqueness estimate.
 * The raw IP is intentionally never persisted or recoverable.
 */
function hl_visitor_hash(?string $ip = null, ?string $ua = null): string
{
    $ip = $ip ?? hl_client_ip();
    $ua = $ua ?? ($_SERVER['HTTP_USER_AGENT'] ?? '');
    return hash('sha256', $ip . '|' . $ua . '|' . APP_SALT);
}

/**
 * Simple User-Agent bot filter. An empty UA is treated as a bot.
 */
function hl_is_bot(?string $ua): bool
{
    if ($ua === null || $ua === '') {
        return true;
    }
    return (bool) preg_match(
        '/bot|crawl|spider|slurp|preview|curl|wget|python|headless|scan|monitor|facebookexternalhit|bingpreview|feedfetcher|http[-_ ]?client/i',
        $ua
    );
}

/**
 * Normalize a source tag: lowercase, [a-z0-9_-] only, max 32 chars,
 * defaulting to "direct".
 */
function hl_normalize_src($raw): string
{
    $s = strtolower(trim((string) $raw));
    $s = preg_replace('/[^a-z0-9_-]/', '', $s);
    if (!is_string($s) || $s === '') {
        return 'direct';
    }
    return substr($s, 0, 32);
}

/**
 * Register an attempt and report whether the visitor is still within the
 * rate limit ($max requests per $windowSec seconds).
 */
function hl_rate_ok(PDO $db, string $visitor, int $max = 5, int $windowSec = 60): bool
{
    // Opportunistic cleanup of rows well outside the window.
    $cleanup = $db->prepare("DELETE FROM rate_hits WHERE created_at < datetime('now', ?)");
    $cleanup->execute(['-' . ($windowSec * 5) . ' seconds']);

    // Count attempts already inside the window (before recording this one).
    $count = $db->prepare(
        "SELECT COUNT(*) FROM rate_hits WHERE visitor = ? AND created_at > datetime('now', ?)"
    );
    $count->execute([$visitor, '-' . $windowSec . ' seconds']);
    $recent = (int) $count->fetchColumn();

    // Record this attempt regardless of the outcome.
    $db->prepare('INSERT INTO rate_hits (visitor) VALUES (?)')->execute([$visitor]);

    return $recent < $max;
}
