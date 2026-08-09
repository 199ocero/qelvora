---
title: Resend failed email
description: Find messages that did not send and try them again.
section: Sending
order: 8
---

Sometimes a send does not go through. Amazon SES may have had a short outage, your keys may have lapsed, or you may have hit a rate limit. When that happens the message is marked **Failed** and kept, so you can look at it and try again.

## Find failed messages

Open **Emails** and set the status filter to **Failed**. This shows only the messages that did not send. Open one to see the error message at the bottom of the page, which explains why it failed.

## Resend

On a failed message, choose **Resend**. Xelqun makes a fresh copy of the email and queues it to send again. The original failed message is kept as a record, so you always have the full history.

A resend runs through the same checks as any other send:

- The sender must still be verified.
- No recipient may be on your [blocked list](/docs/suppressions).

So a resend is safe. It will not email an address that has since bounced or complained.

## What can be resent

Only messages that ended in **Failed** or **Rejected** can be resent. A message that was already sent or delivered cannot, since it already went out. To send the same content again on purpose, use [Compose](/docs/sending-email) or a [template](/docs/templates).

→ Next: [Message events](/docs/message-events)
