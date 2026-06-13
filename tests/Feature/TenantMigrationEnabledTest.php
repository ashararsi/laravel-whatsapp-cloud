<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class TenantMigrationEnabledTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('whatsapp.tenant.enabled', true);
    }

    #[Test]
    public function tenant_mode_migrations_create_tenant_schema(): void
    {
        $this->artisan('migrate:fresh')->assertSuccessful();

        $this->assertTrue(Schema::hasTable('whatsapp_tenants'));
        $this->assertTrue(Schema::hasColumn('whatsapp_accounts', 'tenant_id'));
        $this->assertTrue(Schema::hasColumn('whatsapp_contacts', 'tenant_id'));
        $this->assertTrue(Schema::hasColumn('whatsapp_campaigns', 'tenant_id'));
    }
}
