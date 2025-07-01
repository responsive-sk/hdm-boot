export default {
  plugins: {
    tailwindcss: {},
    autoprefixer: {},

    // CSS optimization for production
    ...(process.env.NODE_ENV === 'production' ? {
      cssnano: {
        preset: ['default', {
          discardComments: {
            removeAll: true,
          },
          normalizeWhitespace: true,
          minifySelectors: true,
          minifyFontValues: true,
          minifyParams: true,
          convertValues: true,
          reduceIdents: false, // Keep class names for debugging
          mergeRules: true,
          mergeLonghand: true,
          discardDuplicates: true,
          discardEmpty: true,
          discardUnused: false, // Keep for dynamic classes
        }]
      }
    } : {})
  }
}
