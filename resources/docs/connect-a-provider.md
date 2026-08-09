---
title: Connect Amazon SES
description: Connect your Amazon SES account and turn on event tracking.
section: Setup
order: 3
---

The Amazon SES connection links your AWS account to your team. Set this up first. Domains, sending, and blocked addresses all need an active connection.

> Xelqun works with Amazon SES.

## What you need from AWS

Create or reuse an IAM user in the AWS account where SES lives, and make an access key. You will enter three things:

- **Access key ID**, like `AKIA…`
- **Secret access key**
- **Region**, the AWS region your SES account uses, like `us-east-1`

Your keys are encrypted and are only used to talk to SES for you.

### Permissions the key needs

The key needs two kinds of access: Amazon SES, to send email and manage your domains and blocked addresses, and Amazon SNS, so Xelqun can set up event tracking.

The easiest way is to attach these two AWS managed policies to the IAM user. You will find both in the AWS console under IAM when you add permissions to the user:

- [AmazonSESFullAccess](https://docs.aws.amazon.com/aws-managed-policy/latest/reference/AmazonSESFullAccess.html) (grants `ses:*`)
- [AmazonSNSFullAccess](https://docs.aws.amazon.com/aws-managed-policy/latest/reference/AmazonSNSFullAccess.html) (grants `sns:*`)

Want tighter access instead? A smaller set also works:

- `ses:*` for account, identity, suppression, and sending actions, and
- `sns:CreateTopic`, `sns:Subscribe`, and `sns:SetTopicAttributes` so Xelqun can set up event delivery.

If the SNS access is missing, the connection still works, but events will not arrive until you fix the permissions and set up the webhook again (see below).

## Connecting

1. Open **Connection**.
2. Enter your access key, secret, and region.
3. Submit.

When you submit, Xelqun:

1. **Checks the keys** by asking SES for your account details. Wrong keys fail here, with an error on the access key field.
2. **Sets up event delivery** by creating the SES and SNS pieces that send events back to Xelqun. If this part fails, the connection is still made and the error is saved so you can try again.
3. **Makes the connection active.** Only one connection is active per team at a time.

Once connected, the screen shows your account health: your sending limit, your send rate, and whether your account is still in the SES sandbox.

## Managing a connection

The Connection screen gives you a few actions:

- **Sync**: refresh your account health from SES.
- **Set up webhook again**: retry the event setup. Use this if event delivery failed or you changed your permissions.
- **Switch**: make a different saved connection the active one.
- **Disconnect**: remove the connection.

## Local development: the webhook tunnel

SES sends events to a public web address, so it cannot reach `http://localhost`. On your own machine, the Connection screen shows a **Local webhook tunnel** field:

1. Start a tunnel with `herd share`, ngrok, Expose, or Cloudflare Tunnel.
2. Paste the tunnel's public address into the field and save.
3. Set up the webhook again so SES points at the new address.

Xelqun reads this address on every request, so update it whenever your tunnel restarts with a new one. This field only shows on your own machine. Live sites always use your real web address.

## The SES sandbox

New SES accounts start in the sandbox. In the sandbox you can only send to addresses you have verified, and your daily limit is low. When you are ready to email anyone, ask AWS for production access in the SES console. Xelqun shows your sandbox status on the Connection screen.

→ Next: [Verify a domain](/docs/verify-a-domain)
