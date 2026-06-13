# Changelog

All notable changes to `ashararsi/laravel-whatsapp-cloud` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added
- Interactive `whatsapp:install` prompt to choose single-app or multi-tenant mode
- Conditional migrations: tenant tables/columns created only when `WHATSAPP_TENANT_ENABLED=true`
- Upgrade migration to add tenant support to existing single-app installs

### Fixed
- MySQL template migration: add standalone `account_id` index before dropping composite unique key

## [1.0.0] - 2026-06-09

First public stable release on Packagist.

### Added

#### Core Messaging
- Fluent `WhatsApp` facade API for text, template, image, document, audio, video, and location messages
- Meta Cloud API provider with direct Graph API integration (`GraphApiClient`)
- Twilio WhatsApp provider with unified provider architecture
- Multi-account support with encrypted credential storage
- Queue support via `SendWhatsAppMessageJob` with retry and dead-letter handling
- Laravel notification channel (`WhatsAppChannel`)
- Media upload API (`sendFile`, `sendImageFile`, `sendDocumentFile`)
- Interactive Meta buttons and list messages

#### Webhooks & Security
- Meta webhook verification and signature validation (`X-Hub-Signature-256`)
- Twilio inbound and status webhooks with signature validation
- Webhook idempotency for duplicate message deliveries
- Incoming and outgoing message logging with direction tracking

#### CRM & Conversations
- Contacts with notes and tags (admin UI)
- Conversation inbox with message timeline and reply form
- Conversation full-text search
- Broadcast campaigns with admin UI and queue option
- Scheduled messages (`whatsapp:scheduled:send`)

#### Templates & Sync
- Template Manager admin UI with search, filter, and sync
- `WhatsApp::template()` API for simple body variable substitution
- `whatsapp:templates:sync`, `whatsapp:sync-business`, `whatsapp:sync-numbers` commands
- Business profile and phone number sync from Meta

#### Analytics & Monitoring
- Analytics dashboard with 7-day charts and cost estimation
- System monitoring page (queue, API, webhook, rate-limit health)
- Database-backed runtime settings (`/admin/whatsapp/settings`)

#### AI & Automation
- AI auto-reply engine (OpenAI)
- Audio transcription (Whisper)
- Keyword and first-message auto-reply rules
- AI workflow engine with configurable steps
- Incoming message processing pipeline

#### Admin Panel
- Bootstrap admin UI: dashboard, accounts, contacts, conversations, campaigns, templates, settings, system monitor
- Publishable views for host-app customization
- `php artisan whatsapp:install`, `whatsapp:test`, `whatsapp:doctor` commands

#### Developer Experience
- 140 PHPUnit tests, PHPStan level 5, Laravel Pint
- GitHub Actions CI workflow
- Comprehensive README, UPGRADE, SECURITY, and CONTRIBUTING docs

### Security
- Encrypted access tokens and secrets at rest
- Per-account Meta `app_secret` for webhook verification
- Admin authorization gate and middleware
- Sanitized admin error responses (no token leakage)

### Note
- Multi-tenant data isolation is optional (`WHATSAPP_TENANT_ENABLED=true`); disabled by default for single-app installs
- Filament admin plugin is **not** included; use the built-in Bootstrap admin panel or publish views

[1.0.0]: https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/releases/tag/v1.0.0
