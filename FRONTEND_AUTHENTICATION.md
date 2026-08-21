# Frontend Authentication Guide

This API uses Laravel Sanctum bearer tokens and allows only one active device per account.

## Login

`POST /api/v1/auth/login`

Request:

```json
{
  "email": "user@example.com",
  "password": "password123"
}
```

Successful response (`200 OK`):

```json
{
  "success": true,
  "message": "...",
  "data": {
    "user": {
      "id": 1,
      "name": "User Name",
      "type": "resident"
    },
    "token": "1|sanctum-token"
  }
}
```

Store the token using the frontend's existing secure token-storage strategy. Send it on every protected request:

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

The frontend must not generate, modify, or send a device identifier. Device identity is generated and managed by the backend.

## Second Login

If the account already has an active device, login is rejected with `409 Conflict`:

```json
{
  "success": false,
  "code": "ACCOUNT_ALREADY_ACTIVE",
  "message": "This account is already logged in on another device. Please contact the developer or administrator to remove the active device."
}
```

Handle the machine-readable `code`, not the message text. Keep the existing device logged in; do not clear the existing user's local session because of this response.

## Revoked Device

When an administrator revokes a device, the next protected request from that device returns `401 Unauthorized`:

```json
{
  "success": false,
  "code": "DEVICE_REVOKED",
  "message": "Your device session has been revoked. Please log in again."
}
```

On `DEVICE_REVOKED`:

1. Clear the locally stored Sanctum token.
2. Clear the authenticated user state.
3. Redirect to the login screen.

The same handling should be used for an ordinary unauthenticated `401` response where the API does not provide a device code.

## Logout

`POST /api/v1/auth/logout`

Send the current bearer token. A successful logout releases the account's active device so another device can log in:

```json
{
  "status": true,
  "message": "..."
}
```

Clear the local token and user state after a successful response. If the request already returns `401`, clear local authentication state as well.

## Admin Device Management

Administrators can revoke a user's active device:

`POST /api/v1/admin/users/{user}/revoke-device`

This endpoint requires an authenticated admin bearer token. It returns `403` for non-admin users. A successful response is:

```json
{
  "success": true,
  "message": "The active device was revoked."
}
```

Revocation does not log in a replacement device automatically. The user must log in again after revocation.

Admin user details also include non-sensitive device status when available:

```json
{
  "active_device": {
    "login_at": "2026-08-18T20:00:00.000000Z",
    "last_activity_at": "2026-08-18T20:05:00.000000Z",
    "status": "active"
  }
}
```

Possible statuses are `active`, `revoked`, or `null` when no device record exists. The device identifier itself is never returned.

## Recommended Client Rules

- Treat `ACCOUNT_ALREADY_ACTIVE` as a login conflict, not as invalid credentials.
- Treat `DEVICE_REVOKED` as a forced logout.
- Do not retry login automatically after `ACCOUNT_ALREADY_ACTIVE`.
- Do not overwrite a valid local session until a new login succeeds.
- Do not use IP address, MAC address, User-Agent, or a frontend-generated identifier as the device identity.
- Keep API error handling based on `code` values so translated messages can change safely.
