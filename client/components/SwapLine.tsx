'use client';

import { useEffect, useState } from 'react';

// The hero signature: a lift that hurts, struck through and swapped for a safe
// variation, rotating on a timer. Matches the original animation and cadence.
const SWAPS: readonly [string, string][] = [
  ['Conventional deadlift', 'Trap bar, high handles'],
  ['Back squat', 'Goblet squat'],
  ['Sit-ups', 'Dead bug'],
  ['Barbell row', 'Chest-supported row'],
];

export function SwapLine() {
  const [i, setI] = useState(0);

  useEffect(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
    const t = setInterval(() => setI((n) => (n + 1) % SWAPS.length), 3600);
    return () => clearInterval(t);
  }, []);

  const [oldText, newText] = SWAPS[i];

  return (
    <p className="swapline" aria-live="off">
      {/* changing key remounts the spans so the strike/appear CSS animations restart */}
      <span className="old" key={`old-${i}`}>
        {oldText}
      </span>
      <span className="arrow">→</span>
      <span className="new" key={`new-${i}`}>
        {newText}
      </span>
    </p>
  );
}
