<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use App\Models\CreditPurchase;
use App\Models\UserSubscription;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PackageController extends Controller
{
    /**
     * List all packages
     */
    public function index(Request $request): JsonResponse
    {
        $query = Package::query();

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $packages = $query->orderBy('sort_order')
            ->get()
            ->map(function ($package) {
                return array_merge($package->toArray(), [
                    'total_sales' => CreditPurchase::where('package_id', $package->id)
                        ->where('status', 'completed')->count(),
                    'total_revenue' => CreditPurchase::where('package_id', $package->id)
                        ->where('status', 'completed')->sum('amount_paid'),
                    'active_subscriptions' => UserSubscription::where('package_id', $package->id)
                        ->where('status', 'active')->count(),
                ]);
            });

        return response()->json([
            'success' => true,
            'data' => [
                'packages' => $packages,
                'summary' => [
                    'total_packages' => $packages->count(),
                    'active_packages' => $packages->where('is_active', true)->count(),
                    'total_revenue' => $packages->sum('total_revenue'),
                ],
            ],
        ]);
    }

    /**
     * Create package
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:credits,subscription',
            'billing_period' => 'required|in:one_time,monthly,yearly',
            'price' => 'required|numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'currency' => 'string|size:3',
            'credits_amount' => 'integer|min:0',
            'bonus_credits' => 'integer|min:0',
            'monthly_credits' => 'integer|min:0',
            'priority_level' => 'integer|min:0|max:100',
            'unlimited_generations' => 'boolean',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        // Check slug uniqueness
        $counter = 1;
        $baseSlug = $validated['slug'];
        while (Package::where('slug', $validated['slug'])->exists()) {
            $validated['slug'] = $baseSlug . '-' . $counter;
            $counter++;
        }

        $package = Package::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Package created successfully',
            'data' => $package,
        ], 201);
    }

    /**
     * Update package
     */
    public function update(Request $request, Package $package): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'nullable|string',
            'price' => 'numeric|min:0',
            'original_price' => 'nullable|numeric|min:0',
            'credits_amount' => 'integer|min:0',
            'bonus_credits' => 'integer|min:0',
            'monthly_credits' => 'integer|min:0',
            'priority_level' => 'integer|min:0|max:100',
            'unlimited_generations' => 'boolean',
            'features' => 'nullable|array',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'sort_order' => 'integer',
        ]);

        $package->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Package updated successfully',
            'data' => $package->fresh(),
        ]);
    }

    /**
     * Toggle active status
     */
    public function toggleActive(Package $package): JsonResponse
    {
        $package->update(['is_active' => !$package->is_active]);

        return response()->json([
            'success' => true,
            'message' => $package->is_active ? 'Package activated' : 'Package deactivated',
            'data' => ['is_active' => $package->is_active],
        ]);
    }

    /**
     * Delete package
     */
    public function destroy(Package $package): JsonResponse
    {
        // Check for existing purchases/subscriptions
        $purchases = CreditPurchase::where('package_id', $package->id)->count();
        $subscriptions = UserSubscription::where('package_id', $package->id)->count();

        if ($purchases > 0 || $subscriptions > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete package with existing purchases or subscriptions',
            ], 422);
        }

        $package->delete();

        return response()->json([
            'success' => true,
            'message' => 'Package deleted successfully',
        ]);
    }

    /**
     * Seed default packages
     */
    public function seedDefaults(): JsonResponse
    {
        $defaults = Package::getDefaultPackages();
        $created = 0;

        foreach ($defaults as $data) {
            if (!Package::where('slug', $data['slug'])->exists()) {
                Package::create($data);
                $created++;
            }
        }

        return response()->json([
            'success' => true,
            'message' => "Created {$created} default packages",
            'data' => ['created' => $created],
        ]);
    }

    /**
     * Get sales statistics
     */
    public function salesStats(Request $request): JsonResponse
    {
        $days = $request->query('days', 30);
        $startDate = now()->subDays($days);

        // Daily sales
        $dailySales = CreditPurchase::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('DATE(created_at) as date, COUNT(*) as count, SUM(amount_paid) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Sales by package
        $salesByPackage = CreditPurchase::where('status', 'completed')
            ->where('created_at', '>=', $startDate)
            ->selectRaw('package_id, COUNT(*) as count, SUM(amount_paid) as revenue')
            ->groupBy('package_id')
            ->with('package:id,name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'period_days' => $days,
                'daily_sales' => $dailySales,
                'sales_by_package' => $salesByPackage,
                'summary' => [
                    'total_sales' => CreditPurchase::where('status', 'completed')
                        ->where('created_at', '>=', $startDate)->count(),
                    'total_revenue' => CreditPurchase::where('status', 'completed')
                        ->where('created_at', '>=', $startDate)->sum('amount_paid'),
                ],
            ],
        ]);
    }
}
