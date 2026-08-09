---
title: Introduction
description: What Xelqun is and how the parts fit together.
section: Getting started
order: 1
---

Xelqun helps your team run email through Amazon SES. SES sends the mail from your own AWS account. Xelqun gives you everything around it: sending, logs, delivery tracking, blocked addresses, domain setup, and API keys.

Your mail setup does not move. You connect your SES keys, and Xelqun becomes the control panel on top.

## Why use it

Amazon SES is cheap and reliable, but plain. It has no easy way to browse what you sent, no clear view of bounces and spam complaints, and no team roles. Xelqun adds those:

- See every email you send, with a live history of what happened to it.
- Bad addresses get blocked on their own, so you stop emailing them.
- Manage domains, senders, blocked addresses, and API keys in one place.
- Let your own app send email through a simple API.

## How the parts connect

Each step turns on the next one:

1. **Provider**: connect your Amazon SES account. Nothing else works until this is done.
2. **Domains**: add the domain or address you send from, and prove you own it with DNS records.
3. **Emails**: send from a verified sender, and watch what happens to each message.
4. **Suppressions**: bad addresses land here and are blocked from future sends.
5. **API keys**: create a key so your app can send email through Xelqun.

The **Overview** page sits on top and shows your recent sending as simple rates: delivered, bounced, and complained.

## Everything belongs to a team

In Xelqun, your provider, domains, emails, blocked addresses, and API keys all belong to a team. What each teammate can do depends on their role. If you run more than one product or client, give each one its own team with its own SES account.

> New here? Start with the [Quickstart](/docs/quickstart) to send your first email fast.
