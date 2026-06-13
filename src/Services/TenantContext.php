<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Illuminate\Support\Facades\Schema;
use Vendor\LaravelWhatsAppCloud\Contracts\TenantResolverInterface;

class TenantContext
{
    protected ?int $tenantId = null;

    public function enabled(): bool
    {
        return (bool) config('whatsapp.tenant.enabled', false);
    }

    public function column(): string
    {
        return (string) config('whatsapp.tenant.column', 'tenant_id');
    }

    public function set(?int $tenantId): void
    {
        $this->tenantId = $tenantId;
    }

    public function id(): ?int
    {
        return $this->tenantId;
    }

    public function hasTenant(): bool
    {
        return $this->tenantId !== null;
    }

    public function shouldScope(): bool
    {
        return $this->usesSchema() && $this->hasTenant();
    }

    public function usesSchema(): bool
    {
        if (! $this->enabled()) {
            return false;
        }

        return Schema::hasTable('whatsapp_tenants')
            && Schema::hasColumn('whatsapp_accounts', $this->column());
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    public function runForTenant(?int $tenantId, callable $callback): mixed
    {
        $previous = $this->tenantId;
        $this->tenantId = $tenantId;

        try {
            return $callback();
        } finally {
            $this->tenantId = $previous;
        }
    }

    public function resolveFromContainer(): void
    {
        if (! $this->enabled()) {
            return;
        }

        $resolver = config('whatsapp.tenant.resolver');

        if (! is_string($resolver) || $resolver === '') {
            return;
        }

        if (! app()->bound($resolver)) {
            return;
        }

        $resolved = app($resolver);

        if (! $resolved instanceof TenantResolverInterface) {
            return;
        }

        $tenantId = $resolved->resolve();

        if ($tenantId !== null) {
            $this->set($tenantId);
        }
    }
}
