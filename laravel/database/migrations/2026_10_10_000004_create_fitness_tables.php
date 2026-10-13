<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workout_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->text('description')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
        Schema::create('exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_category_id')->constrained()->restrictOnDelete();
            $table->string('slug');
            $table->string('name');
            $table->string('sets', 80)->nullable();
            $table->string('reps', 80)->nullable();
            $table->string('difficulty', 80)->nullable();
            $table->string('calories', 80)->nullable();
            $table->string('muscle')->nullable();
            $table->text('tips')->nullable();
            $table->text('image')->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['workout_category_id', 'slug']);
        });
        Schema::create('workout_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('workout_plan_exercises', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workout_plan_id')->constrained()->restrictOnDelete();
            $table->foreignId('exercise_id')->constrained()->restrictOnDelete();
            $table->string('sets', 80)->nullable();
            $table->string('reps', 80)->nullable();
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['workout_plan_id', 'exercise_id']);
        });
        Schema::create('diet_plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('title');
            $table->string('icon')->nullable();
            $table->text('image')->nullable();
            $table->string('calories', 100)->nullable();
            $table->text('breakfast');
            $table->text('lunch');
            $table->text('dinner');
            $table->text('snacks');
            $table->json('benefits')->nullable();
            $table->json('tips')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
        Schema::create('client_plan_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('workout_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('diet_plan_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('assigned_by')->constrained('users')->restrictOnDelete();
            $table->date('starts_on');
            $table->date('ends_on')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'starts_on']);
        });
        Schema::create('fitness_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->date('recorded_on');
            $table->decimal('weight_kg', 6, 2)->unsigned()->nullable();
            $table->decimal('height_cm', 5, 2)->unsigned()->nullable();
            $table->decimal('bmi', 5, 2)->unsigned()->nullable();
            $table->decimal('body_fat_percent', 5, 2)->unsigned()->nullable();
            $table->json('measurements')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'recorded_on']);
        });
        // Small SQL checks keep the stored data consistent.
        DB::statement("ALTER TABLE client_plan_assignments ADD CONSTRAINT assignments_one_plan CHECK ((workout_plan_id IS NOT NULL) + (diet_plan_id IS NOT NULL) = 1), ADD CONSTRAINT assignment_dates CHECK (ends_on IS NULL OR ends_on >= starts_on)");
        DB::statement("ALTER TABLE fitness_progress ADD CONSTRAINT progress_weight CHECK (weight_kg IS NULL OR weight_kg > 0), ADD CONSTRAINT progress_height CHECK (height_cm IS NULL OR height_cm > 0), ADD CONSTRAINT progress_body_fat CHECK (body_fat_percent IS NULL OR body_fat_percent BETWEEN 0 AND 100)");
    }

    public function down(): void
    {
        Schema::dropIfExists('fitness_progress');
        Schema::dropIfExists('client_plan_assignments');
        Schema::dropIfExists('diet_plans');
        Schema::dropIfExists('workout_plan_exercises');
        Schema::dropIfExists('workout_plans');
        Schema::dropIfExists('exercises');
        Schema::dropIfExists('workout_categories');
    }
};
