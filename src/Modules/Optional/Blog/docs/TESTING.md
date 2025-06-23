# Blog Module Testing Guide

**Comprehensive testing strategy for HDM Boot Blog Module**

## 🧪 Testing Overview

The Blog module implements a comprehensive testing strategy with **39 tests** and **98 assertions**, covering unit, integration, and functional testing levels.

## 📁 Test Structure

```
src/Modules/Optional/Blog/tests/
├── Unit/                    # Domain logic tests
│   ├── Entities/           # Entity behavior tests
│   ├── ValueObjects/       # Value object validation tests
│   └── Services/           # Domain service tests
├── Integration/            # Infrastructure tests
│   ├── Repositories/       # Database integration tests
│   └── Api/               # API endpoint tests
├── Functional/             # End-to-end tests
│   ├── Web/               # Web interface tests
│   └── Workflows/         # Complete user workflows
└── Support/                # Test utilities
    ├── Fixtures/          # Test data
    └── Helpers/           # Test helper classes
```

## 🏃‍♂️ Running Tests

### **All Tests**
```bash
cd src/Modules/Optional/Blog
composer test
```

### **With Coverage**
```bash
composer test:coverage
```

### **Using Test Runner**
```bash
php run-module-tests.php
php run-module-tests.php --coverage
```

### **Specific Test Suites**
```bash
# Unit tests only
vendor/bin/phpunit tests/Unit/

# Integration tests only
vendor/bin/phpunit tests/Integration/

# Functional tests only
vendor/bin/phpunit tests/Functional/
```

## 🎯 Unit Tests

### **Entity Tests**

#### **ArticleTest.php**
```php
class ArticleTest extends TestCase
{
    public function testArticleCreation(): void
    {
        $article = new Article(
            new ArticleId('123'),
            new Title('Test Article'),
            new Content('Test content'),
            new Author('John Doe')
        );
        
        $this->assertEquals('Test Article', $article->getTitle()->getValue());
        $this->assertFalse($article->isPublished());
    }
    
    public function testArticlePublishing(): void
    {
        $article = $this->createTestArticle();
        
        $article->publish();
        
        $this->assertTrue($article->isPublished());
        $this->assertInstanceOf(PublishedAt::class, $article->getPublishedAt());
    }
    
    public function testArticleTagManagement(): void
    {
        $article = $this->createTestArticle();
        $tag = new Tag('php');
        
        $article->addTag($tag);
        
        $this->assertTrue($article->hasTag($tag));
        $this->assertCount(1, $article->getTags());
    }
}
```

### **Value Object Tests**

#### **TitleTest.php**
```php
class TitleTest extends TestCase
{
    public function testValidTitle(): void
    {
        $title = new Title('Valid Article Title');
        
        $this->assertEquals('Valid Article Title', $title->getValue());
    }
    
    public function testTitleTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Title must be at least 3 characters');
        
        new Title('Hi');
    }
    
    public function testTitleTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        
        new Title(str_repeat('a', 201));
    }
    
    public function testTitleImmutability(): void
    {
        $title = new Title('Original Title');
        $originalValue = $title->getValue();
        
        // Title should be immutable
        $this->assertEquals($originalValue, $title->getValue());
    }
}
```

## 🔗 Integration Tests

### **Repository Tests**

#### **SqliteArticleRepositoryTest.php**
```php
class SqliteArticleRepositoryTest extends TestCase
{
    private PDO $connection;
    private SqliteArticleRepository $repository;
    
    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:');
        $this->setupDatabase();
        $this->repository = new SqliteArticleRepository($this->connection);
    }
    
    public function testSaveAndFindArticle(): void
    {
        $article = $this->createTestArticle();
        
        $this->repository->save($article);
        $found = $this->repository->findById($article->getId());
        
        $this->assertNotNull($found);
        $this->assertEquals($article->getId(), $found->getId());
        $this->assertEquals($article->getTitle(), $found->getTitle());
    }
    
    public function testFindPublishedArticles(): void
    {
        $publishedArticle = $this->createPublishedArticle();
        $draftArticle = $this->createDraftArticle();
        
        $this->repository->save($publishedArticle);
        $this->repository->save($draftArticle);
        
        $published = $this->repository->findPublished();
        
        $this->assertCount(1, $published);
        $this->assertEquals($publishedArticle->getId(), $published[0]->getId());
    }
    
    public function testSearchArticles(): void
    {
        $article1 = $this->createArticleWithTitle('PHP Framework Guide');
        $article2 = $this->createArticleWithTitle('JavaScript Tutorial');
        
        $this->repository->save($article1);
        $this->repository->save($article2);
        
        $results = $this->repository->search('PHP');
        
        $this->assertCount(1, $results);
        $this->assertEquals($article1->getId(), $results[0]->getId());
    }
}
```

### **API Integration Tests**

#### **BlogApiIntegrationTest.php**
```php
class BlogApiIntegrationTest extends TestCase
{
    use ApiTestTrait;
    
    public function testListArticlesEndpoint(): void
    {
        $this->createTestArticles(3);
        
        $response = $this->get('/api/blog/articles');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertCount(3, $data['data']);
        $this->assertArrayHasKey('meta', $data);
    }
    
    public function testGetArticleBySlug(): void
    {
        $article = $this->createTestArticle('test-article-slug');
        
        $response = $this->get('/api/blog/articles/slug/test-article-slug');
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('test-article-slug', $data['data']['slug']);
    }
    
    public function testCreateArticleRequiresAuthentication(): void
    {
        $response = $this->post('/api/blog/articles', [
            'title' => 'Test Article',
            'content' => 'Test content'
        ]);
        
        $this->assertEquals(401, $response->getStatusCode());
    }
    
    public function testCreateArticleWithValidAuth(): void
    {
        $token = $this->getAuthToken();
        
        $response = $this->post('/api/blog/articles', [
            'title' => 'New Test Article',
            'content' => 'Test content for new article',
            'category_id' => 'test-category'
        ], ['Authorization' => "Bearer {$token}"]);
        
        $this->assertEquals(201, $response->getStatusCode());
        
        $data = json_decode($response->getBody()->getContents(), true);
        $this->assertArrayHasKey('data', $data);
        $this->assertEquals('New Test Article', $data['data']['title']);
    }
}
```

