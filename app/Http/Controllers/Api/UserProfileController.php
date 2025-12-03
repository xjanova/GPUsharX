<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\CreditPurchase;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserProfileController extends Controller
{
    /**
     * Get current user profile
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'success' => true,
            'data' => [
                'profile' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'avatar' => $user->avatar,
                    'phone' => $user->phone,
                    'country' => $user->country,
                    'timezone' => $user->timezone,
                    'language' => $user->language,
                    'referral_code' => $user->referral_code,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ],
                'stats' => [
                    'credits' => round($user->credits, 2),
                    'balance' => round($user->balance, 2),
                    'pending_earnings' => round($user->pending_earnings, 2),
                    'total_earned' => round($user->total_earned, 2),
                    'total_credits_purchased' => $user->total_credits_purchased,
                    'total_jobs' => $user->generationJobs()->count(),
                    'completed_jobs' => $user->generationJobs()->where('status', 'completed')->count(),
                    'total_nodes' => $user->gpuNodes()->count(),
                    'online_nodes' => $user->gpuNodes()->online()->count(),
                ],
                'subscription' => $this->getActiveSubscription($user),
                'referral_stats' => $this->getReferralStats($user),
            ],
        ]);
    }

    /**
     * Update profile
     */
    public function update(Request $request): JsonResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'name' => 'string|max:255',
            'phone' => 'nullable|string|max:20',
            'country' => 'nullable|string|max:2',
            'timezone' => 'nullable|string|max:50',
            'language' => 'nullable|string|in:en,th',
        ]);

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Profile updated successfully',
            'data' => $user->only(['id', 'name', 'email', 'phone', 'country', 'timezone', 'language']),
        ]);
    }

    /**
     * Update avatar
     */
    public function updateAvatar(Request $request): JsonResponse
    {
        $request->validate([
            'avatar' => 'required|image|max:2048', // 2MB max
        ]);

        $user = $request->user();

        // Delete old avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Store new avatar
        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return response()->json([
            'success' => true,
            'message' => 'Avatar updated successfully',
            'data' => [
                'avatar' => Storage::disk('public')->url($path),
            ],
        ]);
    }

    /**
     * Change password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => 'required|string',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        $user = $request->user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Current password is incorrect',
            ], 422);
        }

        $user->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password changed successfully',
        ]);
    }

    /**
     * Get credit purchase history
     */
    public function purchaseHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $purchases = CreditPurchase::where('user_id', $user->id)
            ->with('package:id,name')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $purchases,
        ]);
    }

    /**
     * Get job history
     */
    public function jobHistory(Request $request): JsonResponse
    {
        $user = $request->user();

        $jobs = $user->generationJobs()
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $jobs,
        ]);
    }

    /**
     * Get notification settings
     */
    public function getNotificationSettings(Request $request): JsonResponse
    {
        $user = $request->user();
        $settings = $user->notification_settings ?? [
            'email_job_complete' => true,
            'email_payout' => true,
            'email_newsletter' => false,
            'push_enabled' => true,
        ];

        return response()->json([
            'success' => true,
            'data' => $settings,
        ]);
    }

    /**
     * Update notification settings
     */
    public function updateNotificationSettings(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email_job_complete' => 'boolean',
            'email_payout' => 'boolean',
            'email_newsletter' => 'boolean',
            'push_enabled' => 'boolean',
        ]);

        $user = $request->user();
        $user->update(['notification_settings' => $validated]);

        return response()->json([
            'success' => true,
            'message' => 'Notification settings updated',
            'data' => $validated,
        ]);
    }

    /**
     * Get API tokens
     */
    public function getApiTokens(Request $request): JsonResponse
    {
        $user = $request->user();

        $tokens = $user->tokens()
            ->select('id', 'name', 'abilities', 'last_used_at', 'created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $tokens,
        ]);
    }

    /**
     * Create API token
     */
    public function createApiToken(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'abilities' => 'array',
        ]);

        $user = $request->user();
        $token = $user->createToken(
            $validated['name'],
            $validated['abilities'] ?? ['*']
        );

        return response()->json([
            'success' => true,
            'message' => 'API token created',
            'data' => [
                'token' => $token->plainTextToken,
                'name' => $validated['name'],
            ],
        ]);
    }

    /**
     * Revoke API token
     */
    public function revokeApiToken(Request $request, int $tokenId): JsonResponse
    {
        $user = $request->user();
        $user->tokens()->where('id', $tokenId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'API token revoked',
        ]);
    }

    /**
     * Delete account
     */
    public function deleteAccount(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'password' => 'required|string',
            'confirmation' => 'required|in:DELETE',
        ]);

        $user = $request->user();

        if (!Hash::check($validated['password'], $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Password is incorrect',
            ], 422);
        }

        // Check for pending balance
        if ($user->balance > 0 || $user->pending_earnings > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Please withdraw your balance before deleting account',
            ], 422);
        }

        // Soft delete or actual delete based on your needs
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Account deleted successfully',
        ]);
    }

    /**
     * Get active subscription
     */
    protected function getActiveSubscription(User $user): ?array
    {
        $subscription = UserSubscription::where('user_id', $user->id)
            ->active()
            ->with('package')
            ->first();

        if (!$subscription) {
            return null;
        }

        return [
            'id' => $subscription->id,
            'package_name' => $subscription->package->name,
            'status' => $subscription->status,
            'started_at' => $subscription->started_at,
            'expires_at' => $subscription->expires_at,
            'auto_renew' => $subscription->auto_renew,
            'features' => $subscription->package->features,
        ];
    }

    /**
     * Get referral stats
     */
    protected function getReferralStats(User $user): array
    {
        $directReferrals = User::where('referred_by', $user->id)->count();

        return [
            'referral_code' => $user->referral_code,
            'referral_link' => url('/register?ref=' . $user->referral_code),
            'direct_referrals' => $directReferrals,
            'total_commission' => round($user->referral_earnings ?? 0, 2),
        ];
    }
}
