<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->string('kind', 20)->default('contact')->index();
            $table->string('newsletter_email', 254)->nullable()->unique();
            $table->text('reply')->nullable();
            $table->timestamp('replied_at')->nullable();
        });
    }
    public function down(): void {
        Schema::table('inquiries', function (Blueprint $table) {
            $table->dropUnique(['newsletter_email']);
            $table->dropIndex(['kind']);
            $table->dropColumn(['kind', 'newsletter_email', 'reply', 'replied_at']);
        });
    }
};
