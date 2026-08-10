---
title: API keys
description: Create a key so your app can send through Xelqun.
section: Developers
order: 11
---

An API key lets your own app send email through Xelqun's API. You manage keys on the **API keys** screen, and they belong to your team.

## Creating a key

1. Open **API keys**.
2. Create a key and give it a name that says where it is used, like `production-app` or `billing-worker`.
3. Pick a sender (optional). Leave it on **Any verified domain or address** to let the key send from all of your verified senders, or choose one domain or address to lock the key to it.
4. Copy the key right away.

The full key is shown only once, right after you create it. Xelqun saves only a scrambled version and the last few characters, so it can never show you the full key again. If you lose it, revoke the key and make a new one.

Keys look like this:

```text
qlv_XXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXXX
```

## What a key can do

A key can send email for its team. By default it can send from any of your verified domains and addresses. Treat every key like a password:

- Keep it in your app's secrets or environment, never in your code repo.
- Use a separate key for each app or environment, so you can revoke one without breaking the others.
- Rotate keys now and then by making a new one and revoking the old.

## Locking a key to one sender

When you create a key you can tie it to a single verified sender. After that, the key can only send from that one:

- A **domain** (like `example.com`) lets the key send from any address at that domain, such as `hello@example.com` or `billing@example.com`.
- An **address** (like `hello@example.com`) lets the key send from that exact address only.

You still put the sender in the `from` field of each request. If the `from` does not match what the key is locked to, the send is rejected with a `403` response and nothing goes out. This is a good way to stop a leaked key from sending as any of your other domains.

A locked key shows the sender it is tied to on the API keys screen.

You can only lock a key to a sender that is already verified. If you delete that domain or address later, the key goes back to sending from any of your verified senders, so revoke the key if you no longer want it to send at all.

## Using a key

Send it as a Bearer token when you call the send API:

```bash
curl https://your-xelqun-domain.com/api/v1/emails \
  -H "Authorization: Bearer qlv_your_key_here" \
  -H "Content-Type: application/json" \
  -d '{ "from": "hello@example.com", "to": ["user@example.com"], "subject": "Hi", "html": "<p>Hello!</p>" }'
```

The full request and response are on the [Send API reference](/docs/send-api).

## Revoking a key

Revoke a key on the API keys screen as soon as you no longer need it, or if you think it may be exposed. It stops working right away, so the next request with that key is rejected.

The list also shows who made each key and when it was last used, so you can spot keys that are old or being used when they should not be.
