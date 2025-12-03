<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\ReferralEarning;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReferralController extends Controller
{
    /**
     * Get referral tree for current user
     */
    public function getMyReferralTree(Request $request): JsonResponse
    {
        $user = $request->user();
        $maxDepth = $request->query('depth', 3);

        $tree = $this->buildReferralTree($user->id, $maxDepth);
        $stats = $this->getReferralStats($user->id);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'referral_code' => $user->referral_code,
                ],
                'tree' => $tree,
                'stats' => $stats,
                'referral_link' => url('/register?ref=' . $user->referral_code),
            ],
        ]);
    }

    /**
     * Get referral tree for specific user (Admin)
     */
    public function getUserReferralTree(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);
        $maxDepth = $request->query('depth', 3);

        $tree = $this->buildReferralTree($user->id, $maxDepth);
        $stats = $this->getReferralStats($user->id);

        // Get upline (who referred this user)
        $upline = $this->getUpline($user->id, 3);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'referral_code' => $user->referral_code,
                    'referred_by' => $user->referred_by,
                ],
                'upline' => $upline,
                'tree' => $tree,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Get all referral network (Admin)
     */
    public function getAllReferralNetwork(Request $request): JsonResponse
    {
        // Get top referrers
        $topReferrers = User::select('users.id', 'users.name', 'users.email', 'users.referral_code')
            ->withCount('directReferrals as referrals_count')
            ->having('referrals_count', '>', 0)
            ->orderByDesc('referrals_count')
            ->limit(50)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'referral_code' => $user->referral_code,
                    'direct_referrals' => $user->referrals_count,
                    'total_network' => $this->countTotalNetwork($user->id),
                ];
            });

        // Get users without referrer (root nodes)
        $rootUsers = User::whereNull('referred_by')
            ->has('directReferrals')
            ->select('id', 'name', 'email', 'referral_code')
            ->withCount('directReferrals as referrals_count')
            ->orderByDesc('referrals_count')
            ->limit(20)
            ->get();

        // Network statistics
        $stats = [
            'total_users' => User::count(),
            'users_with_referrer' => User::whereNotNull('referred_by')->count(),
            'users_with_referrals' => User::has('directReferrals')->count(),
            'total_commission_paid' => ReferralEarning::where('status', 'paid')->sum('commission_amount') ?: 0,
        ];

        return response()->json([
            'success' => true,
            'data' => [
                'top_referrers' => $topReferrers,
                'root_users' => $rootUsers,
                'stats' => $stats,
            ],
        ]);
    }

    /**
     * Get referral tree as graph data (for visualization)
     */
    public function getGraphData(Request $request): JsonResponse
    {
        $userId = $request->query('user_id', $request->user()->id);
        $maxDepth = $request->query('depth', 3);

        $nodes = [];
        $edges = [];

        $this->buildGraphData($userId, $nodes, $edges, $maxDepth, 0);

        return response()->json([
            'success' => true,
            'data' => [
                'nodes' => array_values($nodes),
                'edges' => $edges,
                'root_id' => $userId,
            ],
        ]);
    }

    /**
     * Build referral tree recursively
     */
    protected function buildReferralTree(int $userId, int $maxDepth, int $currentDepth = 0): array
    {
        if ($currentDepth >= $maxDepth) {
            return [];
        }

        $referrals = User::where('referred_by', $userId)
            ->select('id', 'name', 'email', 'referral_code', 'created_at', 'credits', 'total_earned')
            ->get();

        $children = [];
        foreach ($referrals as $referral) {
            $childTree = $this->buildReferralTree($referral->id, $maxDepth, $currentDepth + 1);
            $children[] = [
                'id' => $referral->id,
                'name' => $referral->name,
                'email' => $referral->email,
                'referral_code' => $referral->referral_code,
                'joined_at' => $referral->created_at->toDateString(),
                'credits' => round($referral->credits, 2),
                'total_earned' => round($referral->total_earned, 2),
                'level' => $currentDepth + 1,
                'referrals_count' => count($childTree),
                'children' => $childTree,
            ];
        }

        return $children;
    }

    /**
     * Build graph data for visualization
     */
    protected function buildGraphData(int $userId, array &$nodes, array &$edges, int $maxDepth, int $currentDepth): void
    {
        if ($currentDepth > $maxDepth) {
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            return;
        }

        // Add node if not exists
        if (!isset($nodes[$userId])) {
            $nodes[$userId] = [
                'id' => $user->id,
                'name' => $user->name,
                'level' => $currentDepth,
                'referrals_count' => User::where('referred_by', $user->id)->count(),
                'credits' => round($user->credits, 2),
                'color' => $this->getLevelColor($currentDepth),
            ];
        }

        // Get referrals
        $referrals = User::where('referred_by', $userId)
            ->select('id', 'name', 'credits')
            ->get();

        foreach ($referrals as $referral) {
            // Add edge
            $edges[] = [
                'from' => $userId,
                'to' => $referral->id,
                'level' => $currentDepth,
            ];

            // Recursively build
            $this->buildGraphData($referral->id, $nodes, $edges, $maxDepth, $currentDepth + 1);
        }
    }

    /**
     * Get upline (ancestors)
     */
    protected function getUpline(int $userId, int $maxLevels): array
    {
        $upline = [];
        $currentUserId = $userId;
        $level = 0;

        while ($level < $maxLevels) {
            $user = User::find($currentUserId);
            if (!$user || !$user->referred_by) {
                break;
            }

            $referrer = User::find($user->referred_by);
            if (!$referrer) {
                break;
            }

            $upline[] = [
                'id' => $referrer->id,
                'name' => $referrer->name,
                'email' => $referrer->email,
                'level' => $level + 1,
            ];

            $currentUserId = $referrer->id;
            $level++;
        }

        return $upline;
    }

    /**
     * Get referral stats
     */
    protected function getReferralStats(int $userId): array
    {
        $level1 = User::where('referred_by', $userId)->pluck('id');
        $level2 = User::whereIn('referred_by', $level1)->pluck('id');
        $level3 = User::whereIn('referred_by', $level2)->pluck('id');

        return [
            'level_1' => [
                'count' => $level1->count(),
                'total_credits' => round(User::whereIn('id', $level1)->sum('credits'), 2),
            ],
            'level_2' => [
                'count' => $level2->count(),
                'total_credits' => round(User::whereIn('id', $level2)->sum('credits'), 2),
            ],
            'level_3' => [
                'count' => $level3->count(),
                'total_credits' => round(User::whereIn('id', $level3)->sum('credits'), 2),
            ],
            'total_network' => $level1->count() + $level2->count() + $level3->count(),
        ];
    }

    /**
     * Count total network size
     */
    protected function countTotalNetwork(int $userId, int $maxDepth = 5, int $currentDepth = 0): int
    {
        if ($currentDepth >= $maxDepth) {
            return 0;
        }

        $referrals = User::where('referred_by', $userId)->pluck('id');
        $count = $referrals->count();

        foreach ($referrals as $referralId) {
            $count += $this->countTotalNetwork($referralId, $maxDepth, $currentDepth + 1);
        }

        return $count;
    }

    /**
     * Get color for level
     */
    protected function getLevelColor(int $level): string
    {
        $colors = [
            0 => '#8B5CF6', // Purple - root
            1 => '#3B82F6', // Blue - level 1
            2 => '#10B981', // Green - level 2
            3 => '#F59E0B', // Orange - level 3
            4 => '#EF4444', // Red - level 4
        ];

        return $colors[$level] ?? '#6B7280';
    }
}
