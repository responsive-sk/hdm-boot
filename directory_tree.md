.
├── bootstrap
│   ├── Middleware
│   ├── App.php
│   └── ModuleManager.php
├── config
│   ├── env
│   ├── routes
│   │   ├── api.php
│   │   ├── home.php
│   │   ├── test.php
│   │   └── web.php
│   ├── container.php
│   ├── language.php
│   ├── paths.php
│   └── routes.php
├── database
│   └── app.db
├── docs
│   ├── API.md
│   ├── API.md.bak
│   ├── ARCHITECTURE.md
│   ├── DATABASE_MANAGER.md
│   ├── DEPLOYMENT.md
│   ├── LANGUAGE.md
│   ├── MODULES.md
│   ├── PATH_SECURITY.md
│   ├── README.md
│   ├── SECURITY.md
│   ├── SESSION.md
│   ├── USER_API.md
│   └── USER_MODULE.md
├── logs
│   ├── debug-app.log
│   └── security-2025-06-09.log
├── modules
│   ├── Core
│   │   ├── Language
│   │   │   ├── Actions
│   │   │   │   └── Api
│   │   │   │       ├── LanguageSettingsAction.php
│   │   │   │       └── TranslateAction.php
│   │   │   └── Services
│   │   │       └── LocaleService.php
│   │   ├── Security
│   │   │   ├── Actions
│   │   │   │   ├── Web
│   │   │   │   │   ├── LoginPageAction.php
│   │   │   │   │   ├── LoginSubmitAction.php
│   │   │   │   │   └── LogoutAction.php
│   │   │   │   ├── LoginAction.php
│   │   │   │   ├── LogoutAction.php
│   │   │   │   ├── MeAction.php
│   │   │   │   └── RefreshTokenAction.php
│   │   │   ├── Domain
│   │   │   │   └── ValueObjects
│   │   │   │       └── JwtToken.php
│   │   │   ├── Enum
│   │   │   │   └── SecurityType.php
│   │   │   ├── Exception
│   │   │   │   ├── AuthenticationException.php
│   │   │   │   ├── AuthorizationException.php
│   │   │   │   ├── SecurityException.php
│   │   │   │   └── ValidationException.php
│   │   │   ├── Infrastructure
│   │   │   ├── Middleware
│   │   │   │   ├── AuthenticationMiddleware.php
│   │   │   │   └── AuthorizationMiddleware.php
│   │   │   ├── Services
│   │   │   │   ├── AuthenticationService.php
│   │   │   │   ├── AuthenticationValidator.php
│   │   │   │   ├── AuthorizationService.php
│   │   │   │   ├── CsrfService.php
│   │   │   │   ├── JwtService.php
│   │   │   │   ├── SecurityLoginChecker.php
│   │   │   │   └── SessionService.php
│   │   │   ├── config.php
│   │   │   └── routes.php
│   │   └── User
│   │       ├── Actions
│   │       │   ├── Web
│   │       │   │   └── ProfilePageAction.php
│   │       │   ├── CreateUserAction.php
│   │       │   ├── GetUserAction.php
│   │       │   └── ListUsersAction.php
│   │       ├── Domain
│   │       │   ├── Entities
│   │       │   │   └── User.php
│   │       │   └── ValueObjects
│   │       │       └── UserId.php
│   │       ├── Infrastructure
│   │       ├── Repository
│   │       │   ├── SqliteUserRepository.php
│   │       │   └── UserRepositoryInterface.php
│   │       ├── Services
│   │       │   └── UserService.php
│   │       ├── config.php
│   │       └── routes.php
│   └── Optional
│       ├── Article
│       ├── Authentication
│       │   ├── ChangeUserStatus
│       │   │   ├── Repository
│       │   │   │   └── UserStatusUpdaterRepository.php
│       │   │   └── Service
│       │   │       └── UserStatusUpdater.php
│       │   ├── Data
│       │   │   └── UserVerificationData.php
│       │   ├── Exception
│       │   │   └── UserAlreadyVerifiedException.php
│       │   ├── Login
│       │   │   ├── Action
│       │   │   │   ├── LoginPageAction.php
│       │   │   │   └── LoginSubmitAction.php
│       │   │   ├── Domain
│       │   │   │   ├── Exception
│       │   │   │   │   ├── AuthenticationException.php
│       │   │   │   │   ├── InvalidCredentialsException.php
│       │   │   │   │   └── UnableToLoginStatusNotActiveException.php
│       │   │   │   └── Service
│       │   │   │       ├── AuthenticationLogger.php
│       │   │   │       ├── LoginMailSender.php
│       │   │   │       ├── LoginNonActiveUserHandler.php
│       │   │   │       └── LoginVerifier.php
│       │   │   └── Repository
│       │   │       ├── AuthenticationLoggerRepository.php
│       │   │       └── LoginUserFinderRepository.php
│       │   ├── Logout
│       │   │   └── Action
│       │   │       └── LogoutPageAction.php
│       │   ├── PasswordReset
│       │   │   ├── Action
│       │   │   │   ├── NewPasswordResetSubmitAction.php
│       │   │   │   ├── PasswordChangeSubmitAction.php
│       │   │   │   ├── PasswordForgottenEmailSubmitAction.php
│       │   │   │   └── PasswordResetPageAction.php
│       │   │   ├── Repository
│       │   │   │   ├── PasswordChangerRepository.php
│       │   │   │   └── PasswordResetUserFinderRepository.php
│       │   │   └── Service
│       │   │       ├── PasswordChanger.php
│       │   │       ├── PasswordRecoveryEmailSender.php
│       │   │       └── PasswordResetterWithToken.php
│       │   ├── Register
│       │   │   ├── Action
│       │   │   │   └── RegisterVerifyProcessAction.php
│       │   │   └── Service
│       │   │       ├── RegisterTokenVerifier.php
│       │   │       └── RegistrationMailSender.php
│       │   ├── TokenVerification
│       │   │   ├── Exception
│       │   │   │   └── InvalidTokenException.php
│       │   │   ├── Repository
│       │   │   │   ├── VerificationTokenCreatorRepository.php
│       │   │   │   ├── VerificationTokenDeleterRepository.php
│       │   │   │   ├── VerificationTokenFinderRepository.php
│       │   │   │   └── VerificationTokenUpdaterRepository.php
│       │   │   └── Service
│       │   │       ├── VerificationTokenCreator.php
│       │   │       ├── VerificationTokenUpdater.php
│       │   │       └── VerificationTokenVerifier.php
│       │   ├── UnlockAccount
│       │   │   ├── Action
│       │   │   │   └── AccountUnlockProcessAction.php
│       │   │   └── Service
│       │   │       └── AccountUnlockTokenVerifier.php
│       │   └── Validation
│       │       ├── Repository
│       │       │   └── UserPasswordHashFinderRepository.php
│       │       └── Service
│       │           └── AuthenticationValidator.php
│       ├── Authorization
│       │   ├── Enum
│       │   │   └── Privilege.php
│       │   ├── Exception
│       │   │   └── ForbiddenException.php
│       │   ├── Repository
│       │   │   └── AuthorizationUserRoleFinderRepository.php
│       │   └── Service
│       │       └── AuthorizedByRoleChecker.php
│       ├── Security
│       │   ├── Captcha
│       │   │   └── Service
│       │   │       └── SecurityCaptchaVerifier.php
│       │   ├── Email
│       │   │   ├── Repository
│       │   │   │   └── EmailLogFinderRepository.php
│       │   │   └── Service
│       │   │       ├── EmailRequestFinder.php
│       │   │       └── SecurityEmailChecker.php
│       │   ├── Enum
│       │   │   └── SecurityType.php
│       │   ├── Exception
│       │   │   └── SecurityException.php
│       │   └── Login
│       │       ├── Repository
│       │       │   └── LoginLogFinderRepository.php
│       │       └── Service
│       │           ├── LoginRequestFinder.php
│       │           └── SecurityLoginChecker.php
│       ├── slim-example-project-master
│       │   ├── bin
│       │   │   └── console.php
│       │   ├── config
│       │   │   ├── env
│       │   │   │   ├── env.dev.php
│       │   │   │   ├── env.example.php
│       │   │   │   ├── env.github.php
│       │   │   │   ├── env.phinx.php
│       │   │   │   ├── env.prod.php
│       │   │   │   ├── env.scrutinizer.php
│       │   │   │   └── env.test.php
│       │   │   ├── bootstrap.php
│       │   │   ├── container.php
│       │   │   ├── defaults.php
│       │   │   ├── functions.php
│       │   │   ├── middleware.php
│       │   │   ├── routes.php
│       │   │   └── settings.php
│       │   ├── .github
│       │   │   ├── workflows
│       │   │   │   ├── build.yml
│       │   │   │   └── deploy.yml
│       │   │   └── FUNDING.yml
│       │   ├── logs
│       │   │   └── empty
│       │   ├── resources
│       │   │   ├── migrations
│       │   │   │   └── 20240425150810_db_change_1527712828662a_71da_9af_9f.php
│       │   │   ├── schema
│       │   │   │   ├── schema.php
│       │   │   │   └── schema.sql
│       │   │   ├── seeds
│       │   │   │   ├── AdminUserSeeder.php
│       │   │   │   ├── ClientSeeder.php
│       │   │   │   ├── ClientStatusSeeder.php
│       │   │   │   ├── NoteSeeder.php
│       │   │   │   ├── UserFilterSettingSeeder.php
│       │   │   │   ├── UserRoleSeeder.php
│       │   │   │   └── UserSeeder.php
│       │   │   └── translations
│       │   │       ├── de_CH
│       │   │       │   └── LC_MESSAGES
│       │   │       │       ├── messages_de_CH.mo
│       │   │       │       └── messages_de_CH.po
│       │   │       ├── en_US
│       │   │       │   └── LC_MESSAGES
│       │   │       │       ├── messages_en_US.mo
│       │   │       │       └── messages_en_US.po
│       │   │       └── fr_CH
│       │   │           └── LC_MESSAGES
│       │   │               ├── messages_fr_CH.mo
│       │   │               └── messages_fr_CH.po
│       │   ├── src
│       │   │   ├── Application
│       │   │   │   ├── Data
│       │   │   │   │   └── UserNetworkSessionData.php
│       │   │   │   ├── ErrorRenderer
│       │   │   │   │   └── ProdErrorPageRenderer.php
│       │   │   │   ├── Middleware
│       │   │   │   │   ├── CorsMiddleware.php
│       │   │   │   │   ├── ForbiddenExceptionMiddleware.php
│       │   │   │   │   ├── InvalidOperationExceptionMiddleware.php
│       │   │   │   │   ├── LocaleMiddleware.php
│       │   │   │   │   ├── PhpViewMiddleware.php
│       │   │   │   │   ├── UserAuthenticationMiddleware.php
│       │   │   │   │   ├── UserNetworkSessionDataMiddleware.php
│       │   │   │   │   └── ValidationExceptionMiddleware.php
│       │   │   │   └── Responder
│       │   │   │       ├── JsonResponder.php
│       │   │   │       ├── RedirectHandler.php
│       │   │   │       └── TemplateRenderer.php
│       │   │   ├── Common
│       │   │   │   └── Trait
│       │   │   │       └── EnumToArray.php
│       │   │   ├── Domain
│       │   │   │   ├── Exception
│       │   │   │   │   ├── DomainRecordNotFoundException.php
│       │   │   │   │   └── InvalidOperationException.php
│       │   │   │   └── Validation
│       │   │   │       └── RequestBodyKeyValidator.php
│       │   │   ├── Infrastructure
│       │   │   │   ├── Database
│       │   │   │   │   ├── Exception
│       │   │   │   │   │   ├── PersistenceException.php
│       │   │   │   │   │   └── PersistenceRecordNotFoundException.php
│       │   │   │   │   ├── Hydrator.php
│       │   │   │   │   └── QueryFactory.php
│       │   │   │   ├── JsCacheBusting
│       │   │   │   │   └── JsImportCacheBuster.php
│       │   │   │   ├── Locale
│       │   │   │   │   └── LocaleConfigurator.php
│       │   │   │   └── Settings
│       │   │   │       └── Settings.php
│       │   │   └── Module
│       │   │       ├── Authentication
│       │   │       │   ├── ChangeUserStatus
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── UserStatusUpdaterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── UserStatusUpdater.php
│       │   │       │   ├── Data
│       │   │       │   │   └── UserVerificationData.php
│       │   │       │   ├── Exception
│       │   │       │   │   └── UserAlreadyVerifiedException.php
│       │   │       │   ├── Login
│       │   │       │   │   ├── Action
│       │   │       │   │   │   ├── LoginPageAction.php
│       │   │       │   │   │   └── LoginSubmitAction.php
│       │   │       │   │   ├── Domain
│       │   │       │   │   │   ├── Exception
│       │   │       │   │   │   │   ├── AuthenticationException.php
│       │   │       │   │   │   │   ├── InvalidCredentialsException.php
│       │   │       │   │   │   │   └── UnableToLoginStatusNotActiveException.php
│       │   │       │   │   │   └── Service
│       │   │       │   │   │       ├── AuthenticationLogger.php
│       │   │       │   │   │       ├── LoginMailSender.php
│       │   │       │   │   │       ├── LoginNonActiveUserHandler.php
│       │   │       │   │   │       └── LoginVerifier.php
│       │   │       │   │   └── Repository
│       │   │       │   │       ├── AuthenticationLoggerRepository.php
│       │   │       │   │       └── LoginUserFinderRepository.php
│       │   │       │   ├── Logout
│       │   │       │   │   └── Action
│       │   │       │   │       └── LogoutPageAction.php
│       │   │       │   ├── PasswordReset
│       │   │       │   │   ├── Action
│       │   │       │   │   │   ├── NewPasswordResetSubmitAction.php
│       │   │       │   │   │   ├── PasswordChangeSubmitAction.php
│       │   │       │   │   │   ├── PasswordForgottenEmailSubmitAction.php
│       │   │       │   │   │   └── PasswordResetPageAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   ├── PasswordChangerRepository.php
│       │   │       │   │   │   └── PasswordResetUserFinderRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── PasswordChanger.php
│       │   │       │   │       ├── PasswordRecoveryEmailSender.php
│       │   │       │   │       └── PasswordResetterWithToken.php
│       │   │       │   ├── Register
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── RegisterVerifyProcessAction.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── RegisterTokenVerifier.php
│       │   │       │   │       └── RegistrationMailSender.php
│       │   │       │   ├── TokenVerification
│       │   │       │   │   ├── Exception
│       │   │       │   │   │   └── InvalidTokenException.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   ├── VerificationTokenCreatorRepository.php
│       │   │       │   │   │   ├── VerificationTokenDeleterRepository.php
│       │   │       │   │   │   ├── VerificationTokenFinderRepository.php
│       │   │       │   │   │   └── VerificationTokenUpdaterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── VerificationTokenCreator.php
│       │   │       │   │       ├── VerificationTokenUpdater.php
│       │   │       │   │       └── VerificationTokenVerifier.php
│       │   │       │   ├── UnlockAccount
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── AccountUnlockProcessAction.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── AccountUnlockTokenVerifier.php
│       │   │       │   └── Validation
│       │   │       │       ├── Repository
│       │   │       │       │   └── UserPasswordHashFinderRepository.php
│       │   │       │       └── Service
│       │   │       │           └── AuthenticationValidator.php
│       │   │       ├── Authorization
│       │   │       │   ├── Enum
│       │   │       │   │   └── Privilege.php
│       │   │       │   ├── Exception
│       │   │       │   │   └── ForbiddenException.php
│       │   │       │   ├── Repository
│       │   │       │   │   └── AuthorizationUserRoleFinderRepository.php
│       │   │       │   └── Service
│       │   │       │       └── AuthorizedByRoleChecker.php
│       │   │       ├── Client
│       │   │       │   ├── AssignUser
│       │   │       │   │   └── Service
│       │   │       │   │       └── ClientAssignUserAuthorizationChecker.php
│       │   │       │   ├── Authorization
│       │   │       │   │   └── Service
│       │   │       │   │       └── ClientPrivilegeDeterminer.php
│       │   │       │   ├── ClientStatus
│       │   │       │   │   ├── Enum
│       │   │       │   │   │   └── ClientStatus.php
│       │   │       │   │   └── Repository
│       │   │       │   │       └── ClientStatusFinderRepository.php
│       │   │       │   ├── Create
│       │   │       │   │   ├── Action
│       │   │       │   │   │   ├── ApiClientCreateAction.php
│       │   │       │   │   │   ├── ClientCreateAction.php
│       │   │       │   │   │   └── ClientCreateDropdownOptionsFetchAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── ClientCreatorRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── ClientCreateAuthorizationChecker.php
│       │   │       │   │       ├── ClientCreatorFromApi.php
│       │   │       │   │       └── ClientCreator.php
│       │   │       │   ├── Data
│       │   │       │   │   └── ClientData.php
│       │   │       │   ├── Delete
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── ClientDeleteAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── ClientDeleterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── ClientDeleteAuthorizationChecker.php
│       │   │       │   │       └── ClientDeleter.php
│       │   │       │   ├── DropdownFinder
│       │   │       │   │   ├── Data
│       │   │       │   │   │   └── ClientDropdownValuesData.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── ClientDropdownFinder.php
│       │   │       │   ├── Enum
│       │   │       │   │   └── ClientVigilanceLevel.php
│       │   │       │   ├── FindOwner
│       │   │       │   │   └── Repository
│       │   │       │   │       └── ClientOwnerFinderRepository.php
│       │   │       │   ├── List
│       │   │       │   │   ├── Action
│       │   │       │   │   │   ├── ClientFetchListAction.php
│       │   │       │   │   │   └── ClientListPageAction.php
│       │   │       │   │   ├── Data
│       │   │       │   │   │   ├── ClientListResultCollection.php
│       │   │       │   │   │   └── ClientListResult.php
│       │   │       │   │   ├── Domain
│       │   │       │   │   │   ├── Exception
│       │   │       │   │   │   │   └── InvalidClientFilterException.php
│       │   │       │   │   │   └── Service
│       │   │       │   │   │       ├── ClientFilterWhereConditionBuilder.php
│       │   │       │   │   │       ├── ClientFinderWithFilter.php
│       │   │       │   │   │       ├── ClientListFilterChipProvider.php
│       │   │       │   │   │       └── ClientListFinder.php
│       │   │       │   │   └── Repository
│       │   │       │   │       └── ClientListFinderRepository.php
│       │   │       │   ├── Read
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── ClientReadPageAction.php
│       │   │       │   │   ├── Data
│       │   │       │   │   │   └── ClientReadResult.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   ├── ClientReadFinderRepository.php
│       │   │       │   │   │   └── ClientReadNoteAmountFinderRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── ClientReadAuthorizationChecker.php
│       │   │       │   │       └── ClientReadFinder.php
│       │   │       │   ├── Update
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── ClientUpdateAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   ├── ClientDeletedDateFinderRepository.php
│       │   │       │   │   │   └── ClientUpdaterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── ClientUpdateAuthorizationChecker.php
│       │   │       │   │       └── ClientUpdater.php
│       │   │       │   └── Validation
│       │   │       │       ├── Repository
│       │   │       │       │   └── ClientStatusValidatorRepository.php
│       │   │       │       └── Service
│       │   │       │           └── ClientValidator.php
│       │   │       ├── Dashboard
│       │   │       │   ├── DisplayPage
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── DashboardPageAction.php
│       │   │       │   │   ├── Data
│       │   │       │   │   │   └── DashboardData.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── DashboardPanelProvider.php
│       │   │       │   │       └── UserFilterChipProvider.php
│       │   │       │   └── TogglePanel
│       │   │       │       ├── Action
│       │   │       │       │   └── DashboardTogglePanelAction.php
│       │   │       │       └── Service
│       │   │       │           └── ActiveDashboardPanelChanger.php
│       │   │       ├── FilterSetting
│       │   │       │   ├── Enum
│       │   │       │   │   └── FilterModule.php
│       │   │       │   ├── Find
│       │   │       │   │   ├── Data
│       │   │       │   │   │   └── FilterData.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── UserFilterFinderRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── FilterSettingFinder.php
│       │   │       │   └── Save
│       │   │       │       ├── Repository
│       │   │       │       │   └── UserFilterSaverRepository.php
│       │   │       │       └── Service
│       │   │       │           └── FilterSettingSaver.php
│       │   │       ├── FormOption
│       │   │       │   └── SexOption.php
│       │   │       ├── Localization
│       │   │       │   └── Action
│       │   │       │       └── TranslateAction.php
│       │   │       ├── Mail
│       │   │       │   ├── Repository
│       │   │       │   │   └── EmailLoggerRepository.php
│       │   │       │   └── Service
│       │   │       │       └── Mailer.php
│       │   │       ├── Note
│       │   │       │   ├── Authorization
│       │   │       │   │   └── Service
│       │   │       │   │       └── NotePrivilegeDeterminer.php
│       │   │       │   ├── Create
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── NoteCreateAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── NoteCreatorRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── NoteCreateAuthorizationChecker.php
│       │   │       │   │       └── NoteCreator.php
│       │   │       │   ├── Data
│       │   │       │   │   └── NoteData.php
│       │   │       │   ├── Delete
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── NoteDeleteAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── NoteDeleterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── NoteDeleteAuthorizationChecker.php
│       │   │       │   │       └── NoteDeleter.php
│       │   │       │   ├── Find
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── NoteFinderRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── NoteFinder.php
│       │   │       │   ├── List
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── NoteFetchListAction.php
│       │   │       │   │   ├── Data
│       │   │       │   │   │   └── NoteResultData.php
│       │   │       │   │   ├── Domain
│       │   │       │   │   │   ├── Exception
│       │   │       │   │   │   │   └── InvalidNoteFilterException.php
│       │   │       │   │   │   └── Service
│       │   │       │   │   │       ├── NoteFilterFinder.php
│       │   │       │   │   │       └── NoteListFinder.php
│       │   │       │   │   └── Repository
│       │   │       │   │       ├── NoteListClientFinderRepository.php
│       │   │       │   │       └── NoteListFinderRepository.php
│       │   │       │   ├── Read
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── NoteReadPageAction.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── NoteReadAuthorizationChecker.php
│       │   │       │   ├── Update
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── NoteUpdateAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── NoteUpdaterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── NoteUpdateAuthorizationChecker.php
│       │   │       │   │       └── NoteUpdater.php
│       │   │       │   └── Validation
│       │   │       │       ├── Repository
│       │   │       │       │   └── NoteValidatorRepository.php
│       │   │       │       └── Service
│       │   │       │           └── NoteValidator.php
│       │   │       ├── Security
│       │   │       │   ├── Captcha
│       │   │       │   │   └── Service
│       │   │       │   │       └── SecurityCaptchaVerifier.php
│       │   │       │   ├── Email
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── EmailLogFinderRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── EmailRequestFinder.php
│       │   │       │   │       └── SecurityEmailChecker.php
│       │   │       │   ├── Enum
│       │   │       │   │   └── SecurityType.php
│       │   │       │   ├── Exception
│       │   │       │   │   └── SecurityException.php
│       │   │       │   └── Login
│       │   │       │       ├── Repository
│       │   │       │       │   └── LoginLogFinderRepository.php
│       │   │       │       └── Service
│       │   │       │           ├── LoginRequestFinder.php
│       │   │       │           └── SecurityLoginChecker.php
│       │   │       ├── User
│       │   │       │   ├── AssignRole
│       │   │       │   │   └── Service
│       │   │       │   │       └── UserAssignRoleAuthorizationChecker.php
│       │   │       │   ├── Authorization
│       │   │       │   │   └── Service
│       │   │       │   │       └── UserPrivilegeDeterminer.php
│       │   │       │   ├── Create
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── UserCreateAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   ├── UserCreateRoleFinderRepository.php
│       │   │       │   │   │   └── UserCreatorRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── UserCreateAuthorizationChecker.php
│       │   │       │   │       └── UserCreator.php
│       │   │       │   ├── Data
│       │   │       │   │   ├── UserData.php
│       │   │       │   │   ├── UserResultData.php
│       │   │       │   │   └── UserRoleData.php
│       │   │       │   ├── Delete
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── UserDeleteAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── UserDeleterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── UserDeleteAuthorizationChecker.php
│       │   │       │   │       └── UserDeleter.php
│       │   │       │   ├── Enum
│       │   │       │   │   ├── UserActivity.php
│       │   │       │   │   ├── UserLang.php
│       │   │       │   │   ├── UserRole.php
│       │   │       │   │   ├── UserStatus.php
│       │   │       │   │   └── UserTheme.php
│       │   │       │   ├── Find
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── UserFinderRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── UserFinder.php
│       │   │       │   ├── FindAbbreviatedNameList
│       │   │       │   │   └── Service
│       │   │       │   │       ├── AbbreviatedUserNameListFinder.php
│       │   │       │   │       └── UserNameAbbreviator.php
│       │   │       │   ├── FindDropdownOptions
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── UserCreateDropdownOptionsFetchAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── UserDropdownOptionsRoleFinderRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── AuthorizedUserRoleFilterer.php
│       │   │       │   │       └── UserDropdownOptionFinder.php
│       │   │       │   ├── FindList
│       │   │       │   │   └── Repository
│       │   │       │   │       └── UserListFinderRepository.php
│       │   │       │   ├── ListPage
│       │   │       │   │   ├── Action
│       │   │       │   │   │   ├── UserFetchListAction.php
│       │   │       │   │   │   └── UserListPageAction.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── UserListPageFinder.php
│       │   │       │   ├── Read
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── UserReadPageAction.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── UserReadAuthorizationChecker.php
│       │   │       │   │       └── UserReadFinder.php
│       │   │       │   ├── Update
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── UserUpdateAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   ├── UserUpdateAuthorizationRoleFinderRepository.php
│       │   │       │   │   │   └── UserUpdaterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       ├── UserUpdateAuthorizationChecker.php
│       │   │       │   │       └── UserUpdater.php
│       │   │       │   └── Validation
│       │   │       │       ├── Repository
│       │   │       │       │   ├── UserExistenceCheckerRepository.php
│       │   │       │       │   └── ValidationUserRoleFinderRepository.php
│       │   │       │       └── Service
│       │   │       │           └── UserValidator.php
│       │   │       ├── UserActivity
│       │   │       │   ├── Create
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── UserActivityCreatorRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── UserActivityLogger.php
│       │   │       │   ├── Data
│       │   │       │   │   └── UserActivityData.php
│       │   │       │   ├── Delete
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── UserActivityDeleterRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── UserActivityDeleter.php
│       │   │       │   ├── List
│       │   │       │   │   ├── Action
│       │   │       │   │   │   └── UserActivityFetchListAction.php
│       │   │       │   │   ├── Repository
│       │   │       │   │   │   └── UserActivityListFinderRepository.php
│       │   │       │   │   └── Service
│       │   │       │   │       └── UserActivityListFinder.php
│       │   │       │   └── ReadAuthorization
│       │   │       │       └── Service
│       │   │       │           └── UserActivityReadAuthorizationChecker.php
│       │   │       ├── Validation
│       │   │       │   └── Exception
│       │   │       │       └── ValidationException.php
│       │   │       └── module-architecture.md
│       │   ├── templates
│       │   │   ├── authentication
│       │   │   │   ├── email
│       │   │   │   │   ├── de
│       │   │   │   │   │   ├── login-but-locked.email.php
│       │   │   │   │   │   ├── login-but-suspended.email.php
│       │   │   │   │   │   ├── login-but-unverified.email.php
│       │   │   │   │   │   ├── new-account.email.php
│       │   │   │   │   │   └── password-reset.email.php
│       │   │   │   │   ├── fr
│       │   │   │   │   │   ├── login-but-locked.email.php
│       │   │   │   │   │   ├── login-but-suspended.email.php
│       │   │   │   │   │   ├── login-but-unverified.email.php
│       │   │   │   │   │   ├── new-account.email.php
│       │   │   │   │   │   └── password-reset.email.php
│       │   │   │   │   ├── login-but-locked.email.php
│       │   │   │   │   ├── login-but-suspended.email.php
│       │   │   │   │   ├── login-but-unverified.email.php
│       │   │   │   │   ├── new-account.email.php
│       │   │   │   │   └── password-reset.email.php
│       │   │   │   ├── login.html.php
│       │   │   │   └── reset-password.html.php
│       │   │   ├── client
│       │   │   │   ├── client-read.html.php
│       │   │   │   └── clients-list.html.php
│       │   │   ├── dashboard
│       │   │   │   └── dashboard.html.php
│       │   │   ├── error
│       │   │   │   └── error-page.html.php
│       │   │   ├── layout
│       │   │   │   ├── assets.html.php
│       │   │   │   ├── flash-messages.html.php
│       │   │   │   ├── footer.html.php
│       │   │   │   ├── layout.html.php
│       │   │   │   ├── navbar.html.php
│       │   │   │   └── request-throttle.html.php
│       │   │   └── user
│       │   │       ├── user-list.html.php
│       │   │       └── user-read.html.php
│       │   ├── tests
│       │   │   ├── Fixture
│       │   │   │   ├── ClientFixture.php
│       │   │   │   ├── ClientStatusFixture.php
│       │   │   │   ├── NoteFixture.php
│       │   │   │   ├── UserActivityFixture.php
│       │   │   │   ├── UserFilterSettingFixture.php
│       │   │   │   ├── UserFixture.php
│       │   │   │   └── UserRoleFixture.php
│       │   │   ├── TestCase
│       │   │   │   ├── Authentication
│       │   │   │   │   ├── AccountUnlock
│       │   │   │   │   │   └── AccountUnlockActionTest.php
│       │   │   │   │   ├── Login
│       │   │   │   │   │   ├── LoginPageActionTest.php
│       │   │   │   │   │   ├── LoginProvider.php
│       │   │   │   │   │   └── LoginSubmitActionTest.php
│       │   │   │   │   ├── Logout
│       │   │   │   │   │   └── LogoutActionTest.php
│       │   │   │   │   ├── PasswordChange
│       │   │   │   │   │   ├── PasswordChangeSubmitActionTest.php
│       │   │   │   │   │   └── UserChangePasswordProvider.php
│       │   │   │   │   ├── PasswordReset
│       │   │   │   │   │   ├── PasswordForgottenEmailSubmitActionTest.php
│       │   │   │   │   │   └── PasswordResetSubmitActionTest.php
│       │   │   │   │   ├── Provider
│       │   │   │   │   │   └── UserVerificationProvider.php
│       │   │   │   │   └── Register
│       │   │   │   │       └── RegisterVerifyActionTest.php
│       │   │   │   ├── Client
│       │   │   │   │   ├── Create
│       │   │   │   │   │   ├── ClientCreateActionTest.php
│       │   │   │   │   │   ├── ClientCreateDropdownOptionsTest.php
│       │   │   │   │   │   └── ClientCreateProvider.php
│       │   │   │   │   ├── CreateApi
│       │   │   │   │   │   ├── ApiClientCreateActionTest.php
│       │   │   │   │   │   └── ApiClientCreateProvider.php
│       │   │   │   │   ├── Delete
│       │   │   │   │   │   ├── ClientDeleteActionTest.php
│       │   │   │   │   │   └── ClientDeleteProvider.php
│       │   │   │   │   ├── List
│       │   │   │   │   │   ├── ClientListActionTest.php
│       │   │   │   │   │   └── ClientListProvider.php
│       │   │   │   │   ├── Read
│       │   │   │   │   │   ├── ClientReadPageActionTest.php
│       │   │   │   │   │   └── ClientReadProvider.php
│       │   │   │   │   └── Update
│       │   │   │   │       ├── ClientUpdateActionTest.php
│       │   │   │   │       └── ClientUpdateProvider.php
│       │   │   │   ├── Dashboard
│       │   │   │   │   ├── DashboardPageActionTest.php
│       │   │   │   │   └── DashboardTogglePanelActionTest.php
│       │   │   │   ├── Note
│       │   │   │   │   ├── Create
│       │   │   │   │   │   ├── NoteCreateActionTest.php
│       │   │   │   │   │   └── NoteCreateProvider.php
│       │   │   │   │   ├── Delete
│       │   │   │   │   │   └── NoteDeleteActionTest.php
│       │   │   │   │   ├── List
│       │   │   │   │   │   ├── NoteListActionTest.php
│       │   │   │   │   │   └── NoteListProvider.php
│       │   │   │   │   ├── Provider
│       │   │   │   │   │   └── NoteCreateUpdateDeleteProvider.php
│       │   │   │   │   ├── Read
│       │   │   │   │   │   └── NoteReadPageActionTest.php
│       │   │   │   │   └── Update
│       │   │   │   │       ├── NoteUpdateActionTest.php
│       │   │   │   │       └── NoteUpdateProvider.php
│       │   │   │   ├── Security
│       │   │   │   │   ├── Integration
│       │   │   │   │   │   └── LoginSecurityTest.php
│       │   │   │   │   ├── Provider
│       │   │   │   │   │   ├── EmailRequestProvider.php
│       │   │   │   │   │   └── LoginRequestProvider.php
│       │   │   │   │   └── Unit
│       │   │   │   │       ├── SecurityEmailCheckerTest.php
│       │   │   │   │       └── SecurityLoginCheckerTest.php
│       │   │   │   ├── Translation
│       │   │   │   │   └── TranslateActionTest.php
│       │   │   │   ├── User
│       │   │   │   │   ├── Create
│       │   │   │   │   │   ├── UserCreateActionTest.php
│       │   │   │   │   │   ├── UserCreateDropdownOptionsTest.php
│       │   │   │   │   │   └── UserCreateProvider.php
│       │   │   │   │   ├── Delete
│       │   │   │   │   │   ├── UserDeleteActionTest.php
│       │   │   │   │   │   └── UserDeleteProvider.php
│       │   │   │   │   ├── List
│       │   │   │   │   │   ├── UserListActionTest.php
│       │   │   │   │   │   ├── UserListPageActionTest.php
│       │   │   │   │   │   └── UserListProvider.php
│       │   │   │   │   ├── Read
│       │   │   │   │   │   ├── UserReadPageActionTest.php
│       │   │   │   │   │   └── UserReadProvider.php
│       │   │   │   │   └── Update
│       │   │   │   │       ├── UserUpdateActionTest.php
│       │   │   │   │       └── UserUpdateProvider.php
│       │   │   │   └── UserActivity
│       │   │   │       └── UserFetchActivityActionTest.php
│       │   │   ├── Trait
│       │   │   │   ├── AppTestTrait.php
│       │   │   │   └── AuthorizationTestTrait.php
│       │   │   └── docs.md
│       │   ├── composer.json
│       │   ├── .cs.php
│       │   ├── .gitattributes
│       │   ├── .gitignore
│       │   ├── .htaccess
│       │   ├── LICENSE
│       │   ├── phpstan.neon
│       │   ├── phpunit.xml
│       │   ├── README.md
│       │   └── .scrutinizer.yml
│       └── Validation
│           └── Exception
│               └── ValidationException.php
├── resources
│   └── translations
│       ├── cs_CZ
│       │   └── LC_MESSAGES
│       ├── sk_SK
│       │   └── LC_MESSAGES
│       └── messages.pot
├── src
│   ├── Database
│   │   └── DatabaseManager.php
│   ├── Domain
│   ├── Helpers
│   │   └── SecurePathHelper.php
│   ├── Infrastructure
│   └── Shared
│       ├── Contracts
│       │   └── DatabaseManagerInterface.php
│       ├── Factories
│       │   └── RepositoryFactory.php
│       ├── Helpers
│       │   └── SecurePathHelper.php
│       ├── Middleware
│       │   ├── LocaleMiddleware.php
│       │   └── UserAuthenticationMiddleware.php
│       └── Services
│           ├── DatabaseConnectionManager.php
│           ├── LoggerFactory.php
│           └── TemplateRenderer.php
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
│   │   └── Services
│   │       └── UserServiceTest.php
│   ├── bootstrap.php
│   └── TestCase.php
├── var
│   ├── cache
│   │   └── .gitkeep
│   ├── logs
│   │   ├── app.log
│   │   └── .gitkeep
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
├── directory_tree.md
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

407 directories, 524 files
