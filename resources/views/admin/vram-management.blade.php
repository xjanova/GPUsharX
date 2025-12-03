@extends('layouts.admin')

@section('title', 'VRAM Management')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0">
            <i class="fas fa-memory me-2"></i>VRAM Management
        </h1>
        <div>
            <button class="btn btn-outline-primary" onclick="refreshStats()">
                <i class="fas fa-sync-alt me-1"></i> Refresh
            </button>
        </div>
    </div>

    <!-- VRAM Tier Overview -->
    <div class="row mb-4">
        @foreach($vramTiers as $tierName => $tier)
        <div class="col-md-4 col-lg-2 mb-3">
            <div class="card h-100 border-0 shadow-sm" style="border-left: 4px solid {{ $tier['color'] }} !important;">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <h6 class="text-muted mb-1 small">{{ $tier['label'] }}</h6>
                            <h3 class="mb-0">{{ $tier['worker_count'] }}</h3>
                            <small class="text-muted">workers</small>
                        </div>
                        <div class="text-end">
                            <i class="fas fa-microchip fa-2x" style="color: {{ $tier['color'] }}; opacity: 0.3;"></i>
                        </div>
                    </div>
                    <div class="mt-2">
                        <small class="text-success">
                            <i class="fas fa-circle fa-xs me-1"></i>{{ $tier['online_count'] }} online
                        </small>
                    </div>
                    <div class="progress mt-2" style="height: 4px;">
                        <div class="progress-bar" style="width: {{ $tier['utilization'] }}%; background-color: {{ $tier['color'] }}"></div>
                    </div>
                    <small class="text-muted">{{ $tier['utilization'] }}% utilized</small>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h6 class="card-title opacity-75">Total Workers</h6>
                    <h2 class="mb-0">{{ $stats['total_workers'] }}</h2>
                    <small class="opacity-75">{{ $stats['online_workers'] }} online</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h6 class="card-title opacity-75">Low VRAM Workers</h6>
                    <h2 class="mb-0">{{ $stats['low_vram_workers'] }}</h2>
                    <small class="opacity-75">≤ 6GB VRAM</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h6 class="card-title opacity-75">Pending Chunks</h6>
                    <h2 class="mb-0">{{ $stats['pending_chunks'] }}</h2>
                    <small class="opacity-75">waiting for workers</small>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card bg-warning text-dark">
                <div class="card-body">
                    <h6 class="card-title opacity-75">Micro Chunks</h6>
                    <h2 class="mb-0">{{ $stats['micro_chunks'] }}</h2>
                    <small class="opacity-75">for 3GB GPUs</small>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Workers by VRAM -->
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-server me-2"></i>Workers by VRAM Tier
                    </h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th style="min-width: 120px;">Worker</th>
                                    <th style="min-width: 150px;">GPU</th>
                                    <th style="min-width: 70px; text-align: right;">VRAM</th>
                                    <th style="min-width: 80px; text-align: center;">Tier</th>
                                    <th style="min-width: 80px; text-align: center;">Status</th>
                                    <th style="min-width: 80px; text-align: right;">Jobs</th>
                                    <th style="min-width: 90px; text-align: right;">Earnings</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($workers as $worker)
                                <tr>
                                    <td>
                                        <a href="{{ route('admin.nodes.show', $worker->id) }}" class="text-decoration-none">
                                            {{ Str::limit($worker->name ?? $worker->node_id, 15) }}
                                        </a>
                                        <br>
                                        <small class="text-muted">{{ Str::limit($worker->user->name ?? 'N/A', 12) }}</small>
                                    </td>
                                    <td>
                                        <span class="badge bg-dark text-truncate" style="max-width: 140px; display: inline-block;">
                                            {{ Str::limit($worker->gpu_model, 18) }}
                                        </span>
                                    </td>
                                    <td style="text-align: right;">
                                        <strong>{{ number_format($worker->gpu_vram_mb / 1024, 1) }} GB</strong>
                                    </td>
                                    <td style="text-align: center;">
                                        @php
                                            $tier = \App\Models\RenderJob::getVramTier($worker->gpu_vram_mb);
                                        @endphp
                                        <span class="badge" style="background-color: {{ $vramTiers[$tier['name']]['color'] ?? '#666' }}">
                                            {{ Str::limit($tier['label'], 8) }}
                                        </span>
                                    </td>
                                    <td style="text-align: center;">
                                        @if($worker->status === 'online')
                                            <span class="badge bg-success">Online</span>
                                        @elseif($worker->status === 'working')
                                            <span class="badge bg-primary">Working</span>
                                        @else
                                            <span class="badge bg-secondary">Offline</span>
                                        @endif
                                    </td>
                                    <td style="text-align: right;">{{ number_format($worker->total_completed_chunks ?? 0) }}</td>
                                    <td style="text-align: right;">{{ number_format($worker->total_earnings ?? 0, 2) }} ฿</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        No workers registered yet
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @if($workers->hasPages())
                <div class="card-footer bg-white">
                    {{ $workers->links() }}
                </div>
                @endif
            </div>
        </div>

        <!-- VRAM Tier Settings -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-cogs me-2"></i>VRAM Tier Settings
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.vram.update-settings') }}" method="POST">
                        @csrf

                        <div class="mb-3">
                            <label class="form-label">Minimum VRAM Allowed</label>
                            <select name="min_vram_mb" class="form-select">
                                <option value="2048" {{ ($settings['min_vram_mb'] ?? 4096) == 2048 ? 'selected' : '' }}>2 GB (Ultra Low)</option>
                                <option value="3072" {{ ($settings['min_vram_mb'] ?? 4096) == 3072 ? 'selected' : '' }}>3 GB</option>
                                <option value="4096" {{ ($settings['min_vram_mb'] ?? 4096) == 4096 ? 'selected' : '' }}>4 GB (Default)</option>
                                <option value="6144" {{ ($settings['min_vram_mb'] ?? 4096) == 6144 ? 'selected' : '' }}>6 GB</option>
                                <option value="8192" {{ ($settings['min_vram_mb'] ?? 4096) == 8192 ? 'selected' : '' }}>8 GB</option>
                            </select>
                            <small class="text-muted">Workers below this won't receive jobs</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_micro_chunks"
                                       id="enableMicroChunks" {{ ($settings['enable_micro_chunks'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enableMicroChunks">
                                    Enable Micro Chunks (for 3GB)
                                </label>
                            </div>
                            <small class="text-muted">Allow 256x256 tiles for ultra-low VRAM</small>
                        </div>

                        <div class="mb-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="enable_step_splitting"
                                       id="enableStepSplitting" {{ ($settings['enable_step_splitting'] ?? true) ? 'checked' : '' }}>
                                <label class="form-check-label" for="enableStepSplitting">
                                    Enable Step Splitting
                                </label>
                            </div>
                            <small class="text-muted">Split denoising steps across workers</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Max Chunks Per Job</label>
                            <input type="number" name="max_chunks_per_job" class="form-control"
                                   value="{{ $settings['max_chunks_per_job'] ?? 64 }}" min="1" max="256">
                            <small class="text-muted">Limit fragmentation</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Low VRAM Bonus Rate (%)</label>
                            <input type="number" name="low_vram_bonus_percent" class="form-control"
                                   value="{{ $settings['low_vram_bonus_percent'] ?? 10 }}" min="0" max="50" step="1">
                            <small class="text-muted">Extra earnings for low VRAM workers</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-save me-1"></i> Save Settings
                        </button>
                    </form>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="fas fa-chart-pie me-2"></i>VRAM Distribution
                    </h5>
                </div>
                <div class="card-body">
                    <canvas id="vramChart" height="200"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Active Jobs with Chunking Info -->
    <div class="card shadow-sm">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h5 class="mb-0">
                <i class="fas fa-tasks me-2"></i>Active Jobs (Chunking Details)
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Job ID</th>
                            <th>Type</th>
                            <th>Strategy</th>
                            <th>Total Chunks</th>
                            <th>Min VRAM</th>
                            <th>Progress</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($activeJobs as $job)
                        <tr>
                            <td>
                                <code>{{ Str::limit($job->job_id, 12) }}</code>
                            </td>
                            <td>{{ $job->type ?? 'image' }}</td>
                            <td>
                                @if($job->chunking_strategy === 'micro')
                                    <span class="badge bg-warning text-dark">Micro</span>
                                @elseif($job->chunking_strategy === 'hybrid')
                                    <span class="badge bg-info">Hybrid</span>
                                @elseif($job->chunking_strategy === 'tile_based')
                                    <span class="badge bg-primary">Tiles</span>
                                @elseif($job->chunking_strategy === 'step_based')
                                    <span class="badge bg-success">Steps</span>
                                @else
                                    <span class="badge bg-secondary">Single</span>
                                @endif
                            </td>
                            <td>{{ $job->total_chunks }}</td>
                            <td>
                                @php
                                    $minVram = $job->chunks()->min('required_vram_mb') ?? $job->required_vram_mb;
                                @endphp
                                {{ number_format(($minVram ?? 8192) / 1024, 1) }} GB
                            </td>
                            <td>
                                <div class="progress" style="height: 20px; width: 100px;">
                                    <div class="progress-bar" style="width: {{ $job->progress_percentage }}%">
                                        {{ $job->progress_percentage }}%
                                    </div>
                                </div>
                            </td>
                            <td>
                                @if($job->status === 'processing')
                                    <span class="badge bg-primary">Processing</span>
                                @elseif($job->status === 'queued')
                                    <span class="badge bg-warning text-dark">Queued</span>
                                @else
                                    <span class="badge bg-secondary">{{ $job->status }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                No active jobs
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// VRAM Distribution Chart
const ctx = document.getElementById('vramChart').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: {!! json_encode(collect($vramTiers)->pluck('label')->values()) !!},
        datasets: [{
            data: {!! json_encode(collect($vramTiers)->pluck('worker_count')->values()) !!},
            backgroundColor: {!! json_encode(collect($vramTiers)->pluck('color')->values()) !!},
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        layout: {
            padding: {
                top: 10,
                bottom: 10
            }
        },
        plugins: {
            legend: {
                position: 'right',
                align: 'start',
                labels: {
                    boxWidth: 12,
                    padding: 8,
                    font: {
                        size: 11
                    },
                    generateLabels: function(chart) {
                        const data = chart.data;
                        if (data.labels.length && data.datasets.length) {
                            return data.labels.map((label, i) => ({
                                text: label.length > 10 ? label.substring(0, 10) + '...' : label,
                                fillStyle: data.datasets[0].backgroundColor[i],
                                hidden: false,
                                index: i
                            }));
                        }
                        return [];
                    }
                }
            },
            tooltip: {
                callbacks: {
                    label: function(context) {
                        const label = context.label || '';
                        const value = context.raw || 0;
                        return label + ': ' + value + ' workers';
                    }
                }
            }
        }
    }
});

function refreshStats() {
    location.reload();
}
</script>
@endpush
@endsection
