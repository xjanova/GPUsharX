<?php

namespace App\Services;

use App\Models\Earning;
use App\Models\PlatformSetting;
use App\Models\ReferralEarning;
use App\Models\ReferralTree;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReferralService
{
    /**
     * Get commission rates for all levels
     */
    public function getCommissionRates(): array
    {
        return [
            1 => (float) PlatformSetting::get('referral_level_1_rate', 5),
            2 => (float) PlatformSetting::get('referral_level_2_rate', 2),
            3 => (float) PlatformSetting::get('referral_level_3_rate', 1),
        ];
    }

    /**
     * Build referral tree when user registers
     * Creates ReferralTree records for levels 1-3
     */
    public function buildReferralTree(User $newUser, User $referrer): void
    {
        DB::transaction(function () use ($newUser, $referrer) {
            $rates = $this->getCommissionRates();
            $currentReferrer = $referrer;
            $level = 1;

            while ($currentReferrer && $level <= 3) {
                // Create referral tree record
                ReferralTree::create([
                    'user_id' => $newUser->id,
                    'referrer_id' => $currentReferrer->id,
                    'level' => $level,
                    'commission_rate' => $rates[$level] ?? 0,
                    'total_earned' => 0,
                ]);

                Log::info('ReferralTree created', [
                    'new_user_id' => $newUser->id,
                    'referrer_id' => $currentReferrer->id,
                    'level' => $level,
                    'commission_rate' => $rates[$level] ?? 0,
                ]);

                // Move to next level (referrer's referrer)
                $level++;
                $currentReferrer = $currentReferrer->referrer;
            }
        });
    }

    /**
     * Calculate and distribute referral commissions when earning is confirmed
     * This should be called when Earning is confirmed
     */
    public function distributeCommissions(Earning $earning): array
    {
        $user = $earning->user;
        $commissions = [];

        if (!$user) {
            return $commissions;
        }

        // Get referral tree for this user (people who should get commission)
        $referralTree = ReferralTree::where('user_id', $user->id)
            ->orderBy('level')
            ->get();

        if ($referralTree->isEmpty()) {
            return $commissions;
        }

        DB::transaction(function () use ($earning, $user, $referralTree, &$commissions) {
            foreach ($referralTree as $tree) {
                $referrer = $tree->referrer;

                if (!$referrer) {
                    continue;
                }

                // Calculate commission
                $commissionRate = $tree->commission_rate;
                $commissionAmount = ($earning->net_amount * $commissionRate) / 100;

                if ($commissionAmount <= 0) {
                    continue;
                }

                // Create ReferralEarning record
                $referralEarning = ReferralEarning::create([
                    'user_id' => $referrer->id,
                    'from_user_id' => $user->id,
                    'earning_id' => $earning->id,
                    'original_amount' => $earning->net_amount,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                    'level' => $tree->level,
                    'status' => 'pending',
                ]);

                // Update referral tree total earned
                $tree->increment('total_earned', $commissionAmount);

                // Add to referrer's pending earnings
                $referrer->increment('pending_earnings', $commissionAmount);

                // Create wallet transaction for tracking
                WalletTransaction::create([
                    'user_id' => $referrer->id,
                    'type' => 'referral',
                    'amount' => $commissionAmount,
                    'balance_before' => $referrer->balance,
                    'balance_after' => $referrer->balance, // Still pending
                    'status' => 'pending',
                    'description' => "Referral commission Level {$tree->level} from {$user->name}",
                    'metadata' => [
                        'referral_earning_id' => $referralEarning->id,
                        'from_user_id' => $user->id,
                        'earning_id' => $earning->id,
                        'level' => $tree->level,
                        'commission_rate' => $commissionRate,
                    ],
                ]);

                $commissions[] = [
                    'referrer_id' => $referrer->id,
                    'referrer_name' => $referrer->name,
                    'level' => $tree->level,
                    'commission_rate' => $commissionRate,
                    'commission_amount' => $commissionAmount,
                ];

                Log::info('Referral commission created', [
                    'referral_earning_id' => $referralEarning->id,
                    'referrer_id' => $referrer->id,
                    'from_user_id' => $user->id,
                    'earning_id' => $earning->id,
                    'level' => $tree->level,
                    'commission_amount' => $commissionAmount,
                ]);
            }
        });

        return $commissions;
    }

    /**
     * Pay a single referral earning (confirm and move to balance)
     */
    public function payCommission(ReferralEarning $referralEarning, ?User $admin = null): bool
    {
        if ($referralEarning->status === 'paid') {
            return false;
        }

        DB::transaction(function () use ($referralEarning, $admin) {
            $user = $referralEarning->user;

            // Move from pending to balance
            $user->decrement('pending_earnings', $referralEarning->commission_amount);
            $user->increment('balance', $referralEarning->commission_amount);
            $user->increment('total_earned', $referralEarning->commission_amount);

            // Update status
            $referralEarning->update([
                'status' => 'paid',
                'paid_at' => now(),
                'paid_by' => $admin?->id,
            ]);

            // Update wallet transaction
            WalletTransaction::where('metadata->referral_earning_id', $referralEarning->id)
                ->update([
                    'status' => 'completed',
                    'balance_after' => $user->balance,
                ]);

            Log::info('Referral commission paid', [
                'referral_earning_id' => $referralEarning->id,
                'user_id' => $user->id,
                'amount' => $referralEarning->commission_amount,
                'admin_id' => $admin?->id,
            ]);
        });

        return true;
    }

    /**
     * Auto-pay commissions after threshold period
     * Call this from a scheduled command
     */
    public function autoPayCommissions(int $daysOld = 7): int
    {
        $threshold = now()->subDays($daysOld);

        $pendingCommissions = ReferralEarning::where('status', 'pending')
            ->where('created_at', '<', $threshold)
            ->get();

        $paidCount = 0;

        foreach ($pendingCommissions as $commission) {
            if ($this->payCommission($commission)) {
                $paidCount++;
            }
        }

        return $paidCount;
    }

    /**
     * Get referral statistics for a user
     */
    public function getUserReferralStats(User $user): array
    {
        // Direct referrals (level 1)
        $directReferrals = User::where('referred_by', $user->id)->count();

        // Total referrals in tree
        $totalReferrals = ReferralTree::where('referrer_id', $user->id)->count();

        // Total earnings
        $totalEarnings = ReferralEarning::where('user_id', $user->id)
            ->where('status', 'paid')
            ->sum('commission_amount');

        // Pending earnings
        $pendingEarnings = ReferralEarning::where('user_id', $user->id)
            ->where('status', 'pending')
            ->sum('commission_amount');

        // Earnings by level
        $earningsByLevel = ReferralEarning::where('user_id', $user->id)
            ->selectRaw('level, SUM(commission_amount) as total, COUNT(*) as count')
            ->groupBy('level')
            ->get()
            ->keyBy('level')
            ->toArray();

        return [
            'direct_referrals' => $directReferrals,
            'total_referrals' => $totalReferrals,
            'total_earnings' => $totalEarnings,
            'pending_earnings' => $pendingEarnings,
            'earnings_by_level' => $earningsByLevel,
        ];
    }

    /**
     * Get full referral tree for a user
     */
    public function getReferralTree(User $user, int $maxDepth = 3): array
    {
        $tree = [];

        $this->buildTreeRecursive($user, $tree, 1, $maxDepth);

        return $tree;
    }

    protected function buildTreeRecursive(User $user, array &$tree, int $currentLevel, int $maxDepth): void
    {
        if ($currentLevel > $maxDepth) {
            return;
        }

        $directReferrals = User::where('referred_by', $user->id)
            ->with(['gpuNodes' => function ($q) {
                $q->online();
            }])
            ->get();

        foreach ($directReferrals as $referral) {
            $referralData = [
                'user_id' => $referral->id,
                'name' => $referral->name,
                'email' => $referral->email,
                'level' => $currentLevel,
                'registered_at' => $referral->created_at,
                'total_earned' => $referral->total_earned,
                'active_nodes' => $referral->gpuNodes->count(),
                'children' => [],
            ];

            // Get commission earned from this referral
            $commission = ReferralEarning::where('user_id', $user->id)
                ->where('from_user_id', $referral->id)
                ->sum('commission_amount');

            $referralData['commission_earned'] = $commission;

            // Recursively get sub-referrals
            if ($currentLevel < $maxDepth) {
                $this->buildTreeRecursive($referral, $referralData['children'], $currentLevel + 1, $maxDepth);
            }

            $tree[] = $referralData;
        }
    }
}
