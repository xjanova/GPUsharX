<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Earning;
use App\Models\GpuNode;
use App\Models\JobChunk;
use App\Models\Payout;
use App\Models\PoolStatistic;
use App\Models\RenderJob;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;

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
        return view('admin.settings');
    }
}
