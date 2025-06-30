/**
 * Blog Component
 * 
 * Handles blog-specific interactions and animations
 */

// Blog card hover animations
document.addEventListener('DOMContentLoaded', () => {
  // Animate blog cards on hover
  const blogCards = document.querySelectorAll('.blog-card')
  
  blogCards.forEach(card => {
    const image = card.querySelector('.blog-card-image')
    
    card.addEventListener('mouseenter', () => {
      gsap.to(card, {
        y: -5,
        duration: 0.3,
        ease: 'power2.out'
      })
      
      if (image) {
        gsap.to(image, {
          scale: 1.05,
          duration: 0.3,
          ease: 'power2.out'
        })
      }
    })
    
    card.addEventListener('mouseleave', () => {
      gsap.to(card, {
        y: 0,
        duration: 0.3,
        ease: 'power2.out'
      })
      
      if (image) {
        gsap.to(image, {
          scale: 1,
          duration: 0.3,
          ease: 'power2.out'
        })
      }
    })
  })
  
  // Reading progress bar for articles
  const progressBar = document.querySelector('.reading-progress')
  
  if (progressBar) {
    const updateProgress = () => {
      const article = document.querySelector('.article-body')
      if (!article) return
      
      const articleTop = article.offsetTop
      const articleHeight = article.clientHeight
      const windowHeight = window.innerHeight
      const scrollTop = window.scrollY
      
      const progress = Math.min(
        Math.max((scrollTop - articleTop + windowHeight) / articleHeight, 0),
        1
      )
      
      gsap.to(progressBar, {
        scaleX: progress,
        duration: 0.1,
        ease: 'none'
      })
    }
    
    window.addEventListener('scroll', updateProgress)
    updateProgress()
  }
  
  // Tag filtering animation
  const tagFilters = document.querySelectorAll('.tag-filter')
  const blogPosts = document.querySelectorAll('.blog-post-card')
  
  tagFilters.forEach(filter => {
    filter.addEventListener('click', (e) => {
      e.preventDefault()
      
      const tag = filter.dataset.tag
      
      // Update active filter
      tagFilters.forEach(f => f.classList.remove('active'))
      filter.classList.add('active')
      
      // Filter posts
      blogPosts.forEach(post => {
        const postTags = post.dataset.tags ? post.dataset.tags.split(',') : []
        const shouldShow = !tag || tag === 'all' || postTags.includes(tag)
        
        if (shouldShow) {
          gsap.to(post, {
            opacity: 1,
            scale: 1,
            duration: 0.3,
            ease: 'power2.out'
          })
        } else {
          gsap.to(post, {
            opacity: 0.3,
            scale: 0.95,
            duration: 0.3,
            ease: 'power2.out'
          })
        }
      })
    })
  })
  
  // Search functionality
  const searchInput = document.querySelector('.blog-search')
  
  if (searchInput) {
    let searchTimeout
    
    searchInput.addEventListener('input', (e) => {
      clearTimeout(searchTimeout)
      
      searchTimeout = setTimeout(() => {
        const query = e.target.value.toLowerCase()
        
        blogPosts.forEach(post => {
          const title = post.querySelector('.blog-card-title')?.textContent.toLowerCase() || ''
          const excerpt = post.querySelector('.blog-card-excerpt')?.textContent.toLowerCase() || ''
          
          const matches = title.includes(query) || excerpt.includes(query)
          
          if (matches || !query) {
            gsap.to(post, {
              opacity: 1,
              scale: 1,
              duration: 0.3,
              ease: 'power2.out'
            })
          } else {
            gsap.to(post, {
              opacity: 0.3,
              scale: 0.95,
              duration: 0.3,
              ease: 'power2.out'
            })
          }
        })
      }, 300)
    })
  }
  
  // Infinite scroll (if needed)
  const loadMoreButton = document.querySelector('.load-more')
  
  if (loadMoreButton) {
    loadMoreButton.addEventListener('click', async (e) => {
      e.preventDefault()
      
      const button = e.target
      const originalText = button.textContent
      
      // Show loading state
      button.textContent = 'Loading...'
      button.disabled = true
      
      try {
        // Simulate API call
        await new Promise(resolve => setTimeout(resolve, 1000))
        
        // Add new posts (this would be real data from API)
        const newPosts = document.querySelectorAll('.blog-post-card.hidden')
        
        newPosts.forEach((post, index) => {
          setTimeout(() => {
            post.classList.remove('hidden')
            gsap.fromTo(post,
              { opacity: 0, y: 30 },
              { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out' }
            )
          }, index * 100)
        })
        
      } catch (error) {
        console.error('Failed to load more posts:', error)
      } finally {
        button.textContent = originalText
        button.disabled = false
      }
    })
  }
})

// Global search functions for Alpine.js
window.searchArticles = function() {
  const query = document.querySelector('.search-form input')?.value;
  if (query && query.trim()) {
    window.location.href = `/blog/search?q=${encodeURIComponent(query)}`;
  }
};

window.liveSearch = function() {
  const searchInput = document.querySelector('.search-form input');
  if (searchInput) {
    // Trigger the existing search functionality
    searchInput.dispatchEvent(new Event('input'));
  }
};

console.log('📝 Blog component loaded')
