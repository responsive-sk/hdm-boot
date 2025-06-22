# Authentication API

## 🔐 Overview

HDM Boot používa JWT (JSON Web Tokens) pre autentifikáciu API.

## 📑 Endpoints

### 1. Login
```http
POST /api/auth/login
Content-Type: application/json

{
    "email": "user@example.com",
    "password": "your-password"
}
```

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiI...",
        "expires_in": 3600,
        "token_type": "Bearer",
        "user": {
            "id": "uuid",
            "email": "user@example.com",
            "name": "John Doe",
            "role": "user"
        }
    }
}
```

#### Error Response (401)
```json
{
    "success": false,
    "error": {
        "code": "INVALID_CREDENTIALS",
        "message": "Invalid email or password"
    }
}
```

### 2. Logout
```http
POST /api/auth/logout
Authorization: Bearer {token}
```

#### Success Response (200)
```json
{
    "success": true,
    "message": "Successfully logged out"
}
```

### 3. Refresh Token
```http
POST /api/auth/refresh
Authorization: Bearer {refresh_token}
```

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "token": "eyJhbGciOiJIUzI1NiI...",
        "expires_in": 3600,
        "token_type": "Bearer"
    }
}
```

### 4. Get Current User
```http
GET /api/auth/me
Authorization: Bearer {token}
```

#### Success Response (200)
```json
{
    "success": true,
    "data": {
        "user": {
            "id": "uuid",
            "email": "user@example.com",
            "name": "John Doe",
            "role": "user",
            "created_at": "2025-06-12T10:00:00Z"
        }
    }
}
```

## 🔒 Security

### JWT Token Structure
```json
{
    "header": {
        "alg": "HS256",
        "typ": "JWT"
    },
    "payload": {
        "sub": "user-uuid",
        "email": "user@example.com",
        "role": "user",
        "iat": 1686557452,
        "exp": 1686561052
    }
}
```

### Headers
```http
Authorization: Bearer eyJhbGciOiJIUzI1NiI...

Content-Type: application/json
Accept: application/json
```

### Rate Limiting
```http
X-RateLimit-Limit: 5
X-RateLimit-Remaining: 4
X-RateLimit-Reset: 1623581402
```

## ❌ Error Codes

| Code | Description |
|------|-------------|
| INVALID_CREDENTIALS | Nesprávne prihlasovacie údaje |
| TOKEN_EXPIRED | JWT token expiroval |
| TOKEN_INVALID | Neplatný JWT token |
| TOKEN_BLACKLISTED | Token bol zneplatnený (po logout) |
| USER_NOT_FOUND | Užívateľ neexistuje |
| TOO_MANY_ATTEMPTS | Prekročený limit pokusov |

## 📝 Examples

### cURL
```bash
# Login
curl -X POST http://localhost:8001/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Get user with token
curl -X GET http://localhost:8001/api/auth/me \
  -H "Authorization: Bearer eyJhbGciOiJIUzI1NiI..."
```

### PHP
```php
// Login
$response = $client->post('/api/auth/login', [
    'json' => [
        'email' => 'user@example.com',
        'password' => 'password123'
    ]
]);

$token = json_decode($response->getBody())->data->token;

// Use token
$response = $client->get('/api/auth/me', [
    'headers' => [
        'Authorization' => 'Bearer ' . $token
    ]
]);
```

### JavaScript
```javascript
// Login
const response = await fetch('/api/auth/login', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json'
    },
    body: JSON.stringify({
        email: 'user@example.com',
        password: 'password123'
    })
});

const { token } = await response.json();

// Use token
const userResponse = await fetch('/api/auth/me', {
    headers: {
        'Authorization': `Bearer ${token}`
    }
});
```

## 🔄 Flow Diagrams

### Login Flow
```mermaid
sequenceDiagram
    Client->>+API: POST /auth/login
    API->>+Auth: Verify credentials
    Auth->>+DB: Check user
    DB-->>-Auth: User exists
    Auth->>+JWT: Generate token
    JWT-->>-Auth: New token
    Auth-->>-API: Auth result
    API-->>-Client: Token + User data
```

### Token Verification
```mermaid
sequenceDiagram
    Client->>+API: Request with Bearer token
    API->>+JWT: Verify token
    JWT->>JWT: Check signature
    JWT->>JWT: Verify expiration
    JWT-->>-API: Token valid
    API->>+DB: Get user
    DB-->>-API: User data
    API-->>-Client: Protected data
```

## 📊 Best Practices

### 1. Token Management
- Uchovávať token bezpečne (HttpOnly cookies)
- Implementovať token refresh
- Blacklist pre odhlásené tokeny

### 2. Security
- Rate limiting pre login pokusy
- Bezpečné heslo policy
- HTTPS pre všetku komunikáciu
- Validácia všetkých inputov

### 3. Error Handling
- Konzistentné error responses
- Bezpečné error messages
- Proper HTTP status codes

## 🎯 Future Improvements

### Phase 1 [P0]
- [x] Basic JWT auth
- [x] Login/Logout
- [x] User info
- [x] Token refresh

### Phase 2 [P1]
- [ ] 2FA support
- [ ] OAuth integration
- [ ] Remember me
- [ ] Password reset

### Phase 3 [P2]
- [ ] Social login
- [ ] Biometric auth
- [ ] Hardware token support
