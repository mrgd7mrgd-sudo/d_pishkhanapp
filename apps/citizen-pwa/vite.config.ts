/// <reference types="vitest" />
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import tailwindcss from '@tailwindcss/vite';
import { VitePWA } from 'vite-plugin-pwa';
import { imagetools } from 'vite-imagetools';
import path from 'path';

export default defineConfig({
  plugins: [
    imagetools(),
    react(),
    tailwindcss(),
    VitePWA({
      registerType: 'prompt',
      injectRegister: 'auto',
      includeAssets: ['favicon.ico', 'icons/*.svg', 'fonts/**/*.woff2'],
      manifest: {
        name: 'پیشخوان هوشمند شهروندی',
        short_name: 'پیشخوان',
        description: 'سامانه جامع خدمات شهروندی و پیشخوان هوشمند',
        theme_color: '#047857',
        background_color: '#f8fafc',
        display: 'standalone',
        dir: 'rtl',
        lang: 'fa',
        icons: [
          {
            src: '/icons/icon-192.svg',
            sizes: '192x192',
            type: 'image/svg+xml',
            purpose: 'any',
          },
          {
            src: '/icons/icon-512.svg',
            sizes: '512x512',
            type: 'image/svg+xml',
            purpose: 'any',
          },
          {
            src: '/icons/icon-512.svg',
            sizes: '512x512',
            type: 'image/svg+xml',
            purpose: 'maskable',
          },
        ],
      },
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        runtimeCaching: [
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/api/v1/services') || url.pathname.startsWith('/api/v1/categories'),
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'service-catalog-cache',
              expiration: {
                maxAgeSeconds: 7 * 24 * 60 * 60, // 7 days
              },
            },
          },
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/api/v1/cases'),
            handler: 'NetworkFirst',
            options: {
              cacheName: 'cases-cache',
              networkTimeoutSeconds: 3,
              expiration: {
                maxAgeSeconds: 24 * 60 * 60, // 24 hours
              },
            },
          },
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/api/v1/offices'),
            handler: 'StaleWhileRevalidate',
            options: {
              cacheName: 'offices-cache',
              expiration: {
                maxAgeSeconds: 3 * 24 * 60 * 60, // 3 days
              },
            },
          },
          {
            urlPattern: ({ url }) => url.pathname.startsWith('/api/v1/payments') || url.pathname.startsWith('/api/v1/auth'),
            handler: 'NetworkOnly',
          },
        ],
      },
    }),
  ],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./src/test/setup.ts'],
  },
});