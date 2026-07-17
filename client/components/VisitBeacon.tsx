'use client';

import { useEffect, useRef } from 'react';
import { captureSource, getSource } from '@/lib/source';

/**
 * Fire-and-forget visit log. On mount it records the campaign source for the
 * session and pings the PHP endpoint, which writes the visit row (timestamp,
 * source, salted IP+UA hash, bot flag) to SQLite. Renders nothing.
 */
export function VisitBeacon() {
  const fired = useRef(false);

  useEffect(() => {
    if (fired.current) return;
    fired.current = true;

    captureSource();
    try {
      void fetch('/api/visit.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ src: getSource() }),
        keepalive: true,
      }).catch(() => {});
    } catch {
      /* logging must never affect the page */
    }
  }, []);

  return null;
}
