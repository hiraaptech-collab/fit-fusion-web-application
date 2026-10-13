<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('recipient_id')->constrained('users')->restrictOnDelete();
            $table->string('subject')->nullable();
            $table->text('body');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['recipient_id', 'read_at']);
        });
        Schema::create('inquiries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email');
            $table->string('phone', 30)->nullable();
            $table->string('subject')->nullable();
            $table->text('message');
            $table->enum('status', ['new', 'in_progress', 'resolved'])->default('new')->index();
            $table->foreignId('handled_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('ai_conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('title')->nullable();
            $table->timestamp('consented_at')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('ai_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_conversation_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['user', 'assistant']);
            $table->text('content');
            $table->enum('source', ['provider', 'local'])->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->string('notifiable_type');
            $table->foreignId('notifiable_id')->constrained('users')->cascadeOnDelete();
            $table->json('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->index(['notifiable_type', 'notifiable_id']);
        });
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('action', 100)->index();
            $table->string('entity_type', 100)->nullable();
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('ai_messages');
        Schema::dropIfExists('ai_conversations');
        Schema::dropIfExists('inquiries');
        Schema::dropIfExists('messages');
    }
};
