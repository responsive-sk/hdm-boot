---
title: "Welcome to HDM Boot Blog"
author: "HDM Boot Team"
published: true
category: "Getting Started"
tags: ["hdm-boot", "blog", "cms", "php"]
published_at: "2024-12-30"
reading_time: 3
excerpt: "Discover HDM Boot's powerful Orbit-style content management system with theme support, file-based storage, and modern web technologies."
---

# Welcome to HDM Boot Blog

Welcome to the **HDM Boot Blog** - a demonstration of our powerful **Orbit-style content management system**!

## What is HDM Boot?

HDM Boot is a modern PHP framework that combines the best of traditional and modern web development approaches. Our blog system showcases several key features:

### 🎨 Theme System
- **Laravel-style Resources** - Organized theme structure in `resources/themes/`
- **Per-theme Dependencies** - Each theme has its own `node_modules` and build system
- **Modern Stack** - Tailwind CSS + GSAP + Alpine.js for the default theme
- **Vite Build System** - Fast, modern asset compilation with hot reload

### 📝 Content Management
- **File-based Storage** - Articles stored as Markdown files with YAML front-matter
- **Automatic Discovery** - New articles appear automatically when added to `content/articles/`
- **Rich Metadata** - Support for categories, tags, reading time, and more
- **Markdown Processing** - Clean, semantic HTML output from Markdown

### 🚀 Modern Architecture
- **Action Pattern** - Clean separation of concerns with Action classes
- **Theme-aware Views** - Templates that adapt to the active theme
- **RESTful API** - Full API access for programmatic content management
- **Type Safety** - PHP 8.4 with strict typing and PHPStan level 8

## Getting Started

To add new articles to this blog, simply create Markdown files in the `content/articles/` directory:

```markdown
---
title: "Your Article Title"
author: "Your Name"
published: true
category: "Your Category"
tags: ["tag1", "tag2"]
published_at: "2024-12-30"
reading_time: 5
excerpt: "A brief description of your article"
---

# Your Article Content

Write your article content here using Markdown syntax.
```

## Theme Features

The default theme includes:

- **Responsive Design** - Mobile-first approach with Tailwind CSS
- **Smooth Animations** - GSAP-powered page transitions and scroll effects
- **Interactive Components** - Alpine.js for reactive functionality
- **Reading Progress** - Visual progress bar for long articles
- **Syntax Highlighting** - Code blocks with proper formatting

## API Access

The blog also provides a RESTful API for programmatic access:

- `GET /api/blog/articles` - List all articles
- `GET /api/blog/articles/{slug}` - Get specific article
- `GET /api/blog/categories` - List categories
- `GET /api/blog/tags` - List tags

## What's Next?

This blog system is just one part of HDM Boot's comprehensive feature set. Explore the codebase to discover:

- **Modular Architecture** - Clean separation of concerns
- **Security Features** - Built-in protection and validation
- **Development Tools** - CLI tools for theme management and more
- **Production Ready** - Optimized for performance and scalability

Happy blogging with HDM Boot! 🚀
