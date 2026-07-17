/**
 * Campaign-source attribution, shared by the visit beacon and the sign-up form.
 * The tag from ?src=tt|yt|ig|reddit|… is normalized and kept for the browser
 * session so a sign-up is attributed to the source the visitor arrived from.
 */

const KEY = 'hl_src';

/** Read ?src from the URL (if present) and remember it for this session. */
export function captureSource(): void {
  try {
    const raw = new URLSearchParams(window.location.search).get('src');
    if (raw) {
      const clean = raw.toLowerCase().replace(/[^a-z0-9_-]/g, '').slice(0, 32);
      if (clean) sessionStorage.setItem(KEY, clean);
    }
  } catch {
    /* sessionStorage/URL unavailable — fall back to 'direct' at read time */
  }
}

/** The remembered source, or 'direct'. */
export function getSource(): string {
  try {
    return sessionStorage.getItem(KEY) || 'direct';
  } catch {
    return 'direct';
  }
}
