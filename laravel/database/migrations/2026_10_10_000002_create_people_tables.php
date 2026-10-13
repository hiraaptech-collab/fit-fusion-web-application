<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('phone', 30)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 30)->nullable();
            $table->text('fitness_goal')->nullable();
            $table->timestamp('fitness_data_consent_at')->nullable();
            $table->timestamps();
        });
        Schema::create('manager_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('manager_id')->constrained('users')->restrictOnDelete();
            $table->string('permission', 80);
            $table->foreignId('granted_by')->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['manager_id', 'permission']);
        });
        Schema::create('trainers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->text('specialties')->nullable();
            $table->text('qualifications')->nullable();
            $table->date('hired_on')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('workers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->nullable()->unique();
            $table->string('phone', 30)->nullable();
            $table->string('job_title');
            $table->date('hired_on')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('staff_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained()->restrictOnDelete();
            $table->dateTime('starts_at')->index();
            $table->dateTime('ends_at');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trainer_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('worker_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('staff_schedule_id')->nullable()->constrained()->restrictOnDelete();
            $table->date('attendance_date');
            $table->enum('status', ['present', 'absent', 'late', 'leave']);
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('recorded_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->unique(['trainer_id', 'attendance_date']);
            $table->unique(['worker_id', 'attendance_date']);
        });
        Schema::create('gym_settings', function (Blueprint $table) {
            $table->unsignedTinyInteger('id')->primary();
            $table->string('name')->default('FitFusion');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->text('address')->nullable();
            $table->string('timezone', 80)->default('Asia/Karachi');
            $table->char('currency', 3)->default('PKR');
            $table->json('opening_hours')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
        });
        // Small SQL checks keep the stored data consistent.
        DB::statement("ALTER TABLE staff_schedules ADD CONSTRAINT schedules_one_staff CHECK ((trainer_id IS NOT NULL) + (worker_id IS NOT NULL) = 1), ADD CONSTRAINT schedules_dates CHECK (ends_at > starts_at)");
        DB::statement("ALTER TABLE staff_attendances ADD CONSTRAINT attendances_one_staff CHECK ((trainer_id IS NOT NULL) + (worker_id IS NOT NULL) = 1), ADD CONSTRAINT attendances_dates CHECK (checked_out_at IS NULL OR checked_in_at IS NOT NULL AND checked_out_at >= checked_in_at)");
        DB::statement("ALTER TABLE gym_settings ADD CONSTRAINT settings_single_gym CHECK (id = 1)");
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_settings');
        Schema::dropIfExists('staff_attendances');
        Schema::dropIfExists('staff_schedules');
        Schema::dropIfExists('workers');
        Schema::dropIfExists('trainers');
        Schema::dropIfExists('manager_permissions');
        Schema::dropIfExists('client_profiles');
    }
};
