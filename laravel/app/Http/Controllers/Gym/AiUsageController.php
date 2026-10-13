<?php
namespace App\Http\Controllers\Gym;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;

class AiUsageController extends Controller
{
    public function index()
    {
        $daily=AuditLog::where('action','ai.provider')->where('created_at','>=',now()->startOfDay());
        $totals=(clone $daily)->selectRaw("COUNT(*) AS requests, COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.total_tokens')) AS UNSIGNED)),0) AS tokens, COALESCE(SUM(CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata,'$.reserved_tokens')) AS UNSIGNED)),0) AS reserved")->first();
        $requests=AuditLog::with('user:id,name')->where('action','ai.provider')->latest()->paginate(20);
        $limits=config('services.fusion_ai');
        return view('gym.ai-usage',compact('totals','requests','limits'));
    }
}
