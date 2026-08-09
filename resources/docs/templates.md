---
title: Email templates
description: Save an email once and reuse it, with fill-in-the-blank fields.
section: Sending
order: 6
---

A template is an email you save once and reuse. It holds a subject and a body, with fill-in-the-blank fields you set each time you send. Templates keep your email content in one place, so you can fix wording without changing your app's code, and your app can send with a short call instead of building the whole email every time.

## Fill-in-the-blank fields

Anywhere in the subject or body, write a field name inside double curly braces:

```text
Welcome, {{ name }}
```

When you send, you give a value for `name`, and Xelqun swaps it in. So `{{ name }}` becomes `Dana`. You can use as many fields as you like, and use the same field more than once.

If you send without a value for a field, the `{{ name }}` text is left as is, so a missing value is easy to spot rather than showing a blank.

## Create a template

1. Open **Templates**, then **New template**.
2. Give it a **Name** you will recognise later, like "Welcome email".
3. Add a **Subject**, an **HTML body**, a **plain text body**, or a mix. You need at least one body.
4. Use `{{ field }}` fields anywhere you want to fill in a value at send time.
5. Save.

The template list shows each template's name, subject, and the fields it uses.

## Send with a template

**From the dashboard.** On the **Compose** screen, pick a template from the list. It fills in the subject and body for you. Edit anything you want, then send. Replace the `{{ field }}` text with real words before you send.

**From your app.** The [send API](/docs/send-api) takes a `template_id` and a `variables` object. Xelqun builds the email from the template and your values, so your app does not send the full HTML. See the send API page for the exact call.

## Edit or delete

Open a template and choose **Edit** to change its name or content, or **Delete** to remove it. A template's short name in links (its slug) stays the same once created, so saved links keep working.

## Who can manage templates

Anyone on the team can see the template list. Owners and admins can create, edit, and delete them. Members can use a template when they send, but cannot change it. See [roles and permissions](/docs/roles-and-permissions).

→ Next: [Schedule email](/docs/scheduled-email)
