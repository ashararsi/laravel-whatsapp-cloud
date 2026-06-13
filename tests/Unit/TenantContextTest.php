<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppTenant;
use Vendor\LaravelWhatsAppCloud\Services\AccountResolver;
use Vendor\LaravelWhatsAppCloud\Services\TenantContext;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class TenantContextTest extends TestCase
{
    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);
        $app['config']->set('whatsapp.tenant.enabled', true);
    }

    #[Test]
    public function single_tenant_mode_does_not_scope_queries(): void
    {
        $this->app['config']->set('whatsapp.tenant.enabled', false);

        WhatsAppTenant::query()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $tenantB = WhatsAppTenant::query()->create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        WhatsAppAccount::query()->create([
            'name' => 'tenant-a-account',
            'tenant_id' => 1,
            'phone_number' => '923001234567',
            'phone_number_id' => '111',
            'provider' => 'meta',
            'access_token' => 'token-a-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'tenant-b-account',
            'tenant_id' => $tenantB->id,
            'phone_number' => '923009999999',
            'phone_number_id' => '222',
            'provider' => 'meta',
            'access_token' => 'token-b-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->assertSame(2, WhatsAppAccount::query()->count());
    }

    #[Test]
    public function tenant_mode_scopes_queries_when_tenant_is_set(): void
    {
        $this->app['config']->set('whatsapp.tenant.enabled', true);

        $tenantA = WhatsAppTenant::query()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $tenantB = WhatsAppTenant::query()->create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        WhatsAppAccount::query()->create([
            'name' => 'tenant-a-account',
            'tenant_id' => $tenantA->id,
            'phone_number' => '923001234567',
            'phone_number_id' => '111',
            'provider' => 'meta',
            'access_token' => 'token-a-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'tenant-b-account',
            'tenant_id' => $tenantB->id,
            'phone_number' => '923009999999',
            'phone_number_id' => '222',
            'provider' => 'meta',
            'access_token' => 'token-b-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        /** @var TenantContext $context */
        $context = app(TenantContext::class);
        $context->set($tenantA->id);

        $this->assertSame(1, WhatsAppAccount::query()->count());
        $this->assertSame('tenant-a-account', WhatsAppAccount::query()->value('name'));
    }

    #[Test]
    public function tenant_mode_without_active_tenant_does_not_scope_queries(): void
    {
        $this->app['config']->set('whatsapp.tenant.enabled', true);

        $tenant = WhatsAppTenant::query()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);

        WhatsAppAccount::query()->create([
            'name' => 'scoped-account',
            'tenant_id' => $tenant->id,
            'phone_number' => '923001234567',
            'phone_number_id' => '111',
            'provider' => 'meta',
            'access_token' => 'token-a-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'global-account',
            'phone_number' => '923009999999',
            'phone_number_id' => '222',
            'provider' => 'meta',
            'access_token' => 'token-b-1234567890',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->assertSame(2, WhatsAppAccount::query()->count());
    }

    #[Test]
    public function account_resolver_respects_tenant_scope(): void
    {
        $this->app['config']->set('whatsapp.tenant.enabled', true);

        $tenantA = WhatsAppTenant::query()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $tenantB = WhatsAppTenant::query()->create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        WhatsAppAccount::query()->create([
            'name' => 'tenant-a-primary',
            'tenant_id' => $tenantA->id,
            'phone_number' => '923001234567',
            'phone_number_id' => '111',
            'provider' => 'meta',
            'access_token' => 'token-a-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        WhatsAppAccount::query()->create([
            'name' => 'tenant-b-primary',
            'tenant_id' => $tenantB->id,
            'phone_number' => '923009999999',
            'phone_number_id' => '222',
            'provider' => 'meta',
            'access_token' => 'token-b-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        app(TenantContext::class)->set($tenantB->id);

        $account = app(AccountResolver::class)->resolve('tenant-b-primary');

        $this->assertSame($tenantB->id, $account->tenant_id);
    }

    #[Test]
    public function creating_contacts_inherits_tenant_from_account(): void
    {
        $this->app['config']->set('whatsapp.tenant.enabled', true);

        $tenant = WhatsAppTenant::query()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);

        $account = WhatsAppAccount::query()->create([
            'name' => 'primary',
            'tenant_id' => $tenant->id,
            'phone_number' => '923001234567',
            'phone_number_id' => '111',
            'provider' => 'meta',
            'access_token' => 'token-a-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $contact = WhatsAppContact::query()->create([
            'account_id' => $account->id,
            'phone' => '923009999999',
        ]);

        $this->assertSame($tenant->id, $contact->tenant_id);
    }

    #[Test]
    public function set_default_is_scoped_to_same_tenant(): void
    {
        $tenantA = WhatsAppTenant::query()->create(['name' => 'Tenant A', 'slug' => 'tenant-a']);
        $tenantB = WhatsAppTenant::query()->create(['name' => 'Tenant B', 'slug' => 'tenant-b']);

        $accountA = WhatsAppAccount::query()->create([
            'name' => 'a-default',
            'tenant_id' => $tenantA->id,
            'phone_number' => '923001234567',
            'phone_number_id' => '111',
            'provider' => 'meta',
            'access_token' => 'token-a-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $accountB = WhatsAppAccount::query()->create([
            'name' => 'b-default',
            'tenant_id' => $tenantB->id,
            'phone_number' => '923009999999',
            'phone_number_id' => '222',
            'provider' => 'meta',
            'access_token' => 'token-b-1234567890',
            'is_default' => true,
            'is_active' => true,
        ]);

        $accountA2 = WhatsAppAccount::query()->create([
            'name' => 'a-secondary',
            'tenant_id' => $tenantA->id,
            'phone_number' => '923001111111',
            'phone_number_id' => '333',
            'provider' => 'meta',
            'access_token' => 'token-c-1234567890',
            'is_default' => false,
            'is_active' => true,
        ]);

        WhatsAppAccount::setDefault($accountA2);

        $this->assertTrue($accountA2->fresh()->is_default);
        $this->assertFalse($accountA->fresh()->is_default);
        $this->assertTrue($accountB->fresh()->is_default);
    }
}
