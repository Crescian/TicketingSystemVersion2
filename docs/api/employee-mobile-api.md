# Employee Mobile API

The JSON API behind the **employee mobile app** (React Native / Expo). It covers
the requestor side of the system: sign in, file and track support requests,
chat with IT, confirm the fix, rate the service, and receive push notifications.

It enforces exactly the same rules as the web employee pages. Both go through
`App\Services\RequestorTicketService` and `App\Services\TicketChatService`, so
anything the web refuses, this API refuses too, with the same message.

## Base URL

```
https://ictsupportrequest.leoniogroup.com/api
```

Every response is JSON. Error responses always have a `message`:

| Status | Meaning |
|---|---|
| `401` | Missing, invalid or revoked token. Send the user to the login screen. |
| `403` | Not allowed: not an Employee, someone else's ticket, or `"code": "account_setup_required"` (see below). |
| `404` | Not found, or the service report isn't available yet. |
| `409` | The same request was sent twice within a few seconds (double tap). Safe to ignore. |
| `422` | Validation error (`errors` per field), **or** an action not allowed in the ticket's current state, e.g. cancelling after Helpdesk acknowledged it. Show `message` to the user. |
| `429` | Too many requests. Ticket submission allows 10/min, other actions 60/min, login 5/min per email. |

---

## 1. Authentication

Every endpoint under `/mobile` needs:

```
Authorization: Bearer <access_token>
Accept: application/json
```

The `/mobile/*` endpoints are **Employee-only**. A staff account gets `403`. Tokens don't expire; they last until logout.

### Email / password

`POST /login`

```json
{ "email": "juan@leoniogroup.com", "password": "secret", "client": "mobile" }
```

Always send `"client": "mobile"`. Logging in again from the app replaces the previous
mobile token but doesn't sign the user out of other systems.

```json
{
  "message": "Login successful.",
  "access_token": "12|Jx9…",
  "token_type": "Bearer",
  "user": {
    "id": "019f…", "name": "Juan Dela Cruz", "email": "juan@leoniogroup.com",
    "position": "Accountant", "active": true, "role_id": "…", "role": "Employee",
    "department_id": "…", "department": "Accounting",
    "needs_account_setup": false, "must_change_password": false
  }
}
```

The `422` response comes with the error `email: ["The provided credentials are incorrect."]`. A `403` means the account is deactivated.

### Sign in with Microsoft

`POST /auth/microsoft`

Run the Microsoft sign-in in the app with `expo-auth-session`:
- authority: `https://login.microsoftonline.com/<tenant-id>/v2.0`
- scopes: `openid profile email User.Read`

Then send the **access token** you get back:

```json
{ "access_token": "<Microsoft Graph access token>", "client": "mobile" }
```

The response is the same as `/login`. Users who signed in with Microsoft before
are matched by their Microsoft account or email. New ones are created as Employees,
the same as on the web.

The token must come from the company tenant and from an approved app
registration. Otherwise the response is `401`. The server's `.env` needs:
- `AZURE_MOBILE_CLIENT_ID` = the mobile app's client id in Azure.
- In Azure, the mobile app registration needs the Expo redirect URI and the `User.Read` permission.

### Logout

| Call | Body | Effect |
|---|---|---|
| `POST /logout` | `{ "expo_push_token": "ExponentPushToken[…]" }` (optional) | Revokes this token. With the push token, it also stops pushes to this phone. |
| `POST /logout-all` | none | Revokes every token of this user and removes all of their devices. |

### Account setup gate

Accounts that IT created with a temporary password have `needs_account_setup: true`.
Until setup is finished, every `/mobile` endpoint except the setup ones returns:

```json
{ "message": "Please finish setting up your account first.", "code": "account_setup_required" }
```

with status `403`. When that happens, open the setup flow:

1. `GET /mobile/org/hierarchy`, then the department picker, then `PUT /mobile/profile/org-info`.
2. `PUT /mobile/profile/password`.

The gate opens on its own once both steps are done.

During setup these endpoints stay available: `GET /mobile/me`, `PUT /mobile/profile/org-info`,
`PUT /mobile/profile/password`, `GET /mobile/org/hierarchy`, `POST/DELETE /mobile/devices`.

---

## 2. Profile

### `GET /mobile/me`

```json
{
  "data": {
    "id": "019f…", "name": "Juan Dela Cruz", "email": "juan@leoniogroup.com",
    "position": "Accountant", "role": "Employee",
    "department": { "id": "…", "name": "Accounting" },
    "company": { "id": "…", "name": "LLRI" },
    "business_unit": { "id": "…", "name": "Leonio Land" },
    "signs_in_with_microsoft": true,
    "needs_account_setup": false,
    "must_change_password": false,
    "can_update_org_info": true,
    "onboarded": false
  }
}
```

