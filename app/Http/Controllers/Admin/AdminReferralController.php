<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Models\ReferralEarning;
use App\Models\ReferralTree;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminReferralController extends Controller
{
    public function index(Request $request): View
    {
        // Top referrers
        $topReferrers = User::withCount(['referrals' => function ($q) {
            $q->where('level', 1);
        }])
            ->having('referrals_count', '>', 0)
            ->orderBy('referrals_count', 'desc')
            ->limit(10)
            ->get();

        // Recent referral earnings
        $recentEarnings = ReferralEarning::with(['user', 'fromUser'])
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        // Stats
        $stats = [
            'total_users_with_referrals' => User::whereHas('referrals')->count(),
            'total_referral_trees' => ReferralTree::count(),
            'total_commission_paid' => ReferralEarning::where('status', 'paid')->sum('commission_amount'),
            'pending_commission' => ReferralEarning::where('status', 'pending')->sum('commission_amount'),
            'level_1_count' => ReferralTree::where('level', 1)->count(),
            'level_2_count' => ReferralTree::where('level', 2)->count(),
            'level_3_count' => ReferralTree::where('level', 3)->count(),
        ];

        // Commission rates from settings
        $commissionRates = [
            1 => PlatformSetting::get('referral_level_1_rate', 5),
            2 => PlatformSetting::get('referral_level_2_rate', 2),
            3 => PlatformSetting::get('referral_level_3_rate', 1),
        ];

        return view('admin.referrals', compact('topReferrers', 'recentEarnings', 'stats', 'commissionRates'));
    }

    public function referralSettings(): View
    {
        $settings = [
            'referral_level_1_rate' => PlatformSetting::get('referral_level_1_rate', 5),
            'referral_level_2_rate' => PlatformSetting::get('referral_level_2_rate', 2),
            'referral_level_3_rate' => PlatformSetting::get('referral_level_3_rate', 1),
            'max_referral_levels' => PlatformSetting::get('max_referral_levels', 3),
            'min_withdrawal_referral' => PlatformSetting::get('min_withdrawal_referral', 10),
            'referral_bonus_enabled' => PlatformSetting::get('referral_bonus_enabled', true),
            'signup_bonus_amount' => PlatformSetting::get('signup_bonus_amount', 0),
        ];

        return view('admin.referral-settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'referral_level_1_rate' => 'required|numeric|min:0|max:50',
            'referral_level_2_rate' => 'required|numeric|min:0|max:30',
            'referral_level_3_rate' => 'required|numeric|min:0|max:20',
            'max_referral_levels' => 'required|integer|min:1|max:5',
            'min_withdrawal_referral' => 'required|numeric|min:0',
            'referral_bonus_enabled' => 'boolean',
            'signup_bonus_amount' => 'required|numeric|min:0',
        ]);

        foreach ($validated as $key => $value) {
            PlatformSetting::set($key, $value);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Referral settings updated successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Referral settings updated successfully');
    }

    public function userReferrals(Request $request): View
    {
        $query = User::with(['referredBy'])
            ->withCount(['referrals' => function ($q) {
                $q->where('level', 1);
            }]);

        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('referral_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('has_referrals')) {
            $query->has('referrals');
        }

        $users = $query->orderBy('referrals_count', 'desc')->paginate(20);

        return view('admin.user-referrals', compact('users'));
    }

    public function referralTree(User $user): View
    {
        $tree = ReferralTree::where('referrer_id', $user->id)
            ->with('user')
            ->orderBy('level')
            ->get()
            ->groupBy('level');

        $earnings = ReferralEarning::where('user_id', $user->id)
            ->with('fromUser')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $stats = [
            'total_earned' => ReferralEarning::where('user_id', $user->id)->sum('commission_amount'),
            'pending' => ReferralEarning::where('user_id', $user->id)->where('status', 'pending')->sum('commission_amount'),
            'paid' => ReferralEarning::where('user_id', $user->id)->where('status', 'paid')->sum('commission_amount'),
            'level_1_count' => ReferralTree::where('referrer_id', $user->id)->where('level', 1)->count(),
            'level_2_count' => ReferralTree::where('referrer_id', $user->id)->where('level', 2)->count(),
            'level_3_count' => ReferralTree::where('referrer_id', $user->id)->where('level', 3)->count(),
        ];

        return view('admin.referral-tree', compact('user', 'tree', 'earnings', 'stats'));
    }

    public function payCommission(Request $request, ReferralEarning $earning)
    {
        if ($earning->status === 'paid') {
            return response()->json([
                'success' => false,
                'message' => 'Commission already paid',
            ], 400);
        }

        $earning->update(['status' => 'paid']);

        // Add to user balance
        $earning->user->increment('balance', $earning->commission_amount);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Commission paid successfully',
            ]);
        }

        return redirect()->back()->with('success', 'Commission paid successfully');
    }

    public function bulkPayCommissions(Request $request)
    {
        $count = 0;

        // If pay_all is set, pay all pending commissions
        if ($request->boolean('pay_all')) {
            $pendingEarnings = ReferralEarning::where('status', 'pending')->with('user')->get();

            foreach ($pendingEarnings as $earning) {
                $earning->update(['status' => 'paid']);
                $earning->user->increment('balance', $earning->commission_amount);
                $count++;
            }
        } else {
            $validated = $request->validate([
                'earning_ids' => 'required|array',
                'earning_ids.*' => 'exists:referral_earnings,id',
            ]);

            foreach ($validated['earning_ids'] as $id) {
                $earning = ReferralEarning::find($id);
                if ($earning && $earning->status === 'pending') {
                    $earning->update(['status' => 'paid']);
                    $earning->user->increment('balance', $earning->commission_amount);
                    $count++;
                }
            }
        }

        return response()->json([
            'success' => true,
            'message' => "{$count} commissions paid successfully",
        ]);
    }
}
