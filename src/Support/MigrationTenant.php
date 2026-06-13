<?php

namespace Vendor\LaravelWhatsAppCloud\Support;

class MigrationTenant
{
    public static function enabled(): bool
    {
        return (bool) config('whatsapp.tenant.enabled', false);
    }

    public static function column(): string
    {
        return (string) config('whatsapp.tenant.column', 'tenant_id');
    }

    public static function table(): string
    {
        return 'whatsapp_tenants';
    }
}
