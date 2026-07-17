// Copies the static export (client/out) up to the repository root, which is
// what Hostinger's Git auto-deploy publishes to public_html. Each top-level
// entry is replaced individually (copying the whole out/ dir onto its own
// ancestor is unreliable), so PHP files and other root content stay in place.

import { cpSync, rmSync, existsSync, readdirSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const here = dirname(fileURLToPath(import.meta.url));
const outDir = join(here, '..', 'out'); // client/out
const repoRoot = join(here, '..', '..'); // repo root == public_html

if (!existsSync(outDir)) {
  console.error('No export found at client/out — run "next build" first.');
  process.exit(1);
}

for (const name of readdirSync(outDir)) {
  const from = join(outDir, name);
  const to = join(repoRoot, name);
  rmSync(to, { recursive: true, force: true }); // clear stale build output
  cpSync(from, to, { recursive: true });
  console.log('copied', name);
}

console.log('Static export copied to repo root (public_html).');
