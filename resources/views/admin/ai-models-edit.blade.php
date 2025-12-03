@extends('layouts.admin')

@section('title', 'Edit Model: ' . $model->name)
@section('header', 'Edit AI Model')

@section('content')
<div class="max-w-4xl">
    <!-- Back Button -->
    <div class="mb-6">
        <a href="{{ route('admin.ai-models') }}" class="text-gray-400 hover:text-white transition">
            <i class="fas fa-arrow-left mr-2"></i>Back to Models
        </a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Form -->
        <div class="lg:col-span-2">
            <form action="{{ route('admin.ai-models.update', $model) }}" method="POST" class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Model Name</label>
                        <input type="text" name="name" value="{{ old('name', $model->name) }}"
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        @error('name')
                        <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Model ID (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Model ID</label>
                        <input type="text" value="{{ $model->model_id }}" disabled
                            class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-400">
                    </div>

                    <!-- HuggingFace ID (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">HuggingFace ID</label>
                        <input type="text" value="{{ $model->huggingface_id }}" disabled
                            class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-400">
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Description</label>
                        <textarea name="description" rows="3"
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500">{{ old('description', $model->description) }}</textarea>
                    </div>

                    <!-- Type & Category (Read-only) -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Type</label>
                            <input type="text" value="{{ ucfirst($model->type) }}" disabled
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-400">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-300 mb-1">Category</label>
                            <input type="text" value="{{ ucfirst(str_replace('_', ' ', $model->category)) }}" disabled
                                class="w-full px-4 py-2 bg-gray-900 border border-gray-700 rounded-lg text-gray-400">
                        </div>
                    </div>

                    <!-- VRAM Required -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">VRAM Required (MB)</label>
                        <input type="number" name="vram_required_mb" value="{{ old('vram_required_mb', $model->vram_required_mb) }}"
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                    </div>

                    <!-- Default Params -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-1">Default Parameters (JSON)</label>
                        <textarea name="default_params" rows="4"
                            class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white font-mono text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500">{{ old('default_params', $model->default_params) }}</textarea>
                    </div>

                    <!-- Status Toggles -->
                    <div class="flex items-center gap-6 pt-4 border-t border-gray-700">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_active" value="1" {{ $model->is_active ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-gray-600 bg-gray-700 text-purple-500 focus:ring-purple-500">
                            <span class="ml-2 text-gray-300">Active</span>
                        </label>
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="is_featured" value="1" {{ $model->is_featured ? 'checked' : '' }}
                                class="w-5 h-5 rounded border-gray-600 bg-gray-700 text-yellow-500 focus:ring-yellow-500">
                            <span class="ml-2 text-gray-300">Featured</span>
                        </label>
                    </div>
                </div>

                <div class="mt-6 flex gap-3">
                    <button type="submit" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                        <i class="fas fa-save mr-2"></i>Save Changes
                    </button>
                    <a href="{{ route('admin.ai-models') }}" class="px-6 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
                        Cancel
                    </a>
                </div>
            </form>
        </div>

        <!-- Thumbnail Upload -->
        <div class="lg:col-span-1">
            <div class="bg-gray-800 rounded-lg p-6 border border-gray-700">
                <h3 class="text-lg font-semibold text-white mb-4">
                    <i class="fas fa-image mr-2 text-purple-400"></i>Thumbnail
                </h3>

                <!-- Current Thumbnail -->
                <div class="mb-4">
                    @if($model->thumbnail)
                    <img src="{{ $model->thumbnail }}" alt="{{ $model->name }}" id="current-thumbnail"
                        class="w-full h-48 object-cover rounded-lg border border-gray-600">
                    @else
                    <div id="current-thumbnail" class="w-full h-48 bg-gray-700 rounded-lg border border-gray-600 flex items-center justify-center">
                        <i class="fas fa-image text-4xl text-gray-500"></i>
                    </div>
                    @endif
                </div>

                <!-- Upload Form -->
                <form id="thumbnail-form" enctype="multipart/form-data">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-gray-300 mb-2">Upload New Thumbnail</label>
                        <input type="file" name="thumbnail" id="thumbnail-input" accept="image/*"
                            class="w-full text-sm text-gray-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-600 file:text-white hover:file:bg-purple-700 file:cursor-pointer">
                    </div>
                    <button type="submit" id="upload-btn" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition disabled:opacity-50" disabled>
                        <i class="fas fa-upload mr-2"></i>Upload
                    </button>
                </form>

                <div id="upload-status" class="mt-3 hidden">
                    <div class="flex items-center gap-2 text-sm">
                        <div id="upload-spinner" class="animate-spin rounded-full h-4 w-4 border-b-2 border-white"></div>
                        <span id="upload-message"></span>
                    </div>
                </div>

                <!-- Model Info -->
                <div class="mt-6 pt-4 border-t border-gray-700">
                    <h4 class="text-sm font-semibold text-gray-400 mb-3">Model Info</h4>
                    <div class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Size:</span>
                            <span class="text-gray-300">{{ number_format($model->size_mb ?? 0) }} MB</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Popularity:</span>
                            <span class="text-gray-300">{{ $model->popularity }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Created:</span>
                            <span class="text-gray-300">{{ $model->created_at->format('M d, Y') }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const thumbnailInput = document.getElementById('thumbnail-input');
const uploadBtn = document.getElementById('upload-btn');
const thumbnailForm = document.getElementById('thumbnail-form');
const currentThumbnail = document.getElementById('current-thumbnail');

thumbnailInput.addEventListener('change', function() {
    uploadBtn.disabled = !this.files.length;

    // Preview
    if (this.files.length) {
        const reader = new FileReader();
        reader.onload = function(e) {
            if (currentThumbnail.tagName === 'IMG') {
                currentThumbnail.src = e.target.result;
            } else {
                currentThumbnail.innerHTML = `<img src="${e.target.result}" class="w-full h-48 object-cover rounded-lg">`;
            }
        };
        reader.readAsDataURL(this.files[0]);
    }
});

thumbnailForm.addEventListener('submit', async function(e) {
    e.preventDefault();

    const statusDiv = document.getElementById('upload-status');
    const spinner = document.getElementById('upload-spinner');
    const message = document.getElementById('upload-message');

    statusDiv.classList.remove('hidden');
    spinner.classList.remove('hidden');
    message.textContent = 'Uploading...';
    message.className = 'text-white';
    uploadBtn.disabled = true;

    const formData = new FormData(this);

    try {
        const response = await fetch('{{ route("admin.ai-models.upload-thumbnail", $model) }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            }
        });

        const data = await response.json();
        spinner.classList.add('hidden');

        if (data.success) {
            message.textContent = data.message;
            message.className = 'text-green-400';
        } else {
            message.textContent = data.message || 'Upload failed';
            message.className = 'text-red-400';
        }
    } catch (error) {
        spinner.classList.add('hidden');
        message.textContent = 'Upload failed: ' + error.message;
        message.className = 'text-red-400';
    } finally {
        uploadBtn.disabled = !thumbnailInput.files.length;
    }
});
</script>
@endpush