### `PUT /mobile/profile/org-info`

```json
{ "department_id": "<uuid>", "position": "Senior Accountant" }
```

Employees can do this **once**. After that, `can_update_org_info` is `false` and
this endpoint returns `422`, so hide the form at that point. Returns the updated `/me`.

### `PUT /mobile/profile/password`

```json
{ "current_password": "…", "password": "NewPassword1", "password_confirmation": "NewPassword1" }
```

The minimum length is 8. A wrong current password, or a new password identical to the old one, gives a `422` field error. Returns the updated `/me`.

### `GET /mobile/org/hierarchy`

The Business Unit, Company and Department tree for the department picker:

```json
[ { "id": "…", "name": "Leonio Land", "companies": [
    { "id": "…", "name": "LLRI", "departments": [ { "id": "…", "name": "Accounting" } ] } ] } ]
```

### `POST /mobile/onboarding/complete`

Marks the one-time welcome tour as seen and returns `204`. Show the tour while `me.onboarded` is `false`.

### `GET /mobile/it-team`

The "Available IT" panel. `online` means the person was active on the web in the last 5 minutes.

```json
[ { "id": "…", "name": "Maria Santos", "role": "Helpdesk", "online": true } ]
```

---

## 3. Tickets

### Ticket object

The list returns this shape. Detail and every action return it too, with extra fields (see below).

```json
{
  "id": "…",
  "ticket_number": "LGICT-26-2020",
  "subject": "Cannot print",
  "status": "Assigned",
  "step": 2,
  "step_label": "Scheduled",
  "is_on_hold": false,
  "priority": "Medium",
  "category": "Hardware",
  "subcategory": "Printer",
  "assigned_to": "Pedro Reyes",
  "expected_start": "Today, after 2:00 PM",
  "unread_messages": 1,
  "can_edit": false,
  "can_cancel": false,
  "can_confirm": false,
  "can_rate": false,
  "has_service_report": false,
  "created_at": "2026-10-15T09:12:00+00:00",
  "updated_at": "2026-10-15T10:40:00+00:00"
}
```

- **`step` / `step_label`** is the 5-step tracker used on the web: 1 Submitted, 2 Scheduled,
  3 In Progress, 4 Awaiting Your Confirmation, 5 Closed. It is `null` for Cancelled tickets.
  Build the tracker from these fields. `status` is the internal workflow status, for display only.
- **`can_*`** flags tell you which buttons to show. They match the server's rules exactly.
- **`expected_start`** is a deliberately rough ETA, such as "Awaiting assignment", "Tomorrow" or "Being worked on now". It can be `null`.
- Timestamps are ISO-8601 UTC. Convert them to Asia/Manila for display.

### `GET /mobile/tickets`

Paginated, 15 per page by default.

| Query | Values |
|---|---|
| `status` | `all` (default), `submitted`, `scheduled`, `in_progress`, `awaiting_requestor`, `closed`, `cancelled` |
| `search` | Matches subject, ticket number or concern |
| `from_date`, `to_date` | `YYYY-MM-DD` (submission date) |
| `sort` | `newest` (default), `oldest`, `priority` |
| `page`, `per_page` | `per_page` max 50 |

The response is Laravel's standard resource pagination: `data[]`, `links`, and `meta` with `current_page`, `last_page` and `total`.

### `GET /mobile/tickets/counts`

Tab badges:

```json
{ "all": 12, "submitted": 1, "scheduled": 0, "in_progress": 2, "awaiting_requestor": 1, "closed": 7, "cancelled": 1 }
```

### `GET /mobile/tickets/{id}`

Returns the ticket object plus these fields:

```json
{
  "concern": "Printer shows offline",
  "request_details": null,
  "asset": null,
  "location": "3F Accounting",
  "hold_reason": null,
  "resolved_at": null,
  "closed_at": null,
  "service_report_url": null,
  "attachments": [ { "id": "…", "name": "printer.jpg", "mime_type": "image/jpeg", "size": 48213, "size_label": "47.1 KB", "url": "…/api/mobile/attachments/…" } ],
  "history": [ { "status": "For Classification", "notes": "Acknowledged by Helpdesk.", "by": "Maria Santos", "at": "…" } ],
  "feedback": null
}
```

`history` is newest first. `attachments` lists only the ticket's own files; chat
files come with their message. Someone else's ticket returns `403`.

### `POST /mobile/tickets` (submit)

Send `multipart/form-data` when there are attachments, otherwise JSON is fine.