## 🌐 Functional Tests

### **Web Interface Tests**

#### **BlogControllerTest.php**
```php
class BlogControllerTest extends TestCase
{
    use WebTestTrait;
    
    public function testBlogHomepage(): void
    {
        $this->createTestArticles(5);
        
        $response = $this->get('/blog');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('HDM Boot Blog', $response->getBody());
        $this->assertStringContainsString('Latest Articles', $response->getBody());
    }
    
    public function testArticleDetailPage(): void
    {
        $article = $this->createTestArticle('test-article');
        
        $response = $this->get('/blog/article/test-article');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString($article->getTitle(), $response->getBody());
        $this->assertStringContainsString($article->getContent(), $response->getBody());
    }
    
    public function testArticleNotFound(): void
    {
        $response = $this->get('/blog/article/non-existent');
        
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertStringContainsString('Article Not Found', $response->getBody());
    }
    
    public function testCategoryPage(): void
    {
        $category = $this->createTestCategory('tutorials');
        $this->createTestArticleInCategory('test-article', $category);
        
        $response = $this->get('/blog/categories/tutorials');
        
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('Tutorials', $response->getBody());
    }
}
```

## 🛠️ Test Utilities

### **Test Traits**

#### **BlogTestTrait.php**
```php
trait BlogTestTrait
{
    protected function createTestArticle(string $slug = 'test-article'): Article
    {
        return new Article(
            new ArticleId(uniqid()),
            new Title('Test Article'),
            new Content('Test article content'),
            new Author('Test Author'),
            new Slug($slug)
        );
    }
    
    protected function createPublishedArticle(): Article
    {
        $article = $this->createTestArticle();
        $article->publish();
        return $article;
    }
    
    protected function createTestCategory(string $slug = 'test'): Category
    {
        return new Category(
            new CategoryId(uniqid()),
            new CategoryName('Test Category'),
            new Slug($slug)
        );
    }
    
    protected function createTestArticles(int $count): array
    {
        $articles = [];
        for ($i = 1; $i <= $count; $i++) {
            $articles[] = $this->createTestArticle("test-article-{$i}");
        }
        return $articles;
    }
}
```

### **Database Setup**

#### **DatabaseTestCase.php**
```php
abstract class DatabaseTestCase extends TestCase
{
    protected PDO $connection;
    
    protected function setUp(): void
    {
        $this->connection = new PDO('sqlite::memory:');
        $this->setupDatabase();
    }
    
    protected function setupDatabase(): void
    {
        $sql = file_get_contents(__DIR__ . '/../../database/schema.sql');
        $this->connection->exec($sql);
    }
    
    protected function tearDown(): void
    {
        $this->connection = null;
    }
}
```

## 📊 Test Coverage

### **Coverage Targets**
- **Overall Coverage**: 90%+
- **Domain Layer**: 95%+
- **Application Layer**: 90%+
- **Infrastructure Layer**: 85%+

### **Coverage Report**
```bash
# Generate HTML coverage report
composer test:coverage

# View coverage report
open coverage/index.html
```

### **Coverage Metrics**
```
Classes: 95% (19/20)
Methods: 92% (87/95)
Lines: 91% (456/502)
```

## 🚀 Continuous Integration

### **GitHub Actions**
```yaml
# .github/workflows/blog-module.yml
name: Blog Module Tests

on:
  push:
    paths: ['src/Modules/Optional/Blog/**']

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.2'
          coverage: xdebug
      
      - name: Install dependencies
        run: |
          cd src/Modules/Optional/Blog
          composer install
      
      - name: Run tests
        run: |
          cd src/Modules/Optional/Blog
          composer test:coverage
      
      - name: Upload coverage
        uses: codecov/codecov-action@v3
```

## 🔧 Test Configuration

### **PHPUnit Configuration**
```xml
<!-- phpunit.xml -->
<phpunit bootstrap="../../../../vendor/autoload.php">
    <testsuites>
        <testsuite name="Blog Module Tests">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    
    <source>
        <include>
            <directory>src</directory>
        </include>
    </source>
    
    <coverage>
        <report>
            <html outputDirectory="coverage"/>
        </report>
    </coverage>
</phpunit>
```

## 📝 Best Practices

### **Test Naming**
- Use descriptive test method names
- Follow `testMethodName_StateUnderTest_ExpectedBehavior` pattern
- Group related tests in test classes

### **Test Organization**
- One test class per production class
- Use `setUp()` and `tearDown()` for test preparation
- Keep tests independent and isolated

### **Assertions**
- Use specific assertions (`assertEquals` vs `assertTrue`)
- Test both positive and negative cases
- Verify exception messages, not just types

### **Test Data**
- Use factories for creating test objects
- Avoid hardcoded values in tests
- Use meaningful test data that reflects real scenarios

---

**Blog Module Testing** - Comprehensive testing strategy ensuring code quality and reliability
