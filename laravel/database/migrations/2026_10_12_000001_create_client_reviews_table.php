<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('client_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('display_name', 40);
            $table->unsignedTinyInteger('rating');
            $table->text('message');
            $table->char('review_hash', 64);
            $table->string('status', 20)->default('pending');
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'review_hash']);
            $table->index(['status', 'created_at']);
            $table->index(['user_id', 'created_at']);
        });
        DB::statement('ALTER TABLE client_reviews ADD CONSTRAINT reviews_rating_valid CHECK (rating BETWEEN 1 AND 5)');
        DB::statement("ALTER TABLE client_reviews ADD CONSTRAINT reviews_status_valid CHECK (status IN ('pending','approved','rejected','hidden'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('client_reviews');
    }
};
