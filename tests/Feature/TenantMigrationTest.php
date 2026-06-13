<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class TenantMigrationTest extends TestCase
{
    #[Test]
    public function single_mode_migrations_do_not_create_tenant_schema(): void
    {
        $this->app['config']->set('whatsapp.tenant.enabled', false);

        $this->artisan('migrate:fresh')->assertSuccessful();

        $this->assertFalse(Schema::hasTable('whatsapp_tenants'));
        $this->assertFalse(Schema::hasColumn('whatsapp_accounts', 'tenant_id'));
        $this->assertFalse(Schema::hasColumn('whatsapp_contacts', 'tenant_id'));
    }
}
