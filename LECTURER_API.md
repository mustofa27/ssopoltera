# Lecturer API

This document describes the lecturer data endpoint exposed by this SSO server for registered external applications.

It covers:
- Authentication using application credentials
- Request parameters
- Response format
- Error responses

## 1. Overview

The lecturer endpoint returns a paginated list of users who are lecturers (`user_type = employee`, `employee_type = lecturer`), including their primary department and program study.

Access is restricted to applications already registered in the SSO admin panel. No end-user login or OAuth token is required — the request is authenticated directly with the application's own credentials.

## 2. Base Endpoint

```text
GET /api/lecturers
```

Base domain for this environment: `https://sso.poltera.ac.id`.

## 3. Authentication

Every request must include the requesting application's `client_id` and `client_secret` as headers.

| Header | Description |
| --- | --- |
| `X-Client-Id` | The application's `client_id`, generated when the application was registered in SSO. |
| `X-Client-Secret` | The application's `client_secret`, generated at the same time. |

Requirements enforced by SSO:
- Both headers are required.
- `client_id` must belong to an existing application.
- The application must be active (`is_active = true`).
- `client_secret` must match exactly.

Keep `client_secret` on the server side only. Never expose it in browser code.

## 4. Request Parameters

Both parameters are optional query string parameters.

| Parameter | Type | Description |
| --- | --- | --- |
| `q` | string | Filters lecturers by name, email, or NIP (partial match). |
| `per_page` | integer | Number of results per page. Default `25`, minimum `1`, maximum `100`. |

## 5. Example Request

```bash
curl -G "https://sso.poltera.ac.id/api/lecturers" \
  -H "X-Client-Id: YOUR_CLIENT_ID" \
  -H "X-Client-Secret: YOUR_CLIENT_SECRET" \
  -H "Accept: application/json" \
  --data-urlencode "q=budi" \
  --data-urlencode "per_page=2"
```

## 6. Successful Response

`200 OK`

```json
{
  "data": [
    {
      "id": 42,
      "nip": "198501012010121001",
      "name": "Budi Santoso",
      "email": "budi.santoso@poltera.ac.id",
      "job_title": "Lecturer",
      "is_active": true,
      "department": "Information Technology",
      "program_study": "Informatics Engineering",
      "support_unit": null,
      "affiliations": [
        {
          "affiliation_type": "home",
          "is_primary": true,
          "department": "Information Technology",
          "program_study": "Informatics Engineering"
        },
        {
          "affiliation_type": "additional",
          "is_primary": false,
          "support_unit": "Research and Community Service Unit"
        }
      ]
    },
    {
      "id": 57,
      "nip": "199003152015041002",
      "name": "Budi Wijaya",
      "email": "budi.wijaya@poltera.ac.id",
      "job_title": "Senior Lecturer",
      "is_active": true,
      "department": "Information Technology",
      "program_study": "Information Systems",
      "support_unit": null,
      "affiliations": [
        {
          "affiliation_type": "home",
          "is_primary": true,
          "department": "Information Technology",
          "program_study": "Information Systems"
        }
      ]
    }
  ],
  "meta": {
    "current_page": 1,
    "per_page": 2,
    "total": 5,
    "last_page": 3
  }
}
```

Field notes:
- `department`, `program_study`, and `support_unit` reflect the lecturer's primary affiliation (`is_primary = true`) and are `null` when no primary affiliation is set.
- `affiliations` lists every organizational affiliation the lecturer has, not just the primary one. Each entry always has `affiliation_type` and `is_primary`, plus only whichever of `department`, `program_study`, or `support_unit` are actually set on that affiliation record — keys with no data are omitted rather than sent as `null`.
- Results are ordered by `name`.

## 7. Error Responses

| Status | Body | Cause |
| --- | --- | --- |
| `401` | `{"error":"invalid_client","error_description":"Missing application credentials."}` | `X-Client-Id` or `X-Client-Secret` header not sent. |
| `401` | `{"error":"invalid_client","error_description":"Invalid application credentials."}` | Unknown `client_id`, inactive application, or `client_secret` mismatch. |

## 8. Integration Checklist

- Register the application in SSO (or reuse an existing one) and note its `client_id` and `client_secret`.
- Send both credentials as headers on every request.
- Handle pagination using the `meta` block if you expect more than one page of results.
- Treat the top-level `department`, `program_study`, and `support_unit` as nullable.
- Within each `affiliations` entry, check for key presence rather than assuming `department`, `program_study`, and `support_unit` are always there — only the keys with data are included.
- Iterate `affiliations` if you need every organizational unit a lecturer belongs to, not just their primary one.
