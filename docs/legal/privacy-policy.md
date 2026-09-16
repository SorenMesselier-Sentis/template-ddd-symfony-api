---
version: 2026-09-16
---

> ⚠️ **Placeholder — replace with real legal text reviewed by counsel before production use.**
> This document exists so `ConsentType::PRIVACY_POLICY` (see `src/User/Domain/ValueObject/ConsentType.php`)
> has real content to reference, and so a fork has the right structure to start from. It is not legal
> advice and must not be used as-is.
>
> When you replace the content below, bump `version` in the front matter above **and**
> `LegalDocumentVersion::PRIVACY_POLICY` in `src/User/Infrastructure/Legal/LegalDocumentVersion.php`
> in the same change — `GET /api/v1/legal/documents` and the consent-recording flow both read from
> that constant, not from this file.

# Privacy Policy

## 1. Data controller

_Identify who is responsible for the personal data processed by this service (see "Who we are" in
the Terms of Service)._

## 2. What personal data we collect

_List the categories the codebase actually stores — cross-check against every
`PersonalDataExporterInterface` implementation (`GET /users/me/export`), e.g.:_
- _Account data: name, email (`User` bounded context)_
- _Uploaded files and their metadata (`Document` bounded context)_
- _Project/task data referencing a user by id (`Project` bounded context)_
- _Consent records themselves (`Consent` entity)_

## 3. Purpose and legal basis for processing

_For each category above, state why it's processed and which GDPR Article 6 basis applies
(contract, consent, legitimate interest, legal obligation)._

## 4. Data retention

_State the retention window. This template automatically anonymizes soft-deleted account data after
`GDPR_RETENTION_DAYS` days (default 30 — see `.env` and
`src/Shared/Infrastructure/Scheduler/Handler/CleanupExpiredPersonalDataHandler.php`); state the
equivalent for any other personal data your fork stores._

## 5. User rights

_Right of access (`GET /users/me/export`), right to erasure (`DELETE /users/{id}`, soft delete +
automatic anonymization), right to withdraw consent (`DELETE /users/me/consents/{type}`), right to
rectification (`PATCH /users/me`), and how to exercise rights not yet automated (portability,
objection)._

## 6. Consent

_Which processing relies on consent (see `ConsentType` — terms of service, privacy policy, marketing
emails) and how it can be withdrawn at any time without affecting other processing._

## 7. Data sharing and sub-processors

_Any third parties data is shared with (hosting, email delivery, error tracking, etc.)._

## 8. International transfers

_If applicable._

## 9. Security measures

_High-level description — no operational detail that would help an attacker._

## 10. Changes to this policy

_How and when users are notified of changes — mirrors "Changes to these terms" in the Terms of
Service._

## 11. Contact

_Data protection contact / DPO, and the relevant supervisory authority._
