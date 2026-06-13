# Laravel WhatsApp Cloud

[![Tests](https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/actions/workflows/test.yml/badge.svg?branch=main)](https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/actions/workflows/test.yml)
[![License: MIT](https://img.shields.io/badge/License-MIT-blue.svg)](LICENSE)
[![Latest Stable Version](https://img.shields.io/badge/release-v1.0.0-green.svg)](https://github.com/ashararsi/ashararsi-laravel-whatsapp-cloud/releases/tag/v1.0.0)

**v1.0.0** — First public stable release.

A production-ready **WhatsApp conversation platform** for Laravel. Send messages via **Meta Cloud API** or **Twilio WhatsApp** using one unified fluent API, with contacts, conversations, campaigns, analytics, AI automation, and a full admin panel.

## Features

- **Meta Cloud API** — Direct Graph API integration with retry, rate-limit handling, and media upload
- **Twilio Provider** — Unified API for Twilio WhatsApp messaging
- **Multi Account Support** — Multiple Meta/Twilio accounts with encrypted credentials
- **Conversations** — Inbox with message timeline and reply form
- **Contacts** — CRM with notes and tags
- **Campaigns** — Broadcast messaging with queue support
- **Analytics** — Dashboard with charts, delivery rate, and cost estimation
- **AI Auto Reply** — OpenAI-powered automatic responses
- **Workflow Engine** — Configurable multi-step AI workflows
- **Template Sync** — Admin UI and CLI to sync Meta templates
- **Scheduled Messages** — Deferred send via `whatsapp:scheduled:send`
- **Admin Panel** — Bootstrap UI for accounts, settings, templates, and system monitoring
- **Database Settings** — Runtime configuration without `.env` changes

## Requirements

- PHP 8.3+
- Laravel 13.x

## Installation

```bash
composer require ashararsi/laravel-whatsapp-cloud
php artisan whatsapp:install
php artisan migrate
```

During install you choose **single app** or **multi-tenant** mode:

- **Single app** (default) — no `whatsapp_tenants` table, no `tenant_id` columns
- **Multi-tenant** — creates tenant tables and `tenant_id` columns on migrate

Non-interactive install:

```bash
php artisan whatsapp:install --single --migrate
php artisan whatsapp:install --tenant --migrate
```

Set `WHATSAPP_TENANT_ENABLED` in `.env` **before** `migrate` if you skip the installer prompt.

Configure runtime settings at `/admin/whatsapp/settings` after migration.

## Provider Comparison

| Feature | Meta Cloud API | Twilio WhatsApp |
|---------|----------------|-----------------|
| Setup | Meta Business + Graph API token | Twilio Account SID + Auth Token |
| Templates | Native WhatsApp templates | Twilio Content SID |
| Media | URL-based (`link`) | URL-based (`MediaUrl`) |
| Location | Native location message | Sent as formatted text |
| Webhooks | Built-in (`/whatsapp/webhook`) | Built-in (`/whatsapp/twilio/webhook`) |
| Status callbacks | Meta delivery/read events | `/whatsapp/twilio/status` |
| Message ID | `wamid.*` | `SM*` (Twilio SID) |
| Best for | Direct Meta integration | Teams already on Twilio |

## Quick Start

```php
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;

// Send a text message (uses default account)
WhatsApp::send('923001234567', 'Hello World');

// Target a specific account
WhatsApp::account(1)->sendText('923001234567', 'Hello');
WhatsApp::using('marketing')->send('923001234567', 'Sale Started');

// Queue for background delivery
WhatsApp::queue()->send('923001234567', 'Queued message');

// Send a template with variables
WhatsApp::template('923001234567', 'order_confirmed', ['Ali', '#12345']);
```

### Supported Methods

```php
WhatsApp::sendText($to, $message, $previewUrl = false);
WhatsApp::sendTemplate($to, 'hello_world', 'en_US', $components);
WhatsApp::template($to, 'order_confirmed', ['Ali', '#12345']);
WhatsApp::sendImage($to, 'https://example.com/image.jpg', $caption);
WhatsApp::sendDocument($to, 'https://example.com/doc.pdf', $filename, $caption);
WhatsApp::sendAudio($to, 'https://example.com/audio.mp3');
WhatsApp::sendVideo($to, 'https://example.com/video.mp4', $caption);
WhatsApp::sendLocation($to, 24.86, 67.00, 'Office', 'Address');
```

## Meta Cloud API Setup

### 1. Create a Meta App

1. Go to [Meta for Developers](https://developers.facebook.com/)
2. Create an app with **WhatsApp** product enabled
3. Add a phone number and generate a **permanent access token**
4. Copy **Phone Number ID** and **Access Token**

### 2. Create Account (code or admin)

```php
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;

WhatsAppAccount::create([
    'name' => 'primary',
    'provider' => WhatsAppAccount::PROVIDER_META,
    'phone_number' => '923001234567',
    'phone_number_id' => 'YOUR_PHONE_NUMBER_ID',
    'access_token' => 'YOUR_ACCESS_TOKEN',
    'app_secret' => 'YOUR_META_APP_SECRET',
    'webhook_verify_token' => 'my-secret-token',
    'is_default' => true,
    'is_active' => true,
]);
```

### 3. Configure Webhook

```
GET/POST  /whatsapp/webhook
```

In **Admin → Settings** (`/admin/whatsapp/settings`):

- Set **Global App Secret** (or store per-account `app_secret` on `whatsapp_accounts` — recommended)
- Enable **Require Meta Webhook Signature** for production

## Twilio WhatsApp Setup

### 1. Enable WhatsApp in Twilio

1. Sign up at [Twilio](https://www.twilio.com/)
2. Enable WhatsApp Sandbox or register a WhatsApp sender
3. Copy **Account SID**, **Auth Token**, and **WhatsApp number**

### 2. Create Twilio Account

```php
WhatsAppAccount::create([
    'name' => 'twilio-support',
    'provider' => WhatsAppAccount::PROVIDER_TWILIO,
    'phone_number' => '923001234567',
    'twilio_sid' => 'ACxxxxxxxx',
    'twilio_token' => 'your_auth_token',
    'twilio_whatsapp_number' => '14155238886',
    'is_default' => false,
    'is_active' => true,
]);
```

### 3. Configure Twilio Webhooks

Point your Twilio WhatsApp sender to:

```
POST  /whatsapp/twilio/webhook   (inbound messages)
POST  /whatsapp/twilio/status    (delivery status callbacks)
```

Enable **Require Twilio Webhook Signature** in **Admin → Settings** (enabled by default).

Twilio signs requests with `X-Twilio-Signature` using the account **Auth Token** stored on each `WhatsAppAccount`.

### 4. Send via Twilio provider

```php
WhatsApp::using('twilio-support')->sendText('923001234567', 'Hello from Twilio');
```

## Provider Architecture

```
WhatsApp::send()
    └── WhatsAppManager
            └── ProviderFactory::make($account)
                    ├── MetaProvider
                    └── TwilioProvider
```

### Extend with custom providers

Implement `WhatsAppProviderInterface` and register in `config/whatsapp.php`:

```php
'providers' => [
    'meta' => \Vendor\LaravelWhatsAppCloud\Providers\MetaProvider::class,
    'twilio' => \Vendor\LaravelWhatsAppCloud\Providers\TwilioProvider::class,
],
```

## Conversation Platform

The package automatically tracks conversations:

| Table | Purpose |
|-------|---------|
| `whatsapp_contacts` | Per-account contact records |
| `whatsapp_conversations` | One thread per contact |
| `whatsapp_conversation_messages` | Incoming & outgoing timeline |

- **Incoming**: stored automatically from Meta and Twilio webhooks
- **Outgoing**: stored when `WhatsApp::send()` succeeds
- **Message log**: all directions stored in `whatsapp_messages` (`direction`: `incoming` / `outgoing`)
- **Reply from inbox**: conversation detail page includes a reply form (sync or queued)

Disable with `WHATSAPP_CONVERSATIONS_ENABLED=false`.

### Admin URLs

| URL | Feature |
|-----|---------|
| `/admin/whatsapp` | Dashboard (stats + 7-day message volume) |
| `/admin/whatsapp/contacts` | Contact list + search |
| `/admin/whatsapp/contacts/{id}` | Contact detail with notes and tags |
| `/admin/whatsapp/conversations` | Conversation list + search |
| `/admin/whatsapp/conversations/{id}` | Message timeline + reply form |
| `/admin/whatsapp/campaigns` | Broadcast campaigns |
| `/admin/whatsapp/templates` | Message templates (sync, search, filter) |
| `/admin/whatsapp/accounts` | Account management |
| `/admin/whatsapp/settings` | Runtime settings (database) |
| `/admin/whatsapp/system` | Queue, API, and rate-limit health |

### CRM: Notes & Tags

On the contact detail page you can:

- Add and view internal notes
- Create tags and assign them to contacts
- Remove tags from a contact

Tags are scoped per WhatsApp account.

### Template Manager

Manage Meta WhatsApp message templates from the admin panel or CLI.

| URL / Command | Purpose |
|---------------|---------|
| `/admin/whatsapp/templates` | List, search, and filter templates |
| `/admin/whatsapp/templates/{id}` | View template details and components |
| `php artisan whatsapp:templates:sync` | Sync templates from Meta |

**Categories:** `UTILITY`, `AUTHENTICATION`, `MARKETING`

**Statuses:** `APPROVED`, `PENDING`, `REJECTED`

#### Sync templates

```bash
# All active Meta accounts
php artisan whatsapp:templates:sync

# Specific account (ID or name)
php artisan whatsapp:templates:sync --account=primary

# Filter by provider
php artisan whatsapp:templates:sync --provider=meta
```

Requires a Meta account with `waba_id` (or `phone_number_id` as fallback) and a valid access token with `whatsapp_business_management` permission.

#### Send a template (simple variables)

Maps body placeholders `{{1}}`, `{{2}}`, … to an ordered variable list:

```php
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;

// Sends order_confirmed template with body variables
WhatsApp::template('923001234567', 'order_confirmed', [
    'Ali',      // {{1}}
    '#12345',   // {{2}}
]);

// Specific account + language override
WhatsApp::using('marketing')->template('923001234567', 'order_confirmed', ['Ali', '#12345'], 'en_US');
```

#### Send a template (full Meta components)

For header/button parameters, use the low-level API:

```php
WhatsApp::sendTemplate('923001234567', 'order_confirmed', 'en_US', [
    [
        'type' => 'body',
        'parameters' => [
            ['type' => 'text', 'text' => 'Ali'],
            ['type' => 'text', 'text' => '#12345'],
        ],
    ],
]);
```

#### Database table: `whatsapp_templates`

| Column | Description |
|--------|-------------|
| `account_id` | Owning WhatsApp account |
| `provider` | `meta` or `twilio` |
| `template_name` | Meta template name |
| `category` | `UTILITY`, `AUTHENTICATION`, `MARKETING` |
| `language` | BCP-47 code (e.g. `en_US`) |
| `status` | `APPROVED`, `PENDING`, `REJECTED` |
| `components_json` | Raw Meta components array |
| `meta_template_id` | Meta Graph API template ID |
| `synced_at` | Last sync timestamp |

The dashboard shows approved, pending, and rejected template counts.

### Broadcast Campaigns

Create campaigns from `/admin/whatsapp/campaigns` or run pending drafts:

```bash
php artisan whatsapp:campaigns:run
```

Enable **Queue Campaign Sends** in **Admin → Settings** to queue bulk sends.

### AI & Automation (optional)

Set `WHATSAPP_OPENAI_API_KEY` in `.env` (secret — not stored in DB). Enable features in **Admin → Settings**:

- **AI Auto Reply**
- **Audio Transcription**
- **Keyword Auto Reply**
- **Process Incoming Messages**
- **Download Incoming Media**

- **Auto-reply rules** — keyword, first-message, and AI modes (`whatsapp_auto_replies` table)
- **Workflows** — `whatsapp_ai_workflows` with step fallback when OpenAI is unavailable
- **Media download** — Meta incoming attachments saved to disk (`WHATSAPP_MEDIA_DISK` in `.env`)
- **Audio transcription** — Whisper via OpenAI when enabled

## Multi-Tenant Mode (Optional)

Choose **multi-tenant** during `php artisan whatsapp:install`, or set manually:

```env
WHATSAPP_TENANT_ENABLED=true
WHATSAPP_TENANT_RESOLVER=App\\WhatsApp\\TenantResolver
```

**Single-app mode** (default) skips `whatsapp_tenants` and all `tenant_id` columns entirely.

Implement `TenantResolverInterface` in your app:

```php
use Vendor\LaravelWhatsAppCloud\Contracts\TenantResolverInterface;

class TenantResolver implements TenantResolverInterface
{
    public function resolve(): ?int
    {
        return auth()->user()?->tenant_id;
    }
}
```

Register the resolver in a service provider:

```php
$this->app->bind(
    \Vendor\LaravelWhatsAppCloud\Contracts\TenantResolverInterface::class,
    TenantResolver::class,
);

config(['whatsapp.tenant.resolver' => TenantResolver::class]);
```

When tenant mode is enabled:

- Admin routes automatically run `ResolveWhatsAppTenant` middleware (disable with `WHATSAPP_TENANT_ADMIN_MIDDLEWARE=false`)
- Queries are scoped to the active tenant once `TenantContext` is set
- Webhooks and CLI commands still work globally (no tenant scope until you set one)
- `tenant_id` is auto-filled on create from the active tenant or related account

Run code for a specific tenant without middleware:

```php
app(\Vendor\LaravelWhatsAppCloud\Services\TenantContext::class)
    ->runForTenant($tenantId, fn () => WhatsApp::sendText('923001234567', 'Hello'));
```

> **Scope note:** Filament admin resources are not included. The package ships a Bootstrap admin panel; publish views to integrate with your own layout.

## Admin Panel

`/admin/whatsapp/accounts` — create accounts with provider-specific fields:

- **Meta**: Phone Number ID, Access Token, App Secret, Webhook Verify Token
- **Twilio**: Account SID, Auth Token, WhatsApp Number

## Customizing Admin Views & Theme

All admin UI lives in the **package**. Your Laravel app does not need to create CRUD views manually.

### Where views live

| Location | Purpose |
|----------|---------|
| Package (default) | `vendor/ashararsi/laravel-whatsapp-cloud/resources/views/` |
| Published override | `resources/views/vendor/whatsapp/` in your app |

Package views are loaded with the `whatsapp::` namespace:

```blade
@extends('whatsapp::layouts.admin')
```

Laravel uses **published views first**. If a file exists in `resources/views/vendor/whatsapp/`, it overrides the package copy.

### Publish views to your project

```bash
php artisan vendor:publish --tag=whatsapp-views
```

Published structure:

```
resources/views/vendor/whatsapp/
├── layouts/
│   └── admin.blade.php          # Master layout (sidebar, menu, alerts)
└── admin/
    ├── dashboard.blade.php
    ├── accounts/
    ├── contacts/
    └── conversations/
```

Re-publish after package updates (overwrites your copies):

```bash
php artisan vendor:publish --tag=whatsapp-views --force
```

Back up customized files before using `--force`.

### Option 1 — Enhance the package master layout

Edit the published master layout:

```
resources/views/vendor/whatsapp/layouts/admin.blade.php
```

This file controls:

- Sidebar / top navigation
- Active menu state
- Flash messages (`success`, `error`)
- Page wrapper around `@yield('content')`
- `@yield('title')` for the page heading

Child pages only fill the content section. Example account list:

```blade
@extends('whatsapp::layouts.admin')

@section('title', 'WhatsApp Accounts')

@section('content')
    {{-- your table / forms here --}}
@endsection
```

Add your CSS, JS, fonts, or branding inside `admin.blade.php` (or link your existing admin assets).

### Option 2 — Use your app's main admin theme (recommended)

If your project already has a layout (Filament, AdminLTE, custom `layouts.app`, etc.), point package pages to **your** layout instead of the package default.

**Step 1.** Publish views:

```bash
php artisan vendor:publish --tag=whatsapp-views
```

**Step 2.** Change `@extends` in published admin pages. Example for accounts index:

```blade
{{-- resources/views/vendor/whatsapp/admin/accounts/index.blade.php --}}
@extends('layouts.app')   {{-- your app master layout --}}

@section('content')
    @include('whatsapp::admin.accounts.partials.header')
    {{-- keep existing table markup from the published file --}}
@endsection
```

**Step 3.** Move package navigation into your sidebar. Reuse the same routes:

```blade
<a href="{{ route('whatsapp.admin.dashboard') }}">Dashboard</a>
<a href="{{ route('whatsapp.admin.contacts.index') }}">Contacts</a>
<a href="{{ route('whatsapp.admin.conversations.index') }}">Conversations</a>
<a href="{{ route('whatsapp.admin.accounts.index') }}">Accounts</a>
```

**Step 4.** Optionally replace only the master layout by making your layout extend the package structure, or delete sidebar from `layouts/admin.blade.php` and `@include` your global header/sidebar.

### Option 3 — Override a single page

You do not need to publish everything. Publish once, then edit only the pages you care about:

```
resources/views/vendor/whatsapp/admin/accounts/_form.blade.php
```

Unpublished pages still load from the package automatically.

### View resolution order

```
1. resources/views/vendor/whatsapp/...   (your app — wins)
2. vendor/ashararsi/laravel-whatsapp-cloud/resources/views/...   (package default)
```

### After customization

```bash
php artisan view:clear
```

### Tips

- Keep `@section('content')` and `@section('title')` when overriding child views.
- Do not rename route names (`whatsapp.admin.*`); controllers depend on them.
- For provider fields (Meta / Twilio), customize `admin/accounts/_form.blade.php`.
- For conversation bubbles styling, see CSS classes `timeline-incoming` and `timeline-outgoing` in the master layout.

## Incoming Messages

Every webhook delivery is logged to `whatsapp_messages` when **Log Outgoing Messages** is enabled in **Admin → Settings**:

| Column | Description |
|--------|-------------|
| `direction` | `incoming` or `outgoing` |
| `from` / `to` | Sender and recipient phone |
| `whatsapp_message_id` | Meta `wamid.*` or Twilio `SM*` (unique) |
| `meta_json` | Raw webhook payload |
| `status` | `received`, `sent`, `delivered`, `read`, `failed` |

Duplicate webhook deliveries are ignored via unique `whatsapp_message_id` constraints.

## Doctor Command

Run a full health check before production:

```bash
php artisan whatsapp:doctor
```

Checks database tables, queues, routes, webhook secrets, Meta/Twilio credentials, storage, and cache. Output levels: **PASS**, **WARNING**, **ERROR**.

## Runtime Settings (Database)

Operational settings are stored in the `whatsapp_settings` table and managed from the admin panel. **No `.env` variables are required** for these — changes apply at runtime without redeploying.

**Admin URL:** `/admin/whatsapp/settings`

| Setting key | Default | Group |
|-------------|---------|-------|
| `general.default_account` | *(empty)* | General |
| `general.default_provider` | `meta` | General |
| `general.api_version` | `v21.0` | General |
| `webhook.app_secret` | *(empty)* | Webhook |
| `webhook.require_signature` | `false` | Webhook |
| `twilio.require_signature` | `true` | Twilio |
| `graph_api.timeout` | `30` | Graph API |
| `graph_api.max_retries` | `3` | Graph API |
| `queue.enabled` | `true` | Queue |
| `queue.tries` | `3` | Queue |
| `campaigns.use_queue` | `false` | Campaigns |
| `cost.utility` / `cost.marketing` | `0.005` / `0.015` | Cost |
| `ai.enabled` | `false` | AI |
| `ai.transcription_enabled` | `false` | AI |
| `auto_reply.enabled` | `true` | Auto Reply |
| `media.enabled` | `true` | Media |
| `events.process_incoming` | `true` | Events |
| `log_messages` | `true` | Logging |
| `admin.authorization_enabled` | `true` | Admin |

```php
// Programmatic access
app(\Vendor\LaravelWhatsAppCloud\Services\WhatsAppSettingsService::class)->get('queue.enabled');
app(\Vendor\LaravelWhatsAppCloud\Services\WhatsAppSettingsService::class)->updateMany([
    'webhook.require_signature' => true,
]);
```

After `php artisan migrate`, defaults are seeded automatically.

## Environment Variables (infrastructure only)

Use `.env` only for **host-app infrastructure and secrets** — not runtime feature toggles:

```env
# Queue infrastructure
WHATSAPP_QUEUE_CONNECTION=
WHATSAPP_QUEUE_NAME=default

# Admin routing (optional)
WHATSAPP_ADMIN_PREFIX=admin/whatsapp
WHATSAPP_ADMIN_GATE=manage-whatsapp

# Webhook route prefix
WHATSAPP_WEBHOOK_PREFIX=whatsapp

# Storage & API base
WHATSAPP_MEDIA_DISK=local
WHATSAPP_API_BASE_URL=https://graph.facebook.com

# OpenAI secret (never store in DB)
WHATSAPP_OPENAI_API_KEY=

# Optional infrastructure
WHATSAPP_CONVERSATIONS_ENABLED=true
WHATSAPP_CACHE_ENABLED=true
WHATSAPP_CACHE_TTL=300
```

## Notifications

```php
use Vendor\LaravelWhatsAppCloud\Notifications\WhatsAppChannel;

public function via($notifiable): array
{
    return [WhatsAppChannel::class];
}

public function toWhatsApp($notifiable): array
{
    return [
        'using' => 'twilio-support', // or account ID
        'text' => 'Your order shipped!',
        'queue' => true,
    ];
}
```

## Webhooks

### Meta

```
GET/POST  /whatsapp/webhook
```

Per-account `app_secret` on `whatsapp_accounts` is used for `X-Hub-Signature-256` verification. Falls back to the **Global App Secret** from **Admin → Settings** when the account secret is empty.

```php
use Vendor\LaravelWhatsAppCloud\Events\MessageReceived;

Event::listen(MessageReceived::class, function (MessageReceived $event) {
    // Handle incoming message (Meta or Twilio)
});
```

### Twilio

```
POST  /whatsapp/twilio/webhook
POST  /whatsapp/twilio/status
```

Supports inbound text, media, and location payloads plus status callbacks: `queued`, `sent`, `delivered`, `failed`, `undelivered`.

## Testing & Quality

```bash
composer test
composer analyse    # PHPStan
composer format     # Laravel Pint
```

## Security Recommendations

1. Enable **Require Meta Webhook Signature** in **Admin → Settings** for production.
2. Store a unique `app_secret` per Meta account when running multiple apps.
3. Keep **Require Twilio Webhook Signature** enabled (default) for Twilio webhooks.
4. Never commit access tokens or auth tokens — they are encrypted on `whatsapp_accounts`.
5. Keep **Require Admin Authorization** enabled and protect admin routes with your gate.
6. Run `php artisan whatsapp:doctor` after deploy to verify configuration.

See [SECURITY.md](SECURITY.md) and [UPGRADE.md](UPGRADE.md).

## Screenshots

> Placeholder — add screenshots of the admin dashboard, conversation inbox, and account management UI here before release.

| Screenshot | Description |
|------------|-------------|
| `docs/screenshots/dashboard.png` | Admin dashboard with stats |
| `docs/screenshots/inbox.png` | Conversation timeline with reply form |
| `docs/screenshots/accounts.png` | Multi-account provider setup |

## License

This project is licensed under the [MIT License](LICENSE).

Copyright (c) 2026 Ashar Arsi
