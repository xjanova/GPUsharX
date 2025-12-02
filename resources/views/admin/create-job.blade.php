@extends('layouts.admin')

@section('title', 'Create Job')
@section('header', 'Create New Render Job')

@section('content')
<div class="max-w-2xl">
    <form action="{{ route('admin.jobs.store') }}" method="POST" class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        @csrf

        <div class="space-y-6">
            <div>
                <label class="block text-sm text-gray-400 mb-2">Job Title *</label>
                <input type="text" name="title" value="{{ old('title') }}" required
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Enter job title...">
                @error('title')
                <p class="text-red-400 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-2">Description</label>
                <textarea name="description" rows="3"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:ring-2 focus:ring-purple-500 focus:border-transparent"
                    placeholder="Job description...">{{ old('description') }}</textarea>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Job Type *</label>
                    <select name="type" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                        <option value="image" {{ old('type') === 'image' ? 'selected' : '' }}>Image Generation</option>
                        <option value="video" {{ old('type') === 'video' ? 'selected' : '' }}>Video Generation</option>
                        <option value="animation" {{ old('type') === 'animation' ? 'selected' : '' }}>Animation</option>
                        <option value="3d_render" {{ old('type') === '3d_render' ? 'selected' : '' }}>3D Render</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">Priority *</label>
                    <select name="priority" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                        <option value="low" {{ old('priority') === 'low' ? 'selected' : '' }}>Low</option>
                        <option value="normal" {{ old('priority', 'normal') === 'normal' ? 'selected' : '' }}>Normal</option>
                        <option value="high" {{ old('priority') === 'high' ? 'selected' : '' }}>High</option>
                        <option value="urgent" {{ old('priority') === 'urgent' ? 'selected' : '' }}>Urgent</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Estimated Credits *</label>
                    <input type="number" name="estimated_credits" value="{{ old('estimated_credits', 100) }}" min="1" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                    <p class="text-gray-500 text-xs mt-1">Total credits to distribute for this job</p>
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">Required VRAM (MB) *</label>
                    <input type="number" name="required_vram_mb" value="{{ old('required_vram_mb', 4096) }}" min="2048" required
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                    <p class="text-gray-500 text-xs mt-1">Minimum VRAM required (2048+)</p>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-2">Number of Chunks</label>
                <input type="number" name="num_chunks" value="{{ old('num_chunks') }}" min="1" max="100"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white"
                    placeholder="Auto-calculate if empty">
                <p class="text-gray-500 text-xs mt-1">Leave empty to auto-calculate based on credits</p>
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-2">Job Parameters (JSON)</label>
                <textarea name="job_params_json" rows="4" id="job_params"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono text-sm"
                    placeholder='{"prompt": "...", "resolution": "1024x1024"}'>{{ old('job_params_json', '{}') }}</textarea>
                <p class="text-gray-500 text-xs mt-1">Additional parameters for the render job</p>
            </div>
        </div>

        <div class="flex gap-4 mt-8">
            <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-8 py-3 rounded-lg font-medium">
                <i class="fas fa-plus mr-2"></i>Create Job
            </button>
            <a href="{{ route('admin.jobs') }}" class="bg-gray-700 hover:bg-gray-600 px-8 py-3 rounded-lg font-medium">
                Cancel
            </a>
        </div>
    </form>
</div>
@endsection
