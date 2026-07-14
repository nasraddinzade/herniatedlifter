<?php
/**
 * admin.php — password-protected statistics dashboard.
 *
 * Login is a simple form + session; the password is compared with
 * password_verify() against ADMIN_PASSWORD_HASH from config.php.
 * Nothing below the login gate is rendered until authenticated.
 */

declare(strict_types=1);

require __DIR__ . '/db.php';
session_start();

$configured = defined('ADMIN_PASSWORD_HASH')
    && is_string(ADMIN_PASSWORD_HASH)
    && ADMIN_PASSWORD_HASH !== ''
    && strpos(ADMIN_PASSWORD_HASH, 'REPLACE') === false;

// ---------- logout ----------
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

// ---------- login ----------
$error = '';
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST' && isset($_POST['password'])) {
    if ($configured && password_verify((string) $_POST['password'], ADMIN_PASSWORD_HASH)) {
        session_regenerate_id(true);
        $_SESSION['admin'] = true;
        header('Location: admin.php');
        exit;
    }
    usleep(400000); // small, constant-ish delay to blunt brute force
    $error = 'Wrong password.';
}

$authed = !empty($_SESSION['admin']);

// ---------- helpers ----------
function hl_scalar(PDO $db, string $sql, array $params = [])
{
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn();
}
function h(string $s): string
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function pct($num, $den): string
{
    $den = (float) $den;
    if ($den <= 0) {
        return '0.0';
    }
    return number_format(((float) $num / $den) * 100, 1);
}

// ---------- CSV export (auth required) ----------
if ($authed && ($_GET['export'] ?? '') === 'csv') {
    $rows = hl_db()->query('SELECT email, source, created_at FROM signups ORDER BY created_at DESC');
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="herniatedlifter-signups-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['email', 'source', 'created_at_utc']);
    foreach ($rows as $r) {
        fputcsv($out, [$r['email'], $r['source'], $r['created_at']]);
    }
    fclose($out);
    exit;
}

