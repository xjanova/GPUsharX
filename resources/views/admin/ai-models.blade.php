@extends('layouts.admin')

@section('title', 'AI Models Management')
@section('header', 'AI Models Management')

@section('content')
<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Total Models</p>
                <p class="text-2xl font-bold text-white">{{ $stats['total'] }}</p>
            </div>
            <i class="fas fa-brain text-3xl text-purple-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Image Models</p>
                <p class="text-2xl font-bold text-blue-400">{{ $stats['image_models'] }}</p>
            </div>
            <i class="fas fa-image text-3xl text-blue-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Video Models</p>
                <p class="text-2xl font-bold text-green-400">{{ $stats['video_models'] }}</p>
            </div>
            <i class="fas fa-video text-3xl text-green-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Active</p>
                <p class="text-2xl font-bold text-emerald-400">{{ $stats['active'] }}</p>
            </div>
            <i class="fas fa-check-circle text-3xl text-emerald-400"></i>
        </div>
    </div>
    <div class="bg-gray-800 rounded-lg p-4 border border-gray-700">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-gray-400 text-sm">Featured</p>
                <p class="text-2xl font-bold text-yellow-400">{{ $stats['featured'] }}</p>
            </div>
            <i class="fas fa-star text-3xl text-yellow-400"></i>
        </div>
    </div>
</div>

<!-- Update Actions -->
<div class="bg-gray-800 rounded-lg p-4 border border-gray-700 mb-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h3 class="text-lg font-semibold text-white mb-1">Database Updates</h3>
            <p class="text-gray-400 text-sm">Run migrations or refresh AI models from seeders</p>
        </div>
        <div class="flex flex-wrap gap-3">
            <button onclick="runMigrations()" id="btn-migrate" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-database"></i>
                <span>Run Migrations</span>
            </button>
            <button onclick="refreshAiModels()" id="btn-refresh" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-sync-alt"></i>
                <span>Refresh AI Models</span>
            </button>
            <button onclick="runAllSeeders()" id="btn-seed" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-seedling"></i>
                <span>Run All Seeders</span>
            </button>
        </div>
    </div>

    <!-- Update Status -->
    <div id="update-status" class="hidden mt-4 p-4 rounded-lg">
        <div class="flex items-center gap-3">
            <div id="status-spinner" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
            <span id="status-message" class="text-white"></span>
        </div>
        <pre id="status-output" class="mt-3 text-sm text-gray-300 bg-gray-900 p-3 rounded overflow-x-auto hidden"></pre>
    </div>
</div>

