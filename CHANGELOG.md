# Changelog

All notable changes to this project are documented in this file.
The format is based on [Keep a Changelog](https://keepachangelog.com/).

## [Unreleased]

### Added
- Format validation of the SIREN/SIRET (Luhn key) and of the VAT number (French key) on `Company`, without any network call (`ValidCompanyIdentifiers`, validation group `sylius`).
- Lookup of the SIREN/SIRET in the API Recherche d'entreprises (`HttpRegisterClient`): active, closed (with closing date), not found, not applicable, not verified.
- Check of the VAT number in VIES through the REST API of the European Commission (`HttpViesClient`): valid, invalid, no VAT number, not verified.
- History of the runs (`tmonier_company_verification`, migration included), with the identifiers as checked, the sources and the date.
- `VerifyCompany` message and handler, dispatched on `sylius.command_bus` when a company is created or when its `vatNumber` or `registrationNumber` changes.
- Panel in the company edit page of the administration: statuses in words, name and address returned, sources, date, and a *Verify again* button (POST, CSRF token, `ROLE_ADMINISTRATION_ACCESS`).
- Console command `tmonier:company-verification:verify-unverified` for the companies never verified or whose latest run could not reach a service.
- Configuration keys `register_url`, `vies_url` and `timeout` (15 seconds by default: VIES takes 7 to 8 seconds for France).
- Optional *Identity* column in *Customers > Registrations* of `tmonier/sylius-registration-approval-plugin` (needs Sylius 2.3; the column declaration is tested, the rendering in that grid is not).
- English and French translations.

### Notes
- An unreachable service gives "not verified", never "invalid"; no decision is taken automatically.
- Only public company identifiers are sent to the services; the raw responses are not stored.
