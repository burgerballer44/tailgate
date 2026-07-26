import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ command }) => {
    // DDEV exposes the primary project URL through this env var during local development.
    const ddevPrimaryUrl = process.env.DDEV_PRIMARY_URL;
    // Build the browser-facing Vite origin for HMR asset URLs.
    // - In DDEV: use its domain and force Vite's fixed port.
    // - Outside DDEV: fall back to localhost so non-DDEV local dev still works.
    const origin = ddevPrimaryUrl
        ? `${ddevPrimaryUrl.replace(/:\d+$/, '')}:5173`
        : 'http://localhost:5173';

    return {
        // Laravel plugin wires Blade's `@vite(...)` helper to these entry files.
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.js'],
                // Auto-refresh browser when Blade/PHP files change in development.
                refresh: true,
            }),
            // Tailwind v4 integration for Vite-based processing.
            tailwindcss(),
        ],
        // Keep dev-server settings out of production builds (`vite build`).
        // This avoids build-time dependence on DDEV-specific environment values.
        ...(command === 'serve'
            ? {
                  server: {
                      // Bind to all interfaces so Vite is reachable from container/networked hosts.
                      host: '0.0.0.0',
                      // Fixed port keeps HMR URL generation predictable.
                      port: 5173,
                      // Fail if the port is taken instead of silently choosing a different one.
                      strictPort: true,
                      // Explicit origin is required so generated asset/HMR URLs point to the right host.
                      origin,
                      cors: {
                          // Allow browser requests from common local domains used in this project setup.
                          origin: [
                              /https?:\/\/([A-Za-z0-9\-\.]+)?(\.ddev\.site)(?::\d+)?$/,
                              /https?:\/\/localhost(?::\d+)?$/,
                              /https?:\/\/127\.0\.0\.1(?::\d+)?$/,
                          ],
                      },
                  },
              }
            : {}),
    };
});
