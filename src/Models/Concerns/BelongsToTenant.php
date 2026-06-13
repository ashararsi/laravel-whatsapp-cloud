<?php

namespace Vendor\LaravelWhatsAppCloud\Models\Concerns;

use Illuminate\Database\Eloquent\Model;
use Vendor\LaravelWhatsAppCloud\Models\Scopes\TenantScope;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Services\TenantContext;

trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $context = app(TenantContext::class);

            if (! $context->usesSchema()) {
                return;
            }

            $column = $context->column();

            if ($model->getAttribute($column) !== null) {
                return;
            }

            if ($context->hasTenant()) {
                $model->setAttribute($column, $context->id());

                return;
            }

            $accountId = $model->getAttribute('account_id');

            if ($accountId) {
                $account = WhatsAppAccount::query()
                    ->withoutGlobalScope(TenantScope::class)
                    ->find($accountId);

                if ($account?->tenant_id !== null) {
                    $model->setAttribute($column, $account->tenant_id);
                }
            }
        });
    }

    public function scopeForTenant($query, ?int $tenantId)
    {
        if ($tenantId === null) {
            return $query;
        }

        return $query->where(app(TenantContext::class)->column(), $tenantId);
    }
}
