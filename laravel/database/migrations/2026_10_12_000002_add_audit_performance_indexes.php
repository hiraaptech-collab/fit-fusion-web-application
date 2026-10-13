<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    private array $indexes=[
        'users'=>['ff_users_role_created'=>['role','created_at','is_active']],
        'memberships'=>['ff_memberships_dates'=>['ends_on','starts_on','status']],
        'payments'=>['ff_payments_reporting'=>['currency','status','paid_at']],
        'refunds'=>['ff_refunds_reporting'=>['currency','status','refunded_at']],
        'expenses'=>['ff_expenses_reporting'=>['currency','incurred_on']],
        'audit_logs'=>['ff_audit_usage'=>['action','created_at','user_id']],
        'staff_schedules'=>['ff_staff_schedule_start'=>['starts_at']],
    ];
    public function up(): void
    {
        foreach($this->indexes as $table=>$indexes)Schema::table($table,function(Blueprint $blueprint)use($indexes){foreach($indexes as $name=>$columns)$blueprint->index($columns,$name);});
    }
    public function down(): void
    {
        foreach($this->indexes as $table=>$indexes)Schema::table($table,function(Blueprint $blueprint)use($indexes){foreach(array_keys($indexes) as $name)$blueprint->dropIndex($name);});
    }
};
