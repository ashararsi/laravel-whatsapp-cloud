<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vendor\LaravelWhatsAppCloud\Support\MigrationTenant;

return new class extends Migration
{
    public function up(): void
    {
        if (MigrationTenant::enabled()) {
            Schema::create(MigrationTenant::table(), function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->json('settings_json')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            if (MigrationTenant::enabled()) {
                $table->foreignId(MigrationTenant::column())
                    ->nullable()
                    ->after('id')
                    ->constrained(MigrationTenant::table())
                    ->nullOnDelete();
            }

            $table->string('waba_id')->nullable()->after('phone_number_id');
        });

        if (MigrationTenant::enabled()) {
            Schema::table('whatsapp_contacts', function (Blueprint $table) {
                $table->foreignId(MigrationTenant::column())
                    ->nullable()
                    ->after('id')
                    ->constrained(MigrationTenant::table())
                    ->nullOnDelete();
            });
        }

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (MigrationTenant::enabled()) {
                $table->foreignId(MigrationTenant::column())
                    ->nullable()
                    ->after('id')
                    ->constrained(MigrationTenant::table())
                    ->nullOnDelete();
            }

            $table->string('status', 20)->default('open')->after('last_message_at');
            $table->string('assigned_to')->nullable()->after('status');
        });

        Schema::create('whatsapp_tags', function (Blueprint $table) {
            $table->id();

            if (MigrationTenant::enabled()) {
                $table->foreignId(MigrationTenant::column())
                    ->nullable()
                    ->constrained(MigrationTenant::table())
                    ->nullOnDelete();
            }

            $table->foreignId('account_id')->nullable()->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('color', 20)->default('#198754');
            $table->timestamps();
            $table->unique(['account_id', 'name']);
        });

        Schema::create('whatsapp_contact_tag', function (Blueprint $table) {
            $table->foreignId('contact_id')->constrained('whatsapp_contacts')->cascadeOnDelete();
            $table->foreignId('tag_id')->constrained('whatsapp_tags')->cascadeOnDelete();
            $table->primary(['contact_id', 'tag_id']);
        });

        Schema::create('whatsapp_contact_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contact_id')->constrained('whatsapp_contacts')->cascadeOnDelete();
            $table->text('body');
            $table->string('author')->nullable();
            $table->timestamps();
        });

        Schema::create('whatsapp_campaigns', function (Blueprint $table) {
            $table->id();

            if (MigrationTenant::enabled()) {
                $table->foreignId(MigrationTenant::column())
                    ->nullable()
                    ->constrained(MigrationTenant::table())
                    ->nullOnDelete();
            }

            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('type', 30)->default('text');
            $table->text('message')->nullable();
            $table->json('payload_json')->nullable();
            $table->string('status', 20)->default('draft');
            $table->timestamp('scheduled_at')->nullable();
            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->timestamps();
        });

        Schema::create('whatsapp_campaign_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('whatsapp_campaigns')->cascadeOnDelete();
            $table->foreignId('contact_id')->nullable()->constrained('whatsapp_contacts')->nullOnDelete();
            $table->string('phone', 50);
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->json('response_json')->nullable();
            $table->timestamps();
            $table->index(['campaign_id', 'status']);
        });

        Schema::create('whatsapp_scheduled_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('to', 50);
            $table->string('type', 30)->default('text');
            $table->text('message')->nullable();
            $table->json('payload_json')->nullable();
            $table->timestamp('send_at');
            $table->string('status', 20)->default('pending');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->index(['status', 'send_at']);
        });

        Schema::create('whatsapp_auto_replies', function (Blueprint $table) {
            $table->id();

            if (MigrationTenant::enabled()) {
                $table->foreignId(MigrationTenant::column())
                    ->nullable()
                    ->constrained(MigrationTenant::table())
                    ->nullOnDelete();
            }

            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('trigger_type', 30)->default('keyword');
            $table->string('trigger_value');
            $table->text('response');
            $table->boolean('use_ai')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('priority')->default(0);
            $table->timestamps();
            $table->index(['account_id', 'is_active']);
        });

        Schema::create('whatsapp_ai_workflows', function (Blueprint $table) {
            $table->id();

            if (MigrationTenant::enabled()) {
                $table->foreignId(MigrationTenant::column())
                    ->nullable()
                    ->constrained(MigrationTenant::table())
                    ->nullOnDelete();
            }

            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->text('system_prompt')->nullable();
            $table->json('steps_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('whatsapp_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->string('name');
            $table->string('language', 20)->default('en_US');
            $table->string('category')->nullable();
            $table->string('status')->nullable();
            $table->json('components_json')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'name', 'language']);
        });

        Schema::create('whatsapp_media_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('whatsapp_accounts')->cascadeOnDelete();
            $table->foreignId('conversation_message_id')->nullable()->constrained('whatsapp_conversation_messages')->nullOnDelete();
            $table->string('media_id');
            $table->string('mime_type')->nullable();
            $table->string('disk', 50)->default('local');
            $table->string('path')->nullable();
            $table->string('url')->nullable();
            $table->unsignedBigInteger('size')->nullable();
            $table->text('transcription')->nullable();
            $table->timestamps();
            $table->unique(['account_id', 'media_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whatsapp_media_files');
        Schema::dropIfExists('whatsapp_templates');
        Schema::dropIfExists('whatsapp_ai_workflows');
        Schema::dropIfExists('whatsapp_auto_replies');
        Schema::dropIfExists('whatsapp_scheduled_messages');
        Schema::dropIfExists('whatsapp_campaign_recipients');
        Schema::dropIfExists('whatsapp_campaigns');
        Schema::dropIfExists('whatsapp_contact_notes');
        Schema::dropIfExists('whatsapp_contact_tag');
        Schema::dropIfExists('whatsapp_tags');

        Schema::table('whatsapp_conversations', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_conversations', MigrationTenant::column())) {
                $table->dropConstrainedForeignId(MigrationTenant::column());
            }

            $table->dropColumn(['status', 'assigned_to']);
        });

        if (Schema::hasColumn('whatsapp_contacts', MigrationTenant::column())) {
            Schema::table('whatsapp_contacts', function (Blueprint $table) {
                $table->dropConstrainedForeignId(MigrationTenant::column());
            });
        }

        Schema::table('whatsapp_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('whatsapp_accounts', MigrationTenant::column())) {
                $table->dropConstrainedForeignId(MigrationTenant::column());
            }

            $table->dropColumn('waba_id');
        });

        Schema::dropIfExists(MigrationTenant::table());
    }
};
