<?php

namespace Vendor\LaravelWhatsAppCloud\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Vendor\LaravelWhatsAppCloud\Facades\WhatsApp;
use Vendor\LaravelWhatsAppCloud\WhatsAppServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            WhatsAppServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app): array
    {
        return [
            'WhatsApp' => WhatsApp::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        $app['config']->set('whatsapp.log_messages', true);
        $app['config']->set('whatsapp.queue_enabled', false);
        $app['config']->set('whatsapp.admin.enabled', true);
        $app['config']->set('whatsapp.admin.authorization_enabled', false);
        $app['config']->set('whatsapp.cache.enabled', false);
        $app['config']->set('whatsapp.webhook.require_signature', false);
        $app['config']->set('whatsapp.twilio.require_signature', false);
        $app['config']->set('whatsapp.events.process_incoming', false);
        $app['config']->set('whatsapp.media.enabled', false);
        $app['config']->set('whatsapp.ai.enabled', false);
        $app['config']->set('whatsapp.tenant.enabled', false);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
