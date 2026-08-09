---
title: Troubleshooting
description: Fixes for the problems you are most likely to hit.
section: Reference
order: 15
---

Most problems come from doing steps out of order: a missing provider, a domain that is not verified, or a webhook that cannot be reached. Here are the common ones and how to fix them.

## "Connect an email provider first"

You are trying to add a domain, sync blocked addresses, or send, but there is no active provider.

**Fix:** Open **Provider** and connect Amazon SES. See [Connect a provider](/docs/connect-a-provider).

## My keys are rejected when I connect

The access key or secret is wrong, or the key cannot call the SES account.

**Fix:** Check the access key ID, secret, and **region**. The region must be the one your SES account uses. Make sure the key's permissions allow SES.

## The compose screen has no senders

The **From** list is empty because you have no verified sender.

**Fix:** [Verify a domain](/docs/verify-a-domain), or a single address, and wait for the status to reach **Verified**. Then go back to Compose.

## My domain will not verify

The status is stuck on **Pending**.

**Fix:**

- Check that all three DKIM records are added exactly as shown, plus the MAIL FROM record and the SPF record.
- DNS can take minutes to hours to update. Give it time, then click **Refresh** again.
- Watch for a common host quirk: some hosts add your domain to the name for you, which can make a doubled name like `mail.example.com.example.com`.

## Emails send but the history never moves

The message shows **Sent** but never **Delivered**, and there are no opens or bounces. This is almost always the webhook.

**Fix:**

1. On **Provider**, click **Set up webhook again** and read the result.
2. If it fails, add SNS access to your key (the [AmazonSNSFullAccess](https://docs.aws.amazon.com/aws-managed-policy/latest/reference/AmazonSNSFullAccess.html) managed policy is the quickest way) and run it again.
3. On your own machine, make sure a tunnel is running and its address is saved in the **Local webhook tunnel** field, then set up the webhook again. See [Webhooks](/docs/webhooks).

## My scheduled email never sent

The message is stuck on **Scheduled** long after its time has passed.

**Fix:** Scheduled email is sent by a task that runs every minute, so that task must be running. If you self-host, make sure your scheduler is running (the Docker setup includes a scheduler service for this). See [Schedule email](/docs/scheduled-email).

## A send is stopped as "blocked"

A recipient is on your blocked list, usually from an earlier bounce or complaint.

**Fix:** This is working as intended. If the address is really fine, remove it on the [Suppressions](/docs/suppressions) screen. Be sure first, because emailing real bounces again hurts your reputation.

## I cannot send to just anyone

You can only send to verified addresses, and your limit is low.

**Fix:** Your SES account is in the sandbox. Ask AWS for production access in the SES console. Your sandbox status shows on the Provider screen.

## I lost an API key

The full key is shown only once, when you create it.

**Fix:** Revoke the old key and make a new one. See [API keys](/docs/api-keys).

## A teammate cannot see a section

Sidebar sections depend on the role.

**Fix:** Provider is owner only. Domains, blocked addresses, and API keys are for owners and admins. Change the person's role in the team's member settings. See [Roles & permissions](/docs/roles-and-permissions).
