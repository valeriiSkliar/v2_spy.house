# API Authentication System

This document describes the unified API authentication system using access and refresh tokens.

## Overview

The authentication system uses Laravel Sanctum with an additional refresh token mechanism to:
1. Provide short-lived access tokens for security
2. Issue refresh tokens that allow obtaining new access tokens without re-authentication
3. Store refresh tokens securely in HttpOnly cookies (with fallback to including in response body)

## Components

- `AuthController` - Handles standard authentication flows (login, logout, refresh tokens)
- `TokenController` - Provides advanced token management features
- `TokenService` - Core service implementing token generation and refresh logic
- `RefreshToken` model - Database model for storing refresh tokens

## Authentication Flow

### Login

1. Client sends credentials to `/api/login`
2. Server validates credentials and creates an access + refresh token pair
3. Access token is returned in JSON response
4. Refresh token is set as HttpOnly cookie (and optionally in JSON response)

```bash
# Example login request
curl -X POST https://example.com/api/login \
  -H "Content-Type: application/json" \
  -d '{"email": "user@example.com", "password": "your_password", "device_name": "my_device"}'

# Response:
# {
#   "access_token": "1|abcdef1234567890...",
#   "expires_at": 1683123456,
#   "token_type": "Bearer",
#   "user": {...}
# }
# + Set-Cookie: refresh_token=abc123...; HttpOnly; Secure; SameSite=Lax
```

### Using Access Tokens

Access tokens are used as Bearer tokens in the Authorization header:

```bash
curl -X GET https://example.com/api/user \
  -H "Authorization: Bearer 1|abcdef1234567890..."
```

### Refreshing Tokens

When the access token expires, use the refresh token to get a new token pair:

```bash
# Option 1: Using the refresh token cookie (automatic)
curl -X POST https://example.com/api/auth/refresh \
  -b "refresh_token=abc123..." \
  -H "Content-Type: application/json"

# Option 2: Sending refresh token in request body
curl -X POST https://example.com/api/auth/refresh \
  -H "Content-Type: application/json" \
  -d '{"refresh_token": "abc123..."}'

# Response (same as login response):
# {
#   "access_token": "2|newtoken1234567890...",
#   "expires_at": 1683127056,
#   "token_type": "Bearer"
# }
# + Set-Cookie: refresh_token=new123...; HttpOnly; Secure; SameSite=Lax
```

### Logout

```bash
curl -X POST https://example.com/api/logout \
  -H "Authorization: Bearer 1|abcdef1234567890..."

# Response:
# {
#   "message": "Successfully logged out"
# }
# + Set-Cookie: refresh_token=; Expires=Thu, 01 Jan 1970 00:00:00 GMT;
```

## Manual Testing

For testing the unified authentication system:

1. Use any API client (Postman, Insomnia, curl)
2. Make a login request to `/api/login` with valid credentials
3. Check for access token in response and refresh token in cookies
4. Use the access token to make authenticated requests
5. Try refreshing the token by making a request to `/api/auth/refresh`
6. Test logout to ensure both tokens are revoked

## Security Considerations

- Access tokens expire quickly (60 minutes by default)
- Refresh tokens are stored securely in HttpOnly cookies
- All tokens are revoked on logout
- Refresh tokens can be used only once (used tokens are deleted)