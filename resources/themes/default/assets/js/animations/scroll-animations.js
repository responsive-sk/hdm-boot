/**
 * Scroll Animations
 * 
 * Handles scroll-triggered animations using GSAP ScrollTrigger
 */

// Scroll animations
document.addEventListener('DOMContentLoaded', () => {
  // Ensure ScrollTrigger is available
  if (typeof ScrollTrigger === 'undefined') {
    console.warn('ScrollTrigger not available')
    return
  }
  
  // Refresh ScrollTrigger on page load
  ScrollTrigger.refresh()
  
  // Fade in elements on scroll
  gsap.utils.toArray('.scroll-fade-in').forEach(element => {
    gsap.fromTo(element, 
      { 
        opacity: 0, 
        y: 50 
      },
      {
        opacity: 1,
        y: 0,
        duration: 1,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: element,
          start: 'top 85%',
          end: 'bottom 15%',
          toggleActions: 'play none none reverse'
        }
      }
    )
  })
  
  // Scale in elements on scroll
  gsap.utils.toArray('.scroll-scale-in').forEach(element => {
    gsap.fromTo(element, 
      { 
        opacity: 0, 
        scale: 0.8 
      },
      {
        opacity: 1,
        scale: 1,
        duration: 0.8,
        ease: 'back.out(1.7)',
        scrollTrigger: {
          trigger: element,
          start: 'top 85%',
          end: 'bottom 15%',
          toggleActions: 'play none none reverse'
        }
      }
    )
  })
  
  // Slide in from left
  gsap.utils.toArray('.scroll-slide-left').forEach(element => {
    gsap.fromTo(element, 
      { 
        opacity: 0, 
        x: -100 
      },
      {
        opacity: 1,
        x: 0,
        duration: 1,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: element,
          start: 'top 85%',
          end: 'bottom 15%',
          toggleActions: 'play none none reverse'
        }
      }
    )
  })
  
  // Slide in from right
  gsap.utils.toArray('.scroll-slide-right').forEach(element => {
    gsap.fromTo(element, 
      { 
        opacity: 0, 
        x: 100 
      },
      {
        opacity: 1,
        x: 0,
        duration: 1,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: element,
          start: 'top 85%',
          end: 'bottom 15%',
          toggleActions: 'play none none reverse'
        }
      }
    )
  })
  
  // Stagger animations for groups
  gsap.utils.toArray('.scroll-stagger').forEach(container => {
    const items = container.querySelectorAll('.stagger-item')
    
    gsap.fromTo(items, 
      { 
        opacity: 0, 
        y: 30 
      },
      {
        opacity: 1,
        y: 0,
        duration: 0.6,
        stagger: 0.1,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: container,
          start: 'top 85%',
          end: 'bottom 15%',
          toggleActions: 'play none none reverse'
        }
      }
    )
  })
  
  // Parallax backgrounds
  gsap.utils.toArray('.parallax-bg').forEach(element => {
    gsap.to(element, {
      yPercent: -50,
      ease: 'none',
      scrollTrigger: {
        trigger: element.parentElement,
        start: 'top bottom',
        end: 'bottom top',
        scrub: true
      }
    })
  })
  
  // Parallax elements (slower movement)
  gsap.utils.toArray('.parallax-slow').forEach(element => {
    gsap.to(element, {
      yPercent: -20,
      ease: 'none',
      scrollTrigger: {
        trigger: element,
        start: 'top bottom',
        end: 'bottom top',
        scrub: true
      }
    })
  })
  
  // Parallax elements (faster movement)
  gsap.utils.toArray('.parallax-fast').forEach(element => {
    gsap.to(element, {
      yPercent: -80,
      ease: 'none',
      scrollTrigger: {
        trigger: element,
        start: 'top bottom',
        end: 'bottom top',
        scrub: true
      }
    })
  })
  
  // Pin sections
  gsap.utils.toArray('.pin-section').forEach(section => {
    ScrollTrigger.create({
      trigger: section,
      start: 'top top',
      end: 'bottom top',
      pin: true,
      pinSpacing: false
    })
  })
  
  // Horizontal scroll sections
  gsap.utils.toArray('.horizontal-scroll').forEach(container => {
    const sections = container.querySelectorAll('.horizontal-section')
    
    gsap.to(sections, {
      xPercent: -100 * (sections.length - 1),
      ease: 'none',
      scrollTrigger: {
        trigger: container,
        pin: true,
        scrub: 1,
        snap: 1 / (sections.length - 1),
        end: () => '+=' + container.offsetWidth
      }
    })
  })
  
  // Text reveal animations
  gsap.utils.toArray('.text-reveal').forEach(element => {
    const text = element.textContent
    element.innerHTML = text.split('').map(char => 
      char === ' ' ? ' ' : `<span class="char">${char}</span>`
    ).join('')
    
    const chars = element.querySelectorAll('.char')
    
    gsap.fromTo(chars, 
      { 
        opacity: 0, 
        y: 50,
        rotationX: -90
      },
      {
        opacity: 1,
        y: 0,
        rotationX: 0,
        duration: 0.05,
        stagger: 0.02,
        ease: 'back.out(1.7)',
        scrollTrigger: {
          trigger: element,
          start: 'top 85%',
          end: 'bottom 15%',
          toggleActions: 'play none none reverse'
        }
      }
    )
  })
  
  // Counter animations
  gsap.utils.toArray('.counter').forEach(counter => {
    const target = parseInt(counter.dataset.target) || 0
    const duration = parseFloat(counter.dataset.duration) || 2
    
    ScrollTrigger.create({
      trigger: counter,
      start: 'top 85%',
      onEnter: () => {
        gsap.to(counter, {
          innerHTML: target,
          duration: duration,
          ease: 'power2.out',
          snap: { innerHTML: 1 },
          onUpdate: function() {
            counter.innerHTML = Math.ceil(this.targets()[0].innerHTML)
          }
        })
      }
    })
  })
  
  // Progress bars
  gsap.utils.toArray('.progress-bar').forEach(bar => {
    const progress = bar.querySelector('.progress-fill')
    const percentage = parseInt(bar.dataset.percentage) || 0
    
    gsap.fromTo(progress, 
      { 
        width: '0%' 
      },
      {
        width: `${percentage}%`,
        duration: 1.5,
        ease: 'power2.out',
        scrollTrigger: {
          trigger: bar,
          start: 'top 85%',
          end: 'bottom 15%',
          toggleActions: 'play none none reverse'
        }
      }
    )
  })
  
  // Navbar hide/show on scroll
  const navbar = document.querySelector('.navbar')
  if (navbar) {
    let lastScrollY = window.scrollY
    
    ScrollTrigger.create({
      start: 'top -80',
      end: 99999,
      onUpdate: (self) => {
        const currentScrollY = window.scrollY
        
        if (currentScrollY > lastScrollY && currentScrollY > 100) {
          // Scrolling down
          gsap.to(navbar, {
            yPercent: -100,
            duration: 0.3,
            ease: 'power2.out'
          })
        } else {
          // Scrolling up
          gsap.to(navbar, {
            yPercent: 0,
            duration: 0.3,
            ease: 'power2.out'
          })
        }
        
        lastScrollY = currentScrollY
      }
    })
  }
  
  // Refresh ScrollTrigger after all animations are set up
  ScrollTrigger.refresh()
})

console.log('📜 Scroll animations loaded')
