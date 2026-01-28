import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  async rewrites() {
    return [
      {
        source: '/api-proxy/:path*',
        destination: 'https://d2r.nestorovallos.com/api/v1/:path*',
      },
      {
        source: '/auth-gate/:path*',
        destination: 'https://d2r.nestorovallos.com/api/projects/2/auth/:path*',
      },
    ];
  },
};

export default nextConfig;
