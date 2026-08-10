---
title: Send API reference
description: The API endpoint for sending email from your app.
section: Developers
order: 12
---

The send API is one endpoint that sends a message through your team's Amazon SES connection. Use an [API key](/docs/api-keys) to sign in.

## Quick start

Here is a full request you can copy, paste, and run. Swap in your key and a verified sender:

```bash
curl https://your-xelqun-domain.com/api/v1/emails \
  -H "Authorization: Bearer qlv_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{
    "from": "hello@example.com",
    "to": "user@example.com",
    "subject": "Hello from Xelqun",
    "html": "<p>It works!</p>"
  }'
```

A good call returns `202 Accepted` with the message id. The rest of this page lists every field and every response.

## Endpoint

```text
POST /api/v1/emails
```

### Headers

```text
Authorization: Bearer qlv_your_key_here
Content-Type: application/json
```

## Request body

| Field          | Type            | Required | Notes                                                                                                                                                              |
| -------------- | --------------- | -------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| `from`         | string          | yes      | Must be a verified sender (an address or a verified domain). If the key is [locked to one sender](/docs/api-keys#locking-a-key-to-one-sender), this must match it. |
| `to`           | string or array | yes      | One recipient, a list of up to 50, or a comma-separated string.                                                                                                    |
| `subject`      | string          | yes\*\*  | Up to 255 characters.                                                                                                                                              |
| `html`         | string          | no\*     | HTML message.                                                                                                                                                      |
| `text`         | string          | no\*     | Plain text message.                                                                                                                                                |
| `template_id`  | number          | no       | Use a saved [template](/docs/templates) for the subject and body.                                                                                                  |
| `variables`    | object          | no       | Values for the template's `{{ fields }}`, like `{ "name": "Dana" }`.                                                                                               |
| `scheduled_at` | string          | no       | A future time to send instead of now. Any format a date parser reads, such as `2026-08-20T09:00:00Z`.                                                              |

\* Send `html`, `text`, or both. You need at least one, unless you use a `template_id`, which supplies the body.

\*\* `subject` is required unless you use a `template_id`, which supplies the subject.

```json
{
    "from": "hello@example.com",
    "to": ["user@example.com", "another@example.com"],
    "subject": "Welcome aboard",
    "html": "<h1>Welcome!</h1><p>Glad to have you.</p>",
    "text": "Welcome! Glad to have you."
}
```

### Send with a template

Give a `template_id` and the values for its fields. Xelqun builds the subject and body for you, so you do not send the full HTML:

```json
{
    "from": "hello@example.com",
    "to": "user@example.com",
    "template_id": 12,
    "variables": { "name": "Dana", "order": "1001" }
}
```

### Schedule for later

Add a future `scheduled_at` time and the message is held until then:

```json
{
    "from": "hello@example.com",
    "to": "user@example.com",
    "subject": "Reminder",
    "html": "<p>Your trial ends tomorrow.</p>",
    "scheduled_at": "2026-08-20T09:00:00Z"
}
```

A scheduled send is accepted the same way, with a `scheduled` status. It sends when the time arrives. You can cancel it from the message page before then.

## A successful response

The message is accepted and queued to send. You get back its id and status:

```json
{
    "id": 4821,
    "status": "queued"
}
```

```text
202 Accepted
```

Use the `id` to find the message in the dashboard, where its [history](/docs/message-events) fills in as SES reports back.

## Error responses

| Status              | Meaning                                          | Body                                          |
| ------------------- | ------------------------------------------------ | --------------------------------------------- |
| `401 Unauthorized`  | Missing, wrong, or revoked API key               | None                                          |
| `403 Forbidden`     | The key is locked to a different sender          | `{ "message": "..." }`                        |
| `409 Conflict`      | The team has no active Amazon SES connection     | `{ "message": "..." }`                        |
| `422 Unprocessable` | Validation failed, or the sender is not verified | `{ "message": "..." }`                        |
| `422 Unprocessable` | A recipient is blocked                           | `{ "message": "...", "suppressed": ["..."] }` |

The `message` field always says what went wrong, so read it first. A few are easy to mix up:

- **403** means the key works, but it is [locked to one sender](/docs/api-keys#locking-a-key-to-one-sender) and your `from` did not match it. Send from the locked sender, or use a key that is not locked.
- **422 not verified** means the `from` is not a verified domain or address for the team. Add and verify it first, see [verify a domain](/docs/verify-a-domain).
- **422 blocked** means a recipient is on the block list. The `suppressed` list shows which ones. Remove them from your call, or from the [blocked list](/docs/suppressions), and try again.

## Notes

- API sends are queued and sent in the background, which is why you get a `202` instead of waiting for SES.
- The same rules as the dashboard apply: active Amazon SES connection, verified sender, no blocked recipients.
- Every API send shows up in the Emails log, marked as sent through the API, next to your dashboard sends.
