---
title: Webhooks
description: How Xelqun receives SES events, and how to fix them when they stop.
section: Developers
order: 13
---

Xelqun gets delivery events from Amazon SES through a webhook. You do not build or host this yourself. It is set up when you connect Amazon SES. Still, knowing how it works helps when events are not showing up.

## The flow

```text
SES event  ->  SNS topic  ->  POST /webhooks/mail/{token}  ->  Xelqun
```

- Each connection has its own secret token in the webhook address, so Xelqun knows which connection an event belongs to.
- Every event is checked against Amazon's signature before Xelqun trusts it.
- The first message SNS sends is a confirmation, which Xelqun accepts for you to finish the setup.

## What gets set up

When you connect, or set up the webhook again, Xelqun creates these in your SES account:

- A configuration set that captures send, delivery, open, click, bounce, complaint, reject, rendering failure, and delivery delay events.
- An SNS topic that points at your webhook address.
- A rule that sends those events to the topic.

You can run this again safely. It will not create duplicates.

## When events are not showing up

If a message stays at **Sent** and never moves, work through these:

1. **Is the webhook set up?** On the Connection screen, click **Set up webhook again** and read the result. A failure usually points to a missing permission.
2. **Does the key allow SNS?** The setup needs `sns:CreateTopic` and `sns:Subscribe`, plus `sns:SetTopicAttributes`. The quickest fix is to attach the [AmazonSNSFullAccess](https://docs.aws.amazon.com/aws-managed-policy/latest/reference/AmazonSNSFullAccess.html) managed policy to the key. Then run it again.
3. **Can the address be reached from the internet?** SNS has to reach your app. On a live site that is your real domain. On your own machine you need a tunnel.

### On your own machine

AWS cannot reach `localhost`, so you have to expose your app:

1. Start a tunnel with `herd share`, ngrok, Expose, or Cloudflare Tunnel.
2. Paste its public address into the **Local webhook tunnel** field on the Connection screen.
3. Set up the webhook again so SNS points at the tunnel address.

Xelqun reads this address on every request, so update it each time your tunnel restarts with a new one. See [Connect Amazon SES](/docs/connect-a-provider) for the full setup.

## Security

You never expose your own address or secret. The token in the address ties each request to one connection, and the signature check rejects anything that did not really come from AWS. On live sites, the tunnel field is hidden, so webhook delivery cannot be pointed somewhere else.
