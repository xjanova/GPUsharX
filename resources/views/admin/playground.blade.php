@extends('layouts.admin')

@section('title', 'AI Playground')
@section('header', 'AI Playground - ทดสอบโมเดล')

@section('content')
<div class="space-y-6">
    <!-- Header -->
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white flex items-center gap-3">
                <i class="fas fa-flask text-purple-400"></i>
                AI Playground
            </h1>
            <p class="text-gray-400 mt-1">ทดสอบ AI Models ก่อนเปิดให้ผู้ใช้งาน</p>
        </div>
        <a href="{{ route('admin.ai-models') }}" class="px-4 py-2 bg-gray-700 hover:bg-gray-600 text-white rounded-lg transition flex items-center gap-2">
            <i class="fas fa-cube"></i>
            จัดการโมเดล
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-purple-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-cube text-purple-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">โมเดลทั้งหมด</p>
                    <p class="text-xl font-bold text-white">{{ $stats['total_models'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-green-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-check text-green-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">เปิดใช้งาน</p>
                    <p class="text-xl font-bold text-white">{{ $stats['active_models'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-server text-blue-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">Workers ออนไลน์</p>
                    <p class="text-xl font-bold text-white">{{ $stats['online_workers'] }}</p>
                </div>
            </div>
        </div>
        <div class="bg-gray-800/50 rounded-xl border border-gray-700/50 p-4">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                    <i class="fas fa-memory text-yellow-400"></i>
                </div>
                <div>
                    <p class="text-sm text-gray-400">VRAM รวม</p>
                    <p class="text-xl font-bold text-white">{{ number_format($stats['total_vram'] / 1024, 1) }} GB</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Left: Generation Form -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 overflow-hidden">
                <div class="p-6 border-b border-gray-700/50">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-wand-magic-sparkles text-purple-400"></i>
                        ทดสอบสร้างภาพ
                    </h2>
                </div>

                <form id="testForm" class="p-6 space-y-6">
                    @csrf
                    <!-- Model Selection -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-3">
                            <i class="fas fa-cube text-purple-400 mr-2"></i>เลือกโมเดล
                        </label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 max-h-64 overflow-y-auto pr-2" id="modelList">
                            @forelse($models as $model)
                            <label class="model-card cursor-pointer">
                                <input type="radio" name="model_id" value="{{ $model->id }}"
                                    class="hidden"
                                    data-type="{{ $model->type }}"
                                    data-vram="{{ $model->vram_required_mb }}"
                                    {{ $loop->first ? 'checked' : '' }}>
                                <div class="p-3 rounded-xl border-2 border-gray-700 hover:border-purple-500/50 transition model-option">
                                    <div class="flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-lg bg-gray-700 flex items-center justify-center overflow-hidden flex-shrink-0">
                                            @if($model->thumbnail)
                                            <img src="{{ $model->thumbnail }}" alt="" class="w-full h-full object-cover">
                                            @else
                                            <i class="fas fa-{{ $model->type === 'image' ? 'image' : 'video' }} text-gray-500"></i>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <p class="font-medium text-white text-sm truncate">{{ $model->name }}</p>
                                            <div class="flex items-center gap-2 mt-1">
                                                <span class="text-xs px-2 py-0.5 rounded {{ $model->type === 'image' ? 'bg-blue-500/20 text-blue-400' : 'bg-pink-500/20 text-pink-400' }}">
                                                    {{ $model->type }}
                                                </span>
                                                <span class="text-xs text-gray-500">{{ $model->vram_required_gb }}GB</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </label>
                            @empty
                            <div class="col-span-2 text-center py-8 text-gray-500">
                                <i class="fas fa-cube text-4xl mb-3 opacity-50"></i>
                                <p>ไม่มีโมเดลที่เปิดใช้งาน</p>
                                <a href="{{ route('admin.ai-models') }}" class="text-purple-400 hover:underline text-sm">
                                    เพิ่มโมเดล
                                </a>
                            </div>
                            @endforelse
                        </div>
                    </div>

                    <!-- Prompt -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-pen text-purple-400 mr-2"></i>Prompt
                        </label>
                        <textarea name="prompt" rows="3" required
                            class="w-full bg-gray-700/50 border border-gray-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
                            placeholder="บรรยายสิ่งที่ต้องการสร้าง...">a beautiful sunset over mountains, highly detailed, 8k, professional photography</textarea>
                    </div>

                    <!-- Negative Prompt -->
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">
                            <i class="fas fa-ban text-red-400 mr-2"></i>Negative Prompt
                        </label>
                        <textarea name="negative_prompt" rows="2"
                            class="w-full bg-gray-700/50 border border-gray-600 rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent resize-none"
                            placeholder="สิ่งที่ไม่ต้องการ...">blurry, low quality, watermark, text, distorted</textarea>
                    </div>

                    <!-- Settings Grid -->
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Width</label>
                            <input type="number" name="width" value="1024" min="256" max="2048" step="64"
                                class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Height</label>
                            <input type="number" name="height" value="1024" min="256" max="2048" step="64"
                                class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Steps</label>
                            <input type="number" name="steps" value="30" min="1" max="150"
                                class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">CFG Scale</label>
                            <input type="number" name="cfg_scale" value="7.5" min="1" max="30" step="0.5"
                                class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-purple-500">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Seed (ว่าง = สุ่ม)</label>
                            <input type="number" name="seed" placeholder="Random"
                                class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-purple-500">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">Sampler</label>
                            <select name="sampler"
                                class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm focus:ring-2 focus:ring-purple-500">
                                <option value="euler_a">Euler A</option>
                                <option value="euler">Euler</option>
                                <option value="dpm++_2m" selected>DPM++ 2M</option>
                                <option value="dpm++_sde">DPM++ SDE</option>
                                <option value="ddim">DDIM</option>
                            </select>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" id="submitBtn"
                        class="w-full py-4 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 text-white font-bold rounded-xl transition flex items-center justify-center gap-3">
                        <i class="fas fa-play"></i>
                        เริ่มทดสอบ
                    </button>
                </form>
            </div>

            <!-- Result Area -->
            <div id="resultArea" class="hidden bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 overflow-hidden">
                <div class="p-6 border-b border-gray-700/50">
                    <h2 class="text-lg font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-image text-green-400"></i>
                        ผลลัพธ์
                    </h2>
                </div>
                <div class="p-6">
                    <!-- Progress -->
                    <div id="progressArea" class="hidden mb-6">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-sm text-gray-400" id="progressStatus">กำลังประมวลผล...</span>
                            <span class="text-sm text-purple-400" id="progressPercent">0%</span>
                        </div>
                        <div class="h-2 bg-gray-700 rounded-full overflow-hidden">
                            <div id="progressBar" class="h-full bg-gradient-to-r from-purple-500 to-pink-500 transition-all duration-300" style="width: 0%"></div>
                        </div>
                    </div>

                    <!-- Result Image -->
                    <div id="resultImage" class="hidden">
                        <div class="aspect-square max-w-lg mx-auto bg-gray-700 rounded-xl overflow-hidden">
                            <img id="generatedImage" src="" alt="Generated" class="w-full h-full object-contain">
                        </div>
                        <div class="mt-4 flex items-center justify-center gap-4">
                            <a id="downloadLink" href="" download class="px-4 py-2 bg-green-600 hover:bg-green-500 text-white rounded-lg transition flex items-center gap-2">
                                <i class="fas fa-download"></i>
                                ดาวน์โหลด
                            </a>
                            <button onclick="copySettings()" class="px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition flex items-center gap-2">
                                <i class="fas fa-copy"></i>
                                คัดลอก Settings
                            </button>
                        </div>
                    </div>

                    <!-- Error -->
                    <div id="resultError" class="hidden p-4 bg-red-500/10 border border-red-500/30 rounded-xl">
                        <div class="flex items-center gap-3">
                            <i class="fas fa-exclamation-triangle text-red-400"></i>
                            <span class="text-red-400" id="errorMessage">เกิดข้อผิดพลาด</span>
                        </div>
                    </div>

                    <!-- Job Info -->
                    <div id="jobInfo" class="mt-4 p-4 bg-gray-700/30 rounded-xl hidden">
                        <div class="grid grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="text-gray-400">Job ID:</span>
                                <span class="text-white ml-2" id="infoJobId">-</span>
                            </div>
                            <div>
                                <span class="text-gray-400">Worker:</span>
                                <span class="text-white ml-2" id="infoWorker">-</span>
                            </div>
                            <div>
                                <span class="text-gray-400">เวลาประมวลผล:</span>
                                <span class="text-white ml-2" id="infoTime">-</span>
                            </div>
                            <div>
                                <span class="text-gray-400">สถานะ:</span>
                                <span class="text-white ml-2" id="infoStatus">-</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Sidebar -->
        <div class="space-y-6">
            <!-- Workers Status -->
            <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 p-5">
                <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-server text-blue-400"></i>
                    Workers ออนไลน์
                </h3>
                @if($workers->count() > 0)
                <div class="space-y-3 max-h-64 overflow-y-auto">
                    @foreach($workers as $worker)
                    <div class="p-3 bg-gray-700/30 rounded-xl">
                        <div class="flex items-center justify-between mb-2">
                            <span class="text-white font-medium text-sm">{{ $worker->name }}</span>
                            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                        </div>
                        <div class="text-xs text-gray-400 space-y-1">
                            <p><i class="fas fa-microchip mr-1"></i>{{ $worker->gpu_model }}</p>
                            <p><i class="fas fa-memory mr-1"></i>{{ number_format($worker->gpu_vram_mb / 1024, 1) }} GB VRAM</p>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-6 text-gray-500">
                    <i class="fas fa-power-off text-3xl mb-2"></i>
                    <p class="text-sm">ไม่มี Worker ออนไลน์</p>
                </div>
                @endif
            </div>

            <!-- Recent Tests -->
            <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 p-5">
                <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-history text-purple-400"></i>
                    ทดสอบล่าสุด
                </h3>
                @if($recentTests->count() > 0)
                <div class="space-y-2 max-h-80 overflow-y-auto">
                    @foreach($recentTests as $test)
                    <div class="p-3 bg-gray-700/30 rounded-xl hover:bg-gray-700/50 transition cursor-pointer"
                         onclick="loadTestResult('{{ $test->job_id }}')">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-gray-700 overflow-hidden flex-shrink-0">
                                @if($test->result_thumbnail)
                                <img src="{{ $test->result_thumbnail }}" alt="" class="w-full h-full object-cover">
                                @else
                                <div class="w-full h-full flex items-center justify-center">
                                    <i class="fas fa-{{ $test->status === 'processing' ? 'spinner fa-spin' : ($test->status === 'completed' ? 'image' : 'times') }} text-gray-500"></i>
                                </div>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-gray-300 truncate">{{ Str::limit($test->prompt, 30) }}</p>
                                <p class="text-xs {{ $test->status === 'completed' ? 'text-green-400' : ($test->status === 'processing' ? 'text-yellow-400' : ($test->status === 'failed' ? 'text-red-400' : 'text-gray-500')) }}">
                                    {{ $test->status === 'completed' ? 'สำเร็จ' : ($test->status === 'processing' ? 'กำลังประมวลผล' : ($test->status === 'failed' ? 'ล้มเหลว' : 'รอคิว')) }}
                                </p>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div class="text-center py-6 text-gray-500">
                    <i class="fas fa-flask text-3xl mb-2"></i>
                    <p class="text-sm">ยังไม่มีการทดสอบ</p>
                </div>
                @endif
            </div>

            <!-- Quick Tips -->
            <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 p-5">
                <h3 class="font-semibold text-white mb-4 flex items-center gap-2">
                    <i class="fas fa-lightbulb text-yellow-400"></i>
                    Tips
                </h3>
                <ul class="space-y-2 text-sm text-gray-400">
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-1 text-xs"></i>
                        <span>การทดสอบไม่หักเครดิต</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-1 text-xs"></i>
                        <span>งานทดสอบมี Priority สูงสุด</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-1 text-xs"></i>
                        <span>ตรวจสอบ VRAM ของ Worker ก่อนทดสอบ</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <i class="fas fa-check text-green-400 mt-1 text-xs"></i>
                        <span>บันทึก Seed เพื่อสร้างผลลัพธ์เดิม</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- Toast Notification -->
<div id="toast" class="fixed top-4 left-1/2 -translate-x-1/2 z-50 hidden">
    <div class="px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3" id="toastContent">
        <i id="toastIcon" class="fas fa-check-circle text-xl"></i>
        <span id="toastMessage" class="font-medium"></span>
    </div>
</div>

<style>
.model-card input:checked + .model-option {
    border-color: #a855f7;
    background: rgba(168, 85, 247, 0.1);
}
</style>

<script>
let currentJobId = null;
let pollInterval = null;

document.getElementById('testForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const formData = new FormData(this);
    const submitBtn = document.getElementById('submitBtn');

    // Check if model selected
    if (!formData.get('model_id')) {
        showToast('กรุณาเลือกโมเดล', 'error');
        return;
    }

    // Disable button
    submitBtn.disabled = true;
    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังส่งงาน...';

    try {
        const response = await fetch('{{ route("admin.playground.run") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(Object.fromEntries(formData)),
        });

        const data = await response.json();

        if (data.success) {
            showToast(data.message, 'success');
            currentJobId = data.job_id;
            showResultArea();
            startPolling();
        } else {
            showToast(data.message || 'เกิดข้อผิดพลาด', 'error');
        }
    } catch (error) {
        showToast('เกิดข้อผิดพลาดในการเชื่อมต่อ', 'error');
        console.error(error);
    } finally {
        submitBtn.disabled = false;
        submitBtn.innerHTML = '<i class="fas fa-play mr-2"></i>เริ่มทดสอบ';
    }
});

function showResultArea() {
    const area = document.getElementById('resultArea');
    area.classList.remove('hidden');

    // Reset states
    document.getElementById('progressArea').classList.remove('hidden');
    document.getElementById('resultImage').classList.add('hidden');
    document.getElementById('resultError').classList.add('hidden');
    document.getElementById('jobInfo').classList.add('hidden');

    // Reset progress
    document.getElementById('progressBar').style.width = '0%';
    document.getElementById('progressPercent').textContent = '0%';
    document.getElementById('progressStatus').textContent = 'กำลังส่งงานไปยัง Worker...';
}

function startPolling() {
    if (pollInterval) clearInterval(pollInterval);

    pollInterval = setInterval(async () => {
        if (!currentJobId) {
            clearInterval(pollInterval);
            return;
        }

        try {
            const response = await fetch(`{{ url('admin/playground/status') }}/${currentJobId}`);
            const data = await response.json();

            updateProgress(data);

            if (data.status === 'completed' || data.status === 'failed') {
                clearInterval(pollInterval);
            }
        } catch (error) {
            console.error('Polling error:', error);
        }
    }, 2000);
}

function updateProgress(data) {
    const progressBar = document.getElementById('progressBar');
    const progressPercent = document.getElementById('progressPercent');
    const progressStatus = document.getElementById('progressStatus');

    const progress = data.progress || 0;
    progressBar.style.width = progress + '%';
    progressPercent.textContent = progress + '%';

    // Update status text
    const statusTexts = {
        'pending': 'รอคิว...',
        'queued': 'อยู่ในคิว...',
        'processing': 'กำลังประมวลผล...',
        'completed': 'สำเร็จ!',
        'failed': 'ล้มเหลว',
    };
    progressStatus.textContent = statusTexts[data.status] || data.status;

    // Show job info
    document.getElementById('jobInfo').classList.remove('hidden');
    document.getElementById('infoJobId').textContent = data.job_id;
    document.getElementById('infoWorker').textContent = data.worker ? `${data.worker.name} (${data.worker.gpu})` : '-';
    document.getElementById('infoTime').textContent = data.processing_time ? data.processing_time + 's' : '-';
    document.getElementById('infoStatus').textContent = statusTexts[data.status] || data.status;

    if (data.status === 'completed') {
        document.getElementById('progressArea').classList.add('hidden');
        document.getElementById('resultImage').classList.remove('hidden');
        document.getElementById('generatedImage').src = data.result_url || data.result_thumbnail;
        document.getElementById('downloadLink').href = data.result_url || data.result_thumbnail;
        showToast('สร้างภาพสำเร็จ!', 'success');
    } else if (data.status === 'failed') {
        document.getElementById('progressArea').classList.add('hidden');
        document.getElementById('resultError').classList.remove('hidden');
        document.getElementById('errorMessage').textContent = data.error_message || 'เกิดข้อผิดพลาดในการสร้างภาพ';
        showToast('สร้างภาพล้มเหลว', 'error');
    }
}

function loadTestResult(jobId) {
    currentJobId = jobId;
    showResultArea();
    startPolling();
}

function copySettings() {
    const form = document.getElementById('testForm');
    const formData = new FormData(form);
    const settings = Object.fromEntries(formData);

    const text = JSON.stringify(settings, null, 2);
    navigator.clipboard.writeText(text).then(() => {
        showToast('คัดลอก Settings แล้ว', 'success');
    });
}

function showToast(message, type = 'success') {
    const toast = document.getElementById('toast');
    const content = document.getElementById('toastContent');
    const icon = document.getElementById('toastIcon');
    const msg = document.getElementById('toastMessage');

    msg.textContent = message;

    if (type === 'success') {
        content.className = 'px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 bg-green-500 text-white';
        icon.className = 'fas fa-check-circle text-xl';
    } else if (type === 'error') {
        content.className = 'px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 bg-red-500 text-white';
        icon.className = 'fas fa-exclamation-circle text-xl';
    } else {
        content.className = 'px-6 py-4 rounded-xl shadow-2xl flex items-center gap-3 bg-blue-500 text-white';
        icon.className = 'fas fa-info-circle text-xl';
    }

    toast.classList.remove('hidden');

    setTimeout(() => {
        toast.classList.add('hidden');
    }, 3000);
}
</script>
@endsection
