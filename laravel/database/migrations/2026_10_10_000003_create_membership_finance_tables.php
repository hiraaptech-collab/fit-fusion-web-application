<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('duration_days');
            $table->decimal('price', 12, 2)->unsigned();
            $table->char('currency', 3)->default('PKR');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('memberships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_plan_id')->constrained()->restrictOnDelete();
            $table->string('plan_name');
            $table->unsignedInteger('duration_days');
            $table->decimal('agreed_price', 12, 2)->unsigned();
            $table->char('currency', 3);
            $table->date('starts_on');
            $table->date('ends_on');
            $table->enum('status', ['pending', 'active', 'expired', 'cancelled'])->default('pending');
            $table->timestamp('cancelled_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'status']);
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('membership_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('receipt_number')->unique();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->char('currency', 3)->default('PKR');
            $table->enum('method', ['cash', 'bank_transfer', 'card', 'other']);
            $table->enum('status', ['pending', 'paid', 'failed', 'void'])->default('pending');
            $table->string('reference')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->index(['user_id', 'paid_at']);
        });
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained()->restrictOnDelete();
            $table->string('reference')->unique();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->char('currency', 3)->default('PKR');
            $table->text('reason');
            $table->enum('status', ['pending', 'completed', 'failed'])->default('pending');
            $table->timestamp('refunded_at')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->string('description');
            $table->string('category', 80)->index();
            $table->decimal('amount', 12, 2)->unsigned();
            $table->char('currency', 3)->default('PKR');
            $table->date('incurred_on')->index();
            $table->string('reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        // Small SQL checks keep the stored data consistent.
        DB::statement("ALTER TABLE membership_plans ADD CONSTRAINT plans_duration CHECK (duration_days > 0)");
        DB::statement("ALTER TABLE memberships ADD CONSTRAINT membership_dates CHECK (ends_on >= starts_on), ADD CONSTRAINT membership_duration CHECK (duration_days > 0)");
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payment_amount CHECK (amount > 0)");
        DB::statement("ALTER TABLE refunds ADD CONSTRAINT refund_amount CHECK (amount > 0)");
        DB::statement("ALTER TABLE expenses ADD CONSTRAINT expense_amount CHECK (amount > 0)");
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('refunds');
        Schema::dropIfExists('payments');
        Schema::dropIfExists('memberships');
        Schema::dropIfExists('membership_plans');
    }
};
