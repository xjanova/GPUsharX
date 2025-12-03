<?php

namespace App\Http\Controllers;

use App\Models\PlatformSetting;
use App\Models\WalletTransaction;
use App\Models\WithdrawalRequest;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WalletController extends Controller
{
    protected WalletService $walletService;

    public function __construct(WalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    public function index(): View
    {
        $user = Auth::user();

        $transactions = WalletTransaction::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $withdrawals = WithdrawalRequest::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $stats = [
            'balance' => $user->balance,
            'pending_earnings' => $user->pending_earnings,
            'total_earned' => $user->total_earned,
            'total_withdrawn' => $user->total_withdrawn,
            'pending_withdrawal' => WithdrawalRequest::where('user_id', $user->id)
                ->whereIn('status', ['pending', 'processing'])
                ->sum('amount'),
        ];

        $settings = [
            'min_withdrawal' => PlatformSetting::get('min_withdrawal_amount', 10),
            'min_transfer' => PlatformSetting::get('min_earning_transfer', 1),
            'withdrawal_fee_percent' => PlatformSetting::get('withdrawal_fee_percent', 2),
            'withdrawal_fee_fixed' => PlatformSetting::get('withdrawal_fee_fixed', 0),
        ];

        // Get KYC info for withdrawal
        $kyc = $user->kycVerification;

        return view('wallet.index', compact('user', 'transactions', 'withdrawals', 'stats', 'settings', 'kyc'));
    }

    public function transferEarnings(Request $request)
    {
        $user = Auth::user();

        try {
            $transfer = $this->walletService->transferEarningsToWallet($user);

            if (!$transfer) {
                $minTransfer = PlatformSetting::get('min_earning_transfer', 1);
                return response()->json([
                    'success' => false,
                    'message' => "Minimum transfer amount is \${$minTransfer}",
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => "Transferred \${$transfer->amount} to your wallet",
                'new_balance' => $user->fresh()->balance,
                'new_pending' => $user->fresh()->pending_earnings,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function withdraw(Request $request)
    {
        $user = Auth::user();

        // Check KYC verification
        if (!$user->isKycApproved()) {
            $message = match($user->kyc_status) {
                'pending' => 'กรุณารอการตรวจสอบ KYC ของคุณให้เสร็จสิ้นก่อนถอนเงิน',
                'rejected' => 'KYC ของคุณไม่ผ่านการอนุมัติ กรุณาส่งเอกสารใหม่',
                default => 'กรุณายืนยันตัวตน (KYC) ก่อนถอนเงิน',
            };

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                    'kyc_required' => true,
                    'kyc_status' => $user->kyc_status,
                ], 403);
            }

            return redirect()->route('kyc.index')->with('warning', $message);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|in:bank_transfer,promptpay,truemoney,paypal,crypto',
            'bank_name' => 'required_if:payment_method,bank_transfer',
            'account_number' => 'required_if:payment_method,bank_transfer',
            'account_name' => 'required_if:payment_method,bank_transfer',
            'promptpay_number' => 'required_if:payment_method,promptpay',
            'truemoney_number' => 'required_if:payment_method,truemoney',
            'paypal_email' => 'required_if:payment_method,paypal|nullable|email',
            'crypto_address' => 'required_if:payment_method,crypto',
            'crypto_network' => 'required_if:payment_method,crypto',
        ]);

        // Build payment details based on method
        $paymentDetails = match($validated['payment_method']) {
            'bank_transfer' => [
                'bank_name' => $validated['bank_name'],
                'account_number' => $validated['account_number'],
                'account_name' => $validated['account_name'],
            ],
            'promptpay' => [
                'promptpay_number' => $validated['promptpay_number'],
            ],
            'truemoney' => [
                'truemoney_number' => $validated['truemoney_number'],
            ],
            'paypal' => [
                'paypal_email' => $validated['paypal_email'],
            ],
            'crypto' => [
                'crypto_address' => $validated['crypto_address'],
                'crypto_network' => $validated['crypto_network'],
            ],
            default => [],
        };

        try {
            $withdrawal = $this->walletService->createWithdrawal(
                $user,
                $validated['amount'],
                $validated['payment_method'],
                $paymentDetails
            );

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Withdrawal request submitted successfully',
                    'request_id' => $withdrawal->request_id,
                    'new_balance' => $user->fresh()->balance,
                ]);
            }

            return redirect()->back()->with('success', 'Withdrawal request submitted successfully');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 400);
            }

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancelWithdrawal(Request $request, WithdrawalRequest $withdrawal)
    {
        $user = Auth::user();

        if ($withdrawal->user_id !== $user->id) {
            abort(403);
        }

        if ($withdrawal->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Only pending withdrawals can be cancelled',
            ], 400);
        }

        // Refund to balance
        $user->increment('balance', $withdrawal->amount);

        $withdrawal->update(['status' => 'cancelled']);

        // Update related transaction
        WalletTransaction::where('reference_type', 'withdrawal')
            ->where('reference_id', $withdrawal->id)
            ->update(['status' => 'cancelled']);

        // Create refund transaction
        WalletTransaction::create([
            'user_id' => $user->id,
            'type' => 'refund',
            'amount' => $withdrawal->amount,
            'balance_before' => $user->balance - $withdrawal->amount,
            'balance_after' => $user->balance,
            'status' => 'completed',
            'description' => "Cancelled withdrawal #{$withdrawal->request_id}",
            'reference_type' => 'withdrawal',
            'reference_id' => $withdrawal->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal cancelled and refunded',
            'new_balance' => $user->fresh()->balance,
        ]);
    }

    public function transactions(Request $request): View
    {
        $user = Auth::user();

        $query = WalletTransaction::where('user_id', $user->id);

        if ($request->type) {
            $query->where('type', $request->type);
        }

        if ($request->status) {
            $query->where('status', $request->status);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('wallet.transactions', compact('transactions'));
    }
}