| Field | Rules |
|---|---|
| `subject` | required, max 255 |
| `concern` | required |
| `request_details` | optional |
| `asset` | optional, max 255 |
| `location` | optional, max 255 |
| `attachments[]` | optional, up to 5 files, 10 MB each, of type jpg, jpeg, png, gif, pdf, doc, docx, xls, xlsx or txt |

The server fills in the rest:
- department, company and business unit from the user's profile;
- date and time received;
- priority `Medium`, because Helpdesk classifies the ticket later.

Returns `201` with the full ticket. The employee and Helpdesk are emailed, the same as from the web.

### `POST /mobile/tickets/{id}` (edit)

Takes the same fields as submit except `asset`. New `attachments[]` are added to the
existing ones. This is a POST so it can carry multipart files. Only allowed while `can_edit` is true,
which means Helpdesk hasn't acknowledged the ticket yet. Otherwise the response is `422`.

### `PATCH /mobile/tickets/{id}/cancel`

Only while `can_cancel` is true. Returns the ticket with status `Cancelled`.

### `PATCH /mobile/tickets/{id}/confirm`

The employee confirms the fix and the ticket closes. Only while `can_confirm` is true (step 4).
If the employee doesn't confirm, the system closes the ticket automatically after 24 hours.

### `POST /mobile/tickets/{id}/feedback`

```json
{ "rating": 5, "comments": "Fast and friendly" }
```

`rating` is 1–5 and `comments` is up to 500 characters. Only for Closed tickets, once per ticket (`can_rate`).

### `GET /mobile/tickets/{id}/service-report`

Returns the service report PDF (`application/pdf`). Available when `has_service_report` is true,
at step 4 or 5; before that it returns `404`. Download it with the Bearer header, for example with
`expo-file-system` `downloadAsync(url, path, { headers })`, then open it with `expo-sharing`.

### `GET /mobile/attachments/{id}`

The file itself, inline. Add `?download=1` to get it as a download. Needs the Bearer header,
so for images use `<Image source={{ uri, headers: { Authorization } }} />`.

---

## 4. Chat

### `GET /mobile/tickets/{id}/messages`

Returns all messages, oldest first, and **marks IT's messages as read**.

```json
{ "data": [ {
  "id": "…", "message": "Is it plugged in?",
  "sender": { "id": "…", "name": "Maria Santos", "role": "Helpdesk", "initials": "MS" },
  "is_me": false, "is_read": true,
  "attachments": [],
  "created_at": "2026-10-15T10:02:00+00:00"
} ] }
```

### `POST /mobile/tickets/{id}/messages`

Accepts `message` (max 1000), `attachments[]` (up to 5 files of type jpg, png, gif or pdf, 10 MB each), or both.
Returns `201` with the new message. Sending nothing returns `422`.

### `GET /mobile/messages/unread`

```json
{ "total": 3, "by_ticket": { "<ticket id>": 2, "<ticket id>": 1 } }
```

---

## 5. Notifications

### Push notifications (Expo)

After login, and on every app start, register the phone:

`POST /mobile/devices`

```json
{ "expo_push_token": "ExponentPushToken[xxxxxxxx]", "platform": "android" }
```

This is safe to call repeatedly. If another employee had registered the same phone, it moves to the current user.
To stop pushes to this phone, call `DELETE /mobile/devices` with the same `expo_push_token`, or pass the token to `/logout`.

The server sends these pushes:

| `data.type` | When | Title |
|---|---|---|
| `status_change` | The ticket reaches Scheduled, In Progress, Awaiting Your Confirmation or Closed (not when the employee closed it themselves) | e.g. "Please confirm the fix" |
| `on_hold` | IT puts the ticket on hold | "Request on hold" |
| `new_message` | IT sends a chat message | "New message on #LGICT-26-2020" |

Every push carries `data: { type, ticket_id, ticket_number }`. Open the ticket
(or its chat, for `new_message`) when the user taps it.

### `GET /mobile/notifications?since=`

The in-app activity feed: status changes made by IT and messages from IT on
the employee's tickets, newest first, at most 50. With no `since` it covers the last 7 days.
Save `server_time` from each response and send it as `since` next time to get only new items.

```json
{
  "notifications": [
    { "type": "new_message", "ticket_id": "…", "ticket_number": "LGICT-26-2020",
      "title": "Message from Maria Santos", "body": "Is it plugged in?", "is_read": false, "at": "…" },
    { "type": "status_change", "ticket_id": "…", "ticket_number": "LGICT-26-2020",
      "title": "For Classification", "body": "Acknowledged by Helpdesk.", "at": "…" }
  ],
  "server_time": "2026-10-15T10:05:00+00:00"
}
```
