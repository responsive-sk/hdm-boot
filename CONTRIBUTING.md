# Contributing to MVA Bootstrap Application

Thank you for your interest in contributing to the MVA Bootstrap Application! This document provides guidelines and information for contributors.

## 🤝 How to Contribute

### Types of Contributions

We welcome several types of contributions:
- **Bug Reports** - Help us identify and fix issues
- **Feature Requests** - Suggest new functionality
- **Code Contributions** - Submit bug fixes and new features
- **Documentation** - Improve or add documentation
- **Testing** - Add or improve test coverage
- **Security** - Report security vulnerabilities

## 🐛 Reporting Bugs

### Before Submitting a Bug Report

1. **Check existing issues** - Search for similar issues first
2. **Update to latest version** - Ensure you're using the latest version
3. **Test in clean environment** - Verify the bug in a fresh installation

### Bug Report Template

```markdown
**Bug Description**
A clear and concise description of the bug.

**Steps to Reproduce**
1. Go to '...'
2. Click on '...'
3. Scroll down to '...'
4. See error

**Expected Behavior**
What you expected to happen.

**Actual Behavior**
What actually happened.

**Environment**
- OS: [e.g., Ubuntu 20.04]
- PHP Version: [e.g., 8.1.0]
- Application Version: [e.g., 1.0.0]
- Web Server: [e.g., Nginx 1.18]

**Additional Context**
Add any other context about the problem here.

**Logs**
Include relevant log entries if available.
```

## 💡 Suggesting Features

### Feature Request Template

```markdown
**Feature Description**
A clear and concise description of the feature.

**Problem Statement**
What problem does this feature solve?

**Proposed Solution**
Describe your proposed solution.

**Alternatives Considered**
Describe alternative solutions you've considered.

**Additional Context**
Add any other context or screenshots about the feature request.
```

## 🔧 Development Setup

### Prerequisites

- PHP 8.1 or higher
- Composer
- Git

### Setup Instructions

1. **Fork the repository**
```bash
git clone https://github.com/your-username/mva-bootstrap.git
cd mva-bootstrap
```

2. **Install dependencies**
```bash
composer install
```

3. **Setup environment**
```bash
cp .env.example .env
# Edit .env with your settings
```

4. **Create development directories**
```bash
mkdir -p var/{logs,storage,uploads,sessions,cache}
```

5. **Run development server**
```bash
php -S localhost:8001 -t public
```

### Development Workflow

1. **Create feature branch**
```bash
git checkout -b feature/your-feature-name
```

2. **Make changes**
   - Follow coding standards
   - Add tests for new functionality
   - Update documentation

3. **Run quality checks**
```bash
composer quality
```

4. **Commit changes**
```bash
git add .
git commit -m "feat: add new feature description"
```

5. **Push and create PR**
```bash
git push origin feature/your-feature-name
```

## 📝 Coding Standards

### PHP Standards

- **PSR-12** - Extended coding style guide
- **PSR-4** - Autoloading standard
- **Strict Types** - Always use `declare(strict_types=1)`
- **Type Hints** - Use type hints for all parameters and return types

### Code Style

```php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Example;

use SomeNamespace\SomeClass;

/**
 * Example class demonstrating coding standards.
 */
final class ExampleClass
{
    public function __construct(
        private readonly SomeClass $dependency
    ) {
    }

    public function exampleMethod(string $parameter): string
    {
        // Implementation
        return $parameter;
    }
}
```

### Naming Conventions

- **Classes** - PascalCase (e.g., `UserService`)
- **Methods** - camelCase (e.g., `getUserById`)
- **Variables** - camelCase (e.g., `$userId`)
- **Constants** - SCREAMING_SNAKE_CASE (e.g., `MAX_RETRY_COUNT`)
- **Files** - PascalCase for classes, lowercase for others

## 🧪 Testing Guidelines

### Test Structure

```
tests/
├── Unit/           # Unit tests
├── Integration/    # Integration tests
├── Security/       # Security tests
└── Fixtures/       # Test data
```

### Writing Tests

```php
<?php

declare(strict_types=1);

namespace MvaBootstrap\Tests\Unit\Example;

use MvaBootstrap\Example\ExampleClass;
use PHPUnit\Framework\TestCase;

final class ExampleClassTest extends TestCase
{
    public function testExampleMethod(): void
    {
        $example = new ExampleClass();
        $result = $example->exampleMethod('test');
        
        $this->assertEquals('test', $result);
    }
}
```

