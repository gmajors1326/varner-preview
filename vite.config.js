import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  base: './',
  build: {
    target: 'es2022',
    sourcemap: true,
    cssCodeSplit: true,
    chunkSizeWarningLimit: 600,
    rollupOptions: {
      output: {
        manualChunks(id) {
          if (id.includes('node_modules/react/') || id.includes('node_modules/react-dom/') || id.includes('node_modules/scheduler/')) {
            return 'react-vendor'
          }
          if (id.includes('node_modules/lucide-react/')) {
            return 'lucide-vendor'
          }
          if (id.includes('node_modules/recharts/') || id.includes('node_modules/d3-')) {
            return 'recharts-vendor'
          }
          if (id.includes('node_modules/@dnd-kit/')) {
            return 'dnd-vendor'
          }
          if (id.includes('node_modules/jszip/')) {
            return 'jszip-vendor'
          }
        }
      }
    }
  }
})

