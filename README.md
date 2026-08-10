# Xelqun

**An open-source email operations platform for Amazon SES. It adds the dashboards, logs, retries, webhooks, suppression management, and team tools that SES doesn't give you, while your email keeps running inside your own AWS account.**

> ⚠️ **Status: Under active development. Not usable yet.**
> Xelqun is being built in the open and is not production-ready. The features below are planned or in progress. There is no stable release, so don't rely on it for anything real yet.

---

## What is Xelqun?

Xelqun is a self-hosted email operations platform that sits on top of Amazon SES. It does not replace SES or become another email provider. It gives you the operational tooling that SES leaves out.

```mermaid
flowchart LR
    A["Your App<br/><i>sends email</i>"] -->|API| B["Xelqun<br/><i>logs, retries, webhooks, dashboards</i>"]
    B --> C["Amazon SES<br/><i>actually delivers</i><br/>(in your AWS account)"]
```

> **The open-source control plane for Amazon SES.**

Xelqun focuses on email operations, not email delivery. It is built specifically for Amazon SES.

## Why Xelqun?

Connect your AWS account and get a working email platform right away: dashboards, logs, retries, bounce and complaint handling, suppression lists, and team management. It all runs on your own infrastructure, so you don't have to build any of it yourself.

### Why not just point my app at SES directly?

For basic sending, you should. If all you need is `App -> SES`, Xelqun adds nothing. SES already gives you SMTP and an API, and most frameworks already have a mailer that talks to it. Xelqun is not an easier way to send email.

The value shows up once email becomes something you have to operate, not just call. When you're sending real volume, SES leaves these gaps for you to fill:

- **Where did my email go?** You want a searchable history of every message and its status (queued, sent, accepted, delivered), not a send call that returns nothing.
- **Why didn't the customer receive it?** SES reports bounces, complaints, and deliveries, but only if you build the pipeline yourself: SNS, a webhook endpoint, a queue, a database, and a dashboard. Xelqun is that pipeline.
- **Suppression management.** A permanent bounce should stop you from ever emailing that address again. You don't want to rebuild this in every app.
- **Debugging.** When someone asks why John didn't get his password reset, you want one timeline (app accepted, queued, SES accepted, delivered) instead of digging through CloudWatch, app logs, SES, and SNS.

So Xelqun isn't a nicer way to use SES. It's the operations layer around email delivery.

```mermaid
flowchart TB
    subgraph Q["Xelqun (the control plane)"]
        direction LR
        L["Logs & history"]
        E["Events"]
        W["Webhooks"]
        S["Suppression"]
        R["Retries"]
        D["Debugging"]
        T["Team management"]
    end
    Q --> SES["Amazon SES<br/><i>delivery, in your AWS account</i>"]
    SES --> Net["The internet"]
```

SES delivers the email. Xelqun runs the operations around it. If you're running a SaaS and email has become infrastructure you operate rather than a function you call, Xelqun is for you.

## Who it's for

- Developers and engineering teams
- SaaS founders
- Agencies
- Companies already using AWS
- Teams wanting full control over their email infrastructure
- Organizations that prefer self-hosting or have compliance requirements

## Planned MVP features

> Xelqun supports Amazon SES only.

**Email Sending**
- Transactional Email API
- Email templates
- SMTP support (optional, later)

**Operations**
- Email logs & searchable history
- Retry queues
- Failed email management
- Scheduled emails

**Deliverability**
- Bounce handling
- Complaint handling
- Suppression lists
- Webhook processing

**Management**
- API keys
- Domain verification
- Multi-project support
- Team management

**Infrastructure**
- Docker deployment
- Self-hosted
- Open source

## Not included in V1

- Marketing campaigns
- Contact management
- Audience segmentation
- Newsletters
- Marketing automation
- Drag-and-drop builders
- AI features

## Roadmap

### V1: MVP (complete)
Everything you need to operate Amazon SES in production. This is the whole focus.

- [x] Amazon SES support
- [x] Transactional email API
- [x] Email templates
- [x] Email logs & searchable history
- [x] Retry queues & failed email management
- [x] Scheduled emails
- [x] Bounce & complaint handling
- [x] Suppression lists
- [x] Webhook processing
- [x] API keys
- [x] Domain verification
- [x] Multi-project support _(via teams)_
- [x] Team management
- [x] Docker deployment

### V2: Advanced
Resilience, insight, and optionally marketing email. Not started.

- [ ] Unified analytics
- [ ] Marketing email
- [ ] Contact management
- [ ] Audience segmentation

## Technical principles

- Queue-first architecture
- Docker-first deployment
- API-first
- Open-source core

## Tech stack

- **Backend:** Laravel 13, PHP 8.3+
- **Database:** PostgreSQL
- **Queue:** Redis + Laravel Horizon
- **Frontend:** Inertia v3, Vue 3 (TypeScript), Tailwind CSS v4
- **Auth:** Laravel Fortify (login, registration, email verification, 2FA/TOTP, recovery codes)
- **Deployment:** Docker, self-hosted

## Getting started

Installation and setup instructions are coming soon, once the MVP stabilizes.

### Docker deployment

Xelqun ships with a Docker Compose stack for self-hosting. It runs five services: the web app (nginx and php-fpm), a queue worker, a scheduler, PostgreSQL, and Redis.

1. Copy the example env file and set your values:

   ```bash
   cp .env.example .env
   ```

   Set at least `APP_KEY` (generate one with `php artisan key:generate --show`), `APP_URL`, and `DB_PASSWORD`. Add your AWS credentials later from the in-app connection screen.

2. Build and start the stack:

   ```bash
   docker compose up -d --build
   ```

   The web container runs database migrations on startup, so the app is ready once it is healthy. It is safe to run again.

3. Open the app at `http://localhost:8000` (change the port with `APP_PORT`).

The `worker` service runs Laravel Horizon, which processes all queued jobs (email sending, webhook events, identity checks) using Redis. The `scheduler` service runs the every-minute schedule, including releasing scheduled emails and capturing Horizon metrics snapshots. Both reuse the same image as the app.

The Horizon dashboard is available at `/horizon` once the app is running. Any authenticated user can access it.

## License

Xelqun is open source under the [GNU Affero General Public License v3.0](LICENSE) (AGPL-3.0).
