<?php

namespace Vendor\LaravelWhatsAppCloud\Tests\Feature;

use PHPUnit\Framework\Attributes\Test;
use Vendor\LaravelWhatsAppCloud\Tests\TestCase;

class InstallCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        file_put_contents($this->app->basePath('.env'), implode(PHP_EOL, [
            'APP_KEY=base64:'.base64_encode(str_repeat('a', 32)),
            'WHATSAPP_TENANT_ENABLED=false',
        ]).PHP_EOL);
    }

    #[Test]
    public function it_installs_in_single_mode_with_option(): void
    {
        $this->artisan('whatsapp:install', [
            '--single' => true,
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertStringContainsString(
            'WHATSAPP_TENANT_ENABLED=false',
            (string) file_get_contents($this->app->basePath('.env')),
        );
        $this->assertFalse(config('whatsapp.tenant.enabled'));
    }

    #[Test]
    public function it_installs_in_tenant_mode_with_option(): void
    {
        $this->artisan('whatsapp:install', [
            '--tenant' => true,
            '--no-interaction' => true,
        ])->assertSuccessful();

        $this->assertStringContainsString(
            'WHATSAPP_TENANT_ENABLED=true',
            (string) file_get_contents($this->app->basePath('.env')),
        );
        $this->assertTrue(config('whatsapp.tenant.enabled'));
    }
}
