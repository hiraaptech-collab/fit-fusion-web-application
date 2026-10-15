<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\{DB, Schema};

class CheckProduction extends Command
{
    protected $signature = 'fitfusion:check-production';
    protected $description = 'Read-only schema and deployment checks without exposing credentials';

    public function handle(): int
    {
        $failed = false;
        try {
            DB::select('SELECT 1');
            $this->info('Database connection: OK');
            $tables = ['users','password_reset_tokens','sessions','cache','cache_locks','jobs','job_batches','failed_jobs',
                'client_profiles','manager_permissions','trainers','workers','staff_schedules','staff_attendances',
                'gym_settings','membership_plans','memberships','payments','refunds','expenses','workout_categories',
                'exercises','workout_plans','workout_plan_exercises','diet_plans','client_plan_assignments',
                'fitness_progress','messages','inquiries','ai_conversations','ai_messages','notifications','audit_logs','client_reviews'];
            foreach ($tables as $table) {
                if (! Schema::hasTable($table)) { $this->error('Missing table: '.$table); $failed = true; }
            }
            foreach (['users'=>['role','is_active','deleted_at'], 'inquiries'=>['kind','newsletter_email','reply','replied_at'],
                'client_plan_assignments'=>['cancelled_at']] as $table=>$columns) {
                if (! Schema::hasTable($table)) continue;
                foreach ($columns as $column) {
                    if (! Schema::hasColumn($table,$column)) { $this->error('Missing column: '.$table.'.'.$column); $failed = true; }
                }
            }
            if (Schema::hasTable('gym_settings') && ! DB::table('gym_settings')->where('id',1)->exists()) {
                $this->error('Missing gym_settings row id=1. Apply the initialization migration after approval.');
                $failed = true;
            }
            $ran = Schema::hasTable('migrations') ? DB::table('migrations')->pluck('migration')->all() : [];
            foreach (glob(database_path('migrations/*.php')) as $file) {
                $name = basename($file,'.php');
                if (! in_array($name,$ran,true)) { $this->warn('Pending migration: '.$name); $failed = true; }
            }
        } catch (\Throwable $exception) {
            $this->error('Database/schema check failed: '.class_basename($exception));
            $failed = true;
        }
        if (app()->isProduction()) {
            foreach (['app.debug'=>false,'services.fusion_ai.require_provider'=>true] as $key=>$expected) {
                if (config($key) !== $expected) { $this->error('Check setting: '.$key); $failed = true; }
            }
            if (! config('services.fusion_ai.api_key') || config('services.fusion_ai.api_key') === 'YOUR_GROQ_API_KEY_HERE') {
                $this->error('AI_API_KEY is missing or a placeholder.'); $failed = true;
            }
            if (config('database.default') === 'mysql') {
                $ca = config('database.connections.mysql.options.'.\PDO::MYSQL_ATTR_SSL_CA);
                if (! $ca || ! is_readable($ca)) { $this->error('MySQL SSL CA is missing or unreadable.'); $failed = true; }
                try {
                    $ssl = DB::select("SHOW SESSION STATUS LIKE 'Ssl_cipher'");
                    if (empty($ssl[0]->Value)) { $this->error('MySQL connection is not using TLS.'); $failed = true; }
                } catch (\Throwable) { $this->error('Could not verify MySQL TLS.'); $failed = true; }
            }
        }
        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
