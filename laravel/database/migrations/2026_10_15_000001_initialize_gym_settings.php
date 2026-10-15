<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    public function up(): void
    {
        // Never overwrite existing gym settings; no seeder is required.
        if (! DB::table('gym_settings')->where('id', 1)->exists()) {
            DB::table('gym_settings')->insert([
                'id' => 1, 'name' => 'FitFusion', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Retain installation settings and all existing records.
    }
};
