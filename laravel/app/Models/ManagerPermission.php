<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ManagerPermission extends Model
{
    protected $fillable = [
        'manager_id',
        'permission',
        'granted_by',
    ];

    public function manager(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }

    public function grantedBy(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'granted_by');
    }
}
