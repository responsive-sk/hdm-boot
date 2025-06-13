.
├── bin
│   └── route-list.php
├── config
│   ├── env
│   ├── monitoring
│   ├── routes
│   │   ├── docs.php
│   │   └── monitoring.php
│   ├── services
│   │   ├── core_modules.php
│   │   ├── events.php
│   │   ├── interfaces.php
│   │   └── logging.php
│   ├── container.php
│   ├── language.php
│   ├── paths.php
│   └── routes.php
├── database
│   └── migrations
│       └── 003_create_sessions_table.sql
├── docs
│   ├── api
│   │   ├── events-api.md
│   │   └── module-management-api.md
│   ├── architecture
│   │   ├── clean-architecture.md
│   │   ├── dependency-injection.md
│   │   ├── domain-driven-design.md
│   │   ├── error-handling.md
│   │   ├── event-driven-architecture.md
│   │   ├── event-examples.md
│   │   ├── module-isolation.md
│   │   ├── module-management.md
│   │   ├── monitoring-logging.md
│   │   └── README.md
│   ├── guides
│   │   └── module-configuration-guide.md
│   ├── templates
│   │   └── module-config-template.php
│   ├── API.md
│   ├── API.md.bak
│   ├── ARCHITECTURE.md
│   ├── DATABASE_MANAGER.md
│   ├── DEPLOYMENT.md
│   ├── LANGUAGE_FEATURES.md
│   ├── LANGUAGE.md
│   ├── MODULES.md
│   ├── PATH_SECURITY.md
│   ├── README.md
│   ├── SECURITY.md
│   ├── SESSION.md
│   ├── USER_API.md
│   └── USER_MODULE.md
├── .phpunit.cache
│   └── test-results
├── resources
│   └── translations
│       ├── cs_CZ
│       │   └── LC_MESSAGES
│       ├── sk_SK
│       │   └── LC_MESSAGES
│       └── messages.pot
├── src
│   ├── Bootstrap
│   │   ├── App.php
│   │   └── ModuleManager.php
│   ├── Modules
│   │   ├── Core
│   │   │   ├── Database
│   │   │   │   ├── Application
│   │   │   │   │   └── Commands
│   │   │   │   ├── Domain
│   │   │   │   │   └── Contracts
│   │   │   │   │       └── DatabaseManagerInterface.php
│   │   │   │   ├── Infrastructure
│   │   │   │   │   ├── Factories
│   │   │   │   │   │   └── RepositoryFactory.php
│   │   │   │   │   └── Services
│   │   │   │   │       ├── CakePHPDatabaseManager.php
│   │   │   │   │       ├── DatabaseConnectionManager.php
│   │   │   │   │       └── DatabaseManager.php
│   │   │   │   ├── config.php
│   │   │   │   └── module.php
│   │   │   ├── Documentation
│   │   │   │   ├── Application
│   │   │   │   │   └── Actions
│   │   │   │   ├── Domain
│   │   │   │   │   ├── Models
│   │   │   │   │   └── Services
│   │   │   │   └── Infrastructure
│   │   │   │       └── Actions
│   │   │   │           └── DocsViewerAction.php
│   │   │   ├── ErrorHandling
│   │   │   │   ├── Application
│   │   │   │   │   ├── Handlers
│   │   │   │   │   └── Middleware
│   │   │   │   ├── Domain
│   │   │   │   │   └── Contracts
│   │   │   │   └── Infrastructure
│   │   │   │       ├── Exceptions
│   │   │   │       │   ├── AuthenticationException.php
│   │   │   │       │   ├── AuthorizationException.php
│   │   │   │       │   ├── ProblemDetailsException.php
│   │   │   │       │   └── ValidationException.php
│   │   │   │       ├── Handlers
│   │   │   │       │   └── ErrorResponseHandler.php
│   │   │   │       ├── Helpers
│   │   │   │       │   └── ErrorHelper.php
│   │   │   │       ├── Middleware
│   │   │   │       │   └── ErrorHandlerMiddleware.php
│   │   │   │       └── ProblemDetails
│   │   │   │           └── ProblemDetails.php
│   │   │   ├── Language
│   │   │   │   ├── Application
│   │   │   │   │   ├── Actions
│   │   │   │   │   │   └── Api
│   │   │   │   │   │       └── TranslateAction.php
│   │   │   │   │   ├── Commands
│   │   │   │   │   │   └── ChangeLocaleCommand.php
│   │   │   │   │   ├── DTOs
│   │   │   │   │   │   ├── LanguageSettingsRequest.php
│   │   │   │   │   │   └── TranslateRequest.php
│   │   │   │   │   └── Queries
│   │   │   │   │       └── GetTranslationQuery.php
│   │   │   │   ├── Contracts
│   │   │   │   │   ├── DTOs
│   │   │   │   │   ├── Events
│   │   │   │   │   │   └── LanguageModuleEvents.php
│   │   │   │   │   └── Services
│   │   │   │   │       └── LocaleServiceInterface.php
│   │   │   │   ├── Domain
│   │   │   │   │   ├── Contracts
│   │   │   │   │   │   └── TranslationRepositoryInterface.php
│   │   │   │   │   ├── Events
│   │   │   │   │   │   ├── LocaleChangedEvent.php
│   │   │   │   │   │   └── TranslationAddedEvent.php
│   │   │   │   │   ├── Models
│   │   │   │   │   │   └── Translation.php
│   │   │   │   │   ├── Services
│   │   │   │   │   │   └── TranslationService.php
│   │   │   │   │   └── ValueObjects
│   │   │   │   │       ├── Locale.php
│   │   │   │   │       └── TranslationKey.php
│   │   │   │   ├── Exceptions
│   │   │   │   ├── Infrastructure
│   │   │   │   │   ├── External
│   │   │   │   │   ├── Listeners
│   │   │   │   │   │   └── LocaleChangedListener.php
│   │   │   │   │   ├── Middleware
│   │   │   │   │   │   └── LocaleMiddleware.php
│   │   │   │   │   ├── Persistence
│   │   │   │   │   └── Repositories
│   │   │   │   ├── Services
│   │   │   │   │   └── LocaleService.php
│   │   │   │   ├── config.php
│   │   │   │   └── module.php
│   │   │   ├── Logging
│   │   │   │   ├── Application
│   │   │   │   ├── Domain
│   │   │   │   └── Infrastructure
│   │   │   │       └── Services
│   │   │   │           └── LoggerFactory.php
│   │   │   ├── Monitoring
│   │   │   │   ├── Application
│   │   │   │   │   ├── Actions
│   │   │   │   │   ├── Commands
│   │   │   │   │   └── Queries
│   │   │   │   ├── Domain
│   │   │   │   │   ├── Contracts
│   │   │   │   │   ├── Models
│   │   │   │   │   └── Services
│   │   │   │   └── Infrastructure
│   │   │   │       ├── Actions
│   │   │   │       │   └── HealthCheckAction.php
│   │   │   │       ├── Bootstrap
│   │   │   │       │   └── MonitoringBootstrap.php
│   │   │   │       ├── HealthChecks
│   │   │   │       │   ├── DatabaseHealthCheck.php
│   │   │   │       │   ├── FilesystemHealthCheck.php
│   │   │   │       │   └── HealthCheckManager.php
│   │   │   │       └── Metrics
│   │   │   │           └── PerformanceMonitor.php
│   │   │   ├── Security
│   │   │   │   ├── Actions
│   │   │   │   │   └── Web
│   │   │   │   │       ├── LoginPageAction.php
│   │   │   │   │       ├── LoginSubmitAction.php
│   │   │   │   │       └── LogoutAction.php
│   │   │   │   ├── Application
│   │   │   │   │   ├── Actions
│   │   │   │   │   │   └── LoginSubmitAction.php
│   │   │   │   │   ├── Commands
│   │   │   │   │   ├── Handlers
│   │   │   │   │   └── Queries
│   │   │   │   ├── Contracts
│   │   │   │   │   ├── DTOs
│   │   │   │   │   ├── Events
│   │   │   │   │   │   └── SecurityModuleEvents.php
│   │   │   │   │   └── Services
│   │   │   │   │       ├── AuthenticationServiceInterface.php
│   │   │   │   │       └── AuthorizationServiceInterface.php
│   │   │   │   ├── Domain
│   │   │   │   │   ├── DTOs
│   │   │   │   │   │   ├── LoginRequest.php
│   │   │   │   │   │   └── LoginResult.php
│   │   │   │   │   ├── Events
│   │   │   │   │   ├── Models
│   │   │   │   │   └── Services
│   │   │   │   │       └── AuthenticationDomainService.php
│   │   │   │   ├── Exceptions
│   │   │   │   │   ├── AuthenticationException.php
│   │   │   │   │   ├── AuthorizationException.php
│   │   │   │   │   ├── SecurityException.php
│   │   │   │   │   └── ValidationException.php
│   │   │   │   ├── Infrastructure
│   │   │   │   │   └── Middleware
│   │   │   │   │       └── UserAuthenticationMiddleware.php
│   │   │   │   ├── Middleware
│   │   │   │   │   └── AuthorizationMiddleware.php
│   │   │   │   ├── Services
│   │   │   │   │   ├── AuthenticationService.php
│   │   │   │   │   ├── AuthenticationValidator.php
│   │   │   │   │   ├── AuthorizationService.php
│   │   │   │   │   ├── JwtService.php
│   │   │   │   │   └── SecurityLoginChecker.php
│   │   │   │   ├── config.php
│   │   │   │   ├── module.php
│   │   │   │   ├── routes.php
│   │   │   │   └── SecurityModule.php
│   │   │   ├── Session
│   │   │   │   ├── Application
│   │   │   │   │   ├── Actions
│   │   │   │   │   ├── Commands
│   │   │   │   │   └── Queries
│   │   │   │   ├── Domain
│   │   │   │   │   ├── Contracts
│   │   │   │   │   ├── Entities
│   │   │   │   │   ├── Events
│   │   │   │   │   ├── Services
│   │   │   │   │   └── ValueObjects
│   │   │   │   ├── Enum
│   │   │   │   │   └── SecurityType.php
│   │   │   │   ├── Exceptions
│   │   │   │   │   └── SecurityException.php
│   │   │   │   ├── Infrastructure
│   │   │   │   │   ├── Middleware
│   │   │   │   │   │   └── SessionStartMiddleware.php
│   │   │   │   │   ├── Persistence
│   │   │   │   │   └── Services
│   │   │   │   ├── Services
│   │   │   │   │   ├── CsrfService.php
│   │   │   │   │   └── SessionService.php
│   │   │   │   ├── config.php
│   │   │   │   ├── module.php
│   │   │   │   └── README.md
│   │   │   ├── Template
│   │   │   │   ├── Application
│   │   │   │   │   ├── Actions
│   │   │   │   │   │   └── RenderTemplateAction.php
│   │   │   │   │   └── DTOs
│   │   │   │   │       └── RenderTemplateRequest.php
│   │   │   │   ├── Domain
│   │   │   │   │   ├── Contracts
│   │   │   │   │   │   ├── TemplateEngineInterface.php
│   │   │   │   │   │   └── TemplateRendererInterface.php
│   │   │   │   │   ├── Events
│   │   │   │   │   │   └── TemplateRenderedEvent.php
│   │   │   │   │   ├── Services
│   │   │   │   │   │   └── TemplateService.php
│   │   │   │   │   └── ValueObjects
│   │   │   │   │       ├── TemplateData.php
│   │   │   │   │       └── TemplateName.php
│   │   │   │   ├── Infrastructure
│   │   │   │   │   ├── Engines
│   │   │   │   │   │   ├── PhpTemplateEngine.php
│   │   │   │   │   │   └── TwigTemplateEngine.php
│   │   │   │   │   └── Services
│   │   │   │   │       └── TemplateRenderer.php
│   │   │   │   ├── config.php
│   │   │   │   └── module.php
│   │   │   └── User
│   │   │       ├── Actions
│   │   │       │   └── Web
│   │   │       │       └── ProfilePageAction.php
│   │   │       ├── Application
│   │   │       │   ├── Actions
│   │   │       │   ├── Commands
│   │   │       │   │   ├── RegisterUserCommand.php
│   │   │       │   │   └── UpdateUserCommand.php
│   │   │       │   ├── Handlers
│   │   │       │   │   ├── GetUserProfileHandler.php
│   │   │       │   │   └── RegisterUserHandler.php
│   │   │       │   └── Queries
│   │   │       │       ├── FindUserByEmailQuery.php
│   │   │       │       └── GetUserProfileQuery.php
│   │   │       ├── Contracts
│   │   │       │   ├── DTOs
│   │   │       │   │   └── UserDataDTO.php
│   │   │       │   ├── Events
│   │   │       │   │   └── UserModuleEvents.php
│   │   │       │   └── Services
│   │   │       │       └── UserServiceInterface.php
│   │   │       ├── Domain
│   │   │       │   ├── DTOs
│   │   │       │   ├── Events
│   │   │       │   │   ├── UserWasRegistered.php
│   │   │       │   │   └── UserWasUpdated.php
│   │   │       │   ├── Models
│   │   │       │   │   └── User.php
│   │   │       │   └── Services
│   │   │       │       └── UserDomainService.php
│   │   │       ├── Exceptions
│   │   │       │   ├── UserAlreadyExistsException.php
│   │   │       │   └── UserNotFoundException.php
│   │   │       ├── Infrastructure
│   │   │       ├── Repository
│   │   │       │   ├── SqliteUserRepository.php
│   │   │       │   └── UserRepositoryInterface.php
│   │   │       ├── Services
│   │   │       │   └── UserService.php
│   │   │       ├── config.php
│   │   │       ├── module.php
│   │   │       ├── routes.php
│   │   │       └── UserModule.php
│   │   └── Optional
│   │       └── Article
│   │           └── README.md
│   └── SharedKernel
│       ├── Contracts
│       │   ├── MiddlewareInterface.php
│       │   └── ModuleInterface.php
│       ├── CQRS
│       │   ├── Bus
│       │   │   ├── CommandBus.php
│       │   │   └── QueryBus.php
│       │   ├── Commands
│       │   │   └── CommandInterface.php
│       │   ├── Events
│       │   │   └── DomainEventInterface.php
│       │   ├── Handlers
│       │   │   ├── CommandHandlerInterface.php
│       │   │   └── QueryHandlerInterface.php
│       │   └── Queries
│       │       └── QueryInterface.php
│       ├── Events
│       │   ├── AbstractDomainEvent.php
│       │   ├── AbstractSystemEvent.php
│       │   ├── DomainEvent.php
│       │   ├── EventBootstrap.php
│       │   ├── EventDispatcherInterface.php
│       │   ├── EventDispatcher.php
│       │   ├── EventListener.php
│       │   ├── ModuleEventBus.php
│       │   └── SystemEvent.php
│       ├── EventStore
│       │   ├── Contracts
│       │   │   └── EventStoreInterface.php
│       │   ├── Infrastructure
│       │   │   ├── DatabaseEventStore.php
│       │   │   └── InMemoryEventStore.php
│       │   ├── ValueObjects
│       │   │   └── StoredEvent.php
│       │   └── config.php
│       ├── HealthChecks
│       │   ├── Contracts
│       │   │   └── HealthCheckInterface.php
│       │   ├── Infrastructure
│       │   │   └── HealthCheckRegistry.php
│       │   ├── ValueObjects
│       │   │   ├── HealthCheckReport.php
│       │   │   ├── HealthCheckResult.php
│       │   │   └── HealthStatus.php
│       │   └── config.php
│       ├── Helpers
│       │   └── SecurePathHelper.php
│       └── Modules
│           ├── GenericModule.php
│           ├── ModuleManager.php
│           ├── ModuleManifest.php
│           └── ModuleServiceLoader.php
├── stubs
│   └── OdanSessionInterface.php
├── templates
│   ├── auth
│   │   └── login.php
│   └── user
│       ├── profile.php
│       └── profile_simple.php
├── tests
│   ├── Feature
│   │   ├── Security
│   │   │   └── AuthenticationTest.php
│   │   ├── User
│   │   │   └── UserApiTest.php
│   │   └── SimpleApiTest.php
│   ├── Integration
│   │   ├── AuthenticationFlowTest.php
│   │   └── .gitkeep
│   ├── TestCase
│   │   └── ModuleTestCase.php
│   ├── Unit
│   │   ├── Database
│   │   │   ├── DatabaseManagerTest.php
│   │   │   └── SimpleDatabaseTest.php
│   │   ├── Factories
│   │   │   └── RepositoryFactoryTest.php
│   │   ├── Helpers
│   │   │   └── SecurePathHelperTest.php
│   │   ├── Middleware
│   │   │   └── UserAuthenticationMiddlewareTest.php
│   │   ├── Modules
│   │   │   ├── SecurityModuleTest.php
│   │   │   ├── SessionModuleTest.php
│   │   │   └── UserModuleTest.php
│   │   └── Services
│   │       └── UserServiceTest.php
│   ├── bootstrap.php
│   └── TestCase.php
├── var
│   ├── cache
│   │   ├── templates
│   │   ├── translations
│   │   ├── twig
│   │   └── .gitkeep
│   ├── coverage
│   │   ├── testdox.html
│   │   └── testdox.txt
│   ├── logs
│   │   ├── app
│   │   │   └── debug.log
│   │   ├── errors
│   │   ├── performance
│   │   │   └── metrics-2025-06-11.log
│   │   ├── security
│   │   │   └── security-2025-06-09.log
│   │   ├── app.log
│   │   ├── debug-app.log
│   │   ├── .gitkeep
│   │   ├── performance-2025-06-11.log
│   │   └── performance-2025-06-12.log
│   ├── sessions
│   │   └── .gitkeep
│   ├── storage
│   │   ├── app.db
│   │   └── .gitkeep
│   └── uploads
│       └── .gitkeep
├── CHANGELOG.md
├── composer.json
├── composer.lock
├── CONTRIBUTING.md
├── directory_tree-slim4.md
├── .env
├── .env.example
├── .gitignore
├── .php-cs-fixer.cache
├── .php-cs-fixer.php
├── phpstan.neon
├── phpunit.xml
├── README.md
├── test-database.php
├── test-security.php
├── test-user.php
└── USER_MODULE_SUMMARY.md

222 directories, 243 files
