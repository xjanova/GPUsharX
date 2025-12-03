@extends('layouts.admin')

@section('title', 'Model Store')
@section('header', 'Model Store - Import from HuggingFace')

@section('content')
<div class="space-y-6">
    <!-- Header Stats -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-cube text-purple-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Installed Models</p>
                    <p class="text-xl font-bold text-white">{{ count($installedModels) }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-image text-blue-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Image Models</p>
                    <p class="text-xl font-bold text-white">{{ \App\Models\AiModel::where('type', 'image')->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-pink-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-video text-pink-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Video Models</p>
                    <p class="text-xl font-bold text-white">{{ \App\Models\AiModel::where('type', 'video')->count() }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800 rounded-xl p-4 border border-gray-700">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Active Models</p>
                    <p class="text-xl font-bold text-white">{{ \App\Models\AiModel::where('is_active', true)->count() }}</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Search Section -->
    <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-4 flex items-center gap-2">
            <i class="fas fa-search text-purple-400"></i>
            Search HuggingFace Models
        </h3>
        <div class="flex flex-wrap gap-4">
            <div class="flex-1 min-w-[250px]">
                <input type="text" id="search-query" placeholder="Search models (e.g., stable-diffusion, flux, sdxl, animatediff)..."
                    class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white placeholder-gray-400 focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
            </div>
            <select id="search-type" class="px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white min-w-[180px]">
                <option value="text-to-image">Text to Image</option>
                <option value="text-to-video">Text to Video</option>
                <option value="image-to-image">Image to Image</option>
                <option value="image-to-video">Image to Video</option>
            </select>
            <select id="search-sort" class="px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white">
                <option value="downloads">Most Downloads</option>
                <option value="likes">Most Likes</option>
                <option value="lastModified">Recently Updated</option>
            </select>
            <button onclick="searchModels()" id="search-btn" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center gap-2">
                <i class="fas fa-search"></i>
                Search
            </button>
        </div>

        <!-- Quick Search Tags -->
        <div class="mt-4 flex flex-wrap gap-2">
            <span class="text-gray-400 text-sm">Quick search:</span>
            <button onclick="quickSearch('stable-diffusion-xl')" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full text-sm transition">SDXL</button>
            <button onclick="quickSearch('flux')" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full text-sm transition">FLUX</button>
            <button onclick="quickSearch('animatediff')" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full text-sm transition">AnimateDiff</button>
            <button onclick="quickSearch('cogvideox')" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full text-sm transition">CogVideoX</button>
            <button onclick="quickSearch('stable-video')" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full text-sm transition">Stable Video</button>
            <button onclick="quickSearch('dreamshaper')" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full text-sm transition">DreamShaper</button>
            <button onclick="quickSearch('realistic')" class="px-3 py-1 bg-gray-700 hover:bg-gray-600 text-gray-300 rounded-full text-sm transition">Realistic</button>
        </div>
    </div>

    <!-- Search Results -->
    <div id="search-results" class="hidden">
        <div class="bg-gray-800 rounded-xl border border-gray-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-white flex items-center gap-2">
                    <i class="fas fa-list text-blue-400"></i>
                    Search Results
                </h3>
                <span id="result-count" class="text-gray-400 text-sm"></span>
            </div>
            <div id="results-container" class="divide-y divide-gray-700">
                <!-- Results will be inserted here -->
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div id="loading-state" class="hidden">
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-12 text-center">
            <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-purple-500 mx-auto mb-4"></div>
            <p class="text-gray-400">Searching HuggingFace...</p>
        </div>
    </div>

    <!-- Empty State -->
    <div id="empty-state" class="bg-gray-800 rounded-xl border border-gray-700 p-12 text-center">
        <i class="fas fa-cloud-download-alt text-6xl text-gray-600 mb-4"></i>
        <h3 class="text-xl font-semibold text-gray-400 mb-2">Search for AI Models</h3>
        <p class="text-gray-500 max-w-md mx-auto">Search HuggingFace for Stable Diffusion, FLUX, AnimateDiff, CogVideoX and more models to import into your platform</p>
    </div>
</div>

<!-- Model Detail Modal -->
<div id="detail-modal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 p-4">
    <div class="bg-gray-800 rounded-2xl w-full max-w-3xl max-h-[90vh] overflow-hidden border border-gray-700">
        <div class="p-6 border-b border-gray-700 flex items-center justify-between">
            <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                <i class="fas fa-cube text-purple-400"></i>
                <span id="detail-title">Model Details</span>
            </h3>
            <button onclick="closeDetailModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>
        <div class="p-6 overflow-y-auto max-h-[calc(90vh-180px)]" id="detail-content">
            <!-- Content loaded dynamically -->
        </div>
        <div class="p-4 border-t border-gray-700 flex gap-3">
            <button onclick="importFromDetail()" id="detail-import-btn" class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center justify-center gap-2">
                <i class="fas fa-download"></i>
                Import Model
            </button>
            <a id="detail-hf-link" href="#" target="_blank" class="px-4 py-3 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition flex items-center gap-2">
                <i class="fab fa-hubspot"></i>
                View on HuggingFace
            </a>
            <button onclick="closeDetailModal()" class="px-4 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
                Close
            </button>
        </div>
    </div>
</div>

<!-- Import Modal -->
<div id="import-modal" class="fixed inset-0 bg-black/70 hidden items-center justify-center z-50 p-4">
    <div class="bg-gray-800 rounded-2xl p-6 w-full max-w-lg border border-gray-700">
        <h3 class="text-xl font-semibold text-white mb-6 flex items-center gap-2">
            <i class="fas fa-download text-green-400"></i>
            Import Model to Platform
        </h3>
        <form id="import-form">
            <input type="hidden" id="import-hf-id" name="huggingface_id">

            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Model Name</label>
                    <input type="text" id="import-name" name="name" required
                        class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">HuggingFace ID</label>
                    <input type="text" id="import-hf-display" disabled
                        class="w-full px-4 py-3 bg-gray-900 border border-gray-700 rounded-lg text-gray-400 font-mono text-sm">
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Type</label>
                        <select id="import-type" name="type" required
                            class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white">
                            <option value="image">Image Generation</option>
                            <option value="video">Video Generation</option>
                            <option value="audio">Audio</option>
                            <option value="3d">3D</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">Category</label>
                        <select id="import-category" name="category" required
                            class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white">
                            <option value="stable_diffusion">Stable Diffusion</option>
                            <option value="sdxl">SDXL</option>
                            <option value="flux">FLUX</option>
                            <option value="animatediff">AnimateDiff</option>
                            <option value="cogvideo">CogVideo</option>
                            <option value="stable_video">Stable Video</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">VRAM Required (MB)</label>
                    <input type="number" id="import-vram" name="vram_required_mb" value="8192" required min="1024"
                        class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white">
                    <p class="text-xs text-gray-500 mt-1">Recommended: 6144 (6GB), 8192 (8GB), 12288 (12GB), 16384 (16GB), 24576 (24GB)</p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Description</label>
                    <textarea id="import-desc" name="description" rows="3"
                        class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white resize-none"
                        placeholder="Brief description of the model..."></textarea>
                </div>
            </div>

            <div id="import-status" class="mt-4 hidden">
                <div class="flex items-center gap-3 p-4 rounded-lg">
                    <div id="import-spinner" class="animate-spin rounded-full h-5 w-5 border-b-2 border-white"></div>
                    <span id="import-message" class="font-medium"></span>
                </div>
            </div>

            <div class="mt-6 flex gap-3">
                <button type="submit" id="import-btn" class="flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition font-medium flex items-center justify-center gap-2">
                    <i class="fas fa-download"></i>
                    Import Model
                </button>
                <button type="button" onclick="closeImportModal()" class="px-6 py-3 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-[60] hidden">
    <div class="px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3" id="toast-content">
        <i id="toast-icon" class="fas fa-check-circle text-xl"></i>
        <span id="toast-message" class="font-medium"></span>
    </div>
</div>
@endsection

@push('scripts')
<script>
const installedModels = @json($installedModels);
const csrfToken = '{{ csrf_token() }}';
let currentModelData = null;

function quickSearch(query) {
    document.getElementById('search-query').value = query;
    searchModels();
}

async function searchModels() {
    const query = document.getElementById('search-query').value;
    const type = document.getElementById('search-type').value;
    const sort = document.getElementById('search-sort').value;

    if (!query.trim()) {
        showToast('Please enter a search query', 'error');
        return;
    }

    document.getElementById('empty-state').classList.add('hidden');
    document.getElementById('search-results').classList.add('hidden');
    document.getElementById('loading-state').classList.remove('hidden');

    try {
        const response = await fetch('{{ route("admin.model-store.search") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify({ query, type, sort })
        });

        const data = await response.json();
        document.getElementById('loading-state').classList.add('hidden');

        if (data.success && data.models.length > 0) {
            renderResults(data.models);
        } else {
            document.getElementById('empty-state').classList.remove('hidden');
            document.getElementById('empty-state').innerHTML = `
                <i class="fas fa-search text-6xl text-gray-600 mb-4"></i>
                <h3 class="text-xl font-semibold text-gray-400 mb-2">No Results Found</h3>
                <p class="text-gray-500">Try a different search term or filter</p>
            `;
        }
    } catch (error) {
        document.getElementById('loading-state').classList.add('hidden');
        showToast('Search failed: ' + error.message, 'error');
    }
}

function renderResults(models) {
    const container = document.getElementById('results-container');
    document.getElementById('result-count').textContent = `${models.length} models found`;

    container.innerHTML = models.map(model => {
        const isInstalled = installedModels.includes(model.id);
        const downloadsFormatted = formatNumber(model.downloads);
        const likesFormatted = formatNumber(model.likes);

        return `
            <div class="p-4 hover:bg-gray-750 transition">
                <div class="flex items-start gap-4">
                    <!-- Model Info -->
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-3 flex-wrap">
                            <h4 class="font-semibold text-white text-lg">${escapeHtml(model.name.split('/').pop())}</h4>
                            ${isInstalled ? '<span class="px-2 py-0.5 text-xs bg-green-600/20 text-green-400 rounded-full font-medium">Installed</span>' : ''}
                            ${model.pipeline_tag ? `<span class="px-2 py-0.5 text-xs bg-purple-600/20 text-purple-400 rounded-full">${model.pipeline_tag}</span>` : ''}
                        </div>
                        <p class="text-gray-500 text-sm mt-1 font-mono">${escapeHtml(model.id)}</p>

                        <!-- Stats -->
                        <div class="flex items-center gap-6 mt-3">
                            <div class="flex items-center gap-2">
                                <i class="fas fa-download text-blue-400"></i>
                                <span class="text-white font-medium">${downloadsFormatted}</span>
                                <span class="text-gray-500 text-sm">downloads</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <i class="fas fa-heart text-red-400"></i>
                                <span class="text-white font-medium">${likesFormatted}</span>
                                <span class="text-gray-500 text-sm">likes</span>
                            </div>
                            ${model.lastModified ? `
                            <div class="flex items-center gap-2">
                                <i class="fas fa-clock text-gray-400"></i>
                                <span class="text-gray-400 text-sm">${formatDate(model.lastModified)}</span>
                            </div>
                            ` : ''}
                        </div>

                        <!-- Tags -->
                        ${model.tags && model.tags.length > 0 ? `
                        <div class="flex flex-wrap gap-1 mt-3">
                            ${model.tags.slice(0, 5).map(tag => `<span class="px-2 py-0.5 text-xs bg-gray-700 text-gray-400 rounded">${escapeHtml(tag)}</span>`).join('')}
                            ${model.tags.length > 5 ? `<span class="px-2 py-0.5 text-xs text-gray-500">+${model.tags.length - 5} more</span>` : ''}
                        </div>
                        ` : ''}
                    </div>

                    <!-- Actions -->
                    <div class="flex flex-col gap-2 flex-shrink-0">
                        <button onclick='viewModelDetail(${JSON.stringify(model).replace(/'/g, "&#39;")})'
                            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition flex items-center gap-2">
                            <i class="fas fa-eye"></i>
                            Details
                        </button>
                        <a href="https://huggingface.co/${model.id}" target="_blank"
                            class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg text-sm transition flex items-center gap-2">
                            <i class="fas fa-external-link-alt"></i>
                            HuggingFace
                        </a>
                        ${isInstalled ? `
                            <button disabled class="px-4 py-2 bg-gray-700 text-gray-500 rounded-lg text-sm cursor-not-allowed flex items-center gap-2">
                                <i class="fas fa-check"></i>
                                Installed
                            </button>
                        ` : `
                            <button onclick="openImportModal('${model.id}', '${escapeHtml(model.name)}', '${model.pipeline_tag || ''}')"
                                class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition flex items-center gap-2">
                                <i class="fas fa-plus"></i>
                                Import
                            </button>
                        `}
                    </div>
                </div>
            </div>
        `;
    }).join('');

    document.getElementById('search-results').classList.remove('hidden');
}

function viewModelDetail(model) {
    currentModelData = model;
    const isInstalled = installedModels.includes(model.id);

    document.getElementById('detail-title').textContent = model.name.split('/').pop();
    document.getElementById('detail-hf-link').href = `https://huggingface.co/${model.id}`;

    const importBtn = document.getElementById('detail-import-btn');
    if (isInstalled) {
        importBtn.disabled = true;
        importBtn.className = 'flex-1 px-4 py-3 bg-gray-700 text-gray-500 rounded-lg cursor-not-allowed flex items-center justify-center gap-2';
        importBtn.innerHTML = '<i class="fas fa-check"></i> Already Installed';
    } else {
        importBtn.disabled = false;
        importBtn.className = 'flex-1 px-4 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg transition flex items-center justify-center gap-2';
        importBtn.innerHTML = '<i class="fas fa-download"></i> Import Model';
    }

    document.getElementById('detail-content').innerHTML = `
        <div class="space-y-6">
            <!-- Model ID -->
            <div>
                <label class="text-sm text-gray-400">HuggingFace ID</label>
                <p class="text-white font-mono bg-gray-900 px-4 py-2 rounded-lg mt-1">${escapeHtml(model.id)}</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-3 gap-4">
                <div class="bg-gray-700/50 rounded-xl p-4 text-center">
                    <i class="fas fa-download text-blue-400 text-2xl mb-2"></i>
                    <p class="text-2xl font-bold text-white">${formatNumber(model.downloads)}</p>
                    <p class="text-sm text-gray-400">Downloads</p>
                </div>
                <div class="bg-gray-700/50 rounded-xl p-4 text-center">
                    <i class="fas fa-heart text-red-400 text-2xl mb-2"></i>
                    <p class="text-2xl font-bold text-white">${formatNumber(model.likes)}</p>
                    <p class="text-sm text-gray-400">Likes</p>
                </div>
                <div class="bg-gray-700/50 rounded-xl p-4 text-center">
                    <i class="fas fa-star text-yellow-400 text-2xl mb-2"></i>
                    <p class="text-2xl font-bold text-white">${calculateRating(model.likes, model.downloads)}</p>
                    <p class="text-sm text-gray-400">Rating</p>
                </div>
            </div>

            <!-- Pipeline Tag -->
            ${model.pipeline_tag ? `
            <div>
                <label class="text-sm text-gray-400">Pipeline Type</label>
                <p class="mt-1">
                    <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-full">${model.pipeline_tag}</span>
                </p>
            </div>
            ` : ''}

            <!-- Tags -->
            ${model.tags && model.tags.length > 0 ? `
            <div>
                <label class="text-sm text-gray-400 mb-2 block">Tags</label>
                <div class="flex flex-wrap gap-2">
                    ${model.tags.map(tag => `<span class="px-3 py-1 bg-gray-700 text-gray-300 rounded-full text-sm">${escapeHtml(tag)}</span>`).join('')}
                </div>
            </div>
            ` : ''}

            <!-- Recommended Settings -->
            <div class="bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
                <h4 class="font-semibold text-blue-400 mb-3 flex items-center gap-2">
                    <i class="fas fa-lightbulb"></i>
                    Recommended Settings
                </h4>
                <div class="grid grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="text-gray-400">VRAM Required:</span>
                        <span class="text-white ml-2">${guessVram(model)} GB</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Type:</span>
                        <span class="text-white ml-2">${guessType(model.pipeline_tag)}</span>
                    </div>
                    <div>
                        <span class="text-gray-400">Category:</span>
                        <span class="text-white ml-2">${guessCategory(model.id)}</span>
                    </div>
                </div>
            </div>
        </div>
    `;

    document.getElementById('detail-modal').classList.remove('hidden');
    document.getElementById('detail-modal').classList.add('flex');
}

function closeDetailModal() {
    document.getElementById('detail-modal').classList.add('hidden');
    document.getElementById('detail-modal').classList.remove('flex');
}

function importFromDetail() {
    if (currentModelData) {
        closeDetailModal();
        openImportModal(currentModelData.id, currentModelData.name, currentModelData.pipeline_tag || '');
    }
}

function calculateRating(likes, downloads) {
    if (downloads === 0) return '0.0';
    const ratio = (likes / downloads) * 100;
    const rating = Math.min(5, Math.max(0, ratio * 2)).toFixed(1);
    return rating;
}

function guessVram(model) {
    const name = model.id.toLowerCase();
    if (name.includes('xl') || name.includes('sdxl')) return '12';
    if (name.includes('flux')) return '16';
    if (name.includes('video') || name.includes('animate')) return '16';
    if (name.includes('cog')) return '24';
    return '8';
}

function guessType(pipeline) {
    if (!pipeline) return 'Image';
    if (pipeline.includes('video')) return 'Video';
    return 'Image';
}

function guessCategory(id) {
    const name = id.toLowerCase();
    if (name.includes('flux')) return 'FLUX';
    if (name.includes('sdxl') || name.includes('stable-diffusion-xl')) return 'SDXL';
    if (name.includes('animatediff') || name.includes('animate')) return 'AnimateDiff';
    if (name.includes('cogvideo')) return 'CogVideo';
    if (name.includes('stable-video')) return 'Stable Video';
    if (name.includes('stable-diffusion')) return 'Stable Diffusion';
    return 'Other';
}

function formatNumber(num) {
    if (!num) return '0';
    if (num >= 1000000) return (num / 1000000).toFixed(1) + 'M';
    if (num >= 1000) return (num / 1000).toFixed(1) + 'K';
    return num.toString();
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML.replace(/'/g, "&#39;").replace(/"/g, "&quot;");
}

function openImportModal(hfId, name, pipelineTag) {
    document.getElementById('import-hf-id').value = hfId;
    document.getElementById('import-hf-display').value = hfId;
    document.getElementById('import-name').value = name.split('/').pop();

    // Auto-detect type
    if (pipelineTag && (pipelineTag.includes('video'))) {
        document.getElementById('import-type').value = 'video';
    } else {
        document.getElementById('import-type').value = 'image';
    }

    // Auto-detect category and VRAM
    const nameLower = hfId.toLowerCase();
    let category = 'stable_diffusion';
    let vram = 8192;

    if (nameLower.includes('flux')) {
        category = 'flux';
        vram = 16384;
    } else if (nameLower.includes('sdxl') || nameLower.includes('stable-diffusion-xl')) {
        category = 'sdxl';
        vram = 12288;
    } else if (nameLower.includes('animatediff') || nameLower.includes('animate')) {
        category = 'animatediff';
        vram = 16384;
    } else if (nameLower.includes('cogvideo')) {
        category = 'cogvideo';
        vram = 24576;
    } else if (nameLower.includes('stable-video')) {
        category = 'stable_video';
        vram = 16384;
    }

    document.getElementById('import-category').value = category;
    document.getElementById('import-vram').value = vram;

    document.getElementById('import-modal').classList.remove('hidden');
    document.getElementById('import-modal').classList.add('flex');
}

function closeImportModal() {
    document.getElementById('import-modal').classList.add('hidden');
    document.getElementById('import-modal').classList.remove('flex');
    document.getElementById('import-status').classList.add('hidden');
}

document.getElementById('import-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const statusDiv = document.getElementById('import-status');
    const spinner = document.getElementById('import-spinner');
    const message = document.getElementById('import-message');
    const btn = document.getElementById('import-btn');

    statusDiv.classList.remove('hidden');
    statusDiv.className = 'mt-4 bg-blue-600/20 p-4 rounded-lg';
    spinner.classList.remove('hidden');
    message.textContent = 'Importing model...';
    message.className = 'text-blue-400 font-medium';
    btn.disabled = true;

    const formData = new FormData(this);
    const data = Object.fromEntries(formData.entries());

    try {
        const response = await fetch('{{ route("admin.model-store.import") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();
        spinner.classList.add('hidden');

        if (result.success) {
            statusDiv.className = 'mt-4 bg-green-600/20 p-4 rounded-lg';
            message.textContent = 'Model imported successfully!';
            message.className = 'text-green-400 font-medium';

            // Add to installed list
            installedModels.push(data.huggingface_id);

            showToast('Model imported successfully!', 'success');

            // Refresh results
            setTimeout(() => {
                closeImportModal();
                searchModels();
            }, 1500);
        } else {
            statusDiv.className = 'mt-4 bg-red-600/20 p-4 rounded-lg';
            message.textContent = result.message || 'Import failed';
            message.className = 'text-red-400 font-medium';
            showToast(result.message || 'Import failed', 'error');
        }
    } catch (error) {
        spinner.classList.add('hidden');
        statusDiv.className = 'mt-4 bg-red-600/20 p-4 rounded-lg';
        message.textContent = 'Import failed: ' + error.message;
        message.className = 'text-red-400 font-medium';
        showToast('Import failed', 'error');
    } finally {
        btn.disabled = false;
    }
});

function showToast(msg, type = 'success') {
    const toast = document.getElementById('toast');
    const content = document.getElementById('toast-content');
    const icon = document.getElementById('toast-icon');
    const message = document.getElementById('toast-message');

    message.textContent = msg;

    if (type === 'success') {
        content.className = 'px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 bg-green-500 text-white';
        icon.className = 'fas fa-check-circle text-xl';
    } else {
        content.className = 'px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 bg-red-500 text-white';
        icon.className = 'fas fa-exclamation-circle text-xl';
    }

    toast.classList.remove('hidden');
    setTimeout(() => toast.classList.add('hidden'), 3000);
}

// Enter key to search
document.getElementById('search-query').addEventListener('keypress', function(e) {
    if (e.key === 'Enter') searchModels();
});

// Close modals on backdrop click
document.getElementById('detail-modal').addEventListener('click', function(e) {
    if (e.target === this) closeDetailModal();
});
document.getElementById('import-modal').addEventListener('click', function(e) {
    if (e.target === this) closeImportModal();
});
</script>
@endpush
