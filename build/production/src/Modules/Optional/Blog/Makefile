# Blog Module Makefile
# Uses responsive-sk/slim4-paths for safe path handling

.PHONY: test test-verbose test-coverage test-unit test-integration clean help

# Default target
help:
	@echo "📝 Blog Module Test Commands:"
	@echo ""
	@echo "  make test           - Run all tests"
	@echo "  make test-verbose   - Run tests with verbose output"
	@echo "  make test-coverage  - Run tests with coverage report"
	@echo "  make test-unit      - Run only unit tests"
	@echo "  make test-integration - Run only integration tests"
	@echo "  make clean          - Clean test cache and logs"
	@echo "  make help           - Show this help"
	@echo ""

# Run all tests using our safe path runner
test:
	@echo "🧪 Running Blog Module Tests..."
	@php run-tests.php

# Run tests with verbose output
test-verbose:
	@echo "🧪 Running Blog Module Tests (Verbose)..."
	@php run-tests.php --verbose

# Run tests with coverage (requires xdebug)
test-coverage:
	@echo "🧪 Running Blog Module Tests with Coverage..."
	@php run-tests.php --coverage-html ../../../../var/coverage/blog

# Run only unit tests
test-unit:
	@echo "🧪 Running Blog Unit Tests..."
	@php run-tests.php --testsuite "Controllers,Models"

# Run only integration tests  
test-integration:
	@echo "🧪 Running Blog Integration Tests..."
	@php run-tests.php --testsuite "Integration"

# Clean test artifacts
clean:
	@echo "🧹 Cleaning Blog test artifacts..."
	@rm -rf ../../../../var/cache/phpunit/blog*
	@rm -f ../../../../var/logs/blog-*.html
	@rm -f ../../../../var/logs/blog-*.xml
	@echo "✅ Clean complete"

# Quick test for CI/CD
ci-test:
	@php run-tests.php --no-coverage --stop-on-failure
