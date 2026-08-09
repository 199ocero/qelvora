---
title: Message events
description: How delivery, open, click, bounce, and complaint events reach Xelqun.
section: Sending
order: 9
---

Every message has a history: an ordered list of what happened to it after you sent it. This is where you confirm a message was delivered, see who opened or clicked, and catch bounces and spam complaints.

## Where events come from

Xelqun does not keep asking SES for updates. Instead, SES sends events to Xelqun:

```text
Your send  ->  Amazon SES  ->  Amazon SNS  ->  Xelqun webhook  ->  message history
```

When you connect Amazon SES, Xelqun sets up the SES and SNS pieces that send each event to a private web address that only your connection uses. Xelqun checks that the event is really from AWS, saves it, and updates the message.

Because this runs on a webhook, events only arrive if that web address can be reached. If your history never gets past **Sent**, check the webhook first. See [Webhooks](/docs/webhooks), and for your own machine, the tunnel steps in [Connect Amazon SES](/docs/connect-a-provider).

## Event types

The history can show any of these, based on what SES reports:

| Event             | What it means                                       |
| ----------------- | --------------------------------------------------- |
| Send              | SES accepted the message                            |
| Delivery          | Delivered to the recipient's mail server            |
| Open              | The recipient opened the message                    |
| Click             | The recipient clicked a tracked link                |
| Bounce            | Delivery failed                                     |
| Complaint         | The recipient marked it as spam                     |
| Reject            | SES rejected the message before sending             |
| Rendering failure | A placeholder in the message could not be filled in |
| Delivery delay    | Delivery is delayed for now and being retried       |

## How events change a message

As events arrive, Xelqun updates the message:

- The **status** moves to the furthest point reached. Delivered ranks above sent, and so on.
- **Open** and **click** counts add up on the message.
- A **bounce** or **complaint** adds the recipient to your [blocked list](/docs/suppressions), so you stop sending to them.

## Reading the history

Open any message from the Emails list. The history shows the newest event first, with the type and time of each one. Together with the message text above it, it is the full record of what happened.

> Opens and clicks need tracking turned on. SES only reports them when open and click tracking is on for the connection, which Xelqun sets up for you. If you see deliveries but never opens, check that the webhook is set up and that your recipients load images in their email.
