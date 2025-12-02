@extends('layouts.admin')

@section('title', 'จัดการเวอร์ชัน Client')

@section('content')
<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <h2 class="mb-0">จัดการเวอร์ชัน Client</h2>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
                    <i class="bi bi-cloud-upload me-2"></i>อัปโหลดเวอร์ชันใหม่
                </button>
            </div>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-primary bg-opacity-10 p-3 me-3">
                            <i class="bi bi-box-seam text-primary fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">จำนวนเวอร์ชัน</h6>
                            <h3 class="mb-0">{{ $stats['total_versions'] }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-success bg-opacity-10 p-3 me-3">
                            <i class="bi bi-download text-success fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">ดาวน์โหลดทั้งหมด</h6>
                            <h3 class="mb-0">{{ number_format($stats['total_downloads']) }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-circle bg-info bg-opacity-10 p-3 me-3">
                            <i class="bi bi-laptop text-info fs-4"></i>
                        </div>
                        <div>
                            <h6 class="text-muted mb-0">แพลตฟอร์ม</h6>
                            <h3 class="mb-0">{{ $stats['platforms']->count() }}</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Versions Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>เวอร์ชัน</th>
                            <th>แพลตฟอร์ม</th>
                            <th>ขนาดไฟล์</th>
                            <th>ดาวน์โหลด</th>
                            <th>สถานะ</th>
                            <th>อัปโหลดเมื่อ</th>
                            <th>การดำเนินการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($versions as $version)
                        <tr>
                            <td>
                                <div class="d-flex align-items-center">
                                    <span class="fw-bold">v{{ $version->version }}</span>
                                    @if($version->is_latest)
                                        <span class="badge bg-success ms-2">ล่าสุด</span>
                                    @endif
                                </div>
                            </td>
                            <td>
                                @if($version->platform === 'windows')
                                    <i class="bi bi-windows text-primary me-1"></i> Windows
                                @elseif($version->platform === 'linux')
                                    <i class="bi bi-ubuntu text-warning me-1"></i> Linux
                                @elseif($version->platform === 'mac')
                                    <i class="bi bi-apple text-secondary me-1"></i> macOS
                                @else
                                    <i class="bi bi-code-slash text-success me-1"></i> Source
                                @endif
                            </td>
                            <td>{{ $version->formatted_file_size }}</td>
                            <td>{{ number_format($version->download_count) }}</td>
                            <td>
                                @if($version->is_active)
                                    <span class="badge bg-success">เปิดใช้งาน</span>
                                @else
                                    <span class="badge bg-secondary">ปิดใช้งาน</span>
                                @endif
                            </td>
                            <td>{{ $version->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" onclick="viewDetails({{ $version->id }})" title="รายละเอียด">
                                        <i class="bi bi-eye"></i>
                                    </button>
                                    @if(!$version->is_latest)
                                        <button type="button" class="btn btn-outline-success" onclick="setLatest({{ $version->id }})" title="ตั้งเป็นล่าสุด">
                                            <i class="bi bi-star"></i>
                                        </button>
                                    @endif
                                    <button type="button" class="btn btn-outline-warning" onclick="toggleVersion({{ $version->id }})" title="{{ $version->is_active ? 'ปิดใช้งาน' : 'เปิดใช้งาน' }}">
                                        <i class="bi bi-{{ $version->is_active ? 'pause' : 'play' }}"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger" onclick="deleteVersion({{ $version->id }})" title="ลบ">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-box-seam fs-1 d-block mb-3"></i>
                                    <p class="mb-0">ยังไม่มีเวอร์ชัน Client</p>
                                    <p class="small">กดปุ่ม "อัปโหลดเวอร์ชันใหม่" เพื่อเริ่มต้น</p>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Upload Modal -->
<div class="modal fade" id="uploadModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">อัปโหลดเวอร์ชันใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="uploadForm" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">เวอร์ชัน <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="version" placeholder="1.0.0" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">แพลตฟอร์ม <span class="text-danger">*</span></label>
                            <select class="form-select" name="platform" required>
                                <option value="">เลือกแพลตฟอร์ม</option>
                                <option value="windows">Windows</option>
                                <option value="linux">Linux</option>
                                <option value="mac">macOS</option>
                                <option value="source">Source Code</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">ไฟล์ <span class="text-danger">*</span></label>
                        <input type="file" class="form-control" name="file" required accept=".exe,.zip,.tar.gz,.dmg">
                        <div class="form-text">รองรับไฟล์: .exe, .zip, .tar.gz, .dmg (สูงสุด 500MB)</div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">GPU Memory ขั้นต่ำ</label>
                            <input type="text" class="form-control" name="min_gpu_memory" value="4GB">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">RAM ขั้นต่ำ</label>
                            <input type="text" class="form-control" name="min_ram" value="8GB">
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Release Notes</label>
                        <textarea class="form-control" name="release_notes" rows="4" placeholder="รายละเอียดการอัปเดต..."></textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" name="is_latest" id="isLatest" value="1">
                        <label class="form-check-label" for="isLatest">ตั้งเป็นเวอร์ชันล่าสุด</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" id="uploadBtn">
                        <span class="spinner-border spinner-border-sm d-none me-2" id="uploadSpinner"></span>
                        อัปโหลด
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Details Modal -->
<div class="modal fade" id="detailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">รายละเอียดเวอร์ชัน</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="detailsContent">
                <!-- Content loaded dynamically -->
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const versionsData = @json($versions);

document.getElementById('uploadForm').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('uploadBtn');
    const spinner = document.getElementById('uploadSpinner');
    const formData = new FormData(this);

    btn.disabled = true;
    spinner.classList.remove('d-none');

    try {
        const response = await fetch('{{ route("admin.client-versions.store") }}', {
            method: 'POST',
            body: formData,
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาดในการอัปโหลด');
        console.error(error);
    } finally {
        btn.disabled = false;
        spinner.classList.add('d-none');
    }
});

function viewDetails(id) {
    const version = versionsData.find(v => v.id === id);
    if (!version) return;

    const content = `
        <table class="table table-borderless">
            <tr>
                <td class="fw-bold">เวอร์ชัน:</td>
                <td>v${version.version}</td>
            </tr>
            <tr>
                <td class="fw-bold">แพลตฟอร์ม:</td>
                <td>${version.platform}</td>
            </tr>
            <tr>
                <td class="fw-bold">ไฟล์:</td>
                <td>${version.filename}</td>
            </tr>
            <tr>
                <td class="fw-bold">ขนาด:</td>
                <td>${version.formatted_file_size}</td>
            </tr>
            <tr>
                <td class="fw-bold">SHA256:</td>
                <td><code class="small">${version.checksum_sha256 || 'N/A'}</code></td>
            </tr>
            <tr>
                <td class="fw-bold">GPU Memory:</td>
                <td>${version.min_gpu_memory}</td>
            </tr>
            <tr>
                <td class="fw-bold">RAM:</td>
                <td>${version.min_ram}</td>
            </tr>
            <tr>
                <td class="fw-bold">ดาวน์โหลด:</td>
                <td>${version.download_count.toLocaleString()} ครั้ง</td>
            </tr>
            ${version.release_notes ? `
            <tr>
                <td class="fw-bold">Release Notes:</td>
                <td><pre class="mb-0 small">${version.release_notes}</pre></td>
            </tr>
            ` : ''}
        </table>
    `;

    document.getElementById('detailsContent').innerHTML = content;
    new bootstrap.Modal(document.getElementById('detailsModal')).show();
}

async function setLatest(id) {
    if (!confirm('ต้องการตั้งเวอร์ชันนี้เป็นเวอร์ชันล่าสุด?')) return;

    try {
        const response = await fetch(`/admin/client-versions/${id}/set-latest`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาด');
        console.error(error);
    }
}

async function toggleVersion(id) {
    try {
        const response = await fetch(`/admin/client-versions/${id}/toggle`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาด');
        console.error(error);
    }
}

async function deleteVersion(id) {
    if (!confirm('ต้องการลบเวอร์ชันนี้? ไฟล์จะถูกลบถาวร')) return;

    try {
        const response = await fetch(`/admin/client-versions/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            location.reload();
        } else {
            alert(data.message || 'เกิดข้อผิดพลาด');
        }
    } catch (error) {
        alert('เกิดข้อผิดพลาด');
        console.error(error);
    }
}
</script>
@endpush
@endsection
