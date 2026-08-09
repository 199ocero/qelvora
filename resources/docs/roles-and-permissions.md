---
title: Roles & permissions
description: Who on your team can do what.
section: Reference
order: 14
---

Everything in Xelqun belongs to a team, and each member has a role that sets what they can do. Roles are the same across the whole product. This page is about the email permissions.

## The three roles

- **Owner**: full control, including the Amazon SES connection and its keys. Every team has at least one owner.
- **Admin**: runs everything day to day, but cannot manage the Amazon SES keys.
- **Member**: can view the email log and send email.

## Email permissions

| What you can do               | Owner | Admin | Member |
| ----------------------------- | :---: | :---: | :----: |
| View the email log            |  Yes  |  Yes  |  Yes   |
| Send email                    |  Yes  |  Yes  |  Yes   |
| Use email templates           |  Yes  |  Yes  |  Yes   |
| Manage email templates        |  Yes  |  Yes  |   No   |
| Manage domains                |  Yes  |  Yes  |   No   |
| Manage blocked addresses      |  Yes  |  Yes  |   No   |
| Manage API keys               |  Yes  |  Yes  |   No   |
| Connect and manage Amazon SES |  Yes  |  No   |   No   |

What this means in practice:

- **Members** are senders. They write and send email, use saved templates, and can see what was sent, but they do not change setup or manage templates.
- **Admins** run the operation: domains, blocked addresses, and API keys. The SES keys stay with owners.
- **Owners** also hold the Amazon SES connection, since that is the most sensitive part.

## What each person sees

The sidebar only shows the sections a role allows, so the product fits who is looking. If a teammate cannot see **Connection** or **API keys**, that is their role. An owner or admin can change it in the team's member settings.

## More than one team

If you run more than one product, or serve more than one client, give each one its own team. Amazon SES, domains, emails, blocked addresses, and API keys are all kept apart per team, and a person can have a different role in each.
