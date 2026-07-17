import { CaptureForm } from '@/components/CaptureForm';
import { SwapLine } from '@/components/SwapLine';
import { VisitBeacon } from '@/components/VisitBeacon';

export default function Home() {
  return (
    <>
      <VisitBeacon />

      <header>
        <div className="wrap">
          <a className="mark" href="#top">
            HERNIATED<b>LIFTER</b>
          </a>
        </div>
      </header>

      <main id="top">
        {/* ================= HERO ================= */}
        <div className="hero">
          <div className="wrap">
            <p className="eyebrow">
              Training app <span className="dot">·</span> Early access
            </p>
            <h1>
              Keep lifting
              <br />
              with a herniated<span className="accent"> disc.</span>
            </h1>

            <SwapLine />

            <p className="sub">
              Structured strength programs that work around lumbar disc problems —{' '}
              <b>exercise swaps</b>, <b>symptom-based progression</b>, and a clear path back to the
              bar. Built by a wrestler with two disc protrusions who didn&apos;t quit.
            </p>

            <CaptureForm
              id="capture-1"
              note={
                <>
                  <b>First 200 sign-ups lock the launch price for life.</b> No spam — one email at
                  launch.
                </>
              }
            />
          </div>
        </div>

        {/* ================= STORY ================= */}
        <section className="story">
          <div className="wrap">
            <p className="label">The story</p>
            <div className="story-grid">
              <div>
                <p>
                  I wrestled competitively for 15 years. It left me with two lumbar protrusions and
                  the standard advice: stop lifting, swim, be careful.
                </p>
                <p>
                  I didn&apos;t stop. I rebuilt training around my spine — swapped the lifts that
                  hurt, kept the ones that didn&apos;t, progressed by symptoms instead of ego.
                  I&apos;m stronger now than before the diagnosis.
                </p>
                <p>This app is that system, built so you don&apos;t have to figure it out alone.</p>
                <p className="sign">— Ramin · wrestler · L4-L5 + L5-S1 · still lifting</p>
              </div>
              <div className="spine" aria-hidden="true">
                <svg viewBox="0 0 56 220" xmlns="http://www.w3.org/2000/svg">
                  <g fill="#1C2C3F">
                    <rect x="10" y="0" width="36" height="24" rx="6" />
                    <rect x="10" y="32" width="36" height="24" rx="6" />
                    <rect x="10" y="64" width="36" height="24" rx="6" />
                    <rect x="10" y="96" width="36" height="24" rx="6" />
                    <rect x="10" y="128" width="36" height="24" rx="6" />
                  </g>
                  <g fill="#2A3D53">
                    <rect x="14" y="25" width="28" height="6" rx="3" />
                    <rect x="14" y="57" width="28" height="6" rx="3" />
                    <rect x="14" y="89" width="28" height="6" rx="3" />
                  </g>
                  <rect x="14" y="121" width="28" height="6" rx="3" fill="#FF4632" />
                  <text className="lbl" x="0" y="130" transform="rotate(0)">
                    L5
                  </text>
                  <text className="lbl" x="10" y="180">
                    S1 ↑
                  </text>
                </svg>
              </div>
            </div>
          </div>
        </section>

        {/* ================= WHAT'S INSIDE ================= */}
        <section>
          <div className="wrap">
            <p className="label">What&apos;s inside</p>
            <div className="cards">
              <div className="card">
                <p className="k">Swaps</p>
                <h3>Every lift, graded by spine load</h3>
                <p>
                  When a movement hurts mid-set, the app hands you the{' '}
                  <b>next safest variation</b> — no guessing, no quitting the session.
                </p>
                <p className="ex">
                  <s>Back squat</s> <span className="arrow">→</span>{' '}
                  <span className="to">Goblet squat</span>
                  &nbsp;·&nbsp; <s>Sit-ups</s> <span className="arrow">→</span>{' '}
                  <span className="to">Dead bug</span>
                </p>
              </div>
              <div className="card">
                <p className="k">Program</p>
                <h3>Progress by symptoms, not ego</h3>
                <p>
                  An 8–12 week <b>Return to Lifting</b> plan. A two-minute check-in after each
                  session moves you up or holds you back — pain trending down while load trends up
                  is the whole point.
                </p>
              </div>
              <div className="card">
                <p className="k">Progression</p>
                <h3>Back to the bar</h3>
                <p>
                  No forever-restrictions. Levels run from floor work to full barbell lifts, and you{' '}
                  <b>earn each one by training</b> — not by waiting.
                </p>
              </div>
            </div>
          </div>
        </section>

        {/* ================= CTA 2 ================= */}
        <section className="cta2">
          <div className="wrap">
            <h2>
              Your spine isn&apos;t fragile.
              <br />
              Your <span style={{ color: 'var(--signal)' }}>program</span> was.
            </h2>
            <CaptureForm
              id="capture-2"
              note={<b>First 200 sign-ups lock the launch price for life.</b>}
            />
          </div>
        </section>
      </main>

      <footer>
        <div className="wrap">
          <p className="disclaimer">
            <b>Herniated Lifter is a training app, not medical advice.</b> If you have numbness in
            the groin or inner thighs, progressive leg weakness, or bladder / bowel changes — see a
            doctor now, not an app.
          </p>
          <div className="foot-row">
            <a href="https://www.tiktok.com/@herniatedlifter" rel="me">
              TikTok
            </a>
            <a href="https://www.instagram.com/herniatedlifter" rel="me">
              Instagram
            </a>
            <a href="https://www.youtube.com/@herniatedlifter" rel="me">
              YouTube
            </a>
          </div>
          <p className="copy">© 2026 Herniated Lifter</p>
        </div>
      </footer>
    </>
  );
}
