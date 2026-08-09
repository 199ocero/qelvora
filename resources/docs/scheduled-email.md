---
title: Schedule email
description: Pick a future time to send, and cancel before it goes.
section: Sending
order: 7
---

You can write an email now and have Xelqun send it at a future time. This is useful when timing matters, like a reminder that should land in the morning, or a notice that should go out on a set day.

## Schedule a send

On the **Compose** screen, fill in the email as usual. In the **Schedule for later** field, pick a date and time. The send button changes to **Schedule**.

Leave that field empty to send right away.

The time must be in the future. A time in the past is rejected.

## Before it sends

A scheduled email shows up in your [email log](/docs/sending-email) with a **Scheduled** status and the time it will go. It has not been sent yet.

To stop it, open the message and choose **Cancel**. The scheduled email is removed and will not send. You can only cancel while it is still scheduled.

## When the time comes

Xelqun checks for due emails every minute and sends the ones whose time has arrived. Once sent, the message moves through the normal statuses (Sent, then Delivered) and its [history](/docs/message-events) fills in.

If you self-host, this depends on the scheduler running. The Docker setup includes a scheduler service that handles it. If your scheduled emails never send, make sure your task scheduler is running.

## From your app

The [send API](/docs/send-api) takes a `scheduled_at` time. Give it a future time and the message is held until then, instead of sending straight away.

→ Next: [Resend failed email](/docs/resend-failed)
