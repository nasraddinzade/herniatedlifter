'use client';

import { useState, type FormEvent, type ReactNode } from 'react';
import { getSource } from '@/lib/source';

const ENDPOINT = '/api/subscribe.php';

/**
 * Email capture form. Posts {email, src, _gotcha} to the PHP API on the same
 * origin. Keeps the original UI behavior: disabled "Sending…" button, the
 * "sent" success state, and an alert on network failure.
 */
export function CaptureForm({ id, note }: { id: string; note: ReactNode }) {
  const [sent, setSent] = useState(false);
  const [busy, setBusy] = useState(false);

  async function onSubmit(e: FormEvent<HTMLFormElement>) {
    e.preventDefault();
    const form = e.currentTarget;
    const emailInput = form.elements.namedItem('email') as HTMLInputElement;
    const gotcha = (form.elements.namedItem('_gotcha') as HTMLInputElement).value;

    const email = emailInput.value.trim();
    if (!email || !email.includes('@')) {
      emailInput.focus();
      return;
    }

    setBusy(true);
    try {
      const r = await fetch(ENDPOINT, {
        method: 'POST',
        headers: { Accept: 'application/json', 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, src: getSource(), _gotcha: gotcha }),
      });
      if (r.ok) {
        setSent(true);
      } else {
        throw new Error('bad status');
      }
    } catch {
      setBusy(false);
      alert('Could not sign you up — check the connection and try again.');
    }
  }

  return (
    <div className={sent ? 'capture sent' : 'capture'} id={id}>
      <form onSubmit={onSubmit} noValidate>
        <input
          type="text"
          className="hp"
          name="_gotcha"
          tabIndex={-1}
          autoComplete="off"
          aria-hidden="true"
        />
        <input
          type="email"
          name="email"
          required
          placeholder="your@email.com"
          aria-label="Email address"
        />
        <button type="submit" disabled={busy}>
          {busy ? 'Sending…' : 'Get early access'}
        </button>
      </form>
      <p className="done">You&apos;re in. One email when we open the doors — watch your inbox.</p>
      <p className="note">{note}</p>
    </div>
  );
}
