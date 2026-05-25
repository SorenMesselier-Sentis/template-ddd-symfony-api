# Testing Emails (Twig + Mailpit)

This guide describes the full workflow to trigger and view emails locally.

## Prerequisites

* Docker stack running: `make up`
* Database initialized: `make db-fresh` (loads fixtures, including an admin user)
* Mail variables in `.env.local` (copy from `.env` if needed):

```bash
MAILER_DSN=smtp://mailpit:1025
MAILER_FROM=noreply@example.com
```

> **Note:** from the PHP container, the SMTP host is `mailpit`, not `localhost`.

## Flow Architecture

```
POST /api/v1/users  or  DELETE /api/v1/users/{id}
        │
        ▼
CommandHandler → domain event → outbox table (same transaction)
        │
        ▼
make outbox-relay          # publishes to RabbitMQ
        │
        ▼
make consume               # consumes the events.user queue
        │
        ▼
SendWelcomeEmailOnUserCreated  /  SendAccountDeletionEmailOnUserDeleted
        │
        ▼
Twig (templates/email/…) → NotificationSender → Symfony Mailer → Mailpit
```

## Starting Workers

Open **3 terminals**:

```bash
# Terminal 1 — RabbitMQ consumer (keep running)
make consume

# Terminal 2 — outbox relay (rerun after each API action or loop it)
make outbox-relay

# Terminal 3 — curl commands / manual tests
```

To automate the outbox relay continuously:

```bash
watch -n 2 make outbox-relay
```

## Admin Account (fixtures)

| Field    | Value                     |
| -------- | ------------------------- |
| Email    | `john.doe@example.com`    |
| Password | `secret1234`              |
| Roles    | `ROLE_ADMIN`, `ROLE_USER` |

## 1. Welcome Email (UserCreated)

### Login

```bash
TOKEN=$(curl -s -X POST http://localhost:8080/api/v1/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"john.doe@example.com","password":"secret1234"}' \
  | php -r 'echo json_decode(file_get_contents("php://stdin"))->data->access_token;')

echo "$TOKEN"
```

### Create a user

```bash
curl -s -X POST http://localhost:8080/api/v1/users \
  -H "Authorization: Bearer $TOKEN" \
  -H 'Content-Type: application/json' \
  -d '{
    "firstName": "Alice",
    "lastName": "Martin",
    "email": "alice.martin@example.com",
    "password": "secret1234"
  }'
```

### Relay + verification

```bash
make outbox-relay   # terminal 2
```

Open Mailpit: [http://localhost:8025](http://localhost:8025) (or `make mail`)

You should see a **“Welcome to the platform!”** email sent to `alice.martin@example.com`.

Editable templates:

* `templates/email/user/welcome.subject.twig`
* `templates/email/user/welcome.txt.twig`
* `templates/email/user/welcome.html.twig`

## 2. Account Deletion Email (UserDeleted)

### Get a user ID

```bash
USER_ID=$(curl -s "http://localhost:8080/api/v1/users?email=alice.martin@example.com" \
  -H "Authorization: Bearer $TOKEN" \
  | php -r '$d=json_decode(file_get_contents("php://stdin")); echo $d->data[0]->id;')

echo "$USER_ID"
```

### Delete the user

```bash
curl -s -X DELETE "http://localhost:8080/api/v1/users/$USER_ID" \
  -H "Authorization: Bearer $TOKEN" \
  -w "\nHTTP %{http_code}\n"
```

Expected response: `HTTP 204`.

### Relay + verification

```bash
make outbox-relay
```

In Mailpit: email **“Your account has been deleted”** for `alice.martin@example.com`.

Templates:

* `templates/email/user/account_deletion.*.twig`

## Troubleshooting

| Symptom                 | Likely cause               | Action                                    |
| ----------------------- | -------------------------- | ----------------------------------------- |
| No email in Mailpit     | Outbox not relayed         | `make outbox-relay`                       |
| No email in Mailpit     | Consumer stopped           | `make consume`                            |
| SMTP error              | Wrong `MAILER_DSN`         | use `smtp://mailpit:1025` in `.env.local` |
| `401` on POST /users    | Missing or non-admin token | login with `john.doe@example.com`         |
| Message in failed state | Check dead letter queue    | `make messenger-failed-show`              |

PHP logs:

```bash
make logs-php
```

Messenger stats:

```bash
make messenger-stats
```

## Modify content without redeploying

1. Edit files under `templates/email/`
2. Clear cache if needed: `make clear`
3. Re-run API action + `make outbox-relay`

Handlers no longer contain `sprintf`: Twig is solely responsible for email content.
