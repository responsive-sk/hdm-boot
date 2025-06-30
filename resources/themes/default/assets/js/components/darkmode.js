/**
 * Dark Mode Component
 * 
 * Handles dark mode toggle and persistence
 */

// Dark mode functionality
document.addEventListener('DOMContentLoaded', () => {
  // Initialize dark mode from localStorage
  const isDark = localStorage.getItem('darkMode') === 'true' || 
                 (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches);
  
  if (isDark) {
    document.documentElement.classList.add('dark');
  }
  
  // Update Alpine.js data
  document.addEventListener('alpine:init', () => {
    Alpine.store('darkMode', {
      enabled: isDark,
      toggle() {
        this.enabled = !this.enabled;
        if (this.enabled) {
          document.documentElement.classList.add('dark');
          localStorage.setItem('darkMode', 'true');
        } else {
          document.documentElement.classList.remove('dark');
          localStorage.setItem('darkMode', 'false');
        }
        
        // Animate the transition
        gsap.to(document.body, {
          duration: 0.3,
          ease: 'power2.inOut',
          onComplete: () => {
            // Trigger any theme-specific animations
            document.dispatchEvent(new CustomEvent('darkModeToggled', {
              detail: { enabled: this.enabled }
            }));
          }
        });
      }
    });
  });
});

// Global toggle function for buttons
window.toggleDarkMode = function() {
  if (window.Alpine && Alpine.store('darkMode')) {
    Alpine.store('darkMode').toggle();
  } else {
    // Fallback if Alpine.js is not available
    const isDark = document.documentElement.classList.contains('dark');
    if (isDark) {
      document.documentElement.classList.remove('dark');
      localStorage.setItem('darkMode', 'false');
    } else {
      document.documentElement.classList.add('dark');
      localStorage.setItem('darkMode', 'true');
    }
  }
};

// Listen for system theme changes
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
  if (!localStorage.getItem('darkMode')) {
    if (e.matches) {
      document.documentElement.classList.add('dark');
    } else {
      document.documentElement.classList.remove('dark');
    }
  }
});

// Dark mode specific animations
document.addEventListener('darkModeToggled', (e) => {
  const isDark = e.detail.enabled;
  
  // Animate cards and elements
  const cards = document.querySelectorAll('.card, .blog-card, .feature-card, .sidebar-widget');
  cards.forEach((card, index) => {
    gsap.fromTo(card, 
      { scale: 0.98, opacity: 0.8 },
      { 
        scale: 1, 
        opacity: 1, 
        duration: 0.4,
        delay: index * 0.05,
        ease: 'power2.out'
      }
    );
  });
  
  // Animate navigation
  const navbar = document.querySelector('.navbar');
  if (navbar) {
    gsap.fromTo(navbar,
      { y: -10, opacity: 0.8 },
      { y: 0, opacity: 1, duration: 0.3, ease: 'power2.out' }
    );
  }
});

console.log('🌙 Dark mode component loaded');