<!-- Filters -->
<div class="bg-gray-800 rounded-lg p-4 border border-gray-700 mb-6">
    <form action="{{ route('admin.ai-models') }}" method="GET" class="flex flex-wrap gap-4">
        <div class="flex-1 min-w-[200px]">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Search models..."
                class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
        </div>
        <select name="type" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
            <option value="">All Types</option>
            <option value="image" {{ request('type') === 'image' ? 'selected' : '' }}>Image</option>
            <option value="video" {{ request('type') === 'video' ? 'selected' : '' }}>Video</option>
        </select>
        <select name="category" class="px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
            <option value="">All Categories</option>
            @foreach($categories as $category)
            <option value="{{ $category }}" {{ request('category') === $category ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
            @endforeach
        </select>
        <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
            <i class="fas fa-search mr-2"></i>Filter
        </button>
        <a href="{{ route('admin.ai-models') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
            <i class="fas fa-times mr-2"></i>Clear
        </a>
    </form>
</div>

<!-- Models Table -->
<div class="bg-gray-800 rounded-lg border border-gray-700 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gray-700">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Model</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Provider</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Type</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Category</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">VRAM</th>
                    <th class="px-4 py-3 text-left text-xs font-semibold text-gray-300 uppercase tracking-wider">Popularity</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">Status</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">Featured</th>
                    <th class="px-4 py-3 text-center text-xs font-semibold text-gray-300 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-700">
                @forelse($models as $model)
                <tr class="hover:bg-gray-750" id="model-row-{{ $model->id }}">
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-3">
                            @if($model->thumbnail_url)
                            <img src="{{ $model->thumbnail_url }}" alt="{{ $model->name }}" class="w-12 h-12 rounded-lg object-cover">
                            @else
                            <div class="w-12 h-12 rounded-lg bg-gray-700 flex items-center justify-center">
                                <i class="fas fa-{{ $model->type === 'image' ? 'image' : 'video' }} text-gray-500"></i>
                            </div>
                            @endif
                            <div>
                                <p class="font-medium text-white">{{ $model->name }}</p>
                                <p class="text-xs text-gray-400">{{ $model->model_id }}</p>
                            </div>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-gray-300">{{ $model->provider ?? 'HuggingFace' }}</td>
                    <td class="px-4 py-4">
                        <span class="px-2 py-1 text-xs rounded-full {{ $model->type === 'image' ? 'bg-blue-600/20 text-blue-400' : 'bg-green-600/20 text-green-400' }}">
                            {{ ucfirst($model->type) }}
                        </span>
                    </td>
                    <td class="px-4 py-4 text-gray-300">{{ ucfirst(str_replace('_', ' ', $model->category ?? '-')) }}</td>
                    <td class="px-4 py-4 text-gray-300">{{ number_format($model->vram_required_mb ?? 0) }} MB</td>
                    <td class="px-4 py-4">
                        <div class="flex items-center gap-2">
                            <div class="flex-1 bg-gray-700 rounded-full h-2 max-w-[80px]">
                                <div class="bg-purple-500 h-2 rounded-full" style="width: {{ min(100, ($model->popularity ?? 0) / 10) }}%"></div>
                            </div>
                            <span class="text-gray-400 text-sm">{{ $model->popularity ?? 0 }}</span>
                        </div>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <button onclick="toggleStatus({{ $model->id }})" id="status-btn-{{ $model->id }}"
                            class="px-3 py-1 text-xs rounded-full transition {{ $model->is_active ? 'bg-green-600/20 text-green-400 hover:bg-green-600/40' : 'bg-red-600/20 text-red-400 hover:bg-red-600/40' }}">
                            {{ $model->is_active ? 'Active' : 'Inactive' }}
                        </button>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <button onclick="toggleFeatured({{ $model->id }})" id="featured-btn-{{ $model->id }}"
                            class="text-2xl transition {{ $model->is_featured ? 'text-yellow-400 hover:text-yellow-300' : 'text-gray-600 hover:text-gray-400' }}">
                            <i class="fas fa-star"></i>
                        </button>
                    </td>
                    <td class="px-4 py-4 text-center">
                        <div class="flex items-center justify-center gap-2">
                            <a href="{{ route('admin.ai-models.edit', $model) }}" class="text-blue-400 hover:text-blue-300 transition" title="Edit">
                                <i class="fas fa-edit"></i>
                            </a>
                            <a href="{{ route('models.show', $model->model_id) }}" target="_blank" class="text-gray-400 hover:text-white transition" title="View">
                                <i class="fas fa-external-link-alt"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-4 py-8 text-center text-gray-400">
                        No AI models found. <button onclick="refreshAiModels()" class="text-purple-400 hover:underline">Refresh from seeder</button>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($models->hasPages())
    <div class="px-4 py-3 border-t border-gray-700">
        {{ $models->links() }}
    </div>
    @endif
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

function showStatus(message, isLoading = true, isError = false) {
    const statusDiv = document.getElementById('update-status');
    const spinner = document.getElementById('status-spinner');
    const messageSpan = document.getElementById('status-message');
    const outputPre = document.getElementById('status-output');

    statusDiv.classList.remove('hidden', 'bg-green-600/20', 'bg-red-600/20', 'bg-blue-600/20');
    statusDiv.classList.add(isError ? 'bg-red-600/20' : (isLoading ? 'bg-blue-600/20' : 'bg-green-600/20'));

    spinner.classList.toggle('hidden', !isLoading);
    messageSpan.textContent = message;
    outputPre.classList.add('hidden');
}

function showOutput(output) {
    const outputPre = document.getElementById('status-output');
    if (output) {
        outputPre.textContent = output;
        outputPre.classList.remove('hidden');
    }
}

function hideStatus() {
    document.getElementById('update-status').classList.add('hidden');
}

async function runMigrations() {
    const btn = document.getElementById('btn-migrate');
    btn.disabled = true;
    showStatus('Running migrations...', true);

    try {
        const response = await fetch('{{ route("admin.run-migrations") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            showStatus(data.message, false, false);
            showOutput(data.output);
        } else {
            showStatus(data.message, false, true);
        }
    } catch (error) {
        showStatus('Error: ' + error.message, false, true);
    } finally {
        btn.disabled = false;
    }
}

async function refreshAiModels() {
    const btn = document.getElementById('btn-refresh');
    btn.disabled = true;
    showStatus('Refreshing AI Models from seeder...', true);

    try {
        const response = await fetch('{{ route("admin.refresh-ai-models") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            showStatus(data.message, false, false);
            showOutput(data.output);
            // Reload page after 2 seconds to show new models
            setTimeout(() => location.reload(), 2000);
        } else {
            showStatus(data.message, false, true);
        }
    } catch (error) {
        showStatus('Error: ' + error.message, false, true);
    } finally {
        btn.disabled = false;
    }
}

async function runAllSeeders() {
    const btn = document.getElementById('btn-seed');
    btn.disabled = true;
    showStatus('Running all seeders...', true);

    try {
        const response = await fetch('{{ route("admin.run-seeders") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ seeder: 'DatabaseSeeder' })
        });

        const data = await response.json();

        if (data.success) {
            showStatus(data.message, false, false);
            showOutput(data.output);
            setTimeout(() => location.reload(), 2000);
        } else {
            showStatus(data.message, false, true);
        }
    } catch (error) {
        showStatus('Error: ' + error.message, false, true);
    } finally {
        btn.disabled = false;
    }
}

async function toggleStatus(modelId) {
    const btn = document.getElementById('status-btn-' + modelId);
    btn.disabled = true;

    try {
        const response = await fetch(`/admin/ai-models/${modelId}/toggle-status`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            btn.textContent = data.is_active ? 'Active' : 'Inactive';
            btn.classList.remove('bg-green-600/20', 'text-green-400', 'bg-red-600/20', 'text-red-400');
            btn.classList.add(data.is_active ? 'bg-green-600/20' : 'bg-red-600/20');
            btn.classList.add(data.is_active ? 'text-green-400' : 'text-red-400');
        }
    } catch (error) {
        console.error('Error:', error);
    } finally {
        btn.disabled = false;
    }
}

async function toggleFeatured(modelId) {
    const btn = document.getElementById('featured-btn-' + modelId);

    try {
        const response = await fetch(`/admin/ai-models/${modelId}/toggle-featured`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            btn.classList.remove('text-yellow-400', 'text-gray-600');
            btn.classList.add(data.is_featured ? 'text-yellow-400' : 'text-gray-600');
        }
    } catch (error) {
        console.error('Error:', error);
    }
}
</script>
@endpush
