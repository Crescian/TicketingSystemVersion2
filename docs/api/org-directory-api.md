# Org & User Directory API

A **read-only** API that lets other in-house systems look up the organization
hierarchy (Business Unit → Company → Department) and the user directory,
without needing direct database access to this app.

This is separate from the ticketing system's own login/session flow — it's
meant for **system-to-system** integration, authenticated with a dedicated API
token rather than a user's email/password.

## Base URL

```
https://lg-ticketing.leoniogroup.com/api/org
```

## Authentication

Every request must include a Bearer token in the `Authorization` header:

```
Authorization: Bearer <your-token>
Accept: application/json
```

Tokens are scoped to a single ability, `org:read`, so they can only ever read
this directory — nothing else in the ticketing system is reachable with them.

### Getting a token

Tokens are issued per consuming system by an administrator, from the server:

```bash
php artisan api:issue-org-token <system-name>
```

Example:

```bash
php artisan api:issue-org-token finance-system
```

```
Read-only org/user-directory token for "finance-system":
5|vHxZhhjQAczSLcukWWEYaAHyPZKexlEh86jV4DCC3c1a2555
This is shown only once — store it securely now. Send it to that system as a Bearer token.
```

- The token is only ever shown once — store it in the consuming system's
  secrets/config, not in source control.
- Every consuming system should get its **own** token (own `<system-name>`),
  so access can be revoked for one integration without affecting others.

### Revoking a token

There's no CLI command for this yet — an administrator deletes the row
directly, filtering by the name given at issue time:

```sql
delete from personal_access_tokens where name = 'finance-system';
```

### Errors

| Status | Meaning |
|---|---|
| `401 Unauthorized` | Missing or invalid token |
| `403 Forbidden` | Token is valid but doesn't have the `org:read` ability |

## Endpoints

### `GET /business-units`

Returns every business unit.

**Response**

```json
[
  { "id": "8f14e...", "name": "Corporate Support Services" },
  { "id": "3a2f1...", "name": "Mining" }
]
```

### `GET /companies`

Returns every company, with its parent business unit.

**Query parameters**

| Param | Type | Description |
|---|---|---|
| `business_unit_id` | uuid | Only return companies under this business unit |

**Response**

```json
[
  {
    "id": "d437a...",
    "name": "Circle Corporate Inc.",
    "business_unit_id": "8f14e...",
    "business_unit": "Corporate Support Services"
  }
]
```

### `GET /departments`

Returns every department, with its parent company and business unit.

**Query parameters**

| Param | Type | Description |
|---|---|---|
| `company_id` | uuid | Only return departments under this company |

**Response**

```json
[
  {
    "id": "7a2f0...",
    "name": "Management Systems and Technology",
    "company_id": "d437a...",
    "company": "Circle Corporate Inc.",
    "business_unit_id": "8f14e...",
    "business_unit": "Corporate Support Services"
  }
]
```

### `GET /users`

Returns the user directory with each user's org placement flattened in.
**Paginated.**

**Query parameters**

| Param | Type | Description |
|---|---|---|
| `department_id` | uuid | Only return users in this department |
| `company_id` | uuid | Only return users in this company |
| `business_unit_id` | uuid | Only return users in this business unit |
| `active` | boolean | Filter by active/inactive account (`true`/`false`) |
| `per_page` | integer | Results per page (default `50`, max `200`) |
| `page` | integer | Page number |

**Response**

```json
{
  "current_page": 1,
  "data": [
    {
      "id": "207cc...",
      "name": "Jane Dela Cruz",
      "email": "jdelacruz@example.com",
      "position": "Accounting Staff",
      "active": true,
      "department_id": "7a2f0...",
      "department": "Management Systems and Technology",
      "company": "Circle Corporate Inc.",
      "business_unit": "Corporate Support Services"
    }
  ],
  "current_page": 1,
  "last_page": 5,
  "per_page": 50,
  "total": 212,
  "next_page_url": "https://lg-ticketing.leoniogroup.com/api/org/users?page=2",
  "prev_page_url": null
}
```

`department`/`company`/`business_unit` are `null` when a user has no
`department_id` set.

## Example: cURL

```bash
curl -H "Authorization: Bearer 5|vHxZhhjQAczSLcukWWEYaAHyPZKexlEh86jV4DCC3c1a2555" \
     -H "Accept: application/json" \
     "https://lg-ticketing.leoniogroup.com/api/org/users?per_page=5"
```

## Example: Postman

1. Create a `GET` request to `https://lg-ticketing.leoniogroup.com/api/org/users`.
2. Open the **Authorization** tab → type **Bearer Token** → paste the token
   (without the `Bearer ` prefix — Postman adds that for you).
3. Add header `Accept: application/json`.
4. Send.
