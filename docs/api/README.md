# MVA Bootstrap API Documentation

## 🌐 API Prehľad

### 1. Core APIs [P0]
| API | Popis | Status |
|-----|-------|--------|
| [Auth API](auth-api.md) | Autentifikácia a autorizácia | 🚧 |
| [User API](../USER_API.md) | Správa užívateľov | ✅ |
| [Module API](module-management-api.md) | Správa modulov | ✅ |

### 2. System APIs [P0]
| API | Popis | Status |
|-----|-------|--------|
| [Health API](../API.md#monitoring-api) | Health check endpointy | ✅ |
| [Status API](../API.md#monitoring-api) | Systémový status | ✅ |
| [Error API](../API.md#error-responses) | Error handling | ✅ |

### 3. Feature APIs [P1]
| API | Popis | Status |
|-----|-------|--------|
| [Events API](events-api.md) | Event system | ✅ |
| [Language API](../LANGUAGE.md) | Jazykové nastavenia | ✅ |
| [Files API](../API.md#file-uploads) | Správa súborov | 🚧 |

## 📊 Endpoints Overview

### Authentication
```http
POST   /api/auth/login
POST   /api/auth/logout
POST   /api/auth/refresh
GET    /api/auth/me
```

### User Management
```http
GET    /api/users
POST   /api/users
GET    /api/users/{id}
PUT    /api/users/{id}
DELETE /api/users/{id}
```

### System Status
```http
GET    /api/status
GET    /_status
GET    /healthz
GET    /health
```

## 🔒 Security

### Headers
```http
Authorization: Bearer {token}
Content-Type: application/json
Accept: application/json
```

### Rate Limiting
```http
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 99
X-RateLimit-Reset: 1623581402
```

## 📝 Response Formats

### Success Response
```json
{
    "success": true,
    "data": {},
    "meta": {}
}
```

### Error Response
```json
{
    "success": false,
    "error": {
        "code": "ERROR_CODE",
        "message": "Error message",
        "details": {}
    }
}
```

## 🎯 API Status

| Kategória | Hotové | Status |
|-----------|--------|--------|
| Core APIs | 2/3 | ⏳ In Progress |
| System APIs | 3/3 | ✅ Complete |
| Feature APIs | 2/3 | ⏳ In Progress |

## 📚 API Guidelines

### 1. Naming Conventions
- Používať množné číslo pre resources
- Lowercase URLs
- Oddeľovať slová pomlčkami

### 2. HTTP Methods
- GET: Čítanie dát
- POST: Vytváranie
- PUT: Úplná aktualizácia
- PATCH: Čiastočná aktualizácia
- DELETE: Mazanie

### 3. Status Codes
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 422: Validation Error
- 500: Server Error

## 🔄 Ďalšie kroky

### Priority 0 (Kritické)
- [ ] Dokončiť Auth API dokumentáciu
- [ ] Rozšíriť User API príklady
- [ ] Doplniť rate limiting detaily

### Priority 1 (Dôležité)
- [ ] Files API dokumentácia
- [ ] Pagination guidelines
- [ ] API verziovanie

### Priority 2 (Nice to have)
- [ ] API changelog
- [ ] Performance metrics
- [ ] Advanced query parameters
