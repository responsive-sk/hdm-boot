/**
 * Navigation Component
 * 
 * Handles navigation interactions and animations
 */

// Mobile menu toggle functionality
document.addEventListener('DOMContentLoaded', () => {
  // Mobile menu toggle
  const mobileMenuToggle = document.querySelector('.navbar-toggler')
  const mobileMenu = document.querySelector('.mobile-menu')
  
  if (mobileMenuToggle && mobileMenu) {
    mobileMenuToggle.addEventListener('click', () => {
      const isOpen = mobileMenu.classList.contains('show')
      
      if (isOpen) {
        // Close menu
        gsap.to(mobileMenu, {
          opacity: 0,
          y: -10,
          duration: 0.3,
          ease: 'power2.in',
          onComplete: () => {
            mobileMenu.classList.remove('show')
          }
        })
      } else {
        // Open menu
        mobileMenu.classList.add('show')
        gsap.fromTo(mobileMenu, 
          { opacity: 0, y: -10 },
          { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' }
        )
      }
    })
  }
  
  // Close mobile menu when clicking outside
  document.addEventListener('click', (e) => {
    if (mobileMenu && mobileMenu.classList.contains('show')) {
      if (!mobileMenu.contains(e.target) && !mobileMenuToggle.contains(e.target)) {
        gsap.to(mobileMenu, {
          opacity: 0,
          y: -10,
          duration: 0.3,
          ease: 'power2.in',
          onComplete: () => {
            mobileMenu.classList.remove('show')
          }
        })
      }
    }
  })
  
  // Smooth scroll for navigation links
  document.querySelectorAll('.nav-link[href^="#"]').forEach(link => {
    link.addEventListener('click', (e) => {
      e.preventDefault()
      const target = document.querySelector(link.getAttribute('href'))
      
      if (target) {
        gsap.to(window, {
          duration: 1,
          scrollTo: { y: target, offsetY: 80 },
          ease: 'power2.inOut'
        })
        
        // Close mobile menu if open
        if (mobileMenu && mobileMenu.classList.contains('show')) {
          gsap.to(mobileMenu, {
            opacity: 0,
            y: -10,
            duration: 0.3,
            ease: 'power2.in',
            onComplete: () => {
              mobileMenu.classList.remove('show')
            }
          })
        }
      }
    })
  })
  
  // Active navigation highlighting
  const updateActiveNav = () => {
    const sections = document.querySelectorAll('section[id]')
    const navLinks = document.querySelectorAll('.nav-link[href^="#"]')
    
    let current = ''
    
    sections.forEach(section => {
      const sectionTop = section.offsetTop
      const sectionHeight = section.clientHeight
      
      if (window.scrollY >= sectionTop - 100) {
        current = section.getAttribute('id')
      }
    })
    
    navLinks.forEach(link => {
      link.classList.remove('active')
      if (link.getAttribute('href') === `#${current}`) {
        link.classList.add('active')
      }
    })
  }
  
  // Update active nav on scroll
  window.addEventListener('scroll', updateActiveNav)
  updateActiveNav() // Initial call
})

console.log('📱 Navigation component loaded')
