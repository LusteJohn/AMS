import { fileURLToPath, URL } from 'node:url'

import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import vueDevTools from 'vite-plugin-vue-devtools'
import { VitePWA } from 'vite-plugin-pwa'

// https://vite.dev/config/
export default defineConfig({
  plugins: [
    vue(),
    vueDevTools(),
    VitePWA({
      registerType: 'autoUpdate',

      manifest: {
        name: 'OJT Attendance Management System',
        short_name: 'OJT Attendance',
        description: 'Attendance Management System for OJT Students',
        theme_color: '#0d6efd',
        background_color: '#ffffff',
        display: 'standalone',
        orientation: 'portrait',

        icons: [
          {
            src: '/app_logo.jpeg',
            sizes: '192x192',
            type: 'image/jpeg'
          },
          {
            src: '/app_logo.jpeg',
            sizes: '512x512',
            type: 'image/jpeg'
          },
          {
            src: '/app_logo.jpeg',
            sizes: '512x512',
            type: 'image/jpeg',
            purpose: 'any maskable'
          }
        ]
      },

      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,jpg,jpeg,svg,woff2}']
      }
    })
  ],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./src', import.meta.url)),
    },
  },
})
