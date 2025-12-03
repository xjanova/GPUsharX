<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CreditTransaction;
use App\Models\Earning;
use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\ManualPayment;
use App\Models\Payout;
use App\Models\PoolStatistic;
use App\Models\RenderJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Http;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'total_users' => User::count(),
            'new_users_today' => User::whereDate('created_at', today())->count(),
            'total_nodes' => GpuNode::count(),
            'active_nodes' => GpuNode::online()->count(),
            'total_hashrate' => GpuNode::online()->sum('hashrate'),
            'pending_jobs' => RenderJob::whereIn('status', ['pending', 'queued'])->count(),
            'processing_jobs' => RenderJob::where('status', 'processing')->count(),
            'completed_jobs_today' => RenderJob::whereDate('completed_at', today())->count(),
            'total_earnings_distributed' => Earning::sum('net_amount'),
            'platform_revenue' => Earning::sum('platform_fee'),
            'pending_payouts' => Payout::where('status', 'pending')->count(),
            'pending_payout_amount' => Payout::where('status', 'pending')->sum('amount'),
        ];

        $recentJobs = RenderJob::with('creator')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $topNodes = GpuNode::with('user')
            ->online()
            ->orderBy('hashrate', 'desc')
            ->limit(10)
            ->get();

        $recentEarnings = Earning::with(['user', 'gpuNode'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        // Chart data for last 7 days
        $chartData = PoolStatistic::where('date', '>=', now()->subDays(7))
            ->orderBy('date', 'asc')
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentJobs',
            'topNodes',
            'recentEarnings',
            'chartData'
        ));
    }

    public function users(Request $request): View
    {
        $query = User::withCount('gpuNodes');

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('referral_code', 'like', "%{$search}%");
            });
        }

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20);

        return view('admin.users', compact('users'));
    }

    public function userDetail(User $user): View
    {
        $user->load(['gpuNodes', 'earnings', 'payouts', 'referrals']);

        $earningStats = [
            'total_earned' => $user->total_earned,
            'pending' => $user->pending_earnings,
            'balance' => $user->balance,
            'withdrawn' => $user->total_withdrawn,
        ];

        $recentEarnings = $user->earnings()
            ->with('gpuNode')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.user-detail', compact('user', 'earningStats', 'recentEarnings'));
    }

    public function updateUserStatus(Request $request, User $user)
    {
        $validated = $request->validate([
            'status' => 'required|in:active,suspended,banned',
        ]);

        $user->update(['status' => $validated['status']]);

        // If banned, also ban all nodes
        if ($validated['status'] === 'banned') {
            $user->gpuNodes()->update(['status' => 'banned']);
        }

        return redirect()->back()->with('success', 'User status updated');
    }

    public function nodes(Request $request): View
    {
        $query = GpuNode::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('node_id', 'like', "%{$search}%")
                    ->orWhere('gpu_model', 'like', "%{$search}%");
            });
        }

        $nodes = $query->orderBy('hashrate', 'desc')->paginate(20);

        $nodeStats = [
            'total' => GpuNode::count(),
            'online' => GpuNode::where('status', 'online')->count(),
            'working' => GpuNode::where('status', 'working')->count(),
            'idle' => GpuNode::where('status', 'idle')->count(),
            'offline' => GpuNode::where('status', 'offline')->count(),
            'banned' => GpuNode::where('status', 'banned')->count(),
        ];

        return view('admin.nodes', compact('nodes', 'nodeStats'));
    }

    public function jobs(Request $request): View
    {
        $query = RenderJob::with('creator');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->has('type')) {
            $query->where('type', $request->input('type'));
        }

        $jobs = $query->orderBy('created_at', 'desc')->paginate(20);

        $jobStats = [
            'pending' => RenderJob::where('status', 'pending')->count(),
            'queued' => RenderJob::where('status', 'queued')->count(),
            'processing' => RenderJob::where('status', 'processing')->count(),
            'completed' => RenderJob::where('status', 'completed')->count(),
            'failed' => RenderJob::where('status', 'failed')->count(),
        ];

        return view('admin.jobs', compact('jobs', 'jobStats'));
    }

    public function createJob(): View
    {
        return view('admin.create-job');
    }

    public function storeJob(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:image,video,animation,3d_render',
            'priority' => 'required|in:low,normal,high,urgent',
            'estimated_credits' => 'required|integer|min:1',
            'required_vram_mb' => 'required|integer|min:2048',
            'job_params' => 'nullable|array',
            'num_chunks' => 'nullable|integer|min:1|max:100',
        ]);

        $job = RenderJob::create([
            'job_id' => 'JOB-' . strtoupper(\Illuminate\Support\Str::random(12)),
            'title' => $validated['title'],
            'description' => $validated['description'],
            'type' => $validated['type'],
            'priority' => $validated['priority'],
            'estimated_credits' => $validated['estimated_credits'],
            'required_vram_mb' => $validated['required_vram_mb'],
            'job_params' => $validated['job_params'] ?? [],
            'created_by' => auth()->id(),
        ]);

        $job->splitIntoChunks($validated['num_chunks'] ?? null);

        return redirect()->route('admin.jobs')->with('success', 'Job created and queued');
    }

    public function payouts(Request $request): View
    {
        $query = Payout::with('user');

        if ($request->has('status')) {
            $query->where('status', $request->input('status'));
        }

        $payouts = $query->orderBy('created_at', 'desc')->paginate(20);

        $payoutStats = [
            'pending' => Payout::where('status', 'pending')->sum('amount'),
            'processing' => Payout::where('status', 'processing')->sum('amount'),
            'completed_today' => Payout::where('status', 'completed')
                ->whereDate('processed_at', today())
                ->sum('amount'),
            'total_paid' => Payout::where('status', 'completed')->sum('amount'),
        ];

        return view('admin.payouts', compact('payouts', 'payoutStats'));
    }

    public function processPayout(Request $request, Payout $payout)
    {
        $validated = $request->validate([
            'action' => 'required|in:process,complete,fail',
            'transaction_id' => 'required_if:action,complete|nullable|string',
            'reason' => 'required_if:action,fail|nullable|string',
        ]);

        switch ($validated['action']) {
            case 'process':
                $payout->process();
                break;
            case 'complete':
                $payout->complete($validated['transaction_id']);
                break;
            case 'fail':
                $payout->fail($validated['reason']);
                break;
        }

        return redirect()->back()->with('success', 'Payout updated');
    }

    public function settings(): View
    {
        $settings = \App\Models\SiteSetting::all()->groupBy('group');
        return view('admin.settings', compact('settings'));
    }

    /**
     * Update site settings
     */
    public function updateSettings(Request $request)
    {
        $request->validate([
            'site_name' => 'required|string|max:255',
            'site_tagline' => 'nullable|string|max:500',
            'site_description' => 'nullable|string|max:1000',
            'contact_email' => 'nullable|email|max:255',
            'primary_color' => 'nullable|string|max:20',
            'secondary_color' => 'nullable|string|max:20',
            'site_logo' => 'nullable|image|mimes:png,jpg,jpeg,svg,webp|max:2048',
            'site_favicon' => 'nullable|image|mimes:png,ico,svg|max:512',
            'facebook_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'discord_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'footer_text' => 'nullable|string|max:500',
        ]);

        // Handle text settings
        $textSettings = [
            'site_name', 'site_tagline', 'site_description', 'contact_email',
            'primary_color', 'secondary_color', 'facebook_url', 'twitter_url',
            'discord_url', 'github_url', 'footer_text', 'meta_keywords'
        ];

        foreach ($textSettings as $key) {
            if ($request->has($key)) {
                \App\Models\SiteSetting::set($key, $request->input($key));
            }
        }

        // Handle logo upload
        if ($request->hasFile('site_logo')) {
            $logo = $request->file('site_logo');
            $logoPath = $logo->store('settings', 'public');

            // Delete old logo
            $oldLogo = \App\Models\SiteSetting::get('site_logo');
            if ($oldLogo && \Storage::disk('public')->exists($oldLogo)) {
                \Storage::disk('public')->delete($oldLogo);
            }

            \App\Models\SiteSetting::set('site_logo', $logoPath);
        }

        // Handle favicon upload
        if ($request->hasFile('site_favicon')) {
            $favicon = $request->file('site_favicon');
            $faviconPath = $favicon->store('settings', 'public');

            // Delete old favicon
            $oldFavicon = \App\Models\SiteSetting::get('site_favicon');
            if ($oldFavicon && \Storage::disk('public')->exists($oldFavicon)) {
                \Storage::disk('public')->delete($oldFavicon);
            }

            \App\Models\SiteSetting::set('site_favicon', $faviconPath);
        }

        // Handle OG Image upload
        if ($request->hasFile('og_image')) {
            $ogImage = $request->file('og_image');
            $ogImagePath = $ogImage->store('settings', 'public');

            // Delete old OG image
            $oldOgImage = \App\Models\SiteSetting::get('og_image');
            if ($oldOgImage && \Storage::disk('public')->exists($oldOgImage)) {
                \Storage::disk('public')->delete($oldOgImage);
            }

            \App\Models\SiteSetting::set('og_image', $ogImagePath);
        }

        return redirect()->back()->with('success', 'บันทึกการตั้งค่าเรียบร้อยแล้ว');
    }

    /**
     * Delete a setting image (logo, favicon, etc.)
     */
    public function deleteSettingImage(Request $request)
    {
        $key = $request->input('key');
        $allowedKeys = ['site_logo', 'site_favicon', 'og_image', 'site_logo_light'];

        if (!in_array($key, $allowedKeys)) {
            return response()->json(['error' => 'Invalid key'], 400);
        }

        $currentValue = \App\Models\SiteSetting::get($key);
        if ($currentValue && \Storage::disk('public')->exists($currentValue)) {
            \Storage::disk('public')->delete($currentValue);
        }

        \App\Models\SiteSetting::set($key, null);

        return response()->json(['success' => true]);
    }

    /**
     * Payment Settings Page
     */
    public function paymentSettings(): View
    {
        return view('admin.payment-settings');
    }

    /**
     * Update Payment Settings
     */
    public function updatePaymentSettings(Request $request)
    {
        $request->validate([
            'payment_gateway' => 'required|in:stripe,omise,gbprimepay,manual',
            'stripe_key' => 'nullable|string|max:255',
            'stripe_secret' => 'nullable|string|max:255',
            'stripe_webhook_secret' => 'nullable|string|max:255',
            'omise_public_key' => 'nullable|string|max:255',
            'omise_secret_key' => 'nullable|string|max:255',
            'gbprimepay_token' => 'nullable|string|max:255',
            'gbprimepay_secret' => 'nullable|string|max:255',
            'promptpay_id' => 'nullable|string|max:20',
            'promptpay_name' => 'nullable|string|max:255',
        ]);

        // Save all payment settings
        $settings = [
            'payment_gateway',
            'stripe_key', 'stripe_secret', 'stripe_webhook_secret',
            'omise_public_key', 'omise_secret_key',
            'gbprimepay_token', 'gbprimepay_secret',
            'promptpay_id', 'promptpay_name',
        ];

        foreach ($settings as $key) {
            if ($request->has($key)) {
                \App\Models\SiteSetting::set($key, $request->input($key));
            }
        }

        // Handle checkboxes
        \App\Models\SiteSetting::set('promptpay_enabled', $request->has('promptpay_enabled') ? '1' : '0');
        \App\Models\SiteSetting::set('bank_transfer_enabled', $request->has('bank_transfer_enabled') ? '1' : '0');

        // Handle bank accounts as JSON
        $bankAccounts = $request->input('bank_accounts', []);
        $filteredAccounts = array_filter($bankAccounts, function($account) {
            return !empty($account['account_number']);
        });
        \App\Models\SiteSetting::set('bank_accounts', json_encode(array_values($filteredAccounts)));

        return redirect()->back()->with('success', 'บันทึกการตั้งค่า Payment Gateway เรียบร้อยแล้ว');
    }

    /**
     * Test Payment Gateway Connection
     */
    public function testPaymentConnection(Request $request)
    {
        $gateway = $request->input('gateway', 'stripe');

        try {
            switch ($gateway) {
                case 'stripe':
                    $secretKey = \App\Models\SiteSetting::get('stripe_secret');
                    if (!$secretKey) {
                        return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Stripe Secret Key']);
                    }
                    \Stripe\Stripe::setApiKey($secretKey);
                    $account = \Stripe\Account::retrieve();
                    return response()->json(['success' => true, 'message' => 'Connected to: ' . ($account->business_profile->name ?? $account->id)]);

                case 'omise':
                    $secretKey = \App\Models\SiteSetting::get('omise_secret_key');
                    if (!$secretKey) {
                        return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า Omise Secret Key']);
                    }
                    // Omise test would go here
                    return response()->json(['success' => true, 'message' => 'Omise API configured']);

                case 'gbprimepay':
                    $token = \App\Models\SiteSetting::get('gbprimepay_token');
                    if (!$token) {
                        return response()->json(['success' => false, 'message' => 'ยังไม่ได้ตั้งค่า GB Prime Pay Token']);
                    }
                    return response()->json(['success' => true, 'message' => 'GB Prime Pay configured']);

                case 'manual':
                    $promptpayEnabled = \App\Models\SiteSetting::get('promptpay_enabled');
                    $bankEnabled = \App\Models\SiteSetting::get('bank_transfer_enabled');
                    if (!$promptpayEnabled && !$bankEnabled) {
                        return response()->json(['success' => false, 'message' => 'ยังไม่ได้เปิดใช้งาน PromptPay หรือ Bank Transfer']);
                    }
                    return response()->json(['success' => true, 'message' => 'Manual payment configured']);

                default:
                    return response()->json(['success' => false, 'message' => 'Unknown gateway']);
            }
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * Cancel a job and refund credits to user
     */
    public function cancelJob(Request $request, RenderJob $job)
    {
        // Only allow canceling jobs that are not completed
        if ($job->status === 'completed') {
            return redirect()->back()->with('error', 'ไม่สามารถยกเลิกงานที่เสร็จแล้วได้');
        }

        $user = $job->creator;
        $refundAmount = $job->estimated_credits;

        // Cancel all pending/processing chunks
        $job->chunks()->whereIn('status', ['pending', 'processing', 'queued'])->update([
            'status' => 'cancelled',
        ]);

        // Update job status
        $job->update([
            'status' => 'cancelled',
            'completed_at' => now(),
        ]);

        // Refund credits to user
        if ($user && $refundAmount > 0) {
            $user->increment('credits', $refundAmount);

            // Log the refund
            \App\Models\CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'refund',
                'amount' => $refundAmount,
                'balance_after' => $user->credits + $refundAmount,
                'description' => "คืนเครดิตจากงานที่ถูกยกเลิก: {$job->job_id}",
                'reference_type' => 'render_job',
                'reference_id' => $job->id,
                'created_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', "ยกเลิกงานสำเร็จและคืนเครดิต {$refundAmount} ให้ผู้ใช้");
    }

    /**
     * Force fail a stuck job and refund
     */
    public function forceFailJob(Request $request, RenderJob $job)
    {
        if ($job->status === 'completed') {
            return redirect()->back()->with('error', 'ไม่สามารถ fail งานที่เสร็จแล้วได้');
        }

        $user = $job->creator;
        $refundAmount = $job->estimated_credits;

        // Fail all non-completed chunks
        $job->chunks()->whereNotIn('status', ['completed'])->update([
            'status' => 'failed',
            'error_message' => 'Force failed by admin',
        ]);

        $job->update([
            'status' => 'failed',
            'completed_at' => now(),
        ]);

        // Refund credits
        if ($user && $refundAmount > 0) {
            $user->increment('credits', $refundAmount);

            \App\Models\CreditTransaction::create([
                'user_id' => $user->id,
                'type' => 'refund',
                'amount' => $refundAmount,
                'balance_after' => $user->credits + $refundAmount,
                'description' => "คืนเครดิตจากงานที่ล้มเหลว: {$job->job_id}",
                'reference_type' => 'render_job',
                'reference_id' => $job->id,
                'created_by' => auth()->id(),
            ]);
        }

        return redirect()->back()->with('success', "ทำให้งานล้มเหลวและคืนเครดิต {$refundAmount} สำเร็จ");
    }

    /**
     * Retry a failed job
     */
    public function retryJob(Request $request, RenderJob $job)
    {
        if (!in_array($job->status, ['failed', 'cancelled'])) {
            return redirect()->back()->with('error', 'สามารถ retry ได้เฉพาะงานที่ failed หรือ cancelled เท่านั้น');
        }

        // Reset chunks to pending
        $job->chunks()->update([
            'status' => 'pending',
            'error_message' => null,
            'gpu_node_id' => null,
            'assigned_at' => null,
            'started_at' => null,
            'completed_at' => null,
        ]);

        $job->update([
            'status' => 'queued',
            'completed_chunks' => 0,
            'started_at' => null,
            'completed_at' => null,
        ]);

        return redirect()->back()->with('success', 'งานถูก restart เรียบร้อยแล้ว');
    }

    /**
     * Add credits to user
     */
    public function addCredits(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        $user->increment('credits', $validated['amount']);

        \App\Models\CreditTransaction::create([
            'user_id' => $user->id,
            'type' => 'admin_add',
            'amount' => $validated['amount'],
            'balance_after' => $user->credits,
            'description' => $validated['reason'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "เพิ่มเครดิต {$validated['amount']} ให้ {$user->name} สำเร็จ");
    }

    /**
     * Deduct credits from user
     */
    public function deductCredits(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:255',
        ]);

        if ($user->credits < $validated['amount']) {
            return redirect()->back()->with('error', 'ผู้ใช้มีเครดิตไม่เพียงพอ');
        }

        $user->decrement('credits', $validated['amount']);

        \App\Models\CreditTransaction::create([
            'user_id' => $user->id,
            'type' => 'admin_deduct',
            'amount' => -$validated['amount'],
            'balance_after' => $user->credits,
            'description' => $validated['reason'],
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "หักเครดิต {$validated['amount']} จาก {$user->name} สำเร็จ");
    }

    /**
     * Set user credits to specific value
     */
    public function setCredits(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'reason' => 'required|string|max:255',
        ]);

        $oldCredits = $user->credits;
        $user->update(['credits' => $validated['amount']]);

        $diff = $validated['amount'] - $oldCredits;
        \App\Models\CreditTransaction::create([
            'user_id' => $user->id,
            'type' => 'admin_set',
            'amount' => $diff,
            'balance_after' => $validated['amount'],
            'description' => $validated['reason'] . " (เดิม: {$oldCredits})",
            'created_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "ตั้งค่าเครดิตของ {$user->name} เป็น {$validated['amount']} สำเร็จ");
    }

    /**
     * Create new user
     */
    public function createUser(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:user,moderator,admin',
            'credits' => 'nullable|numeric|min:0',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => bcrypt($validated['password']),
            'role' => $validated['role'],
            'credits' => $validated['credits'] ?? 0,
            'status' => 'active',
        ]);

        return redirect()->route('admin.users')->with('success', "สร้างผู้ใช้ {$user->name} สำเร็จ");
    }

    /**
     * Update user info
     */
    public function updateUser(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required|in:user,moderator,admin',
            'status' => 'required|in:active,suspended,banned',
            'password' => 'nullable|min:8',
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'role' => $validated['role'],
            'status' => $validated['status'],
        ]);

        if (!empty($validated['password'])) {
            $user->update(['password' => bcrypt($validated['password'])]);
        }

        // If banned, also ban all nodes
        if ($validated['status'] === 'banned') {
            $user->gpuNodes()->update(['status' => 'banned']);
        }

        return redirect()->back()->with('success', 'อัปเดตข้อมูลผู้ใช้สำเร็จ');
    }

    /**
     * Delete user
     */
    public function deleteUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'ไม่สามารถลบบัญชีตัวเองได้');
        }

        // Ban all nodes first
        $user->gpuNodes()->update(['status' => 'banned']);

        // Soft delete or hard delete based on your preference
        $user->update(['status' => 'deleted']);
        // Or: $user->delete();

        return redirect()->route('admin.users')->with('success', 'ลบผู้ใช้สำเร็จ');
    }

    /**
     * Ban user
     */
    public function banUser(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'ไม่สามารถแบนตัวเองได้');
        }

        $user->update(['status' => 'banned']);
        $user->gpuNodes()->update(['status' => 'banned']);

        // Revoke all tokens
        $user->tokens()->delete();

        return redirect()->back()->with('success', "แบน {$user->name} สำเร็จ");
    }

    /**
     * Unban user
     */
    public function unbanUser(User $user)
    {
        $user->update(['status' => 'active']);

        return redirect()->back()->with('success', "ปลดแบน {$user->name} สำเร็จ");
    }

    /**
     * Node detail view
     */
    public function nodeDetail(GpuNode $node): View
    {
        $node->load(['user', 'earnings', 'jobChunks']);

        $stats = [
            'total_earnings' => $node->total_earnings,
            'total_completed' => $node->total_completed_chunks,
            'success_rate' => $node->success_rate,
            'avg_completion_time' => $node->avg_completion_time,
        ];

        $recentJobs = $node->jobChunks()
            ->with('renderJob')
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();

        return view('admin.node-detail', compact('node', 'stats', 'recentJobs'));
    }

    /**
     * Update node status
     */
    public function updateNodeStatus(Request $request, GpuNode $node)
    {
        $validated = $request->validate([
            'status' => 'required|in:online,offline,idle,banned,maintenance',
        ]);

        $node->update(['status' => $validated['status']]);

        return redirect()->back()->with('success', 'อัปเดตสถานะ Node สำเร็จ');
    }

    /**
     * Ban node
     */
    public function banNode(GpuNode $node)
    {
        $node->update(['status' => 'banned']);

        // Cancel any processing chunks
        $node->jobChunks()->where('status', 'processing')->update([
            'status' => 'pending',
            'gpu_node_id' => null,
            'assigned_at' => null,
        ]);

        return redirect()->back()->with('success', "แบน Node {$node->node_id} สำเร็จ");
    }

    /**
     * Unban node
     */
    public function unbanNode(GpuNode $node)
    {
        $node->update(['status' => 'offline']);

        return redirect()->back()->with('success', "ปลดแบน Node {$node->node_id} สำเร็จ");
    }

    /**
     * Delete node
     */
    public function deleteNode(GpuNode $node)
    {
        // Reassign any pending chunks
        $node->jobChunks()->whereIn('status', ['pending', 'processing'])->update([
            'status' => 'pending',
            'gpu_node_id' => null,
            'assigned_at' => null,
        ]);

        $node->delete();

        return redirect()->route('admin.nodes')->with('success', 'ลบ Node สำเร็จ');
    }

    /**
     * View credit transactions history
     */
    public function creditHistory(Request $request): View
    {
        $query = \App\Models\CreditTransaction::with(['user', 'creator']);

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        $transactions = $query->orderBy('created_at', 'desc')->paginate(50);

        return view('admin.credit-history', compact('transactions'));
    }

    /**
     * Manual Payments Management
     */
    public function manualPayments(Request $request): View
    {
        $query = ManualPayment::with('user');

        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->has('search')) {
            $search = $request->search;
            $query->whereHas('user', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $payments = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'pending' => ManualPayment::where('status', 'pending')->count(),
            'approved' => ManualPayment::where('status', 'approved')->count(),
            'rejected' => ManualPayment::where('status', 'rejected')->count(),
            'pending_amount' => ManualPayment::where('status', 'pending')->sum('amount'),
        ];

        return view('admin.manual-payments', compact('payments', 'stats'));
    }

    public function manualPaymentDetail(ManualPayment $payment): View
    {
        $payment->load(['user', 'processor']);
        return view('admin.manual-payment-detail', compact('payment'));
    }

    public function approveManualPayment(Request $request, ManualPayment $payment)
    {
        if (!$payment->isPending()) {
            return back()->with('error', 'รายการนี้ถูกดำเนินการไปแล้ว');
        }

        $user = $payment->user;
        $credits = $payment->credits;

        // Add credits to user
        $user->increment('credits', $credits);

        // Create credit transaction
        CreditTransaction::create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => $credits,
            'balance_after' => $user->credits,
            'description' => "ซื้อแพ็คเกจ " . config("stripe.packages.{$payment->package}.name", $payment->package) . " (Manual)",
            'reference_type' => 'manual_payment',
            'reference_id' => $payment->id,
            'created_by' => auth()->id(),
        ]);

        // Update payment status
        $payment->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', "อนุมัติการชำระเงินเรียบร้อย - เพิ่ม {$credits} เครดิตให้ {$user->name}");
    }

    public function rejectManualPayment(Request $request, ManualPayment $payment)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        if (!$payment->isPending()) {
            return back()->with('error', 'รายการนี้ถูกดำเนินการไปแล้ว');
        }

        $payment->update([
            'status' => 'rejected',
            'reject_reason' => $request->reason,
            'processed_by' => auth()->id(),
            'processed_at' => now(),
        ]);

        return back()->with('success', 'ปฏิเสธการชำระเงินเรียบร้อย');
    }

    /**
     * Update API Keys settings
     */
    public function updateApiKeys(Request $request)
    {
        $request->validate([
            'huggingface_token' => 'nullable|string|max:500',
            'openai_api_key' => 'nullable|string|max:500',
            'stability_api_key' => 'nullable|string|max:500',
        ]);

        $apiKeys = ['huggingface_token', 'openai_api_key', 'stability_api_key'];

        foreach ($apiKeys as $key) {
            if ($request->has($key)) {
                // Only update if value is provided (don't clear existing if empty)
                $value = $request->input($key);
                if ($value !== null && $value !== '') {
                    \App\Models\SiteSetting::set($key, $value);
                } elseif ($value === '') {
                    // Clear if explicitly set to empty
                    \App\Models\SiteSetting::set($key, null);
                }
            }
        }

        return redirect()->back()->with('success', 'บันทึก API Keys เรียบร้อยแล้ว');
    }

    /**
     * Test HuggingFace Token
     */
    public function testHuggingFaceToken(Request $request)
    {
        $token = $request->input('token');

        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'กรุณาใส่ Token',
            ]);
        }

        try {
            $httpClient = Http::timeout(10)
                ->withToken($token);

            // Disable SSL verification in local/development environment
            if (app()->environment('local', 'development')) {
                $httpClient = $httpClient->withoutVerifying();
            }

            $response = $httpClient->get('https://huggingface.co/api/whoami-v2');

            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'username' => $data['name'] ?? $data['fullname'] ?? 'User',
                    'message' => 'Token ถูกต้อง',
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Token ไม่ถูกต้องหรือหมดอายุ',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่สามารถเชื่อมต่อ HuggingFace ได้: ' . $e->getMessage(),
            ]);
        }
    }
}
