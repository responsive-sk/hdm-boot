# 📝 MVA Bootstrap Orbit CMS - Package Plan

## 📋 **Overview**

**Package Name:** `mva-bootstrap/orbit-cms`  
**Purpose:** Laravel Orbit-inspired content management for MVA Bootstrap Core  
**Type:** Separate package that extends MVA Bootstrap Core  
**License:** MIT  

## 🎯 **Why Separate Package?**

### **✅ Benefits of Separation:**
1. **Core stays minimal** - PathsFactory and security focus
2. **Optional dependency** - Users choose what they need
3. **Independent versioning** - CMS can evolve separately
4. **Specialized focus** - Content management expertise
5. **Easier maintenance** - Smaller, focused codebase

### **🔗 Integration with Core:**
```php
// Orbit CMS builds on Core
composer require mva-bootstrap/core
composer require mva-bootstrap/orbit-cms

$app = new Application();
$orbit = new OrbitCMS($app);
$orbit->initialize();
```

## 📦 **Orbit CMS Package Structure**

```
mva-bootstrap-orbit-cms/
├── src/
│   ├── Models/
│   │   ├── Article.php           # Article model with metadata
│   │   ├── Document.php          # Document model
│   │   └── Collection.php        # Collection of items
│   ├── Services/
│   │   ├── OrbitManager.php      # Main CMS manager
│   │   ├── DatabaseManager.php   # SQLite database handling
│   │   ├── FileManager.php       # File-based storage
│   │   └── SearchService.php     # Content search
│   ├── Storage/
│   │   ├── Drivers/
│   │   │   ├── SqliteDriver.php  # SQLite storage
│   │   │   ├── FileDriver.php    # File storage
│   │   │   └── HybridDriver.php  # Combined storage
│   │   └── Contracts/
│   │       └── StorageInterface.php
│   ├── Admin/
│   │   ├── Controllers/
│   │   │   ├── ArticleController.php
│   │   │   └── DashboardController.php
│   │   └── Views/
│   │       ├── dashboard.php
│   │       └── article-form.php
│   └── Contracts/
│       ├── ModelInterface.php
│       └── CMSInterface.php
├── config/
│   ├── orbit.php.dist           # CMS configuration
│   └── database.php.dist        # Database configuration
├── migrations/
│   └── 001_create_articles.sql  # Database schema
├── tests/
│   ├── Unit/
│   ├── Integration/
│   └── Feature/
├── docs/
│   ├── README.md
│   ├── GETTING_STARTED.md
│   └── API.md
└── composer.json
```

## 🔧 **Core Features**

### **1. Article Management**
```php
use MvaBootstrap\OrbitCMS\Models\Article;

// Create article
$article = Article::create([
    'title' => 'My Article',
    'content' => 'Article content...',
    'slug' => 'my-article',
    'status' => 'published',
]);

// Find articles
$articles = Article::all();
$article = Article::find('my-article');
$published = Article::where('status', 'published')->get();
```

### **2. Hybrid Storage**
```php
use MvaBootstrap\OrbitCMS\Services\OrbitManager;

$orbit = new OrbitManager([
    'storage' => 'hybrid',  // file + sqlite
    'content_path' => 'content',
    'database_path' => 'var/orbit/articles.db',
]);

// Automatic sync between file and database
$orbit->sync();
```

### **3. Admin Interface**
```php
// Built-in admin routes
$app->group('/admin', function () {
    $this->get('/', DashboardController::class . ':index');
    $this->get('/articles', ArticleController::class . ':index');
    $this->post('/articles', ArticleController::class . ':create');
    $this->put('/articles/{id}', ArticleController::class . ':update');
});
```

### **4. Search & Filtering**
```php
use MvaBootstrap\OrbitCMS\Services\SearchService;

$search = new SearchService();
$results = $search->query('php framework')
    ->in(['title', 'content'])
    ->where('status', 'published')
    ->limit(10)
    ->get();
```

## 🏗️ **Integration with Core**

### **Uses Core Features:**
- ✅ **PathsFactory** for secure file paths
- ✅ **SecurePathHelper** for file operations
- ✅ **ModuleSystem** for CMS modules
- ✅ **Application** for bootstrap

### **Example Integration:**
```php
use MvaBootstrap\Core\Bootstrap\Application;
use MvaBootstrap\OrbitCMS\OrbitCMS;

// Create core application
$app = new Application();
$app->initialize();

// Add Orbit CMS
$cms = new OrbitCMS($app);
$cms->configure([
    'content_path' => 'content/articles',
    'database_path' => 'var/orbit/cms.db',
    'admin_enabled' => true,
]);

$cms->initialize();
$app->run();
```

## 📊 **Package Dependencies**

### **composer.json:**
```json
{
    "name": "mva-bootstrap/orbit-cms",
    "description": "Laravel Orbit-inspired CMS for MVA Bootstrap Core",
    "require": {
        "php": ">=8.3",
        "mva-bootstrap/core": "^1.0",
        "ext-sqlite3": "*",
        "ext-pdo": "*"
    },
    "require-dev": {
        "phpunit/phpunit": "^10.0",
        "phpstan/phpstan": "^1.10"
    }
}
```

## 🎯 **Development Phases**

### **Phase 1: Core Models (1 week)**
- Article model with metadata
- Document model
- Collection handling
- Basic CRUD operations

### **Phase 2: Storage Layer (1 week)**
- SQLite driver
- File driver  
- Hybrid storage
- Migration system

### **Phase 3: Admin Interface (1 week)**
- Dashboard
- Article management
- User interface
- Authentication

### **Phase 4: Advanced Features (1 week)**
- Search functionality
- Content relationships
- Media management
- Performance optimization

## 🚀 **Benefits of This Approach**

### **For Core:**
- ✅ Stays minimal and focused
- ✅ Faster adoption (less complexity)
- ✅ Better testing (smaller scope)
- ✅ Clear separation of concerns

### **For Orbit CMS:**
- ✅ Specialized for content management
- ✅ Can evolve independently
- ✅ Optional for users who don't need CMS
- ✅ Can have its own community

### **For Ecosystem:**
- ✅ Multiple packages for different needs
- ✅ Mix and match functionality
- ✅ Easier to maintain
- ✅ Better documentation per package

## 💡 **Alternative: Minimal Core Extensions**

**If we want to add something to Core, I suggest only:**

### **1. Event System (lightweight)**
```php
// Very simple event dispatcher
class EventDispatcher
{
    private array $listeners = [];
    
    public function dispatch(string $event, array $data = []): void
    public function listen(string $event, callable $listener): void
}
```

### **2. Configuration Manager**
```php
// Simple config management
class ConfigManager
{
    public function get(string $key, mixed $default = null): mixed
    public function load(string $file): array
}
```

---

## 🎯 **Recommendation**

**I recommend:**

1. **Keep Core minimal** - Current features are perfect
2. **Create Orbit CMS as separate package** - `mva-bootstrap/orbit-cms`
3. **Maybe add lightweight Event System** to Core
4. **Build ecosystem of focused packages**

**This approach:**
- ✅ Keeps Core adoption easy
- ✅ Allows specialized packages
- ✅ Follows Unix philosophy (do one thing well)
- ✅ Enables community contributions per package

**What do you think? Should we keep Core minimal or add some lightweight features?** 🤔
