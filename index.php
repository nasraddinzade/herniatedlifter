<?php
/**
 * Landing page. Serves the same markup as the original index.html and, before
 * rendering, logs the visit (timestamp, source, salted IP+UA hash, bot flag).
 * A DB hiccup must never break the page, so logging is wrapped in try/catch.
 */
require __DIR__ . '/db.php';

try {
    $ua  = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $src = hl_normalize_src($_GET['src'] ?? 'direct');
    $db  = hl_db();
    $db->prepare('INSERT INTO visits (source, visitor_hash, is_bot) VALUES (?, ?, ?)')
       ->execute([$src, hl_visitor_hash(null, $ua), hl_is_bot($ua) ? 1 : 0]);
} catch (Throwable $e) {
    hl_log('visit log failed: ' . $e->getMessage());
}
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Herniated Lifter — Keep lifting with a herniated disc</title>
<meta name="description" content="A training app for lifters with lumbar disc problems. Exercise swaps, symptom-based programs, and a clear path back to the bar. Built by a wrestler with two disc protrusions.">
<meta property="og:title" content="Herniated Lifter — Keep lifting with a herniated disc">
<meta property="og:description" content="Exercise swaps, symptom-based programs, and a clear path back to the bar. Get early access.">
<meta property="og:type" content="website">
<meta property="og:url" content="https://herniatedlifter.com">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Anton&family=IBM+Plex+Mono:wght@400;500&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">
<!-- ANALYTICS: paste your Cloudflare Web Analytics snippet here (free, no cookies) -->
<style>
  :root{
    --film:      #0A121D;  /* x-ray lightbox blue-black */
    --film-2:    #0F1B2B;  /* raised panels */
    --line:      #1C2C3F;  /* hairlines on film */
    --bone:      #E4ECF4;  /* primary text, radiograph pale */
    --bone-dim:  #8FA3B8;  /* secondary text */
    --signal:    #FF4632;  /* the pain point: accent, swaps, CTA */
    --maxw: 680px;
  }
  *{ margin:0; padding:0; box-sizing:border-box; }
  html{ scroll-behavior:smooth; }
  body{
    background:var(--film);
    color:var(--bone);
    font-family:'Inter',system-ui,sans-serif;
    font-size:17px; line-height:1.6;
    -webkit-font-smoothing:antialiased;
  }
  ::selection{ background:var(--signal); color:var(--film); }

  .wrap{ max-width:var(--maxw); margin:0 auto; padding:0 22px; }

  /* ---------- header ---------- */
  header{
    padding:20px 0;
    border-bottom:1px solid var(--line);
  }
  header .wrap{ display:flex; align-items:center; justify-content:space-between; }
  .mark{
    font-family:'IBM Plex Mono',monospace; font-size:13px; letter-spacing:.14em;
    text-transform:uppercase; color:var(--bone);
    text-decoration:none;
  }
  .mark b{ color:var(--signal); font-weight:500; }
  .mark:hover{ color:var(--signal); }

  /* ---------- hero ---------- */
  .hero{ padding:72px 0 64px; }
  .eyebrow{
    font-family:'IBM Plex Mono',monospace; font-size:12px; font-weight:500;
    letter-spacing:.18em; text-transform:uppercase; color:var(--bone-dim);
    margin-bottom:22px;
  }
  .eyebrow .dot{ color:var(--signal); }
  h1{
    font-family:'Anton',sans-serif; font-weight:400;
    font-size:clamp(44px, 9.5vw, 76px);
    line-height:1.02; letter-spacing:.005em;
    text-transform:uppercase;
  }
  h1 .accent{ color:var(--signal); }

  /* the signature: live exercise swaps */
  .swapline{
    margin-top:26px; min-height:30px;
    font-family:'IBM Plex Mono',monospace; font-size:15px;
    color:var(--bone-dim); display:flex; align-items:center; gap:12px; flex-wrap:wrap;
  }
  .swapline .old{ position:relative; white-space:nowrap; }
  .swapline .old::after{
    content:''; position:absolute; left:0; top:52%; height:2px; width:0;
    background:var(--signal); animation:strike .5s .35s ease-out forwards;
  }
  .swapline .arrow{ color:var(--signal); }
  .swapline .new{
    color:var(--bone); white-space:nowrap; opacity:0;
    animation:appear .4s 1s ease-out forwards;
  }
  @keyframes strike{ to{ width:100%; } }
  @keyframes appear{ to{ opacity:1; } }
  @media (prefers-reduced-motion: reduce){
    .swapline .old::after{ animation:none; width:100%; }
    .swapline .new{ animation:none; opacity:1; }
  }

  .sub{
    margin-top:26px; color:var(--bone-dim); font-size:18px; max-width:56ch;
  }
  .sub b{ color:var(--bone); font-weight:600; }

  /* ---------- email form ---------- */
  .capture{ margin-top:36px; }
  .capture form{ display:flex; gap:10px; flex-wrap:wrap; }
  .capture input[type="email"]{
    flex:1 1 240px; min-width:0;
    background:var(--film-2); border:1px solid var(--line); border-radius:6px;
    color:var(--bone); font:inherit; font-size:16px;
    padding:15px 16px;
  }
  .capture input[type="email"]::placeholder{ color:#5C7186; }
  .capture input[type="email"]:focus-visible{
    outline:2px solid var(--bone); outline-offset:2px; border-color:transparent;
  }
  .capture button{
    flex:0 0 auto;
    background:var(--signal); color:#160604;
    font:inherit; font-weight:600; font-size:16px;
    border:0; border-radius:6px; padding:15px 26px; cursor:pointer;
  }
  .capture button:hover{ filter:brightness(1.08); }
  .capture button:active{ transform:translateY(1px); }
  .capture button:focus-visible{ outline:2px solid var(--bone); outline-offset:2px; }
  .capture .note{
    margin-top:12px; font-family:'IBM Plex Mono',monospace;
    font-size:12.5px; color:var(--bone-dim); letter-spacing:.02em;
  }
  .capture .note b{ color:var(--bone); font-weight:500; }
  .capture .done{
    display:none; padding:15px 16px;
    background:var(--film-2); border:1px solid var(--signal); border-radius:6px;
    font-size:16px;
  }
  .capture.sent form{ display:none; }
  .capture.sent .done{ display:block; }
  .hp{ position:absolute; left:-5000px; }  /* honeypot */

  /* ---------- story ---------- */
  section{ padding:64px 0; border-top:1px solid var(--line); }
  .label{
    font-family:'IBM Plex Mono',monospace; font-size:12px; font-weight:500;
    letter-spacing:.18em; text-transform:uppercase; color:var(--signal);
    margin-bottom:18px;
  }
  .story-grid{ display:flex; gap:34px; align-items:flex-start; }
  .story p{ max-width:58ch; }
  .story p + p{ margin-top:14px; }
  .story .sign{
    margin-top:22px; font-family:'IBM Plex Mono',monospace;
    font-size:13.5px; color:var(--bone-dim);
  }
  /* schematic spine, one disc lit */
  .spine{ flex:0 0 56px; }
  .spine svg{ display:block; width:56px; height:auto; }
  .spine .lbl{
    font-family:'IBM Plex Mono',monospace; font-size:11px;
    fill:var(--signal); letter-spacing:.08em;
  }
  @media (max-width:560px){ .spine{ display:none; } }

  /* ---------- inside ---------- */
  .cards{ display:grid; gap:14px; margin-top:8px; }
  .card{
    background:var(--film-2); border:1px solid var(--line); border-radius:8px;
    padding:22px 22px 24px;
  }
  .card .k{
    font-family:'IBM Plex Mono',monospace; font-size:12px; font-weight:500;
    letter-spacing:.16em; text-transform:uppercase; color:var(--signal);
  }
  .card h3{
    font-family:'Anton',sans-serif; font-weight:400; text-transform:uppercase;
    font-size:22px; letter-spacing:.01em; margin:10px 0 8px;
  }
  .card p{ color:var(--bone-dim); font-size:16px; }
  .card p b{ color:var(--bone); font-weight:600; }
  .card .ex{
    margin-top:14px; font-family:'IBM Plex Mono',monospace; font-size:13.5px;
    color:var(--bone-dim);
  }
  .card .ex s{ text-decoration-color:var(--signal); text-decoration-thickness:2px; }
  .card .ex .arrow{ color:var(--signal); }
  .card .ex .to{ color:var(--bone); }

  /* ---------- second cta ---------- */
  .cta2 h2{
    font-family:'Anton',sans-serif; font-weight:400; text-transform:uppercase;
    font-size:clamp(30px,6vw,44px); line-height:1.05; margin-bottom:8px;
  }
  .cta2 .sub{ margin-top:10px; }

  /* ---------- footer ---------- */
  footer{ border-top:1px solid var(--line); padding:44px 0 64px; }
  .disclaimer{
    font-size:13.5px; color:var(--bone-dim); max-width:62ch; line-height:1.65;
  }
  .disclaimer b{ color:var(--bone); font-weight:600; }
  .foot-row{
    margin-top:28px; display:flex; gap:20px; flex-wrap:wrap;
    font-family:'IBM Plex Mono',monospace; font-size:13px;
  }
  .foot-row a{ color:var(--bone-dim); text-decoration:none; }
  .foot-row a:hover{ color:var(--signal); }
  .foot-row a:focus-visible{ outline:2px solid var(--bone); outline-offset:2px; }
  .copy{ margin-top:20px; font-family:'IBM Plex Mono',monospace; font-size:12px; color:#5C7186; }
</style>
</head>
<body>

<header>
  <div class="wrap">
    <a class="mark" href="#top">HERNIATED<b>LIFTER</b></a>
  </div>
</header>

<main id="top">
  <!-- ================= HERO ================= -->
  <div class="hero">
    <div class="wrap">
      <p class="eyebrow">Training app <span class="dot">·</span> Early access</p>
      <h1>Keep lifting<br>with a herniated<span class="accent"> disc.</span></h1>

      <p class="swapline" id="swapline" aria-live="off">
        <span class="old" id="swap-old">Conventional deadlift</span>
        <span class="arrow">→</span>
        <span class="new" id="swap-new">Trap bar, high handles</span>
      </p>

      <p class="sub">Structured strength programs that work around lumbar disc problems —
      <b>exercise swaps</b>, <b>symptom-based progression</b>, and a clear path back to the bar.
      Built by a wrestler with two disc protrusions who didn't quit.</p>

      <div class="capture" id="capture-1">
        <form data-capture novalidate>
          <input type="text" class="hp" name="_gotcha" tabindex="-1" autocomplete="off" aria-hidden="true">
          <input type="email" name="email" required placeholder="your@email.com" aria-label="Email address">
          <button type="submit">Get early access</button>
        </form>
        <p class="done">You're in. One email when we open the doors — watch your inbox.</p>
        <p class="note"><b>First 200 sign-ups lock the launch price for life.</b> No spam — one email at launch.</p>
      </div>
    </div>
  </div>

  <!-- ================= STORY ================= -->
  <section class="story">
    <div class="wrap">
      <p class="label">The story</p>
      <div class="story-grid">
        <div>
          <p>I wrestled competitively for 15 years. It left me with two lumbar protrusions
          and the standard advice: stop lifting, swim, be careful.</p>
          <p>I didn't stop. I rebuilt training around my spine — swapped the lifts that hurt,
          kept the ones that didn't, progressed by symptoms instead of ego.
          I'm stronger now than before the diagnosis.</p>
          <p>This app is that system, built so you don't have to figure it out alone.</p>
          <p class="sign">— Ramin · wrestler · L4-L5 + L5-S1 · still lifting</p>
        </div>
        <div class="spine" aria-hidden="true">
          <svg viewBox="0 0 56 220" xmlns="http://www.w3.org/2000/svg">
            <g fill="#1C2C3F">
              <rect x="10" y="0"   width="36" height="24" rx="6"/>
              <rect x="10" y="32"  width="36" height="24" rx="6"/>
              <rect x="10" y="64"  width="36" height="24" rx="6"/>
              <rect x="10" y="96"  width="36" height="24" rx="6"/>
              <rect x="10" y="128" width="36" height="24" rx="6"/>
            </g>
            <g fill="#2A3D53">
              <rect x="14" y="25"  width="28" height="6" rx="3"/>
              <rect x="14" y="57"  width="28" height="6" rx="3"/>
              <rect x="14" y="89"  width="28" height="6" rx="3"/>
            </g>
            <rect x="14" y="121" width="28" height="6" rx="3" fill="#FF4632"/>
            <text class="lbl" x="0" y="130" transform="rotate(0)">L5</text>
            <text class="lbl" x="10" y="180">S1 ↑</text>
          </svg>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= WHAT'S INSIDE ================= -->
  <section>
    <div class="wrap">
      <p class="label">What's inside</p>
      <div class="cards">
        <div class="card">
          <p class="k">Swaps</p>
          <h3>Every lift, graded by spine load</h3>
          <p>When a movement hurts mid-set, the app hands you the <b>next safest variation</b> —
          no guessing, no quitting the session.</p>
          <p class="ex"><s>Back squat</s> <span class="arrow">→</span> <span class="to">Goblet squat</span>
          &nbsp;·&nbsp; <s>Sit-ups</s> <span class="arrow">→</span> <span class="to">Dead bug</span></p>
        </div>
        <div class="card">
          <p class="k">Program</p>
          <h3>Progress by symptoms, not ego</h3>
          <p>An 8–12 week <b>Return to Lifting</b> plan. A two-minute check-in after each session
          moves you up or holds you back — pain trending down while load trends up is the whole point.</p>
        </div>
        <div class="card">
          <p class="k">Progression</p>
          <h3>Back to the bar</h3>
          <p>No forever-restrictions. Levels run from floor work to full barbell lifts,
          and you <b>earn each one by training</b> — not by waiting.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= CTA 2 ================= -->
  <section class="cta2">
    <div class="wrap">
      <h2>Your spine isn't fragile.<br>Your <span style="color:var(--signal)">program</span> was.</h2>
      <div class="capture" id="capture-2">
        <form data-capture novalidate>
          <input type="text" class="hp" name="_gotcha" tabindex="-1" autocomplete="off" aria-hidden="true">
          <input type="email" name="email" required placeholder="your@email.com" aria-label="Email address">
          <button type="submit">Get early access</button>
        </form>
        <p class="done">You're in. One email when we open the doors — watch your inbox.</p>
        <p class="note"><b>First 200 sign-ups lock the launch price for life.</b></p>
      </div>
    </div>
  </section>
</main>

<footer>
  <div class="wrap">
    <p class="disclaimer"><b>Herniated Lifter is a training app, not medical advice.</b>
    If you have numbness in the groin or inner thighs, progressive leg weakness,
    or bladder / bowel changes — see a doctor now, not an app.</p>
    <div class="foot-row">
      <a href="https://www.tiktok.com/@herniatedlifter" rel="me">TikTok</a>
      <a href="https://www.instagram.com/herniatedlifter" rel="me">Instagram</a>
      <a href="https://www.youtube.com/@herniatedlifter" rel="me">YouTube</a>
    </div>
    <p class="copy">© 2026 Herniated Lifter</p>
  </div>
</footer>

<script>
/* ============================================================
   1) EMAIL CAPTURE — self-hosted API (api/subscribe.php)
   ============================================================ */
const SUBSCRIBE_ENDPOINT = '/api/subscribe.php';

/* Remember the campaign source (?src=tt|yt|ig|reddit|…) for this browser
   session, so the visit and the sign-up share the same attribution. */
(function(){
  try{
    const raw = new URLSearchParams(location.search).get('src');
    if(raw){
      const clean = raw.toLowerCase().replace(/[^a-z0-9_-]/g,'').slice(0,32);
      if(clean) sessionStorage.setItem('hl_src', clean);
    }
  }catch(_){}
})();
function hlSource(){
  try{ return sessionStorage.getItem('hl_src') || 'direct'; }
  catch(_){ return 'direct'; }
}

document.querySelectorAll('[data-capture]').forEach(form => {
  form.addEventListener('submit', async (e) => {
    e.preventDefault();
    const email = form.email.value.trim();
    if(!email || !email.includes('@')) { form.email.focus(); return; }
    const btn = form.querySelector('button');
    btn.disabled = true; btn.textContent = 'Sending…';
    try{
      const r = await fetch(SUBSCRIBE_ENDPOINT, {
        method:'POST',
        headers:{ 'Accept':'application/json', 'Content-Type':'application/json' },
        body: JSON.stringify({ email, src: hlSource(), _gotcha: form._gotcha.value })
      });
      if(r.ok){ form.closest('.capture').classList.add('sent'); }
      else{ throw new Error(); }
    }catch(_){
      btn.disabled = false; btn.textContent = 'Get early access';
      alert('Could not sign you up — check the connection and try again.');
    }
  });
});

/* ============================================================
   2) SIGNATURE — rotating exercise swaps in the hero
   ============================================================ */
const SWAPS = [
  ['Conventional deadlift', 'Trap bar, high handles'],
  ['Back squat',            'Goblet squat'],
  ['Sit-ups',               'Dead bug'],
  ['Barbell row',           'Chest-supported row'],
];
const line = document.getElementById('swapline');
const oldEl = document.getElementById('swap-old');
const newEl = document.getElementById('swap-new');
const reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

if(!reduced){
  let i = 0;
  setInterval(() => {
    i = (i + 1) % SWAPS.length;
    // restart CSS animations by re-inserting nodes
    const o = oldEl.cloneNode(); o.textContent = SWAPS[i][0]; o.id = 'swap-old';
    const n = newEl.cloneNode(); n.textContent = SWAPS[i][1]; n.id = 'swap-new';
    line.replaceChild(o, line.querySelector('#swap-old'));
    line.replaceChild(n, line.querySelector('#swap-new'));
  }, 3600);
}
</script>
</body>
</html>
