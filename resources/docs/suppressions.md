---
title: Suppressions
description: Keep bad addresses off your sends, automatically and by hand.
section: Sending
order: 7
---

A suppression is an address you do not want to email. Sending to hard bounces and people who marked you as spam hurts your reputation, so Xelqun keeps a blocked list for your team and stops any send to an address on it.

## How addresses get blocked

There are three ways:

- **On its own, from events**: when a message [bounces or gets a spam complaint](/docs/message-events), the recipient is blocked right away, with the reason saved. This is the important one. It happens without you doing anything.
- **By hand**: add an address yourself on the Suppressions screen, with an optional note about why.
- **Synced from SES**: pull SES's own blocked list into Xelqun so the two match.

## Adding an address by hand

On the **Suppressions** screen, enter the email address and, if you want, a note. Add it. From then on, any send to that address, from the dashboard or the API, is stopped.

> Adding by hand and syncing both need an active provider connection.

## Syncing from SES

Click **Sync from provider** to pull in the addresses SES already blocks. This is handy right after you connect an existing SES account that has been blocking addresses on its own.

## Removing an address

If an address was blocked by mistake, or the person asked to get email again, remove it from the list. Removing it also clears it from SES, so future sends go through again.

Be careful here. Removing a real bounce or complaint and emailing that address again is exactly what hurts your reputation.

## Why this helps you

Email providers watch how you handle bounces and complaints. A blocked list that works on its own means:

- You never send to an address that already bounced.
- Complaints are honored right away, which keeps you in good standing.
- Your bounce and complaint rates, shown on the Overview page, stay low.
