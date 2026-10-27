# Changelog

This changelog is based on the repository's tags, Git history and published GitHub releases. It records user-relevant changes; the Git history remains the source for implementation-level detail.

## [Unreleased]

### Added

- Customer analytics and a reservation statistics dashboard with occupancy, room, volume and value views.
- Tourism statistics with reservation-based prefill, plausibility checks and CSV export.
- Detailed invoice and reservation slide-overs, including an invoice issue action.
- A contact form for pricing enquiries on the public website.

### Changed

- Improved reservation sorting and refreshed documentation screenshots.
- Coupled project assignment visibility to the Projects module setting.
- Simplified list actions by moving record details into the new detail views.

### Fixed

- Preserved records correctly when using “create another”.
- Isolated public contact mail delivery and improved compatibility with hosted mail transports.

## [1.0.0] - 2026-10-24

Initial public release.

### Added

- Feature-first ERP foundation built with Laravel, Filament and Livewire.
- Customer, project, product, quote, invoice, payment and master-data management.
- Invoice PDFs, configurable HTML/CSS layouts, email delivery, XRechnung, credit notes, cancellations and progress/final invoices.
- CSV bank-transaction import and assignment to invoices, payments and Paperless documents.
- Room and reservation management with a visual schedule and invoice conversion.
- Contract templates and contracts with custom fields.
- Dashboard and invoice, payment, sales, tax and VAT advance-return reporting.
- Paperless-ngx, ZVTAPI Gateway and embedded Roundcube integrations.
- English/German interface translations, module visibility settings and optional two-factor authentication.
- Docker production deployment, Laravel Sail development environment, GHCR images, CI, release automation and VitePress documentation.

[Unreleased]: https://github.com/Fidoma/Fidoma/compare/v1.0.0...HEAD
[1.0.0]: https://github.com/Fidoma/Fidoma/releases/tag/v1.0.0
