/**
 * HDM Boot Default Theme - Main JavaScript
 * 
 * Stack: Alpine.js + GSAP + Tailwind CSS
 */

// Import Alpine.js
import Alpine from 'alpinejs'

// Import GSAP
import { gsap } from 'gsap'
import { ScrollTrigger } from 'gsap/ScrollTrigger'

// Import components
import './components/navigation'
import './components/blog'
import './components/forms'
import './components/darkmode'

// Import animations
import './animations/page-transitions'
import './animations/scroll-animations'

// Register GSAP plugins
gsap.registerPlugin(ScrollTrigger)

// Make GSAP available globally
window.gsap = gsap
window.ScrollTrigger = ScrollTrigger

// Alpine.js global data and methods
Alpine.data('app', () => ({
  // Theme state
  theme: 'default',
  
  // Navigation state
  mobileMenuOpen: false,
  
  // Loading state
  loading: false,
  
  // Initialize app
  init() {
    console.log('🚀 HDM Boot Default Theme initialized')
    this.initAnimations()
    this.initScrollEffects()
  },
  
  // Toggle mobile menu
  toggleMobileMenu() {
    this.mobileMenuOpen = !this.mobileMenuOpen
    
    // Animate menu
    if (this.mobileMenuOpen) {
      gsap.fromTo('.mobile-menu', 
        { opacity: 0, y: -10 },
        { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' }
      )
    }
  },
  
  // Close mobile menu
  closeMobileMenu() {
    this.mobileMenuOpen = false
  },
  
  // Initialize animations
  initAnimations() {
    // Fade in elements on page load
    gsap.fromTo('.animate-fade-in', 
      { opacity: 0, y: 20 },
      { opacity: 1, y: 0, duration: 0.6, stagger: 0.1, ease: 'power2.out' }
    )
    
    // Scale in cards
    gsap.fromTo('.animate-scale-in', 
      { opacity: 0, scale: 0.95 },
      { opacity: 1, scale: 1, duration: 0.4, stagger: 0.1, ease: 'back.out(1.7)' }
    )
  },
  
  // Initialize scroll effects
  initScrollEffects() {
    // Parallax hero background
    gsap.to('.hero-background', {
      yPercent: -50,
      ease: 'none',
      scrollTrigger: {
        trigger: '.hero-section',
        start: 'top bottom',
        end: 'bottom top',
        scrub: true
      }
    })
    
    // Fade in elements on scroll
    gsap.utils.toArray('.scroll-fade-in').forEach(element => {
      gsap.fromTo(element, 
        { opacity: 0, y: 30 },
        {
          opacity: 1,
          y: 0,
          duration: 0.6,
          ease: 'power2.out',
          scrollTrigger: {
            trigger: element,
            start: 'top 80%',
            end: 'bottom 20%',
            toggleActions: 'play none none reverse'
          }
        }
      )
    })
    
    // Scale in cards on scroll
    gsap.utils.toArray('.scroll-scale-in').forEach(element => {
      gsap.fromTo(element, 
        { opacity: 0, scale: 0.9 },
        {
          opacity: 1,
          scale: 1,
          duration: 0.5,
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
  },
  
  // Smooth scroll to element
  scrollTo(target) {
    const element = document.querySelector(target)
    if (element) {
      gsap.to(window, {
        duration: 1,
        scrollTo: { y: element, offsetY: 80 },
        ease: 'power2.inOut'
      })
    }
  },
  
  // Show loading state
  showLoading() {
    this.loading = true
  },
  
  // Hide loading state
  hideLoading() {
    this.loading = false
  }
}))

// Alpine.js blog component
Alpine.data('blog', () => ({
  // Blog state
  articles: [],
  categories: [],
  tags: [],
  loading: false,
  error: null,
  
  // Filters
  selectedCategory: '',
  selectedTag: '',
  searchQuery: '',
  
  // Initialize blog
  async init() {
    await this.loadArticles()
    await this.loadCategories()
    await this.loadTags()
  },
  
  // Load articles from API
  async loadArticles() {
    this.loading = true
    this.error = null
    
    try {
      const response = await fetch('/api/blog/articles')
      const data = await response.json()
      
      if (data.success) {
        this.articles = data.articles
      } else {
        this.error = 'Failed to load articles'
      }
    } catch (error) {
      this.error = 'Network error'
      console.error('Failed to load articles:', error)
    } finally {
      this.loading = false
    }
  },
  
  // Load categories
  async loadCategories() {
    try {
      const response = await fetch('/api/blog/categories')
      const data = await response.json()
      
      if (data.success) {
        this.categories = data.categories
      }
    } catch (error) {
      console.error('Failed to load categories:', error)
    }
  },
  
  // Load tags
  async loadTags() {
    try {
      const response = await fetch('/api/blog/tags')
      const data = await response.json()
      
      if (data.success) {
        this.tags = data.tags
      }
    } catch (error) {
      console.error('Failed to load tags:', error)
    }
  },
  
  // Filter articles
  get filteredArticles() {
    return this.articles.filter(article => {
      const matchesCategory = !this.selectedCategory || article.category === this.selectedCategory
      const matchesTag = !this.selectedTag || (article.tags && article.tags.includes(this.selectedTag))
      const matchesSearch = !this.searchQuery || 
        article.title.toLowerCase().includes(this.searchQuery.toLowerCase()) ||
        article.excerpt.toLowerCase().includes(this.searchQuery.toLowerCase())
      
      return matchesCategory && matchesTag && matchesSearch
    })
  },
  
  // Clear filters
  clearFilters() {
    this.selectedCategory = ''
    this.selectedTag = ''
    this.searchQuery = ''
  }
}))

// Alpine.js form component
Alpine.data('form', () => ({
  // Form state
  submitting: false,
  success: false,
  error: null,
  
  // Submit form
  async submit(formData, endpoint) {
    this.submitting = true
    this.success = false
    this.error = null
    
    try {
      const response = await fetch(endpoint, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(formData)
      })
      
      const data = await response.json()
      
      if (data.success) {
        this.success = true
        // Reset form or redirect
      } else {
        this.error = data.error || 'Submission failed'
      }
    } catch (error) {
      this.error = 'Network error'
      console.error('Form submission error:', error)
    } finally {
      this.submitting = false
    }
  }
}))

// Start Alpine.js
Alpine.start()

// Make Alpine available globally for debugging
window.Alpine = Alpine

// Initialize theme
document.addEventListener('DOMContentLoaded', () => {
  console.log('🎨 Default Theme (Tailwind + GSAP + Alpine) loaded')
  
  // Add theme class to body
  document.body.classList.add('theme-default')
  
  // Initialize smooth scrolling for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault()
      const target = document.querySelector(this.getAttribute('href'))
      if (target) {
        gsap.to(window, {
          duration: 1,
          scrollTo: { y: target, offsetY: 80 },
          ease: 'power2.inOut'
        })
      }
    })
  })
})
