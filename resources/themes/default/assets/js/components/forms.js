/**
 * Forms Component
 * 
 * Handles form interactions, validation, and animations
 */

// Form enhancements
document.addEventListener('DOMContentLoaded', () => {
  // Floating labels
  const floatingInputs = document.querySelectorAll('.form-floating .form-input')
  
  floatingInputs.forEach(input => {
    const label = input.parentElement.querySelector('.form-label')
    
    if (label) {
      const updateLabel = () => {
        if (input.value || input === document.activeElement) {
          label.classList.add('floating')
        } else {
          label.classList.remove('floating')
        }
      }
      
      input.addEventListener('focus', updateLabel)
      input.addEventListener('blur', updateLabel)
      input.addEventListener('input', updateLabel)
      
      // Initial state
      updateLabel()
    }
  })
  
  // Form validation
  const forms = document.querySelectorAll('form[data-validate]')
  
  forms.forEach(form => {
    const inputs = form.querySelectorAll('.form-input[required]')
    
    // Real-time validation
    inputs.forEach(input => {
      input.addEventListener('blur', () => validateField(input))
      input.addEventListener('input', () => clearFieldError(input))
    })
    
    // Form submission
    form.addEventListener('submit', async (e) => {
      e.preventDefault()
      
      let isValid = true
      
      // Validate all fields
      inputs.forEach(input => {
        if (!validateField(input)) {
          isValid = false
        }
      })
      
      if (!isValid) {
        // Shake form on error
        gsap.to(form, {
          x: [-10, 10, -10, 10, 0],
          duration: 0.5,
          ease: 'power2.out'
        })
        return
      }
      
      // Submit form
      await submitForm(form)
    })
  })
  
  // Field validation function
  function validateField(input) {
    const value = input.value.trim()
    const type = input.type
    const required = input.hasAttribute('required')
    
    clearFieldError(input)
    
    // Required validation
    if (required && !value) {
      showFieldError(input, 'This field is required')
      return false
    }
    
    // Email validation
    if (type === 'email' && value) {
      const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
      if (!emailRegex.test(value)) {
        showFieldError(input, 'Please enter a valid email address')
        return false
      }
    }
    
    // Password validation
    if (type === 'password' && value) {
      if (value.length < 8) {
        showFieldError(input, 'Password must be at least 8 characters')
        return false
      }
    }
    
    // URL validation
    if (type === 'url' && value) {
      try {
        new URL(value)
      } catch {
        showFieldError(input, 'Please enter a valid URL')
        return false
      }
    }
    
    return true
  }
  
  // Show field error
  function showFieldError(input, message) {
    input.classList.add('error')
    
    let errorElement = input.parentElement.querySelector('.form-error')
    
    if (!errorElement) {
      errorElement = document.createElement('div')
      errorElement.className = 'form-error'
      input.parentElement.appendChild(errorElement)
    }
    
    errorElement.textContent = message
    
    // Animate error
    gsap.fromTo(errorElement,
      { opacity: 0, y: -5 },
      { opacity: 1, y: 0, duration: 0.3, ease: 'power2.out' }
    )
  }
  
  // Clear field error
  function clearFieldError(input) {
    input.classList.remove('error')
    
    const errorElement = input.parentElement.querySelector('.form-error')
    if (errorElement) {
      gsap.to(errorElement, {
        opacity: 0,
        y: -5,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: () => errorElement.remove()
      })
    }
  }
  
  // Submit form function
  async function submitForm(form) {
    const submitButton = form.querySelector('button[type="submit"]')
    const originalText = submitButton?.textContent
    
    // Show loading state
    if (submitButton) {
      submitButton.disabled = true
      submitButton.textContent = 'Submitting...'
      
      // Add spinner
      const spinner = document.createElement('div')
      spinner.className = 'spinner'
      submitButton.appendChild(spinner)
    }
    
    try {
      const formData = new FormData(form)
      const data = Object.fromEntries(formData.entries())
      
      const response = await fetch(form.action || '/api/contact', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(data)
      })
      
      const result = await response.json()
      
      if (result.success) {
        showFormSuccess(form, result.message || 'Form submitted successfully!')
        form.reset()
      } else {
        showFormError(form, result.error || 'Submission failed')
      }
      
    } catch (error) {
      console.error('Form submission error:', error)
      showFormError(form, 'Network error. Please try again.')
    } finally {
      // Restore button
      if (submitButton) {
        submitButton.disabled = false
        submitButton.textContent = originalText
        
        const spinner = submitButton.querySelector('.spinner')
        if (spinner) {
          spinner.remove()
        }
      }
    }
  }
  
  // Show form success
  function showFormSuccess(form, message) {
    const alert = createAlert('success', message)
    form.parentElement.insertBefore(alert, form)
    
    gsap.fromTo(alert,
      { opacity: 0, y: -20 },
      { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out' }
    )
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
      gsap.to(alert, {
        opacity: 0,
        y: -20,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: () => alert.remove()
      })
    }, 5000)
  }
  
  // Show form error
  function showFormError(form, message) {
    const alert = createAlert('error', message)
    form.parentElement.insertBefore(alert, form)
    
    gsap.fromTo(alert,
      { opacity: 0, y: -20 },
      { opacity: 1, y: 0, duration: 0.5, ease: 'power2.out' }
    )
    
    // Auto-hide after 5 seconds
    setTimeout(() => {
      gsap.to(alert, {
        opacity: 0,
        y: -20,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: () => alert.remove()
      })
    }, 5000)
  }
  
  // Create alert element
  function createAlert(type, message) {
    const alert = document.createElement('div')
    alert.className = `alert alert-${type} mb-4`
    alert.innerHTML = `
      <div class="flex items-center">
        <div class="flex-1">${message}</div>
        <button class="alert-close ml-4 text-lg">&times;</button>
      </div>
    `
    
    // Close button
    alert.querySelector('.alert-close').addEventListener('click', () => {
      gsap.to(alert, {
        opacity: 0,
        y: -20,
        duration: 0.3,
        ease: 'power2.in',
        onComplete: () => alert.remove()
      })
    })
    
    return alert
  }
})

console.log('📋 Forms component loaded')
