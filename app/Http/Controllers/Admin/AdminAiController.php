<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiModel;
use App\Models\GenerationJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

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
}
