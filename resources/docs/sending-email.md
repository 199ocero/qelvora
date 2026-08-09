---
title: Sending email
description: Send from the dashboard and read the email log.
section: Sending
order: 5
---

With Amazon SES connected and a domain verified, you can send. The **Emails** screen is both your sent list and your log. Every email sent through Xelqun, from the dashboard or the API, shows up here.

## Writing a message

Open **Emails**, then **Compose**. The form has:

- **From**: a list of your verified senders. If it is empty, you have no verified sender yet, so [verify a domain](/docs/verify-a-domain) first.
- **To**: one or more recipients, up to 50. You can paste a list separated by commas.
- **Subject**.
- **Message**: an HTML version, a plain text version, or both. You need at least one.

Send, and you land on the message page with its live history.

Two optional fields sit on the same form:

- **Template**: start from a saved email instead of a blank one. See [email templates](/docs/templates).
- **Schedule for later**: pick a future time to send. See [schedule email](/docs/scheduled-email).

## What Xelqun checks before sending

A send is stopped, with a clear message, if any of these is true:

- **No active connection.** Connect Amazon SES on the Connection screen.
- **The sender is not verified.** The from address must belong to a verified domain or address.
- **A recipient is blocked.** If any recipient is on your [blocked list](/docs/suppressions), the send is stopped so you do not email a known bad address.

## The email log

The Emails list shows every message with its recipient, subject, status, how it was sent (dashboard or API), and when. The newest are first.

Click any row to open the message and see the full text plus its [history](/docs/message-events).

### Search and filter

At the top of the list you can narrow it down. This matters once you have sent a lot of email and need to find one message:

- **Search** by subject, sender, or recipient.
- **Status**: show only one status, like Delivered or Failed.
- **Source**: show only dashboard sends or only API sends.

The filters combine, and they stay applied as you page through the results. Clear them to see everything again.

### Message statuses

A message's status shows the furthest point it has reached. It updates as SES reports back:

| Status     | Meaning                                           |
| ---------- | ------------------------------------------------- |
| Scheduled  | Set to send at a future time, not sent yet        |
| Queued     | Accepted, waiting to be sent                      |
| Sent       | Handed to SES                                     |
| Delivered  | SES delivered it to the recipient's mail server   |
| Bounced    | Rejected. The address is blocked on its own       |
| Complained | Marked as spam. The address is blocked on its own |
| Failed     | Could not be sent. You can try it again           |

Opens and clicks are extra events on top of delivery. They do not replace the delivered status.

A **Scheduled** message can be canceled before it sends. A **Failed** message can be sent again. See [schedule email](/docs/scheduled-email) and [resend failed email](/docs/resend-failed).

## Testing safely with the SES simulator

Amazon gives you test addresses that trigger a set result without hurting your reputation. They are the cleanest way to try the whole flow:

- `success@simulator.amazonses.com`: delivers, then reports open and click events.
- `bounce@simulator.amazonses.com`: bounces, and the address is blocked.
- `complaint@simulator.amazonses.com`: files a spam complaint, and the address is blocked.

Send to each one and watch the message page and your [Suppressions](/docs/suppressions) list react.

→ Next: [Email templates](/docs/templates)
