---
paths:
  - 'app/Actions/Mail/**'
---

# Actions Mail

## Domain-restricted API keys
An ApiKey may set mail_identity_id to lock it to one verified identity. SendEmail::handle enforces it via the $restrictedTo param: a domain identity authorizes any address @that-domain, an email identity only its exact address; a mismatch throws RestrictedSenderException (403), distinct from UnverifiedSenderException (422). Only the API path (Api/V1/EmailController) passes $apiKey->mailIdentity; dashboard/resend sends are unrestricted. The FK is nullOnDelete, so deleting the locked identity silently unrestricts the key (fail-open) — it can then send from any remaining verified sender.
