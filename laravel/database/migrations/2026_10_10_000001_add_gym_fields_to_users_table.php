<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['owner', 'manager', 'client'])->default('client')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamp('last_login_at')->nullable();
            $table->softDeletes();
        });
        Schema::table('sessions', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sessions', fn (Blueprint $table) => $table->dropForeign(['user_id']));
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropIndex(['is_active']);
            $table->dropColumn(['role', 'is_active', 'last_login_at', 'deleted_at']);
        });
    }
};
