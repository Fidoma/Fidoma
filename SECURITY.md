# Security Policy

## Supported versions

Fidoma is actively developed and currently maintains a single release line. Security fixes are applied to the latest released version and the `main` branch; older versions are not guaranteed to receive backports.

Before reporting a problem, confirm it against the latest release or current `main` when it is safe to do so.

## Reporting a vulnerability

Please do **not** open a public issue, discussion or pull request for a suspected vulnerability.

1. Use GitHub's private vulnerability reporting form at <https://github.com/Fidoma/Fidoma/security/advisories/new> if it is available to you.
2. If private reporting is unavailable, contact the maintainers through <https://fidoma.de> and request a private security contact channel. Do not include exploit details or secrets in the initial public-facing message.

Include enough information to reproduce and assess the report:

- affected Fidoma version or commit;
- affected component and configuration;
- reproduction steps or a minimal proof of concept;
- expected and observed impact;
- relevant logs with credentials and personal data removed;
- whether the issue is already public or being actively exploited.

The maintainers will acknowledge the report when it is received, coordinate validation and remediation privately, and credit reporters when requested and appropriate. Response or resolution times cannot currently be guaranteed.

## Scope

Reports about Fidoma application code and the provided container/deployment configuration are in scope. Vulnerabilities in Laravel, Filament, PHP, MySQL, browsers or external services should also be reported to the respective upstream project; please notify Fidoma privately when the issue materially affects a supported deployment.

Do not access data that is not yours, disrupt production systems, use social engineering, or perform destructive testing.
