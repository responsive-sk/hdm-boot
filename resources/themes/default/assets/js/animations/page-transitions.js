/**
 * Page Transitions
 * 
 * Handles smooth page transitions and loading animations
 */

// Page transition system
document.addEventListener('DOMContentLoaded', () => {
  // Page load animation
  const pageLoader = document.querySelector('.page-loader')
  
  if (pageLoader) {
    // Hide loader after page load
    window.addEventListener('load', () => {
      gsap.to(pageLoader, {
        opacity: 0,
        duration: 0.5,
        ease: 'power2.inOut',
        onComplete: () => {
          pageLoader.style.display = 'none'
        }
      })
    })
  }
  
  // Animate page elements on load
  const animatePageLoad = () => {
    // Fade in main content
    gsap.fromTo('.page-content',
      { opacity: 0, y: 20 },
      { opacity: 1, y: 0, duration: 0.8, ease: 'power2.out' }
    )
    
    // Stagger animate cards
    gsap.fromTo('.card, .blog-card',
      { opacity: 0, y: 30, scale: 0.95 },
      { 
        opacity: 1, 
        y: 0, 
        scale: 1,
        duration: 0.6,
        stagger: 0.1,
        ease: 'back.out(1.7)'
      }
    )
    
    // Animate navigation
    gsap.fromTo('.navbar',
      { opacity: 0, y: -20 },
      { opacity: 1, y: 0, duration: 0.6, ease: 'power2.out' }
    )
    
    // Animate hero section
    const hero = document.querySelector('.hero-section')
    if (hero) {
      gsap.fromTo('.hero-title',
        { opacity: 0, y: 50 },
        { opacity: 1, y: 0, duration: 1, ease: 'power2.out' }
      )
      
      gsap.fromTo('.hero-subtitle',
        { opacity: 0, y: 30 },
        { opacity: 1, y: 0, duration: 1, delay: 0.2, ease: 'power2.out' }
      )
      
      gsap.fromTo('.hero-cta',
        { opacity: 0, y: 20 },
        { opacity: 1, y: 0, duration: 1, delay: 0.4, ease: 'power2.out' }
      )
    }
  }
  
  // Run page load animation
  animatePageLoad()
  
  // Link transition handling
  const links = document.querySelectorAll('a[href^="/"], a[href^="./"], a[href^="../"]')
  
  links.forEach(link => {
    link.addEventListener('click', (e) => {
      // Skip if it's an external link or has special attributes
      if (link.target === '_blank' || link.hasAttribute('download')) {
        return
      }
      
      e.preventDefault()
      
      const href = link.getAttribute('href')
      
      // Page exit animation
      gsap.to('.page-content', {
        opacity: 0,
        y: -20,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: () => {
          // Navigate to new page
          window.location.href = href
        }
      })
    })
  })
  
  // Back/forward button handling
  window.addEventListener('popstate', () => {
    // Animate page change
    gsap.fromTo('.page-content',
      { opacity: 0, y: 20 },
      { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out' }
    )
  })
})

// Route-specific animations
const routeAnimations = {
  '/blog': () => {
    // Blog page specific animations
    gsap.fromTo('.blog-header',
      { opacity: 0, scale: 0.95 },
      { opacity: 1, scale: 1, duration: 0.8, ease: 'power2.out' }
    )
    
    gsap.fromTo('.blog-grid .blog-card',
      { opacity: 0, y: 50, rotationX: 15 },
      { 
        opacity: 1, 
        y: 0, 
        rotationX: 0,
        duration: 0.8,
        stagger: 0.1,
        ease: 'power2.out'
      }
    )
  },
  
  '/': () => {
    // Home page specific animations
    const features = document.querySelectorAll('.feature-card')
    
    gsap.fromTo(features,
      { opacity: 0, y: 30, scale: 0.9 },
      { 
        opacity: 1, 
        y: 0, 
        scale: 1,
        duration: 0.6,
        stagger: 0.15,
        ease: 'back.out(1.7)'
      }
    )
  }
}

// Apply route-specific animations
const applyRouteAnimation = () => {
  const path = window.location.pathname
  const animation = routeAnimations[path]
  
  if (animation) {
    // Delay to ensure DOM is ready
    setTimeout(animation, 100)
  }
}

// Apply on page load
document.addEventListener('DOMContentLoaded', applyRouteAnimation)

console.log('🎬 Page transitions loaded')
