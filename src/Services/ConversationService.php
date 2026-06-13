<?php

namespace Vendor\LaravelWhatsAppCloud\Services;

use Vendor\LaravelWhatsAppCloud\Contracts\ConversationRecorderInterface;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppAccount;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppContact;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversation;
use Vendor\LaravelWhatsAppCloud\Models\WhatsAppConversationMessage;
use Vendor\LaravelWhatsAppCloud\Support\IncomingMessageParser;

class ConversationService implements ConversationRecorderInterface
{
    public function isEnabled(): bool
    {
        return (bool) config('whatsapp.conversations.enabled', true);
    }

    /**
     * @param  array<string, mixed>  $webhookMessage
     * @param  array<int, array<string, mixed>>  $contacts
     */
    public function recordIncoming(
        WhatsAppAccount $account,
        array $webhookMessage,
        array $contacts = [],
    ): WhatsAppConversationMessage {
        $parsed = IncomingMessageParser::parse($webhookMessage, $contacts);

        $contact = $this->findOrCreateContact(
            $account,
            $parsed['phone'],
            $parsed['name'],
            ['last_incoming_at' => now()->toIso8601String()],
        );

        $conversation = $this->findOrCreateConversation($account, $contact);

        $attributes = [
            'conversation_id' => $conversation->id,
            'direction' => WhatsAppConversationMessage::DIRECTION_INCOMING,
            'phone' => $parsed['phone'],
            'message' => $parsed['body'],
            'type' => $parsed['type'],
            'payload_json' => $parsed['payload'],
        ];

        if ($parsed['whatsapp_message_id']) {
            $message = WhatsAppConversationMessage::query()->firstOrCreate(
                ['whatsapp_message_id' => $parsed['whatsapp_message_id']],
                $attributes,
            );
        } else {
            $message = WhatsAppConversationMessage::query()->create($attributes);
        }

        $this->touchConversation($conversation);

        return $message;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function recordOutgoing(
        WhatsAppAccount $account,
        string $phone,
        string $type,
        ?string $message,
        array $payload,
        ?string $whatsappMessageId = null,
    ): WhatsAppConversationMessage {
        $normalizedPhone = WhatsAppMessageBuilder::normalizePhone($phone);

        $contact = $this->findOrCreateContact($account, $normalizedPhone);
        $conversation = $this->findOrCreateConversation($account, $contact);

        $record = WhatsAppConversationMessage::query()->create([
            'conversation_id' => $conversation->id,
            'direction' => WhatsAppConversationMessage::DIRECTION_OUTGOING,
            'whatsapp_message_id' => $whatsappMessageId,
            'phone' => $normalizedPhone,
            'message' => $message,
            'type' => $type,
            'payload_json' => $payload,
        ]);

        $this->touchConversation($conversation);

        return $record;
    }

    public function findOrCreateContact(
        WhatsAppAccount $account,
        string $phone,
        ?string $name = null,
        array $metadata = [],
    ): WhatsAppContact {
        $normalizedPhone = WhatsAppMessageBuilder::normalizePhone($phone);

        $contact = WhatsAppContact::query()->firstOrCreate(
            [
                'account_id' => $account->id,
                'phone' => $normalizedPhone,
            ],
            array_merge(
                $this->tenantAttributes($account),
                [
                    'name' => $name,
                    'metadata_json' => $metadata,
                ],
            ),
        );

        $updates = [];

        if ($name && $contact->name !== $name) {
            $updates['name'] = $name;
        }

        if ($metadata !== []) {
            $updates['metadata_json'] = array_merge($contact->metadata_json ?? [], $metadata);
        }

        if ($updates !== []) {
            $contact->update($updates);
        }

        return $contact->fresh();
    }

    public function findOrCreateConversation(
        WhatsAppAccount $account,
        WhatsAppContact $contact,
    ): WhatsAppConversation {
        return WhatsAppConversation::query()->firstOrCreate(
            [
                'account_id' => $account->id,
                'contact_id' => $contact->id,
            ],
            array_merge(
                $this->tenantAttributes($account, $contact),
                [
                    'last_message_at' => now(),
                ],
            ),
        );
    }

    /**
     * @return array<string, int|null>
     */
    protected function tenantAttributes(WhatsAppAccount $account, ?WhatsAppContact $contact = null): array
    {
        if (! app(TenantContext::class)->usesSchema()) {
            return [];
        }

        return [
            'tenant_id' => $account->tenant_id ?? $contact?->tenant_id,
        ];
    }

    protected function touchConversation(WhatsAppConversation $conversation): void
    {
        $conversation->update(['last_message_at' => now()]);
    }
}
