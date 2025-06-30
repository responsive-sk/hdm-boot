import { defineConfig } from 'vite'
import { resolve } from 'path'

export default defineConfig({
  // Base path for assets
  base: '/assets/themes/default/',
  
  // Build configuration
  build: {
    // Output directory relative to theme root
    outDir: '../../../public/assets/themes/default',
    
    // Clear output directory before build
    emptyOutDir: true,
    
    // Generate manifest for asset versioning
    manifest: true,
    
    // Rollup options
    rollupOptions: {
      input: {
        // Main application entry points
        app: resolve(__dirname, 'assets/js/app.js'),
        style: resolve(__dirname, 'assets/css/app.css')
      },
      output: {
        // Asset file naming
        assetFileNames: (assetInfo) => {
          const info = assetInfo.name.split('.')
          const ext = info[info.length - 1]
          
          if (/\.(css)$/.test(assetInfo.name)) {
            return `css/[name]-[hash].${ext}`
          }
          if (/\.(png|jpe?g|svg|gif|tiff|bmp|ico)$/i.test(assetInfo.name)) {
            return `images/[name]-[hash].${ext}`
          }
          if (/\.(woff2?|eot|ttf|otf)$/i.test(assetInfo.name)) {
            return `fonts/[name]-[hash].${ext}`
          }
          
          return `assets/[name]-[hash].${ext}`
        },
        
        // Chunk file naming
        chunkFileNames: 'js/[name]-[hash].js',
        entryFileNames: 'js/[name]-[hash].js'
      }
    },
    
    // Source maps for development
    sourcemap: process.env.NODE_ENV === 'development'
  },
  
  // CSS configuration
  css: {
    postcss: './postcss.config.js'
  },
  
  // Development server
  server: {
    host: 'localhost',
    port: 5173,
    open: false,
    cors: true,
    
    // Proxy API requests to PHP server
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true
      },
      '/blog': {
        target: 'http://localhost:8000',
        changeOrigin: true
      }
    }
  },
  
  // Plugin configuration
  plugins: [
    // Legacy browser support
    // legacy({
    //   targets: ['defaults', 'not IE 11']
    // })
  ],
  
  // Resolve configuration
  resolve: {
    alias: {
      '@': resolve(__dirname, 'assets'),
      '@css': resolve(__dirname, 'assets/css'),
      '@js': resolve(__dirname, 'assets/js'),
      '@images': resolve(__dirname, 'assets/images')
    }
  },
  
  // Define global constants
  define: {
    __THEME_NAME__: JSON.stringify('default'),
    __THEME_VERSION__: JSON.stringify('1.0.0')
  }
})
