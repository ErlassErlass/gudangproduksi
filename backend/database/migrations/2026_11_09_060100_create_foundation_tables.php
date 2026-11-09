<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Cross-cutting foundation tables: audit, numbering, idempotency, outbox, attachments
 * (agent_docs/05_DATABASE_SCHEMA.md §3 and §10).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action', 120);
            $table->string('entity_type', 120)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['entity_type', 'entity_id']);
            $table->index(['action', 'created_at']);
            $table->index('created_at');
        });

        /*
         * Deviation from the logical schema: the spec lists UNIQUE(document_type, reset_period).
         * `reset_period` here holds the policy (DAILY/MONTHLY/YEARLY/NEVER) while `scope_key`
         * holds the concrete period/context value (e.g. "20261109" or "1001|261107"), so the
         * unique key is (document_type, scope_key).
         */
        Schema::create('document_sequences', function (Blueprint $table) {
            $table->id();
            $table->string('document_type', 64);
            $table->string('scope_key', 120);
            $table->string('prefix', 32)->nullable();
            $table->string('date_format', 16)->nullable();
            $table->string('reset_period', 16);
            $table->unsignedBigInteger('last_number')->default(0);
            $table->timestamps();

            $table->unique(['document_type', 'scope_key']);
        });

        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->string('key', 128)->unique();
            $table->string('operation', 120);
            $table->char('request_hash', 64);
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_json')->nullable();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('expires_at')->nullable()->index();
        });

        Schema::create('outbox_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->string('aggregate_type', 120);
            $table->unsignedBigInteger('aggregate_id');
            $table->string('event_type', 120);
            $table->json('payload_json');
            $table->timestamp('occurred_at');
            $table->timestamp('processed_at')->nullable();
            $table->unsignedInteger('attempts')->default(0);
            $table->text('last_error')->nullable();

            $table->index(['processed_at', 'occurred_at']);
            $table->index(['aggregate_type', 'aggregate_id']);
        });

        Schema::create('attachments', function (Blueprint $table) {
            $table->id();
            $table->string('owner_type', 120);
            $table->unsignedBigInteger('owner_id');
            $table->string('file_name', 255);
            $table->string('disk', 32);
            $table->string('file_path', 512);
            $table->string('mime_type', 120);
            $table->unsignedBigInteger('file_size');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['owner_type', 'owner_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attachments');
        Schema::dropIfExists('outbox_events');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('document_sequences');
        Schema::dropIfExists('audit_logs');
    }
};