### Running Tests

```bash
# Run all tests
composer test

# Run with coverage
composer test-coverage

# Run specific test
./vendor/bin/phpunit tests/Unit/Example/ExampleClassTest.php
```

## 📚 Documentation Guidelines

### Documentation Types

- **Code Comments** - Inline documentation for complex logic
- **PHPDoc** - Class and method documentation
- **README** - Project overview and setup
- **Guides** - Detailed documentation in `/docs`

### PHPDoc Standards

```php
/**
 * Brief description of the method.
 *
 * Longer description if needed, explaining the purpose,
 * behavior, and any important details.
 *
 * @param string $parameter Description of parameter
 * @param array<string, mixed> $options Configuration options
 * @return string Description of return value
 * @throws InvalidArgumentException When parameter is invalid
 */
public function exampleMethod(string $parameter, array $options = []): string
{
    // Implementation
}
```

## 🔒 Security Guidelines

### Security Considerations

- **Input Validation** - Validate all inputs
- **Output Encoding** - Escape all outputs
- **Path Security** - Use SecurePathHelper for file operations
- **SQL Injection** - Use prepared statements
- **Authentication** - Implement proper authentication
- **Authorization** - Check permissions for all operations

### Reporting Security Issues

**DO NOT** create public issues for security vulnerabilities.

Instead, email security issues to: [security@example.com]

Include:
- Description of the vulnerability
- Steps to reproduce
- Potential impact
- Suggested fix (if any)

## 📋 Pull Request Process

### PR Checklist

- [ ] Code follows project coding standards
- [ ] Tests added for new functionality
- [ ] All tests pass
- [ ] Documentation updated
- [ ] Security considerations addressed
- [ ] Performance impact considered
- [ ] Backward compatibility maintained

### PR Template

```markdown
## Description
Brief description of changes.

## Type of Change
- [ ] Bug fix
- [ ] New feature
- [ ] Breaking change
- [ ] Documentation update

## Testing
- [ ] Unit tests added/updated
- [ ] Integration tests added/updated
- [ ] Manual testing completed

## Checklist
- [ ] Code follows style guidelines
- [ ] Self-review completed
- [ ] Documentation updated
- [ ] No new warnings introduced
```

### Review Process

1. **Automated Checks** - CI/CD pipeline runs automatically
2. **Code Review** - Maintainers review the code
3. **Testing** - Comprehensive testing in staging environment
4. **Approval** - At least one maintainer approval required
5. **Merge** - Squash and merge to main branch

## 🏷 Commit Message Guidelines

### Commit Format

```
type(scope): description

[optional body]

[optional footer]
```

### Types

- `feat` - New feature
- `fix` - Bug fix
- `docs` - Documentation changes
- `style` - Code style changes (formatting, etc.)
- `refactor` - Code refactoring
- `test` - Adding or updating tests
- `chore` - Maintenance tasks

### Examples

```bash
feat(user): add user authentication system
fix(security): resolve path traversal vulnerability
docs(api): update API documentation
test(user): add unit tests for user service
```

## 🎯 Development Priorities

### Current Focus Areas

1. **User Module** - Authentication and user management
2. **Security Module** - Authorization and JWT implementation
3. **Testing** - Comprehensive test coverage
4. **Documentation** - Complete API documentation
5. **Performance** - Optimization and caching

### Future Roadmap

- Article module implementation
- Advanced security features
- Performance monitoring
- Internationalization
- Plugin system

## 📞 Getting Help

### Resources

- **Documentation** - Check `/docs` directory
- **Issues** - Search existing issues
- **Discussions** - Use GitHub Discussions for questions
- **Code Examples** - Check existing code for patterns

### Contact

- **General Questions** - Create a GitHub Discussion
- **Bug Reports** - Create a GitHub Issue
- **Security Issues** - Email security@example.com
- **Feature Requests** - Create a GitHub Issue with feature template

## 📄 License

By contributing to this project, you agree that your contributions will be licensed under the same license as the project (MIT License).

Thank you for contributing to the MVA Bootstrap Application! 🚀
