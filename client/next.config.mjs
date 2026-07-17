/** @type {import('next').NextConfig} */
const nextConfig = {
  // Static HTML export — Hostinger shared hosting serves the files directly.
  output: 'export',
  // No Next image optimizer on a static host.
  images: { unoptimized: true },
  // The landing has no ESLint config wired up; never let it block a build.
  eslint: { ignoreDuringBuilds: true },
};

export default nextConfig;
