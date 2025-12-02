<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PoolStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'total_nodes',
        'active_nodes',
        'total_hashrate',
        'jobs_completed',
        'chunks_processed',
        'credits_distributed',
        'platform_revenue',
        'new_users',
    ];

    protected $casts = [
        'date' => 'date',
        'total_hashrate' => 'decimal:4',
        'credits_distributed' => 'decimal:8',
        'platform_revenue' => 'decimal:8',
    ];

    public static function recordDaily(): self
    {
        $today = now()->toDateString();

        return self::updateOrCreate(
            ['date' => $today],
            [
                'total_nodes' => GpuNode::count(),
                'active_nodes' => GpuNode::online()->count(),
                'total_hashrate' => GpuNode::online()->sum('hashrate'),
                'jobs_completed' => RenderJob::whereDate('completed_at', $today)->count(),
                'chunks_processed' => JobChunk::whereDate('completed_at', $today)->count(),
                'credits_distributed' => Earning::whereDate('created_at', $today)
                    ->where('status', '!=', 'paid')
                    ->sum('net_amount'),
                'platform_revenue' => Earning::whereDate('created_at', $today)
                    ->sum('platform_fee'),
                'new_users' => User::whereDate('created_at', $today)->count(),
            ]
        );
    }
}
