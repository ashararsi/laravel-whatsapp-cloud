<?php

namespace Vendor\LaravelWhatsAppCloud\Commands;

use Illuminate\Console\Command;
use Vendor\LaravelWhatsAppCloud\Support\EnvWriter;

class InstallCommand extends Command
{
    protected $signature = 'whatsapp:install
                            {--single : Install for a single app (no tenant columns)}
                            {--tenant : Install with multi-tenant support}
                            {--migrate : Run migrations after publishing files}';

    protected $description = 'Publish WhatsApp Cloud package config, migrations, and views';

    public function handle(): int
    {
        $tenantEnabled = $this->resolveTenantMode();

        $this->call('vendor:publish', [
            '--tag' => 'whatsapp-config',
        ]);

        $this->applyTenantSetting($tenantEnabled);

        $this->call('vendor:publish', [
            '--tag' => 'whatsapp-migrations',
        ]);

        $this->call('vendor:publish', [
            '--tag' => 'whatsapp-views',
        ]);

        $modeLabel = $tenantEnabled ? 'multi-tenant' : 'single-app';

        $this->components->info("WhatsApp Cloud installed in {$modeLabel} mode.");
        $this->components->bulletList([
            'WHATSAPP_TENANT_ENABLED='.($tenantEnabled ? 'true' : 'false'),
            $tenantEnabled
                ? 'Tenant tables and tenant_id columns will be created on migrate.'
                : 'No tenant tables or tenant_id columns will be created on migrate.',
        ]);

        if ($this->option('migrate')) {
            $this->call('migrate');
        } else {
            $this->components->warn('Run `php artisan migrate` to create database tables.');
        }

        if ($tenantEnabled) {
            $this->components->warn('Implement TenantResolverInterface and set WHATSAPP_TENANT_RESOLVER in .env.');
        }

        return self::SUCCESS;
    }

    protected function resolveTenantMode(): bool
    {
        if ($this->option('single')) {
            return false;
        }

        if ($this->option('tenant')) {
            return true;
        }

        if ($this->input->isInteractive()) {
            $choice = $this->choice(
                'How should WhatsApp run in your app?',
                [
                    'single' => 'Single app — one business, no tenant columns',
                    'tenant' => 'Multi-tenant — isolated data per tenant',
                ],
                'single',
            );

            return $choice === 'tenant';
        }

        return false;
    }

    protected function applyTenantSetting(bool $tenantEnabled): void
    {
        $value = $tenantEnabled ? 'true' : 'false';

        config(['whatsapp.tenant.enabled' => $tenantEnabled]);

        if (EnvWriter::set('WHATSAPP_TENANT_ENABLED', $value, $this->envPath())) {
            $this->components->info('Updated WHATSAPP_TENANT_ENABLED in .env');

            return;
        }

        $this->components->warn(
            'Could not update .env automatically. Set WHATSAPP_TENANT_ENABLED='.$value.' before running migrate.',
        );
    }

    protected function envPath(): string
    {
        if (method_exists($this->laravel, 'environmentFilePath')) {
            return $this->laravel->environmentFilePath();
        }

        return base_path('.env');
    }
}
