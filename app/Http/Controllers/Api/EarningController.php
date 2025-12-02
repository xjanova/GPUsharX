<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Models\Payout;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class EarningController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        $earnings = $user->earnings()
            ->with(['gpuNode:id,node_id,gpu_model', 'jobChunk:id,chunk_id'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'earnings' => $earnings->items(),
                'pagination' => [
                    'current_page' => $earnings->currentPage(),
                    'last_page' => $earnings->lastPage(),
                    'per_page' => $earnings->perPage(),
                    'total' => $earnings->total(),
                ],
                'summary' => [
                    'balance' => $user->balance,
                    'pending_earnings' => $user->pending_earnings,
                    'total_earned' => $user->total_earned,
                    'total_withdrawn' => $user->total_withdrawn,
                ],
            ],
        ]);
    }

    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();

        // Today's earnings
        $todayEarnings = $user->earnings()
            ->whereDate('created_at', today())
            ->sum('net_amount');

        // This week's earnings
        $weekEarnings = $user->earnings()
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->sum('net_amount');

        // This month's earnings
        $monthEarnings = $user->earnings()
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('net_amount');

        // Earnings by node
        $earningsByNode = $user->earnings()
            ->whereNotNull('gpu_node_id')
            ->with('gpuNode:id,node_id,gpu_model')
            ->selectRaw('gpu_node_id, SUM(net_amount) as total')
            ->groupBy('gpu_node_id')
            ->get()
            ->map(fn($e) => [
                'node_id' => $e->gpuNode?->node_id,
                'gpu_model' => $e->gpuNode?->gpu_model,
                'total_earned' => $e->total,
            ]);

        return response()->json([
            'success' => true,
            'data' => [
                'balance' => $user->balance,
                'pending_earnings' => $user->pending_earnings,
                'total_earned' => $user->total_earned,
                'total_withdrawn' => $user->total_withdrawn,
                'periods' => [
                    'today' => $todayEarnings,
                    'this_week' => $weekEarnings,
                    'this_month' => $monthEarnings,
                ],
                'by_node' => $earningsByNode,
            ],
        ]);
    }

    public function requestPayout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:10', // Minimum withdrawal
            'payment_method' => 'required|string|in:bank_transfer,promptpay,crypto',
            'payment_details' => 'required|array',
        ]);

        $user = $request->user();

        if ($user->balance < $validated['amount']) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
                'data' => [
                    'available_balance' => $user->balance,
                    'requested_amount' => $validated['amount'],
                ],
            ], 400);
        }

        // Check for pending payouts
        $hasPending = Payout::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'processing'])
            ->exists();

        if ($hasPending) {
            return response()->json([
                'success' => false,
                'message' => 'You already have a pending payout request',
            ], 400);
        }

        try {
            $payout = Payout::requestPayout(
                $user,
                $validated['amount'],
                $validated['payment_method'],
                $validated['payment_details']
            );

            return response()->json([
                'success' => true,
                'message' => 'Payout request submitted',
                'data' => [
                    'payout_id' => $payout->payout_id,
                    'amount' => $payout->amount,
                    'payment_method' => $payout->payment_method,
                    'status' => $payout->status,
                    'new_balance' => $user->fresh()->balance,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function payoutHistory(Request $request): JsonResponse
    {
        $payouts = $request->user()->payouts()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => [
                'payouts' => $payouts->items(),
                'pagination' => [
                    'current_page' => $payouts->currentPage(),
                    'last_page' => $payouts->lastPage(),
                    'per_page' => $payouts->perPage(),
                    'total' => $payouts->total(),
                ],
            ],
        ]);
    }

    public function payoutStatus(Request $request, string $payoutId): JsonResponse
    {
        $payout = Payout::where('payout_id', $payoutId)
            ->where('user_id', $request->user()->id)
            ->firstOrFail();

        return response()->json([
            'success' => true,
            'data' => [
                'payout_id' => $payout->payout_id,
                'amount' => $payout->amount,
                'payment_method' => $payout->payment_method,
                'status' => $payout->status,
                'transaction_id' => $payout->transaction_id,
                'created_at' => $payout->created_at->toIso8601String(),
                'processed_at' => $payout->processed_at?->toIso8601String(),
            ],
        ]);
    }
}
