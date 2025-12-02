<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GpuRanking extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'gpu_node_id',
        'benchmark_score',
        'stars',
        'rank',
        'rank_title',
        'benchmark_details',
    ];

    protected $casts = [
        'benchmark_details' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function gpuNode(): BelongsTo
    {
        return $this->belongsTo(GpuNode::class);
    }

    public static function calculateRank(int $score): array
    {
        if ($score >= 50000) {
            return ['rank' => 'diamond', 'stars' => 5, 'title' => 'Diamond Miner'];
        } elseif ($score >= 30000) {
            return ['rank' => 'platinum', 'stars' => 5, 'title' => 'Platinum Miner'];
        } elseif ($score >= 20000) {
            return ['rank' => 'gold', 'stars' => 4, 'title' => 'Gold Miner'];
        } elseif ($score >= 10000) {
            return ['rank' => 'silver', 'stars' => 3, 'title' => 'Silver Miner'];
        } elseif ($score >= 5000) {
            return ['rank' => 'bronze', 'stars' => 2, 'title' => 'Bronze Miner'];
        } else {
            return ['rank' => 'bronze', 'stars' => 1, 'title' => 'Beginner Miner'];
        }
    }

    public static function getRankColor(string $rank): string
    {
        return match($rank) {
            'diamond' => '#b9f2ff',
            'platinum' => '#e5e4e2',
            'gold' => '#ffd700',
            'silver' => '#c0c0c0',
            'bronze' => '#cd7f32',
            default => '#888888',
        };
    }
}
