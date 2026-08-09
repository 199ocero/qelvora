---
title: Verify a domain
description: Add a sending address and prove you own it with DNS records.
section: Setup
order: 4
---

Before you can send, SES needs to know you own the address you send from. That proof is a sending identity: either a whole domain or a single email address. You manage it on the **Domains** screen.

> You need an active Amazon SES connection first. Without one, the add form is replaced by a prompt to connect Amazon SES.

## Domain or single address

- **Domain** (recommended): verify `example.com` once, then send from any address on it, like `hello@`, `support@`, or `noreply@`. You verify it with DNS records.
- **Single address**: verify one address like `you@example.com`. SES sends a confirmation email instead of using DNS. This is good for quick tests, and it has no DNS records.

## Adding a domain

1. Open **Domains** and add your domain.
2. You land on the domain's page, which lists the DNS records to add.
3. Add those records with your DNS host.
4. Click **Refresh** to check again. When SES confirms them, the status becomes **Verified**.

The status starts as **Pending** and stays there until every record is found. DNS can take a few minutes to a few hours to update.

## The DNS records

For a domain, Xelqun gives you four kinds of records. Each row on the domain page has copy buttons for the name and the value.

### DKIM: proves the email is really from you

Three `CNAME` records:

```text
Type:  CNAME
Name:  <token>._domainkey.example.com
Value: <token>.dkim.amazonses.com
```

All three DKIM records must be found for the domain to verify.

### MAIL FROM: your own return path

One `MX` record on the `mail.` subdomain, so bounces come back through your own domain:

```text
Type:     MX
Name:     mail.example.com
Value:    feedback-smtp.<region>.amazonses.com
Priority: 10
```

### SPF: lets SES send for you

One `TXT` record on the same subdomain:

```text
Type:  TXT
Name:  mail.example.com
Value: v=spf1 include:amazonses.com ~all
```

### DMARC: a starting policy

One `TXT` record. It starts relaxed (`p=none`), so nothing is blocked while you watch how your email does:

```text
Type:  TXT
Name:  _dmarc.example.com
Value: v=DMARC1; p=none;
```

## Checking the status

DNS is not instant. Come back to the domain page and click **Refresh** to ask SES to check again. Once it is verified, the domain shows up as a sender when you compose an email.

## Removing a domain or address

Delete an identity from its page or the Domains list. This also removes it from SES. After that, no address on a deleted domain can send.

→ Next: [Sending email](/docs/sending-email)
