@extends('layouts.app')

@section('title', 'Generation Status')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">
        <!-- Back button -->
        <a href="{{ route('generate') }}" class="inline-flex items-center text-gray-400 hover:text-white mb-8 transition">
            <i class="fas fa-arrow-left mr-2"></i>กลับไปหน้าสร้าง
        </a>

        <div class="glass-card rounded-3xl overflow-hidden">
            <!-- Result Preview Area -->
            <div class="aspect-video bg-gray-900 relative flex items-center justify-center" id="resultContainer">
                @if($job->status === 'completed' && $job->result_url)
                    @if($job->type === 'video')
                    <video src="{{ $job->result_url }}" controls class="max-h-full max-w-full"></video>
                    @else
                    <img src="{{ $job->result_url }}" alt="Generated Image" class="max-h-full max-w-full object-contain">
                    @endif
                @elseif($job->status === 'failed')
                <div class="text-center text-red-400 p-8">
                    <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-red-500/20 flex items-center justify-center">
                        <i class="fas fa-exclamation-triangle text-5xl"></i>
                    </div>
                    <p class="text-xl font-semibold mb-2">การสร้างล้มเหลว</p>
                    <p class="text-gray-500">{{ $job->error_message }}</p>
                </div>
                @else
                <!-- Processing Animation -->
                <div class="w-full h-full flex flex-col items-center justify-center p-8" id="loadingState">

                    <!-- Main Progress Circle -->
                    <div class="relative w-40 h-40 mb-8">
                        <!-- Background circles -->
                        <svg class="absolute inset-0 w-full h-full -rotate-90">
                            <circle cx="80" cy="80" r="70" stroke="rgba(139, 92, 246, 0.1)" stroke-width="8" fill="none"/>
                            <circle id="progressCircle" cx="80" cy="80" r="70"
                                stroke="url(#progressGradient)"
                                stroke-width="8"
                                fill="none"
                                stroke-linecap="round"
                                stroke-dasharray="440"
                                stroke-dashoffset="{{ 440 - (440 * $job->progress / 100) }}"
                                class="transition-all duration-500"/>
                            <defs>
                                <linearGradient id="progressGradient" x1="0%" y1="0%" x2="100%" y2="0%">
                                    <stop offset="0%" style="stop-color:#8B5CF6"/>
                                    <stop offset="100%" style="stop-color:#EC4899"/>
                                </linearGradient>
                            </defs>
                        </svg>

                        <!-- Animated rings -->
                        <div class="absolute inset-4 border-2 border-purple-500/20 rounded-full animate-pulse"></div>
                        <div class="absolute inset-8 border-2 border-pink-500/20 rounded-full animate-pulse" style="animation-delay: 0.5s"></div>

                        <!-- Center content -->
                        <div class="absolute inset-0 flex flex-col items-center justify-center">
                            <span class="text-4xl font-bold gradient-text" id="progressText">{{ $job->progress }}%</span>
                            <span class="text-xs text-gray-500 mt-1" id="stageIcon">
                                <i class="fas fa-clock animate-pulse"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Stage Indicator -->
                    <div class="text-center mb-6">
                        <p class="text-xl font-semibold text-white mb-1" id="stageNameTh">
                            @if($job->status === 'queued')
                            รอคิว
                            @else
                            กำลังประมวลผล
                            @endif
                        </p>
                        <p class="text-gray-500 text-sm" id="stageName">
                            @if($job->status === 'queued')
                            Waiting in Queue
                            @else
                            Processing your {{ $job->type }}
                            @endif
                        </p>
                    </div>

                    <!-- Stages Timeline -->
                    <div class="flex items-center justify-center gap-2 mb-8 flex-wrap" id="stagesTimeline">
                        <div class="stage-item active" data-stage="1">
                            <div class="stage-dot"><i class="fas fa-clock"></i></div>
                            <span class="stage-label">รอคิว</span>
                        </div>
                        <div class="stage-connector"></div>
                        <div class="stage-item" data-stage="2">
                            <div class="stage-dot"><i class="fas fa-search"></i></div>
                            <span class="stage-label">จับคู่</span>
                        </div>
                        <div class="stage-connector"></div>
                        <div class="stage-item" data-stage="3">
                            <div class="stage-dot"><i class="fas fa-cog"></i></div>
                            <span class="stage-label">ประมวลผล</span>
                        </div>
                        <div class="stage-connector"></div>
                        <div class="stage-item" data-stage="4">
                            <div class="stage-dot"><i class="fas fa-puzzle-piece"></i></div>
                            <span class="stage-label">รวม</span>
                        </div>
                        <div class="stage-connector"></div>
                        <div class="stage-item" data-stage="5">
                            <div class="stage-dot"><i class="fas fa-cloud-upload-alt"></i></div>
                            <span class="stage-label">อัพโหลด</span>
                        </div>
                        <div class="stage-connector"></div>
                        <div class="stage-item" data-stage="6">
                            <div class="stage-dot"><i class="fas fa-check"></i></div>
                            <span class="stage-label">สำเร็จ</span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full max-w-md">
                        <div class="h-2 bg-gray-800 rounded-full overflow-hidden">
                            <div class="h-full rounded-full transition-all duration-500 relative overflow-hidden"
                                 id="progressBar"
                                 style="width: {{ $job->progress }}%; background: linear-gradient(90deg, #8B5CF6, #EC4899);">
                                <div class="absolute inset-0 shimmer"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Info Cards -->
                    <div class="grid grid-cols-3 gap-4 mt-8 w-full max-w-md">
                        <div class="glass rounded-xl p-3 text-center">
                            <div class="flex items-center justify-center gap-2 mb-1">
                                <span class="w-2 h-2 rounded-full bg-green-400 animate-pulse" id="workerIndicator"></span>
                                <span class="text-xs text-gray-500">Workers</span>
                            </div>
                            <p class="font-semibold text-white" id="workersOnline">-</p>
                            <p class="text-xs text-gray-600">ออนไลน์</p>
                        </div>
                        <div class="glass rounded-xl p-3 text-center">
                            <div class="text-xs text-gray-500 mb-1">
                                <i class="fas fa-list-ol"></i> ตำแหน่งคิว
                            </div>
                            <p class="font-semibold text-white" id="queuePosition">-</p>
                            <p class="text-xs text-gray-600" id="queueTotal">รอ</p>
                        </div>
                        <div class="glass rounded-xl p-3 text-center">
                            <div class="text-xs text-gray-500 mb-1">
                                <i class="fas fa-clock"></i> เวลาโดยประมาณ
                            </div>
                            <p class="font-semibold text-white" id="estimatedTime">~30s</p>
                            <p class="text-xs text-gray-600">เหลืออีก</p>
                        </div>
                    </div>

                    <!-- Worker Info (when processing) -->
                    <div class="mt-6 glass rounded-xl p-4 w-full max-w-md hidden" id="workerInfo">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                <i class="fas fa-microchip text-purple-400"></i>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">กำลังประมวลผลโดย</p>
                                <p class="font-medium text-white" id="workerName">-</p>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Details Section -->
            <div class="p-8">
                <!-- Status Badges -->
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <span class="px-3 py-1 rounded-full text-sm font-medium
                        {{ $job->status === 'completed' ? 'bg-green-500/20 text-green-400' :
                           ($job->status === 'processing' ? 'bg-yellow-500/20 text-yellow-400' :
                           ($job->status === 'failed' ? 'bg-red-500/20 text-red-400' : 'bg-purple-500/20 text-purple-400')) }}"
                        id="statusBadge">
                        <i class="fas {{ $job->status === 'completed' ? 'fa-check' :
                           ($job->status === 'processing' ? 'fa-spinner fa-spin' :
                           ($job->status === 'failed' ? 'fa-times' : 'fa-clock')) }} mr-1"></i>
                        <span id="statusText">{{ $job->status === 'completed' ? 'สำเร็จ' : ($job->status === 'processing' ? 'กำลังประมวลผล' : ($job->status === 'failed' ? 'ล้มเหลว' : 'รอคิว')) }}</span>
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm bg-purple-500/20 text-purple-400">
                        {{ $job->aiModel->name ?? 'Unknown Model' }}
                    </span>
                    <span class="px-3 py-1 rounded-full text-sm bg-blue-500/20 text-blue-400">
                        {{ $job->type === 'image' ? 'รูปภาพ' : 'วีดีโอ' }}
                    </span>
                </div>

                <!-- Prompt -->
                <h2 class="text-xl font-bold mb-4">
                    <i class="fas fa-magic text-purple-400 mr-2"></i>Prompt
                </h2>
                <p class="text-gray-300 mb-6 p-4 glass rounded-xl leading-relaxed">{{ $job->prompt }}</p>

                @if($job->negative_prompt)
                <h3 class="text-sm font-medium text-gray-400 mb-2">
                    <i class="fas fa-ban text-red-400 mr-2"></i>Negative Prompt
                </h3>
                <p class="text-gray-500 mb-6 text-sm p-3 glass rounded-xl">{{ $job->negative_prompt }}</p>
                @endif

                <!-- Parameters -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
                    <div class="glass rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-expand mr-1"></i>ขนาด</p>
                        <p class="font-medium">{{ $job->params['width'] ?? '1024' }} x {{ $job->params['height'] ?? '1024' }}</p>
                    </div>
                    <div class="glass rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-shoe-prints mr-1"></i>Steps</p>
                        <p class="font-medium">{{ $job->params['steps'] ?? '30' }}</p>
                    </div>
                    <div class="glass rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-sliders-h mr-1"></i>CFG Scale</p>
                        <p class="font-medium">{{ $job->params['cfg_scale'] ?? '7.5' }}</p>
                    </div>
                    <div class="glass rounded-xl p-4 text-center">
                        <p class="text-xs text-gray-500 mb-1"><i class="fas fa-seedling mr-1"></i>Seed</p>
                        <p class="font-medium font-mono text-sm">{{ $job->params['seed'] ?? 'Random' }}</p>
                    </div>
                </div>

                <!-- Action Buttons -->
                @if($job->status === 'completed')
                <div class="flex flex-wrap gap-4">
                    <a href="{{ $job->result_url }}" download class="flex-1 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 py-3 rounded-xl font-medium text-center transition flex items-center justify-center gap-2">
                        <i class="fas fa-download"></i>ดาวน์โหลด
                    </a>
                    <button onclick="shareResult()" class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition">
                        <i class="fas fa-share-alt mr-2"></i>แชร์
                    </button>
                    <a href="{{ route('generate') }}?prompt={{ urlencode($job->prompt) }}" class="px-6 py-3 glass-card rounded-xl hover:bg-white/10 transition">
                        <i class="fas fa-redo mr-2"></i>สร้างใหม่
                    </a>
                </div>
                @endif

                @if($job->status === 'failed')
                <div class="mt-6">
                    @if($job->refunded_at)
                    <div class="mb-4 p-4 rounded-xl bg-green-500/10 border border-green-500/30">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                                <i class="fas fa-undo text-green-400"></i>
                            </div>
                            <div>
                                <p class="font-medium text-green-400">เครดิตถูกคืนแล้ว</p>
                                <p class="text-sm text-gray-400">คุณได้รับ {{ number_format($job->refunded_amount, 0) }} Credits คืนเรียบร้อยแล้ว</p>
                            </div>
                        </div>
                    </div>
                    @endif
                    <a href="{{ route('generate') }}" class="inline-flex items-center gap-2 bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-xl font-medium transition">
                        <i class="fas fa-redo"></i>ลองอีกครั้ง
                    </a>
                </div>
                @endif

                <!-- Job Info Footer -->
                <div class="mt-8 pt-6 border-t border-gray-800 flex flex-col sm:flex-row items-start sm:items-center justify-between text-sm text-gray-500 gap-2">
                    <div class="flex items-center gap-4">
                        <span><i class="fas fa-hashtag mr-1"></i>{{ $job->job_id }}</span>
                        <span><i class="fas fa-coins mr-1"></i>{{ number_format($job->credits_used, 1) }} Credits</span>
                    </div>
                    <span><i class="fas fa-clock mr-1"></i>{{ $job->created_at->diffForHumans() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Stage Timeline Styles */
.stage-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 4px;
}

.stage-dot {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(55, 65, 81, 0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    color: #6b7280;
    border: 2px solid transparent;
    transition: all 0.3s ease;
}

.stage-label {
    font-size: 10px;
    color: #6b7280;
    transition: all 0.3s ease;
}

.stage-connector {
    width: 20px;
    height: 2px;
    background: rgba(55, 65, 81, 0.5);
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.stage-item.active .stage-dot {
    background: linear-gradient(135deg, #8B5CF6, #EC4899);
    color: white;
    box-shadow: 0 0 20px rgba(139, 92, 246, 0.5);
    animation: pulse-glow 2s infinite;
}

.stage-item.active .stage-label {
    color: #a855f7;
    font-weight: 500;
}

.stage-item.completed .stage-dot {
    background: #10B981;
    color: white;
}

.stage-item.completed .stage-label {
    color: #10B981;
}

.stage-connector.completed {
    background: #10B981;
}

@keyframes pulse-glow {
    0%, 100% { box-shadow: 0 0 20px rgba(139, 92, 246, 0.5); }
    50% { box-shadow: 0 0 30px rgba(139, 92, 246, 0.8); }
}

/* Shimmer effect */
.shimmer {
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
    animation: shimmer 1.5s infinite;
}

@keyframes shimmer {
    0% { transform: translateX(-100%); }
    100% { transform: translateX(100%); }
}

/* Gradient text */
.gradient-text {
    background: linear-gradient(90deg, #8B5CF6, #EC4899);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}
</style>

@if($job->status !== 'completed' && $job->status !== 'failed')
@push('scripts')
<script>
    const jobId = '{{ $job->job_id }}';
    let pollInterval;
    let lastStage = 0;

    function updateStageTimeline(currentStage) {
        document.querySelectorAll('.stage-item').forEach(item => {
            const stage = parseInt(item.dataset.stage);
            item.classList.remove('active', 'completed');

            if (stage < currentStage) {
                item.classList.add('completed');
            } else if (stage === currentStage) {
                item.classList.add('active');
            }
        });

        document.querySelectorAll('.stage-connector').forEach((connector, index) => {
            connector.classList.toggle('completed', index < currentStage - 1);
        });
    }

    function updateProgress(progress) {
        const circle = document.getElementById('progressCircle');
        const offset = 440 - (440 * progress / 100);
        circle.style.strokeDashoffset = offset;

        document.getElementById('progressText').textContent = Math.round(progress) + '%';
        document.getElementById('progressBar').style.width = progress + '%';
    }

    function checkStatus() {
        fetch(`/generate/${jobId}/status`)
            .then(r => r.json())
            .then(data => {
                // Update progress
                updateProgress(data.stage_progress || data.progress);

                // Update stage info
                if (data.stage !== lastStage) {
                    updateStageTimeline(data.stage);
                    lastStage = data.stage;
                }

                // Update stage name
                document.getElementById('stageNameTh').textContent = data.stage_name_th || 'กำลังประมวลผล';
                document.getElementById('stageName').textContent = data.stage_name || 'Processing';
                document.getElementById('stageIcon').innerHTML = `<i class="fas fa-${data.stage_icon || 'cog'} ${data.stage < 6 ? 'animate-pulse' : ''}"></i>`;

                // Update stats
                document.getElementById('workersOnline').textContent = data.workers_online || '-';

                if (data.queue_position > 0) {
                    document.getElementById('queuePosition').textContent = '#' + data.queue_position;
                    document.getElementById('queueTotal').textContent = 'จาก ' + data.queue_total + ' งาน';
                } else {
                    document.getElementById('queuePosition').textContent = '-';
                    document.getElementById('queueTotal').textContent = 'กำลังทำ';
                }

                if (data.estimated_time) {
                    document.getElementById('estimatedTime').textContent = '~' + data.estimated_time + 's';
                }

                // Update worker indicator
                const indicator = document.getElementById('workerIndicator');
                if (data.workers_available > 0) {
                    indicator.classList.remove('bg-red-400');
                    indicator.classList.add('bg-green-400');
                } else {
                    indicator.classList.remove('bg-green-400');
                    indicator.classList.add('bg-red-400');
                }

                // Show worker info when processing
                const workerInfo = document.getElementById('workerInfo');
                if (data.worker_name && data.status === 'processing') {
                    workerInfo.classList.remove('hidden');
                    document.getElementById('workerName').textContent = data.worker_name;
                }

                // Handle completion/failure
                if (data.status === 'completed' || data.status === 'failed') {
                    clearInterval(pollInterval);

                    if (data.status === 'completed') {
                        updateStageTimeline(6);
                        updateProgress(100);
                        document.getElementById('stageNameTh').textContent = 'เสร็จสิ้น!';
                        document.getElementById('stageName').textContent = 'Generation Complete';

                        setTimeout(() => location.reload(), 1000);
                    } else {
                        location.reload();
                    }
                }
            })
            .catch(err => {
                console.error('Error checking status:', err);
            });
    }

    // Initial stage setup
    updateStageTimeline(1);

    // Start polling
    pollInterval = setInterval(checkStatus, 2000);
    checkStatus();
</script>
@endpush
@endif

@push('scripts')
<script>
    function shareResult() {
        if (navigator.share) {
            navigator.share({
                title: 'AI Generated Art',
                text: '{{ Str::limit($job->prompt, 100) }}',
                url: window.location.href
            });
        } else {
            navigator.clipboard.writeText(window.location.href);
            alert('ลิงก์ถูกคัดลอกแล้ว!');
        }
    }
</script>
@endpush
@endsection
