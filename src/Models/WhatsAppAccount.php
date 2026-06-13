<?php

namespace Vendor\LaravelWhatsAppCloud\Models;

use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Vendor\LaravelWhatsAppCloud\Models\Concerns\BelongsToTenant;
use Vendor\LaravelWhatsAppCloud\Observers\WhatsAppAccountObserver;
use Vendor\LaravelWhatsAppCloud\Services\TenantContext;

/**
 * @property int $id
 * @property string $name
 * @property string|null $provider
 * @property string $phone_number
 * @property string|null $phone_number_id
 * @property string|null $waba_id
 * @property string|null $access_token
 * @property string|null $app_secret
 * @property string|null $webhook_verify_token
 * @property string|null $twilio_sid
 * @property string|null $twilio_token
 * @property string|null $twilio_whatsapp_number
 * @property bool $is_default
 * @property bool $is_active
 */
#[ObservedBy([WhatsAppAccountObserver::class])]
class WhatsAppAccount extends Model
{
    use BelongsToTenant;

    public const PROVIDER_META = 'meta';

    public const PROVIDER_TWILIO = 'twilio';

    protected $table = 'whatsapp_accounts';

    protected $fillable = [
        'name',
        'provider',
        'phone_number',
        'phone_number_id',
        'waba_id',
        'tenant_id',
        'access_token',
        'app_secret',
        'webhook_verify_token',
        'twilio_sid',
        'twilio_token',
        'twilio_whatsapp_number',
        'is_default',
        'is_active',
    ];

    /**
     * @var list<string>
     */
    protected $hidden = [
        'access_token',
        'app_secret',
        'twilio_token',
    ];

    protected function casts(): array
    {
        return [
            'access_token' => 'encrypted',
            'app_secret' => 'encrypted',
            'twilio_token' => 'encrypted',
            'is_default' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(WhatsAppTenant::class, 'tenant_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WhatsAppMessage::class, 'account_id');
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(WhatsAppContact::class, 'account_id');
    }

    public function conversations(): HasMany
    {
        return $this->hasMany(WhatsAppConversation::class, 'account_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    public function isMeta(): bool
    {
        return ($this->provider ?? self::PROVIDER_META) === self::PROVIDER_META;
    }

    public function isTwilio(): bool
    {
        return $this->provider === self::PROVIDER_TWILIO;
    }

    public function providerLabel(): string
    {
        return match ($this->provider ?? self::PROVIDER_META) {
            self::PROVIDER_TWILIO => 'Twilio WhatsApp',
            default => 'Meta Cloud API',
        };
    }

    public static function setDefault(self $account): void
    {
        $query = static::query()->where('id', '!=', $account->id);

        $context = app(TenantContext::class);

        if ($context->usesSchema() && $account->tenant_id !== null) {
            $query->where($context->column(), $account->tenant_id);
        }

        $query->update(['is_default' => false]);
        $account->update(['is_default' => true]);
    }
}
