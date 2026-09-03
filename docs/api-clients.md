# API clients (OAuth2 machine-to-machine)

The `ApiClient` bounded context lets a service, script, or job authenticate without a human
user account, using the OAuth2 **`client_credentials`** grant ([RFC 6749 §4.4](https://www.rfc-editor.org/rfc/rfc6749#section-4.4)).
Server side is [`league/oauth2-server`](https://oauth2.thephpleague.com/).

This is the only supported machine-auth mechanism — there is no separate static API-key scheme.

## Creating a client

Admin only (`ROLE_ADMIN`):

```bash
POST /api/v1/api-clients
{"name": "Billing sync worker", "scopes": ["documents:write"]}
```

Response includes `id` (also the OAuth2 `client_id`) and the plain-text `secret` — **shown only
once**. If it's lost, rotate it (`POST /api/v1/api-clients/{id}/rotate-secret`, also admin-only,
also one-time-visible, and it immediately revokes every access token already issued to that
client).

Other admin endpoints: `GET /api/v1/api-clients` (paginated list), `GET /api/v1/api-clients/{id}`,
`POST /api/v1/api-clients/{id}/revoke` (blocks new tokens + revokes existing ones, soft),
`DELETE /api/v1/api-clients/{id}` (soft delete, same revocation).

## Getting a token

```bash
POST /api/v1/oauth/token
Content-Type: application/x-www-form-urlencoded

grant_type=client_credentials&client_id=<id>&client_secret=<secret>&scope=documents:write
```

This endpoint is deliberately **RFC 6749-shaped**, not the app's usual `ApiResponse` envelope —
standard OAuth2 client libraries expect `access_token`/`token_type`/`expires_in`/`scope` on
success and `error`/`error_description` on failure (e.g. `invalid_client`,
`unsupported_grant_type`). Omit `scope` to get every scope granted to the client; requesting a
scope the client wasn't granted is silently dropped, never escalated.

Access tokens are short-lived JWTs signed with `config/oauth/private.key` (a dedicated RSA
keypair — never the Lexik JWT keypair used for human logins, see README "Reproduce the CI
pipeline locally" for how to generate it). Every issued token is also tracked in
`issued_access_tokens` so revocation is real (mirrors `RefreshToken` in the User BC), checked on
every authenticated request — not just relying on the token's expiry.

## Using a token

Same as a human bearer token: `Authorization: Bearer <access_token>` on any `/api/v1/...`
request. `App\ApiClient\Infrastructure\Security\OAuth2ClientAuthenticator` runs on the same `api`
firewall as the human-user `JwtAuthenticator`, distinguishing the two token formats before doing
any cryptographic verification (see the docblock on either class for the exact heuristic).

A successfully authenticated client is **not** automatically allowed anywhere. It gets the roles:

- `ROLE_API_CLIENT` always.
- `SCOPE_<SCOPE_UPPER_SNAKE>` for each scope on the issued token — e.g. scope `documents:write`
  becomes role `SCOPE_DOCUMENTS_WRITE`.

By default **no existing command or query accepts these roles** — every reference use case is
gated on `ROLE_USER`/`ROLE_ADMIN`, which a machine client never has. To let a client call a
specific use case, add its scope role to that message's `RoleRequirement`:

```php
public function roleRequirement(): RoleRequirement
{
    return RoleRequirement::any('ROLE_ADMIN', 'SCOPE_DOCUMENTS_WRITE');
}
```

This is the same `AuthorizedMessage`/`RoleRequirement` mechanism every other command/query
already uses (see the "Architecture" section of `CLAUDE.md`) — machine clients aren't a special
case at the authorization layer, only at authentication.

## Why `MessageAuthorizerInterface` changed

Before this feature, the sole implementation of `Shared\Domain\Security\MessageAuthorizerInterface`
(`UserAuthorizer`) lived in the User BC and only recognized `User\Infrastructure\Security\SecurityUserAdapter`
as "authenticated" — so an OAuth2 client, despite passing Symfony's firewall, would get
`401 unauthenticated` on every `AuthorizedMessage`-gated command/query instead of a role-based
`403`. It's now `Shared\Infrastructure\Security\PrincipalRoleAuthorizer`, which reads roles off
whatever `Symfony\Bundle\SecurityBundle\Security::getUser()` returns — no bounded-context
coupling, works for any current or future authenticator on the `api` firewall. Human-user
behavior is unchanged: the error code for a role failure is still `insufficient_privileges`.
