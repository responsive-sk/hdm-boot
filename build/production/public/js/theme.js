// Theme management
class ThemeManager {
    constructor() {
        this.theme = localStorage.getItem('theme') || 'light';
        this.init();
    }

    init() {
        // Set initial theme
        document.documentElement.setAttribute('data-theme', this.theme);

        // Create and append toggle button
        const button = this.createToggleButton();
        document.body.appendChild(button);

        // Update button icon
        this.updateButtonIcon();
    }

    createToggleButton() {
        const button = document.createElement('button');
        button.className = 'theme-toggle';
        button.innerHTML = `
            <span class="icon">🌓</span>
            <span class="text">Theme</span>
        `;

        button.addEventListener('click', () => this.toggleTheme());
        return button;
    }

    toggleTheme() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        document.documentElement.setAttribute('data-theme', this.theme);
        localStorage.setItem('theme', this.theme);
        this.updateButtonIcon();
    }

    updateButtonIcon() {
        const icon = document.querySelector('.theme-toggle .icon');
        if (icon) {
            icon.textContent = this.theme === 'light' ? '🌓' : '🌙';
        }
    }
}

// Initialize theme manager when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new ThemeManager();

    // Check user's system preference
    if (!localStorage.getItem('theme')) {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        document.documentElement.setAttribute('data-theme', prefersDark ? 'dark' : 'light');
    }
});