// ---------- gather stats (only when authed) ----------
$stats = null;
if ($authed) {
    $db = hl_db();

    $win = "datetime('now','-7 days')";

    $stats = [
        'visits_all'        => (int) hl_scalar($db, 'SELECT COUNT(*) FROM visits'),
        'visits_human'      => (int) hl_scalar($db, 'SELECT COUNT(*) FROM visits WHERE is_bot = 0'),
        'visits_unique'     => (int) hl_scalar($db, 'SELECT COUNT(DISTINCT visitor_hash) FROM visits WHERE is_bot = 0'),

        'visits_all_7'      => (int) hl_scalar($db, "SELECT COUNT(*) FROM visits WHERE created_at >= $win"),
        'visits_human_7'    => (int) hl_scalar($db, "SELECT COUNT(*) FROM visits WHERE is_bot = 0 AND created_at >= $win"),
        'visits_unique_7'   => (int) hl_scalar($db, "SELECT COUNT(DISTINCT visitor_hash) FROM visits WHERE is_bot = 0 AND created_at >= $win"),

        'signups_all'       => (int) hl_scalar($db, 'SELECT COUNT(*) FROM signups'),
        'signups_7'         => (int) hl_scalar($db, "SELECT COUNT(*) FROM signups WHERE created_at >= $win"),
    ];

    // by-source breakdown
    $bySrc = [];
    foreach (['tt', 'yt', 'ig', 'reddit', 'direct'] as $s) {
        $bySrc[$s] = ['visits' => 0, 'signups' => 0];
    }
    $vs = $db->query('SELECT source, COUNT(*) n FROM visits WHERE is_bot = 0 GROUP BY source');
    foreach ($vs as $r) {
        $bySrc[$r['source']] ??= ['visits' => 0, 'signups' => 0];
        $bySrc[$r['source']]['visits'] = (int) $r['n'];
    }
    $ss = $db->query('SELECT source, COUNT(*) n FROM signups GROUP BY source');
    foreach ($ss as $r) {
        $bySrc[$r['source']] ??= ['visits' => 0, 'signups' => 0];
        $bySrc[$r['source']]['signups'] = (int) $r['n'];
    }

    // recent emails (cap the on-page table; full list is in the CSV export)
    $emails = $db->query('SELECT email, source, created_at FROM signups ORDER BY created_at DESC LIMIT 500')
                 ->fetchAll();
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="robots" content="noindex, nofollow">
<title>Admin · Herniated Lifter</title>
<style>
  :root{
    --bg:#0A121D; --panel:#0F1B2B; --line:#1C2C3F;
    --bone:#E4ECF4; --dim:#8FA3B8; --signal:#FF4632;
  }
  *{ margin:0; padding:0; box-sizing:border-box; }
  body{
    background:var(--bg); color:var(--bone);
    font-family:'Inter',system-ui,-apple-system,sans-serif;
    font-size:15px; line-height:1.5; padding:32px 20px;
  }
  a{ color:var(--signal); }
  .wrap{ max-width:900px; margin:0 auto; }
  .mono{ font-family:'IBM Plex Mono',ui-monospace,monospace; }

  /* header row */
  .top{ display:flex; align-items:baseline; justify-content:space-between; gap:16px; margin-bottom:26px; flex-wrap:wrap; }
  .top h1{ font-size:19px; letter-spacing:.02em; font-weight:600; }
  .top h1 b{ color:var(--signal); }
  .top .out{ font-size:13px; color:var(--dim); text-decoration:none; }
  .top .out:hover{ color:var(--signal); }

  /* hero conversion */
  .hero{
    background:var(--panel); border:1px solid var(--line); border-radius:12px;
    padding:30px 28px; margin-bottom:22px; text-align:center;
  }
  .hero .cap{
    font-size:11.5px; letter-spacing:.18em; text-transform:uppercase; color:var(--dim);
    margin-bottom:10px;
  }
  .hero .big{
    font-family:'IBM Plex Mono',ui-monospace,monospace;
    font-size:clamp(56px,15vw,104px); line-height:1; font-weight:500; color:var(--signal);
  }
  .hero .big .u{ font-size:.4em; color:var(--dim); margin-left:.1em; }
  .hero .den{ margin-top:10px; font-size:13.5px; color:var(--dim); }
  .hero .den b{ color:var(--bone); font-weight:600; }

  /* stat grid */
  .grid{ display:grid; grid-template-columns:repeat(auto-fit,minmax(150px,1fr)); gap:12px; margin-bottom:22px; }
  .stat{ background:var(--panel); border:1px solid var(--line); border-radius:10px; padding:16px 18px; }
  .stat .k{ font-size:11px; letter-spacing:.14em; text-transform:uppercase; color:var(--dim); }
  .stat .v{ font-family:'IBM Plex Mono',ui-monospace,monospace; font-size:28px; margin-top:6px; }
  .stat .s{ font-size:12px; color:var(--dim); margin-top:4px; }
  .stat .s b{ color:var(--bone); font-weight:600; }

  h2.sec{ font-size:12px; letter-spacing:.16em; text-transform:uppercase; color:var(--signal); margin:26px 0 12px; }

  table{ width:100%; border-collapse:collapse; background:var(--panel); border:1px solid var(--line); border-radius:10px; overflow:hidden; }
  th,td{ text-align:left; padding:11px 14px; border-bottom:1px solid var(--line); font-size:14px; }
  th{ font-size:11px; letter-spacing:.12em; text-transform:uppercase; color:var(--dim); font-weight:500; }
  tr:last-child td{ border-bottom:0; }
  td.num, th.num{ text-align:right; font-family:'IBM Plex Mono',ui-monospace,monospace; }
  td.email{ font-family:'IBM Plex Mono',ui-monospace,monospace; font-size:13px; }
  .src-tag{ font-family:'IBM Plex Mono',ui-monospace,monospace; font-size:12px; color:var(--dim); }

  .btn{
    display:inline-block; background:var(--signal); color:#160604;
    font-weight:600; font-size:14px; text-decoration:none;
    border:0; border-radius:6px; padding:10px 18px; cursor:pointer;
  }
  .btn:hover{ filter:brightness(1.08); }
  .rowbar{ display:flex; align-items:center; justify-content:space-between; gap:12px; margin:26px 0 12px; }
  .rowbar h2{ margin:0; }

  /* login */
  .login{ max-width:340px; margin:12vh auto 0; }
  .login h1{ font-size:18px; font-weight:600; margin-bottom:4px; }
  .login h1 b{ color:var(--signal); }
  .login p.desc{ color:var(--dim); font-size:13px; margin-bottom:20px; }
  .login input{
    width:100%; background:var(--panel); border:1px solid var(--line); border-radius:6px;
    color:var(--bone); font:inherit; font-size:15px; padding:13px 14px; margin-bottom:12px;
  }
  .login input:focus-visible{ outline:2px solid var(--bone); outline-offset:2px; }
  .login .btn{ width:100%; text-align:center; }
  .err{ color:var(--signal); font-size:13px; margin-bottom:12px; }
  .warn{
    background:#241206; border:1px solid #5a3410; color:#f0c9a0;
    border-radius:8px; padding:14px 16px; font-size:13px; line-height:1.55; margin-bottom:18px;
  }
  .warn code{ font-family:'IBM Plex Mono',ui-monospace,monospace; color:#ffd9b0; }
</style>
</head>
<body>
<?php if (!$authed): ?>
  <div class="login">
    <h1>HERNIATED<b>LIFTER</b> · Admin</h1>
    <p class="desc">Enter the admin password to view statistics.</p>
    <?php if (!$configured): ?>
      <div class="warn">
        <strong>Not configured yet.</strong> Copy <code>config.php.example</code> to
        <code>config.php</code> and set <code>ADMIN_PASSWORD_HASH</code>. Generate a hash with:<br><br>
        <code>php -r "echo password_hash('YOUR_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"</code>
      </div>
    <?php endif; ?>
    <?php if ($error): ?><div class="err"><?= h($error) ?></div><?php endif; ?>
    <form method="post" autocomplete="off">
      <input type="password" name="password" placeholder="Password" aria-label="Admin password" autofocus>
      <button class="btn" type="submit">Sign in</button>
    </form>
  </div>
<?php else: ?>
  <div class="wrap">
    <div class="top">
      <h1>HERNIATED<b>LIFTER</b> · Stats</h1>
      <a class="out" href="?logout=1">Sign out</a>
    </div>

    <?php
      $convAll = pct($stats['signups_all'], $stats['visits_human']);
      $conv7   = pct($stats['signups_7'],   $stats['visits_human_7']);
    ?>

    <!-- MAIN METRIC: conversion of human visits -> signups -->
    <div class="hero">
      <div class="cap">Conversion · human visits → sign-ups (all time)</div>
      <div class="big"><?= $convAll ?><span class="u">%</span></div>
      <div class="den">
        <b><?= number_format($stats['signups_all']) ?></b> sign-ups from
        <b><?= number_format($stats['visits_human']) ?></b> human visits
        &nbsp;·&nbsp; last 7 days: <b><?= $conv7 ?>%</b>
      </div>
    </div>

    <!-- visit / signup totals -->
    <div class="grid">
      <div class="stat">
        <div class="k">Visits · all</div>
        <div class="v"><?= number_format($stats['visits_all']) ?></div>
        <div class="s">7d: <b><?= number_format($stats['visits_all_7']) ?></b></div>
      </div>
      <div class="stat">
        <div class="k">Visits · humans</div>
        <div class="v"><?= number_format($stats['visits_human']) ?></div>
        <div class="s">7d: <b><?= number_format($stats['visits_human_7']) ?></b></div>
      </div>
      <div class="stat">
        <div class="k">≈ Unique</div>
        <div class="v"><?= number_format($stats['visits_unique']) ?></div>
        <div class="s">7d: <b><?= number_format($stats['visits_unique_7']) ?></b></div>
      </div>
      <div class="stat">
        <div class="k">Sign-ups</div>
        <div class="v"><?= number_format($stats['signups_all']) ?></div>
        <div class="s">7d: <b><?= number_format($stats['signups_7']) ?></b></div>
      </div>
    </div>

    <!-- by source -->
    <h2 class="sec">By source</h2>
    <table>
      <thead>
        <tr>
          <th>Source</th>
          <th class="num">Human visits</th>
          <th class="num">Sign-ups</th>
          <th class="num">Conv.</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($bySrc as $name => $row): ?>
          <tr>
            <td class="src-tag"><?= h($name) ?></td>
            <td class="num"><?= number_format($row['visits']) ?></td>
            <td class="num"><?= number_format($row['signups']) ?></td>
            <td class="num"><?= pct($row['signups'], $row['visits']) ?>%</td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <!-- emails -->
    <div class="rowbar">
      <h2 class="sec" style="margin:0;">Sign-ups<?= $stats['signups_all'] > 500 ? ' · latest 500' : '' ?></h2>
      <a class="btn" href="?export=csv">Export CSV</a>
    </div>
    <table>
      <thead>
        <tr>
          <th>Email</th>
          <th>Source</th>
          <th>When (UTC)</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($emails)): ?>
          <tr><td colspan="3" style="color:var(--dim);">No sign-ups yet.</td></tr>
        <?php else: foreach ($emails as $row): ?>
          <tr>
            <td class="email"><?= h($row['email']) ?></td>
            <td class="src-tag"><?= h($row['source']) ?></td>
            <td class="mono" style="color:var(--dim); font-size:13px;"><?= h($row['created_at']) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>
</body>
</html>
