# API Endpoint List

Base URL: `/api`

Protected endpoints require:

```text
Authorization: Bearer <sanctum-token>
```

## Public endpoints

| Method | Endpoint | Description |
|---|---|---|
| POST | `/auth/login` | Authenticate a user and issue a Sanctum token. |
| POST | `/auth/register` | Register a new user and issue a Sanctum token. |
| GET | `/provinces` | Return all provinces. |
| GET | `/cities/{province_code}` | Return cities belonging to a province. |
| GET | `/districts/{city_code}` | Return districts belonging to a city. |
| GET | `/villages/{district_code}` | Return villages belonging to a district. |
| GET | `/complaint-categories` | Return complaint categories used by the complaint form and dashboard. |
| GET | `/industrial-sectors` | Return industrial sectors used by the complaint form. |
| GET | `/categories` | Return categories through the category controller. |
| GET | `/company-sizes` | Return configured company-size definitions. |
| GET | `/companies` | Return a paginated list of companies; supports the `search` query parameter. |
| POST | `/complaints` | Create a complaint without requiring login. Supports complaint data and image attachments. |

## Authentication endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/auth/me` | Return the currently authenticated user. |
| POST | `/auth/logout` | Revoke the current user's Sanctum token. |

All endpoints in this section require Sanctum authentication.

## Dashboard endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/dashboard/complaint-summary` | Return complaint counts for every category. Use `period=today` to limit counts to today's complaints. |
| GET | `/dashboard/statistics` | Return dashboard cards and chart data: total complaints, completed complaints, satisfaction index, complaints by company size, and complaints by category. Use `period=today` for today's statistics. |

All endpoints in this section require Sanctum authentication.

## Complaint endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/complaints` | Return a paginated complaint list. Supports `search`, `status`, `category_id`, `month`, `year`, `page`, and `per_page`. |
| GET | `/complaints/{complaint}` | Return the full details of one complaint. |
| GET | `/complaints/{complaint}/pdf` | Download one complaint as a PDF, including complaint details and attachment information. |
| PUT/PATCH | `/complaints/{complaint}` | Update complaint description, status, follow-up date, assigned admin, or internal notes. A status change creates an update-history record. |
| DELETE | `/complaints/{complaint}` | Delete a complaint and its related records according to database cascade rules. |

All endpoints in this section require Sanctum authentication except `POST /complaints`.

## Attachment endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/complaints/{complaint}/attachments` | Return all attachments belonging to a complaint. |
| POST | `/complaints/{complaint}/attachments` | Upload one or more JPG, JPEG, PNG, or WEBP image attachments. |
| DELETE | `/complaints/{complaint}/attachments/{attachment}` | Delete an attachment belonging to the specified complaint. |

All endpoints in this section require Sanctum authentication.

## Company endpoints

| Method | Endpoint | Description |
|---|---|---|
| GET | `/companies` | Return a paginated company list with sector data. |
| POST | `/companies` | Create a company record. Requires an authenticated user. |

## Admin endpoints

These endpoints require Sanctum authentication and an `admin` or `super_admin` role.

| Method | Endpoint | Description |
|---|---|---|
| POST | `/admin/categories` | Create a complaint category. |
| POST | `/admin/company-sizes` | Create a company-size definition. |
| GET | `/admin/settings` | Return application settings. |
| PUT | `/admin/settings/{setting}` | Update an application setting. |

## Endpoint behavior notes

- Complaint creation is public; complaint listing and management require Sanctum authentication.
- New complaints default to status `DIPROSES` and priority `MEDIUM`.
- Valid complaint statuses are `DIPROSES` and `SELESAI`.
- Complaint list responses are paginated with a default page size of 15 and a maximum page size of 100.
- The dashboard summary and statistics endpoints return all configured categories or company-size groups, including groups with zero complaints.
- The PDF endpoint is protected and returns a file download rather than JSON.
- Validation failures return HTTP `422`.
- Unauthenticated protected requests return HTTP `401`.
