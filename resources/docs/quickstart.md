---
title: Quickstart
description: Go from an empty account to a delivered email in five steps.
section: Getting started
order: 2
---

This is the fast path. Each step links to a full guide if you want more detail. You will need an Amazon SES account and access to your domain's DNS settings.

## 1. Connect your SES account

Open **Provider** and connect **Amazon SES** with your access key ID, secret access key, and region. Xelqun checks the keys and turns on event tracking for you.

→ [Connect a provider](/docs/connect-a-provider)

## 2. Verify a sending domain

Open **Domains**, add your domain, and add the DNS records Xelqun gives you. Click **Refresh** until the status turns to **Verified**.

→ [Verify a domain](/docs/verify-a-domain)

## 3. Send your first email

Open **Emails**, then **Compose**. Pick your verified sender, add a recipient, subject, and message, and send.

→ [Sending email](/docs/sending-email)

## 4. Watch what happens

On the message page, the history fills in as SES reports back: sent, delivered, opened, clicked. Bounces and spam complaints show here too.

→ [Message events](/docs/message-events)

## 5. (Optional) Use the API

Create an **API key** and send email straight from your app with one request.

→ [API keys](/docs/api-keys) · [Send API reference](/docs/send-api)

---

## The order matters

If a screen looks empty or stops you, an earlier step is usually not done yet:

| You want to                   | You need first                          |
| ----------------------------- | --------------------------------------- |
| Add a domain                  | An active provider connection           |
| Compose an email              | A verified sender                       |
| See delivered and open events | Webhooks set up (done when you connect) |
| Send with the API             | An API key and a verified sender        |

Work from the top down and each screen turns on in turn.
