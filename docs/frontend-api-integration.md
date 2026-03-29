# Frontend API Integration Guide

This document provides the exact API contracts for the Portal frontend integration. The backend is a Laravel API using hybrid SSO authentication (central session + Sanctum token exchange).

## 1. Global Conventions

- **Base URL**: `/api`
- **Auth Strategy**: Hybrid SSO (central session + Sanctum bearer token exchange).
- **Headers**:
  - `Accept: application/json`
  - `Content-Type: application/json`
  - `X-Requested-With: XMLHttpRequest` (for CSRF/Sanctum)
- **Authentication**:
  - `401 Unauthenticated`: Redirect to central login.
  - `403 Forbidden`: User lacks permission or account is inactive.

### Pagination Format
List endpoints use standard Laravel pagination:
```json
{
  "data": [...],
  "links": { "first": "...", "last": "...", "prev": null, "next": "..." },
  "meta": { "current_page": 1, "from": 1, "last_page": 1, "path": "...", "per_page": 10, "to": 10, "total": 10 }
}
```

### Error Responses
- **Validation (422)**:
```json
{
  "message": "The given data was invalid.",
  "errors": {
    "email": ["The email has already been taken."],
    "role_id": ["The selected role id is invalid."]
  }
}
```
- **Generic Errors (403/404)**:
```json
{ "message": "Forbidden." }
```

---

## 2. Authentication & Profile

### GET `/api/me`
**Purpose**: Bootstrap current user profile, role, and permissions.
**Response**:
```json
{
  "message": "Authenticated user retrieved successfully",
  "user": {
    "id": 1,
    "name": "Admin User",
    "email": "admin@example.com",
    "status": "active",
    "role": { "id": 1, "name": "Admin", "slug": "admin" },
    "created_at": "...", "updated_at": "..."
  },
  "role": { "id": 1, "name": "Admin", "slug": "admin" },
  "permissions": ["users.view", "users.create", "roles.view", "portal.access"]
}
```

---


### SSO Endpoints
- **GET `/sso/authorize`** (browser redirect endpoint)
  - Query: `client_id`, `redirect_uri`, optional `state`
  - Behavior:
    - If central auth session exists: redirects back to `redirect_uri` with one-time `code`
    - If no session: returns `401` with `{ message: "Unauthenticated." }`
- **POST `/api/sso/token`**
  - Payload: `grant_type=authorization_code`, `client_id`, `client_secret`, `code`, `redirect_uri`
  - Returns Sanctum bearer token for API calls

## 3. User Management

### GET `/api/users`
**Query Params**: `search`, `status` (active/inactive), `per_page` (default 10, max 100).
**Response**: Paginated list of users including their role slug.

### POST `/api/users`
**Payload**:
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "status": "active",
  "role_id": 2
}
```

### PUT `/api/users/{user}`
**Payload**: All fields optional (use `sometimes|required`).
- `password` and `password_confirmation` only needed if changing password.

### PATCH `/api/users/{user}/status`
**Payload**: `{ "status": "active" }` or `{ "status": "inactive" }`.

---

## 4. Role Management

### GET `/api/roles`
**Query Params**:
- `search`: Filter by name/slug.
- `dropdown=1`: Lightweight mode (returns all roles without pagination).
- `per_page`: Standard pagination (if dropdown is NOT provided).
**Response**:
```json
{
  "data": [
    {
      "id": 1, "name": "Admin", "slug": "admin",
      "permissions_count": 15,
      "created_at": "...", "updated_at": "..."
    }
  ]
}
```

### GET `/api/roles/{role}`
**Response**: Includes full `permissions` array.

### POST `/api/roles` / PUT `/api/roles/{role}`
**Payload**:
```json
{
  "name": "Product Manager",
  "slug": "product_manager",
  "permission_ids": [1, 2, 5]
}
```

### DELETE `/api/roles/{role}`
**Notes**:
- Fails (422) if users are assigned to the role.
- Fails (403) for critical roles like `admin`.

---

## 5. Permission Reference

### GET `/api/permissions`
**Purpose**: Populate role create/edit checkbox UI.
**Response**: Grouped by resource prefix (e.g., `users.*`).
```json
{
  "data": [
    {
      "resource": "users",
      "label": "Users Management",
      "permissions": [
        { "id": 1, "name": "View Users", "slug": "users.view", "resource": "users", "action": "view" }
      ]
    }
  ]
}
```

---

## 6. Frontend Wiring Notes

1.  **Permission Guards**: Use the `permissions` array from `/api/me` to toggle UI elements.
    - Example: `hasPermission('users.create')`.
2.  **Role Selection**: Fetch `/api/roles?dropdown=1` to populate select inputs in User forms.
3.  **Permission Selection**: Use the grouped data from `/api/permissions` to render Accordion/Sectioned checkbox groups in Role forms.
4.  **Single Role Enforcement**: Backend only supports `role_id` (single value). The frontend should use a single-select dropdown, not checkboxes, for user roles.
5.  **Status Toggles**: Use the `PATCH /api/users/{id}/status` endpoint for quick table toggles.
6.  **Inactive Blocking**: If `/api/me` or any endpoint returns `403 Forbidden` with a message about "inactive", redirect the user to the login screen as their session is invalidated.

---

## 7. Validation Reference

| Form | Field | Rules |
| :--- | :--- | :--- |
| **User Create** | `name`, `email`, `password`, `password_confirmation`, `status`, `role_id` | All required. `email` unique. `password` min 8. `status` in `active,inactive`. |
| **User Update** | `name`, `email`, `password`, `status`, `role_id` | All optional (`sometimes`). `email` unique (ignores self). `password` confirmed. |
| **Role Create** | `name`, `slug`, `permission_ids` | All required. `name` and `slug` unique. `permission_ids` must be array of valid IDs. |
| **Role Update** | `name`, `slug`, `permission_ids` | Same as Create, unique rules ignore self. |
