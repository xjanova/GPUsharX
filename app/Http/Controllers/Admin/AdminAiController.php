<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\GenerationJob;
use App\Models\Package;
use App\Models\User;
use App\Models\GpuNode;
use App\Models\Earning;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Carbon\Carbon;

class AdminAiController extends Controller
{
    public function aiModels(Request $request): View
    {
        $query = AiModel::query();

        if ($request->has('search') && $request->search) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('model_id', 'like', "%{$search}%")
                    ->orWhere('provider', 'like', "%{$search}%");
            });
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        if ($request->has('category') && $request->category) {
            $query->where('category', $request->category);
        }

        $models = $query->orderBy('popularity', 'desc')->paginate(20);

        $stats = [
            'total' => AiModel::count(),
            'image_models' => AiModel::where('type', 'image')->count(),
            'video_models' => AiModel::where('type', 'video')->count(),
            'active' => AiModel::where('is_active', true)->count(),
            'featured' => AiModel::where('is_featured', true)->count(),
        ];

        $categories = AiModel::distinct()->pluck('category')->filter();

        return view('admin.ai-models', compact('models', 'stats', 'categories'));
    }

    public function toggleModelStatus(Request $request, AiModel $model)
    {
        $model->update(['is_active' => !$model->is_active]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_active' => $model->is_active,
                'message' => $model->is_active ? 'Model activated' : 'Model deactivated',
            ]);
        }

        return redirect()->back()->with('success', 'Model status updated');
    }

    public function toggleModelFeatured(Request $request, AiModel $model)
    {
        $model->update(['is_featured' => !$model->is_featured]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'is_featured' => $model->is_featured,
                'message' => $model->is_featured ? 'Model featured' : 'Model unfeatured',
            ]);
        }

        return redirect()->back()->with('success', 'Model featured status updated');
    }

    public function generations(Request $request): View
    {
        $query = GenerationJob::with(['user', 'aiModel']);

        if ($request->has('search') && $request->search) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('job_id', 'like', "%{$search}%")
                    ->orWhere('prompt', 'like', "%{$search}%");
            });
        }

        if ($request->has('status') && $request->status) {
            $query->where('status', $request->status);
        }

        if ($request->has('type') && $request->type) {
            $query->where('type', $request->type);
        }

        $generations = $query->orderBy('created_at', 'desc')->paginate(20);

        $stats = [
            'total' => GenerationJob::count(),
            'pending' => GenerationJob::where('status', 'pending')->count(),
            'processing' => GenerationJob::where('status', 'processing')->count(),
            'completed' => GenerationJob::where('status', 'completed')->count(),
            'failed' => GenerationJob::where('status', 'failed')->count(),
            'total_credits' => GenerationJob::sum('credits_used'),
        ];

        return view('admin.generations', compact('generations', 'stats'));
    }

    public function runMigrations(Request $request)
    {
        try {
            // Run migrations
            Artisan::call('migrate', ['--force' => true]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => 'Migrations completed successfully',
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Migration failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function runSeeders(Request $request)
    {
        try {
            $seeder = $request->input('seeder', 'AiModelSeeder');

            // Validate seeder name
            $allowedSeeders = ['AiModelSeeder', 'DatabaseSeeder'];
            if (!in_array($seeder, $allowedSeeders)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid seeder specified',
                ], 400);
            }

            Artisan::call('db:seed', [
                '--class' => $seeder,
                '--force' => true,
            ]);
            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => "Seeder {$seeder} completed successfully",
                'output' => $output,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Seeder failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function refreshAiModels(Request $request)
    {
        try {
            // Re-run the AI model seeder to refresh/update models
            Artisan::call('db:seed', [
                '--class' => 'AiModelSeeder',
                '--force' => true,
            ]);
            $output = Artisan::output();

            $modelCount = AiModel::count();

            return response()->json([
                'success' => true,
                'message' => "AI Models refreshed successfully. Total models: {$modelCount}",
                'output' => $output,
                'model_count' => $modelCount,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Refresh failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function checkUpdates(Request $request)
    {
        try {
            // Check for pending migrations
            Artisan::call('migrate:status');
            $migrateOutput = Artisan::output();

            $pendingMigrations = substr_count($migrateOutput, '| No') > 0 ||
                                  str_contains($migrateOutput, 'Pending');

            // Check AI models count
            $modelCount = AiModel::count();

            return response()->json([
                'success' => true,
                'pending_migrations' => $pendingMigrations,
                'model_count' => $modelCount,
                'migrate_output' => $migrateOutput,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Check failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function deleteGeneration(Request $request, GenerationJob $generation)
    {
        try {
            $generation->delete();

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Generation deleted successfully',
                ]);
            }

            return redirect()->back()->with('success', 'Generation deleted');
        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Delete failed: ' . $e->getMessage(),
                ], 500);
            }

            return redirect()->back()->with('error', 'Delete failed: ' . $e->getMessage());
        }
    }

    // ==================== Edit Model ====================
    public function editModel(AiModel $model): View
    {
        return view('admin.ai-models-edit', compact('model'));
    }

    public function updateModel(Request $request, AiModel $model)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'vram_required_mb' => 'required|integer|min:1024',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'default_params' => 'nullable|string',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        if ($request->has('default_params')) {
            $validated['default_params'] = $request->default_params;
        }

        $model->update($validated);

        return redirect()->route('admin.ai-models')->with('success', 'Model updated successfully');
    }

    public function uploadThumbnail(Request $request, AiModel $model)
    {
        $request->validate([
            'thumbnail' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            $file = $request->file('thumbnail');
            $filename = 'model_' . $model->id . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('models/thumbnails', $filename, 'public');

            $model->update(['thumbnail' => '/storage/' . $path]);

            return response()->json([
                'success' => true,
                'thumbnail_url' => asset('storage/' . $path),
                'message' => 'Thumbnail uploaded successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Upload failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Model Store (HuggingFace) ====================
    public function modelStore(): View
    {
        $installedModels = AiModel::pluck('huggingface_id')->toArray();
        return view('admin.model-store', compact('installedModels'));
    }

    public function searchHuggingFace(Request $request)
    {
        $query = $request->input('query', 'stable-diffusion');
        $type = $request->input('type', 'text-to-image');
        $sort = $request->input('sort', 'downloads');

        try {
            // Build search parameters
            $params = [
                'search' => $query,
                'sort' => $sort,
                'direction' => -1,
                'limit' => 30,
            ];

            // Add pipeline filter based on type
            if ($type) {
                $params['pipeline_tag'] = $type;
            }

            // Get HuggingFace token from settings
            $hfToken = \App\Models\SiteSetting::get('huggingface_token');

            $httpClient = Http::timeout(30)
                ->withHeaders([
                    'Accept' => 'application/json',
                ]);

            // Disable SSL verification in local/development environment
            if (app()->environment('local', 'development')) {
                $httpClient = $httpClient->withoutVerifying();
            }

            // Add authorization if token is available
            if ($hfToken) {
                $httpClient = $httpClient->withToken($hfToken);
            }

            $response = $httpClient->get('https://huggingface.co/api/models', $params);

            if ($response->successful()) {
                $models = collect($response->json())->map(function ($model) {
                    return [
                        'id' => $model['id'] ?? '',
                        'name' => $model['id'] ?? '',
                        'downloads' => $model['downloads'] ?? 0,
                        'likes' => $model['likes'] ?? 0,
                        'pipeline_tag' => $model['pipeline_tag'] ?? '',
                        'tags' => $model['tags'] ?? [],
                        'lastModified' => $model['lastModified'] ?? null,
                        'author' => $model['author'] ?? null,
                        'private' => $model['private'] ?? false,
                    ];
                })->filter(function ($model) {
                    // Filter out private models and models without ID
                    return !empty($model['id']) && !$model['private'];
                })->values();

                return response()->json([
                    'success' => true,
                    'models' => $models,
                    'count' => $models->count(),
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch from HuggingFace. Status: ' . $response->status(),
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function importModel(Request $request)
    {
        $validated = $request->validate([
            'huggingface_id' => 'required|string',
            'name' => 'required|string|max:255',
            'type' => 'required|in:image,video,audio,text,3d',
            'category' => 'required|string|max:50',
            'vram_required_mb' => 'required|integer|min:1024',
            'description' => 'nullable|string',
        ]);

        // Check if already exists
        if (AiModel::where('huggingface_id', $validated['huggingface_id'])->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Model already exists in database',
            ], 422);
        }

        try {
            $model = AiModel::create([
                'model_id' => Str::slug($validated['name']),
                'name' => $validated['name'],
                'huggingface_id' => $validated['huggingface_id'],
                'type' => $validated['type'],
                'category' => $validated['category'],
                'description' => $validated['description'] ?? '',
                'vram_required_mb' => $validated['vram_required_mb'],
                'is_active' => true,
                'is_featured' => false,
                'popularity' => 0,
                'status' => 'available',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Model imported successfully',
                'model' => $model,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Import failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    // ==================== Analytics ====================
    public function analytics(): View
    {
        $today = Carbon::today();
        $last30Days = Carbon::now()->subDays(30);

        // Overview stats
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('updated_at', '>=', Carbon::now()->subDays(7))->count(),
            'total_nodes' => GpuNode::count(),
            'online_nodes' => GpuNode::where('status', 'online')->count(),
            'total_jobs' => GenerationJob::count(),
            'completed_jobs' => GenerationJob::where('status', 'completed')->count(),
            'total_credits_used' => GenerationJob::sum('credits_used') ?: 0,
            'total_earnings' => Earning::sum('platform_fee') ?: 0,
        ];

        // Daily jobs chart (last 30 days)
        $dailyJobs = GenerationJob::select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as total'),
                DB::raw('SUM(CASE WHEN status = "completed" THEN 1 ELSE 0 END) as completed'),
                DB::raw('SUM(credits_used) as credits')
            )
            ->where('created_at', '>=', $last30Days)
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get();

        // Model usage
        $modelUsage = GenerationJob::select(
                'ai_model_id',
                DB::raw('COUNT(*) as uses'),
                DB::raw('SUM(credits_used) as credits')
            )
            ->where('created_at', '>=', $last30Days)
            ->groupBy('ai_model_id')
            ->orderByDesc('uses')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $model = AiModel::find($item->ai_model_id);
                return [
                    'name' => $model->name ?? 'Unknown',
                    'uses' => $item->uses,
                    'credits' => round($item->credits, 2),
                ];
            });

        // Top users
        $topUsers = GenerationJob::select(
                'user_id',
                DB::raw('COUNT(*) as jobs'),
                DB::raw('SUM(credits_used) as credits')
            )
            ->where('created_at', '>=', $last30Days)
            ->groupBy('user_id')
            ->orderByDesc('credits')
            ->limit(10)
            ->get()
            ->map(function ($item) {
                $user = User::find($item->user_id);
                return [
                    'name' => $user->name ?? 'Unknown',
                    'email' => $user->email ?? '',
                    'jobs' => $item->jobs,
                    'credits' => round($item->credits, 2),
                ];
            });

        return view('admin.analytics', compact('stats', 'dailyJobs', 'modelUsage', 'topUsers'));
    }

    // ==================== Packages ====================
    public function packages(): View
    {
        $packages = Package::orderBy('sort_order')->get();
        $stats = [
            'total' => Package::count(),
            'active' => Package::where('is_active', true)->count(),
            'credit_packages' => Package::where('type', 'credits')->count(),
            'subscription_packages' => Package::where('type', 'subscription')->count(),
        ];

        return view('admin.packages', compact('packages', 'stats'));
    }

    public function storePackage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:packages,slug',
            'type' => 'required|in:credits,subscription',
            'billing_period' => 'required|in:one_time,monthly,yearly',
            'price' => 'required|numeric|min:0',
            'credits_amount' => 'required_if:type,credits|integer|min:0',
            'monthly_credits' => 'required_if:type,subscription|integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');
        $validated['sort_order'] = Package::max('sort_order') + 1;

        Package::create($validated);

        return redirect()->back()->with('success', 'Package created successfully');
    }

    public function updatePackage(Request $request, Package $package)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'credits_amount' => 'integer|min:0',
            'monthly_credits' => 'integer|min:0',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        $validated['is_active'] = $request->boolean('is_active');
        $validated['is_featured'] = $request->boolean('is_featured');

        $package->update($validated);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'message' => 'Package updated']);
        }

        return redirect()->back()->with('success', 'Package updated successfully');
    }

    public function deletePackage(Package $package)
    {
        $package->delete();
        return redirect()->back()->with('success', 'Package deleted successfully');
    }

    public function togglePackage(Package $package)
    {
        $package->update(['is_active' => !$package->is_active]);
        return response()->json([
            'success' => true,
            'is_active' => $package->is_active,
        ]);
    }

    /**
     * Admin Playground - Test AI Models
     */
    public function playground(Request $request): View
    {
        $models = AiModel::where('is_active', true)
            ->orderBy('is_featured', 'desc')
            ->orderBy('name')
            ->get();

        $workers = GpuNode::where('status', 'online')
            ->where('is_verified', true)
            ->get();

        $recentTests = GenerationJob::where('created_by', auth()->id())
            ->where('is_admin_test', true)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        $stats = [
            'total_models' => AiModel::count(),
            'active_models' => AiModel::where('is_active', true)->count(),
            'online_workers' => $workers->count(),
            'total_vram' => $workers->sum('gpu_vram_mb'),
        ];

        return view('admin.playground', compact('models', 'workers', 'recentTests', 'stats'));
    }

    /**
     * Run test generation from Playground
     */
    public function runTest(Request $request)
    {
        $request->validate([
            'model_id' => 'required|exists:ai_models,id',
            'prompt' => 'required|string|max:2000',
            'negative_prompt' => 'nullable|string|max:1000',
            'width' => 'nullable|integer|min:256|max:2048',
            'height' => 'nullable|integer|min:256|max:2048',
            'steps' => 'nullable|integer|min:1|max:150',
            'cfg_scale' => 'nullable|numeric|min:1|max:30',
            'seed' => 'nullable|integer',
            'sampler' => 'nullable|string',
        ]);

        $model = AiModel::findOrFail($request->model_id);

        // Check if there are any available workers (skip VRAM check for admin tests)
        $availableWorker = GpuNode::where('status', 'online')
            ->where('is_verified', true)
            ->first();

        if (!$availableWorker) {
            return response()->json([
                'success' => false,
                'message' => 'ไม่มี Worker ที่ออนไลน์และได้รับการยืนยันแล้ว',
            ], 422);
        }

        // Parse size
        $width = $request->width ?? 1024;
        $height = $request->height ?? 1024;
        $seed = $request->seed ?? rand(1, 999999999);

        // Create test generation job
        $jobId = 'TEST-' . Str::upper(Str::random(12));

        $generationJob = GenerationJob::create([
            'job_id' => $jobId,
            'user_id' => auth()->id(),
            'ai_model_id' => $model->id,
            'type' => $model->type ?? 'image',
            'prompt' => $request->prompt,
            'negative_prompt' => $request->negative_prompt,
            'params' => [
                'width' => $width,
                'height' => $height,
                'steps' => $request->steps ?? 30,
                'cfg_scale' => $request->cfg_scale ?? 7.5,
                'seed' => $seed,
                'sampler' => $request->sampler ?? 'dpm++_2m',
            ],
            'status' => 'queued',
            'is_admin_test' => true,
            'credits_used' => 0, // Free for admin tests
            'created_by' => auth()->id(),
        ]);

        // Create RenderJob for workers to pick up
        $renderJob = \App\Models\RenderJob::create([
            'job_id' => $jobId,
            'title' => 'Admin Test: ' . Str::limit($request->prompt, 50),
            'description' => 'Admin playground test generation',
            'type' => $model->type ?? 'image',
            'priority' => 'urgent', // High priority for admin tests
            'status' => 'pending',
            'estimated_credits' => 10,
            'required_vram_mb' => $model->vram_required_mb,
            'job_params' => [
                'generation_job_id' => $generationJob->id,
                'prompt' => $request->prompt,
                'negative_prompt' => $request->negative_prompt ?? '',
                'model_id' => $model->model_id,
                'huggingface_id' => $model->huggingface_id,
                'width' => $width,
                'height' => $height,
                'steps' => $request->steps ?? 30,
                'cfg_scale' => $request->cfg_scale ?? 7.5,
                'seed' => $seed,
                'sampler' => $request->sampler ?? 'dpm++_2m',
                'is_admin_test' => true,
            ],
            'created_by' => auth()->id(),
        ]);

        // Split into chunks (1 chunk for simple generation)
        $renderJob->splitIntoChunks(1);

        return response()->json([
            'success' => true,
            'message' => 'ส่งงานทดสอบเรียบร้อย กำลังรอ Worker รับงาน',
            'job_id' => $jobId,
            'redirect' => route('admin.playground.status', $jobId),
        ]);
    }

    /**
     * Get test job status
     */
    public function testStatus(string $jobId)
    {
        $job = GenerationJob::where('job_id', $jobId)
            ->where('is_admin_test', true)
            ->firstOrFail();

        return response()->json([
            'job_id' => $job->job_id,
            'status' => $job->status,
            'progress' => $job->progress ?? 0,
            'result_url' => $job->result_url,
            'result_thumbnail' => $job->result_thumbnail,
            'error_message' => $job->error_message,
            'processing_time' => $job->processing_time,
            'worker' => $job->gpuNode ? [
                'name' => $job->gpuNode->name,
                'gpu' => $job->gpuNode->gpu_model,
            ] : null,
            'created_at' => $job->created_at->format('Y-m-d H:i:s'),
            'completed_at' => $job->completed_at?->format('Y-m-d H:i:s'),
        ]);
    }
}
