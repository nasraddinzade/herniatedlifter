import type { Metadata } from 'next';
import { Anton, IBM_Plex_Mono, Inter } from 'next/font/google';
import './globals.css';

// Self-hosted at build time (no external font request), identical faces to the
// original Google Fonts <link>. Exposed as CSS variables used across globals.css.
const inter = Inter({
  subsets: ['latin'],
  weight: ['400', '500', '600'],
  variable: '--font-inter',
  display: 'swap',
});
const anton = Anton({
  subsets: ['latin'],
  weight: '400',
  variable: '--font-anton',
  display: 'swap',
});
const mono = IBM_Plex_Mono({
  subsets: ['latin'],
  weight: ['400', '500'],
  variable: '--font-mono',
  display: 'swap',
});

export const metadata: Metadata = {
  metadataBase: new URL('https://herniatedlifter.com'),
  title: 'Herniated Lifter — Keep lifting with a herniated disc',
  description:
    'A training app for lifters with lumbar disc problems. Exercise swaps, symptom-based programs, and a clear path back to the bar. Built by a wrestler with two disc protrusions.',
  openGraph: {
    title: 'Herniated Lifter — Keep lifting with a herniated disc',
    description:
      'Exercise swaps, symptom-based programs, and a clear path back to the bar. Get early access.',
    type: 'website',
    url: 'https://herniatedlifter.com',
  },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" className={`${inter.variable} ${anton.variable} ${mono.variable}`}>
      <body>{children}</body>
    </html>
  );
}
