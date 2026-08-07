# Ticket Analysis API

A **read-only** API for pulling ticket data for analysis — SLA compliance,
workload/category distribution, resolution-time patterns — without direct
database access. Same pattern as the [Org & User Directory API](./org-directory-api.md),
but scoped to a separate token ability so access to one doesn't imply access
to the other.

This intentionally excludes free-text fields (`concern`, `resolution_notes`,
`findings`, `recommendation`) — those carry the most sensitive content and
aren't needed for aggregate analysis. If a real use case needs them later,
they should be a separate, more tightly scoped endpoint.

## Base URL

```
https://lg-ticketing.leoniogroup.com/api
```

## Authentication

```
Authorization: Bearer <your-token>
Accept: application/json
```

Tokens are scoped to the `tickets:read` ability — separate from `org:read`,
so a system can be granted ticket-data access without also getting the user
directory (or vice versa). A token with only `org:read` gets a `403` here,
and a `tickets:read` token gets a `403` on the `/api/org/*` endpoints.

### Getting a token

```bash
php artisan api:issue-tickets-token <system-name>
```

Example:

```bash
php artisan api:issue-tickets-token analytics-dashboard
```

```
Read-only ticket-analysis token for "analytics-dashboard":
6|9B57iaHEoOzFBke8uov5T8dMo89xFn5cJhc5XUTGcfd4e32e
This is shown only once — store it securely now. Send it to that system as a Bearer token.
```

- Shown once — store it in the consuming system's secrets/config, not source control.
- Give every consuming system its own token (own `<system-name>`).

### Revoking a token

```sql
delete from personal_access_tokens where name = '<system-name>:tickets';
```

(Note the `:tickets` suffix — this keeps it distinct from an `org:read` token
issued to the same system name, so they can be revoked independently.)

### Errors

| Status | Meaning |
|---|---|
| `401 Unauthorized` | Missing or invalid token |
| `403 Forbidden` | Token is valid but doesn't have the `tickets:read` ability |

## Endpoint

### `GET /tickets`

Returns tickets with their key foreign keys already resolved to readable
values. **Paginated.**

**Query parameters**

| Param | Type | Description |
|---|---|---|
| `status` | string | e.g. `In Progress`, `Closed`, `Escalated` |
| `ticket_type` | string | Priority: `Critical`, `High`, `Medium`, `Low` |
| `sla_category_id` | uuid | Filter by category |
| `assigned_to` | uuid | Filter by assigned technician |
| `department_id` | uuid | Filter by requester's department |
| `company_id` | uuid | Filter by requester's company |
| `business_unit_id` | uuid | Filter by requester's business unit |
| `date_from` / `date_to` | date (`Y-m-d`) | Filter by `created_at` range |
| `per_page` | integer | Results per page (default `50`, max `200`) |
| `page` | integer | Page number |

**Response**

```json
{
  "current_page": 1,
  "data": [
    {
      "id": "019fbb60-aafb-72f3-817a-d00726eb05bb",
      "ticket_number": "LGICT-26-0001",
      "status": "Closed",
      "priority": "Critical",
      "category": "Hardware Support",
      "subcategory": "Keyboard",
      "assigned_to": "f9c397c0-b705-42c5-b8ed-ffbdee5bbb5b",
      "assigned_technician": "Jane Dela Cruz",
      "requester_department": "Management Systems and Technology",
      "requester_company": "Circle Corporate Inc.",
      "requester_business_unit": "CSS",
      "created_at": "2026-08-01T03:31:42.000000Z",
      "started_at": "2026-08-01T12:00:49.000000Z",
      "resolved_at": "2026-08-01T12:02:09.000000Z",
      "sla_due_at": "2026-08-03T04:00:00.000000Z",
      "response_time_minutes": 30,
      "resolution_time_minutes": 240,
      "actual_resolution_minutes": 1.33,
      "sla_met": true,
      "is_overtime": false,
      "escalation_level": 0,
      "cannot_resolve": false
    }
  ],
  "last_page": 1,
  "per_page": 50,
  "total": 4
}
```

**Field notes**

- `actual_resolution_minutes` — wall-clock minutes from `started_at` to
  `resolved_at`; `null` if the ticket isn't resolved yet. Compare against
  `resolution_time_minutes` (the SLA target) to see over/under-run.
- `sla_met` — `true`/`false` once resolved (`resolved_at <= sla_due_at`);
  `null` if not yet resolved or no SLA was set (e.g. ticket never classified).
- `requester_*` fields describe the person who **submitted** the ticket, not
  the assigned technician — useful for workload/category breakdowns by
  business unit.

## Example: cURL

```bash
curl -H "Authorization: Bearer 6|9B57iaHEoOzFBke8uov5T8dMo89xFn5cJhc5XUTGcfd4e32e" \
     -H "Accept: application/json" \
     "https://lg-ticketing.leoniogroup.com/api/tickets?status=Closed&date_from=2026-07-01"
```
