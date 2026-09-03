# Outbound webhooks

The `Webhook` bounded context lets an admin register a third-party `https://` URL that receives a
signed HTTP `POST` whenever a chosen domain event fires — anywhere in the app, not just in one
bounded context.

## How it works

Every command handler that mutates an aggregate publishes that aggregate's domain events on
`event.bus` (see the "Event flow" diagram in the README). `Webhook\Infrastructure\EventHandler\
DispatchWebhooksOnAnyDomainEvent` is type-hinted to the abstract `App\Shared\Domain\Bus\Event\
DomainEvent` class that every bounded context's events extend, so Symfony Messenger's
class-hierarchy handler resolution invokes it for **all** of them — no per-BC integration needed
to make a new event type webhook-eligible. It looks up active subscriptions whose `event_names`
list contains that event's name and, for each match, dispatches a `DeliverWebhookCommand`.

That command runs on its own Messenger transport and worker (`webhook_delivery` / `make
webhook-consumer`), deliberately separate from the shared `async` transport used for emails and
other event side-effects. A slow or unreachable third party is the expected case here, not a bug
— isolating it means it can never delay anything else in the app. The transport's retry strategy
(`max_retries: 5, delay: 2000, multiplier: 2`, see `config/packages/messenger.yaml`) is more
tolerant than the default `async` transport for the same reason; a delivery that still fails after
the retry budget lands in the shared `async.dead_letter` queue like any other failed message.

## Managing subscriptions

Admin only (`ROLE_ADMIN`), all under `/api/v1/webhook-subscriptions`:

```bash
POST /api/v1/webhook-subscriptions
{"name": "Billing system sync", "url": "https://example.com/webhooks/inbound", "event_names": ["document.uploaded", "user.created"]}
```

The response includes the plain-text `secret` used to sign deliveries — **shown only once**. If
it's lost, rotate it (`POST /api/v1/webhook-subscriptions/{id}/rotate-secret`, also one-time
visible).

Other endpoints: `GET /api/v1/webhook-subscriptions` (paginated list, filterable by `status`),
`GET /api/v1/webhook-subscriptions/{id}`, `PUT /api/v1/webhook-subscriptions/{id}` (replace
name/url/event_names — never the secret), `POST .../{id}/disable` and `POST .../{id}/enable`
(pause/resume deliveries without losing the subscription), `DELETE /api/v1/webhook-subscriptions/{id}`
(soft delete, not meant to be reversed — use disable for that).

### The URL must be safe to call

`Webhook\Domain\ValueObject\WebhookUrl` rejects anything that isn't `https://`, plus `localhost`,
any `*.local`/`*.localhost` hostname, and any IP-literal host in a private, loopback, or
link-local/reserved range (this also covers the `169.254.169.254` cloud metadata endpoint on
AWS/GCP). A rejected URL fails with `400 webhook.invalid_url`. This check does **not** resolve
DNS at creation time — a hostname that currently points at a public IP could later be repointed
at an internal one (DNS rebinding). Closing that fully would mean re-resolving and re-checking on
every delivery attempt, which is out of scope for this first pass.

## Payload format

```json
{
  "id": "3f1b1e2e-....",
  "event": "document.uploaded",
  "occurred_at": "2026-09-01T12:00:00+00:00",
  "data": {
    "ownerId": "...",
    "bucketName": "documents",
    "objectPath": "...",
    "originalName": "invoice.pdf",
    "size": 10240,
    "mimeType": "application/pdf",
    "status": "success"
  }
}
```

- `id` is the domain event's own id (`DomainEvent::eventId()`) — stable across delivery retries,
  so receivers should deduplicate on it (see "Delivery guarantees" below).
- `event` is the domain event name (`DomainEvent::eventName()`), the same string matched against
  a subscription's `event_names`.
- `data` is every **public** property the event class declares, reflected generically (see
  `Shared\Infrastructure\Messaging\DomainEventPayloadExtractor` — the same mechanism the
  transactional outbox already used, reused here rather than duplicated). Keys keep the event
  class's own PHP property names (camelCase) — this is the one response body in the app that
  intentionally doesn't go through `ApiResponse`'s snake_case conversion, since it mirrors the
  domain event's own shape, not a REST resource.

## Verifying the signature

Every request carries `X-Webhook-Id` (same as `data.id`), `X-Webhook-Event`, and
`X-Webhook-Signature: sha256=<hex>` — an HMAC-SHA256 of the *raw request body* keyed with the
subscription's secret (GitHub/Stripe convention). Verify it before trusting the payload:

```php
$expected = 'sha256=' . hash_hmac('sha256', $rawRequestBody, $subscriptionSecret);
if (!hash_equals($expected, $request->headers->get('X-Webhook-Signature'))) {
    throw new \RuntimeException('Invalid webhook signature.');
}
```

Always compare against the raw bytes received, before any JSON parsing — re-encoding the parsed
payload can produce a byte-for-byte different string (key order, whitespace, escaping) and break
the comparison.

## Event names

Every domain event currently published is a valid `event_names` entry:

| Context | Events |
|---|---|
| `user` | `user.registered`, `user.created`, `user.updated`, `user.replaced`, `user.activated`, `user.deactivated`, `user.deleted`, `user.roles_updated`, `user.email_verification_requested`, `user.email_verified`, `user.password_reset_requested`, `user.data_erased` |
| `document` | `document.uploaded`, `document.accessed`, `document.deleted` |
| `project` | `project.created`, `project.updated`, `project.replaced`, `project.deleted` |
| `task` | `task.created`, `task.updated`, `task.replaced`, `task.deleted` |
| `api_client` | `api_client.created`, `api_client.revoked`, `api_client.secret_rotated`, `api_client.deleted` |
| `webhook_subscription` | `webhook_subscription.created`, `webhook_subscription.updated`, `webhook_subscription.secret_rotated`, `webhook_subscription.disabled`, `webhook_subscription.enabled`, `webhook_subscription.deleted` |

A new bounded context's events become subscribable automatically — nothing to register here.

## Delivery guarantees and limitations (v1)

- **At-least-once, not exactly-once.** A retried delivery after a timeout your receiver actually
  processed will arrive again. Deduplicate on `data.id` / `X-Webhook-Id`.
- **No ordering guarantee** across different events, even for the same subscription.
- **No delivery-attempt history.** There's no `WebhookDelivery` table to browse past attempts —
  observability goes through the same channels as everything else in this template: structured
  error logs (`LoggerInterface::error`, includes the real exception), the existing Prometheus bus
  metrics, and the `async.dead_letter` queue for exhausted retries. Add a tracking entity if a
  fork needs a UI for delivery history; it wasn't proportionate to build for the template itself.
- **The signing secret is stored in plain text**, not hashed — unlike a password or an
  `ApiClient` secret, it must be re-readable to recompute the signature on every delivery. This
  mirrors the existing `refresh_tokens.token` / Garage `rpc_secret` precedent: protection is at
  the database access level, not the application level.
