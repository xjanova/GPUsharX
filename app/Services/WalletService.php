<?php

namespace App\Services;

use App\Models\EarningTransfer;
use App\Models\PlatformSetting;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use Illuminate\Support\Facades\DB;

class WalletService
{
    /**
     * Transfer pending earnings to wallet balance when threshold is met
     */
    public function transferEarningsToWallet(User $user): ?EarningTransfer
    {
        $minTransfer = PlatformSetting::get('min_earning_transfer', 1.00);

        if ($user->pending_earnings < $minTransfer) {
            return null;
        }

        return DB::transaction(function () use ($user) {
            $amount = $user->pending_earnings;
            $pendingBefore = $user->pending_earnings;
            $balanceBefore = $user->balance;

            // Create wallet transaction
            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'earning',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'status' => 'completed',
                'description' => 'Earnings transferred to wallet',
            ]);

            // Update user balances
            $user->update([
                'pending_earnings' => 0,
                'balance' => $balanceBefore + $amount,
                'total_earned' => $user->total_earned + $amount,
            ]);

            // Create transfer record
            return EarningTransfer::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'pending_before' => $pendingBefore,
                'pending_after' => 0,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'wallet_transaction_id' => $transaction->id,
            ]);
        });
    }

    /**
     * Create a withdrawal request
     */
    public function createWithdrawal(User $user, float $amount, string $paymentMethod, array $paymentDetails): WithdrawalRequest
    {
        $minWithdrawal = PlatformSetting::get('min_withdrawal_amount', 10.00);
        $withdrawalFeePercent = PlatformSetting::get('withdrawal_fee_percent', 2.00);
        $withdrawalFeeFixed = PlatformSetting::get('withdrawal_fee_fixed', 0.00);

        if ($amount < $minWithdrawal) {
            throw new \Exception("Minimum withdrawal amount is \${$minWithdrawal}");
        }

        if ($user->balance < $amount) {
            throw new \Exception("Insufficient balance");
        }

        // Calculate fee
        $fee = ($amount * $withdrawalFeePercent / 100) + $withdrawalFeeFixed;
        $netAmount = $amount - $fee;

        return DB::transaction(function () use ($user, $amount, $fee, $netAmount, $paymentMethod, $paymentDetails) {
            $balanceBefore = $user->balance;

            // Create withdrawal request
            $withdrawal = WithdrawalRequest::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
                'payment_method' => $paymentMethod,
                'payment_details' => $paymentDetails,
                'status' => 'pending',
            ]);

            // Deduct from balance immediately (hold)
            $user->update([
                'balance' => $balanceBefore - $amount,
            ]);

            // Create wallet transaction
            WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'withdrawal',
                'amount' => -$amount,
                'fee' => $fee,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore - $amount,
                'status' => 'pending',
                'description' => "Withdrawal request #{$withdrawal->request_id}",
                'reference_type' => 'withdrawal',
                'reference_id' => $withdrawal->id,
            ]);

            return $withdrawal;
        });
    }

    /**
     * Process withdrawal (admin action)
     */
    public function processWithdrawal(WithdrawalRequest $withdrawal, int $adminId): void
    {
        if ($withdrawal->status !== 'pending') {
            throw new \Exception("Withdrawal is not pending");
        }

        $withdrawal->update([
            'status' => 'processing',
            'processed_by' => $adminId,
        ]);

        // Update related transaction
        WalletTransaction::where('reference_type', 'withdrawal')
            ->where('reference_id', $withdrawal->id)
            ->update(['status' => 'processing']);
    }

    /**
     * Complete withdrawal (admin action)
     */
    public function completeWithdrawal(WithdrawalRequest $withdrawal, string $transactionRef, int $adminId): void
    {
        if (!in_array($withdrawal->status, ['pending', 'processing'])) {
            throw new \Exception("Withdrawal cannot be completed");
        }

        DB::transaction(function () use ($withdrawal, $transactionRef, $adminId) {
            $withdrawal->update([
                'status' => 'completed',
                'transaction_ref' => $transactionRef,
                'processed_at' => now(),
                'processed_by' => $adminId,
            ]);

            // Update user total withdrawn
            $withdrawal->user->increment('total_withdrawn', $withdrawal->net_amount);

            // Update related transaction
            WalletTransaction::where('reference_type', 'withdrawal')
                ->where('reference_id', $withdrawal->id)
                ->update(['status' => 'completed']);
        });
    }

    /**
     * Reject withdrawal (admin action)
     */
    public function rejectWithdrawal(WithdrawalRequest $withdrawal, string $reason, int $adminId): void
    {
        if (!in_array($withdrawal->status, ['pending', 'processing'])) {
            throw new \Exception("Withdrawal cannot be rejected");
        }

        DB::transaction(function () use ($withdrawal, $reason, $adminId) {
            // Refund to user balance
            $withdrawal->user->increment('balance', $withdrawal->amount);

            $withdrawal->update([
                'status' => 'rejected',
                'reject_reason' => $reason,
                'processed_at' => now(),
                'processed_by' => $adminId,
            ]);

            // Update related transaction
            WalletTransaction::where('reference_type', 'withdrawal')
                ->where('reference_id', $withdrawal->id)
                ->update(['status' => 'failed']);

            // Create refund transaction
            WalletTransaction::create([
                'user_id' => $withdrawal->user_id,
                'type' => 'refund',
                'amount' => $withdrawal->amount,
                'balance_before' => $withdrawal->user->balance - $withdrawal->amount,
                'balance_after' => $withdrawal->user->balance,
                'status' => 'completed',
                'description' => "Refund for rejected withdrawal #{$withdrawal->request_id}",
                'reference_type' => 'withdrawal',
                'reference_id' => $withdrawal->id,
            ]);
        });
    }

    /**
     * Add bonus to user wallet
     */
    public function addBonus(User $user, float $amount, string $description): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $description) {
            $balanceBefore = $user->balance;

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'bonus',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'status' => 'completed',
                'description' => $description,
            ]);

            $user->increment('balance', $amount);

            return $transaction;
        });
    }

    /**
     * Add referral commission to wallet
     */
    public function addReferralCommission(User $user, float $amount, int $fromUserId, int $level): WalletTransaction
    {
        return DB::transaction(function () use ($user, $amount, $fromUserId, $level) {
            $balanceBefore = $user->balance;

            $transaction = WalletTransaction::create([
                'user_id' => $user->id,
                'type' => 'referral',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $balanceBefore + $amount,
                'status' => 'completed',
                'description' => "Referral commission (Level {$level})",
                'metadata' => [
                    'from_user_id' => $fromUserId,
                    'level' => $level,
                ],
            ]);

            $user->increment('balance', $amount);

            return $transaction;
        });
    }
}
