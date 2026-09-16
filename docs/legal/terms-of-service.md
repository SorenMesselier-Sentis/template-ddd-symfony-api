---
version: 2026-09-16
---

> ⚠️ **Placeholder — replace with real legal text reviewed by counsel before production use.**
> This document exists so `ConsentType::TERMS_OF_SERVICE` (see `src/User/Domain/ValueObject/ConsentType.php`)
> has real content to reference, and so a fork has the right structure to start from. It is not legal
> advice and must not be used as-is.
>
> When you replace the content below, bump `version` in the front matter above **and**
> `LegalDocumentVersion::TERMS_OF_SERVICE` in `src/User/Infrastructure/Legal/LegalDocumentVersion.php`
> in the same change — `GET /api/v1/legal/documents` and the consent-recording flow both read from
> that constant, not from this file.

# Terms of Service

## 1. Who we are

_Identify the legal entity operating this service: company name, registration number, registered
address, contact details._

## 2. Acceptance of these terms

_State when and how a user is bound by these terms (e.g. account creation, continued use)._

## 3. The service

_Describe what the service does and any usage restrictions._

## 4. User accounts and responsibilities

_Account creation, credential security, acceptable use, termination conditions._

## 5. Fees and payment

_If applicable — pricing, billing cycle, refunds. Remove this section if the service is free._

## 6. Intellectual property

_Ownership of the service itself vs. user-submitted content._

## 7. Liability and warranties

_Disclaimers, limitation of liability, applicable law._

## 8. Changes to these terms

_How and when users are notified of changes, and what re-acceptance is required — this is what
`POST /users/me/consents` with a new `version` models._

## 9. Governing law and disputes

## 10. Contact

_Where to send legal notices or questions about these terms._
