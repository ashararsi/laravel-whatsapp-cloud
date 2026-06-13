<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Vendor\LaravelWhatsAppCloud\Support\MigrationTenant;

return new class extends Migration
{
    public function up(): void
    {
        if (! MigrationTenant::enabled()) {
            return;
        }

        if (! Schema::hasTable(MigrationTenant::table())) {
            Schema::create(MigrationTenant::table(), function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->boolean('is_active')->default(true);
                $table->json('settings_json')->nullable();
                $table->timestamps();
            });
        }

        $this->addTenantColumn('whatsapp_accounts', after: 'id');
        $this->addTenantColumn('whatsapp_contacts', after: 'id');
        $this->addTenantColumn('whatsapp_conversations', after: 'id');
        $this->addTenantColumn('whatsapp_tags');
        $this->addTenantColumn('whatsapp_campaigns');
        $this->addTenantColumn('whatsapp_auto_replies');
        $this->addTenantColumn('whatsapp_ai_workflows');
    }

    public function down(): void
    {
        if (! Schema::hasTable(MigrationTenant::table())) {
            return;
        }

        foreach ([
            'whatsapp_ai_workflows',
            'whatsapp_auto_replies',
            'whatsapp_campaigns',
            'whatsapp_tags',
            'whatsapp_conversations',
            'whatsapp_contacts',
            'whatsapp_accounts',
        ] as $table) {
            if (Schema::hasColumn($table, MigrationTenant::column())) {
                Schema::table($table, function (Blueprint $table) {
                    $table->dropConstrainedForeignId(MigrationTenant::column());
                });
            }
        }

        Schema::dropIfExists(MigrationTenant::table());
    }

    protected function addTenantColumn(string $table, ?string $after = null): void
    {
        if (Schema::hasColumn($table, MigrationTenant::column())) {
            return;
        }

        Schema::table($table, function (Blueprint $table) use ($after) {
            $column = $table->foreignId(MigrationTenant::column())
                ->nullable()
                ->constrained(MigrationTenant::table())
                ->nullOnDelete();

            if ($after !== null) {
                $column->after($after);
            }
        });
    }
};
