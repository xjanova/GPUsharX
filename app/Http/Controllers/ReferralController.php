<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\ReferralTree;
use App\Models\EarningsShowcase;
use App\Models\PlatformSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class ReferralController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        // Get referral stats
        $directReferrals = User::where('referred_by', $user->id)->count();
        $totalTeam = $this->getTotalTeamCount($user->id);

        // Get referral tree
        $referralTree = ReferralTree::where('referrer_id', $user->id)
            ->with('user')
            ->orderBy('level')
            ->get()
            ->groupBy('level');

        // Get earnings from referrals
        $referralEarnings = $user->referralEarnings()
            ->with('fromUser')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Commission rates
        $commissionRates = [
            1 => PlatformSetting::get('referral_level_1_rate', 5),
            2 => PlatformSetting::get('referral_level_2_rate', 2),
            3 => PlatformSetting::get('referral_level_3_rate', 1),
        ];

        // Generate QR Code
        $referralUrl = route('register', ['ref' => $user->referral_code]);
        $qrCode = base64_encode(QrCode::format('svg')
            ->size(200)
            ->backgroundColor(26, 26, 46)
            ->color(168, 85, 247)
            ->generate($referralUrl));

        return view('referral.index', compact(
            'user',
            'directReferrals',
            'totalTeam',
            'referralTree',
            'referralEarnings',
            'commissionRates',
            'referralUrl',
            'qrCode'
        ));
    }

    public function showcase()
    {
        $showcases = EarningsShowcase::public()
            ->verified()
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $topEarners = User::orderBy('total_earned', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'total_earned', 'referral_code']);

        return view('referral.showcase', compact('showcases', 'topEarners'));
    }

    public function createShowcase(Request $request)
    {
        $validated = $request->validate([
            'period' => 'required|in:daily,weekly,monthly,total',
            'message' => 'nullable|string|max:500',
            'is_public' => 'boolean',
        ]);

        $user = Auth::user();

        // Calculate display amount based on period
        $amount = match($validated['period']) {
            'daily' => $user->earnings()->whereDate('created_at', today())->sum('net_amount'),
            'weekly' => $user->earnings()->whereBetween('created_at', [now()->startOfWeek(), now()])->sum('net_amount'),
            'monthly' => $user->earnings()->whereMonth('created_at', now()->month)->sum('net_amount'),
            'total' => $user->total_earned,
        };

        EarningsShowcase::create([
            'user_id' => $user->id,
            'display_amount' => $amount,
            'period' => $validated['period'],
            'message' => $validated['message'] ?? null,
            'is_verified' => true, // Auto-verify since we calculate from actual data
            'is_public' => $validated['is_public'] ?? true,
        ]);

        return back()->with('success', 'Showcase created successfully!');
    }

    protected function getTotalTeamCount(int $userId): int
    {
        $count = 0;
        $directReferrals = User::where('referred_by', $userId)->pluck('id');
        $count += $directReferrals->count();

        foreach ($directReferrals as $refId) {
            $count += $this->getTotalTeamCount($refId);
        }

        return $count;
    }

    public function generateQr()
    {
        $user = Auth::user();
        $referralUrl = route('register', ['ref' => $user->referral_code]);

        $qrCode = QrCode::format('png')
            ->size(400)
            ->backgroundColor(26, 26, 46)
            ->color(168, 85, 247)
            ->generate($referralUrl);

        return response($qrCode)->header('Content-Type', 'image/png');
    }
}
