@extends('install.layout')

@section('title', 'Database Configuration')

@section('content')
<div>
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">ตั้งค่าฐานข้อมูล MySQL</h2>
            <p class="text-gray-400 text-sm">กรอกข้อมูลการเชื่อมต่อฐานข้อมูล</p>
        </div>
    </div>

    <!-- Installation Progress Modal -->
    <div id="installModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="glass rounded-2xl p-8 max-w-md w-full mx-4 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-purple-500/20 flex items-center justify-center">
                <svg class="w-10 h-10 text-purple-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">กำลังติดตั้งระบบ</h3>
            <p id="installStatus" class="text-gray-400 mb-4">กำลังเชื่อมต่อฐานข้อมูล...</p>
            <div class="h-2 bg-white/10 rounded-full overflow-hidden mb-4">
                <div id="installProgress" class="h-full bg-gradient-to-r from-purple-600 to-pink-600 rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>
            <p class="text-gray-500 text-sm">กรุณารอสักครู่ อย่าปิดหน้านี้</p>
        </div>
    </div>

    <form id="dbForm">
        <div class="space-y-5">
            <!-- Database Host -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="db_host">
                    Database Host <span class="text-red-400">*</span>
                </label>
                <input type="text" id="db_host" name="db_host" value="{{ old('db_host', '127.0.0.1') }}"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                    placeholder="127.0.0.1" required>
            </div>

            <!-- Database Port -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="db_port">
                    Database Port <span class="text-red-400">*</span>
                </label>
                <input type="number" id="db_port" name="db_port" value="{{ old('db_port', '3306') }}"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                    placeholder="3306" required>
            </div>

            <!-- Database Name -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="db_database">
                    Database Name <span class="text-red-400">*</span>
                </label>
                <input type="text" id="db_database" name="db_database" value="{{ old('db_database', 'gpu_sharing') }}"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                    placeholder="gpu_sharing" required>
                <p class="text-gray-500 text-xs mt-1">ระบบจะสร้างฐานข้อมูลให้อัตโนมัติถ้ายังไม่มี</p>
            </div>

            <!-- Database Username -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="db_username">
                    Database Username <span class="text-red-400">*</span>
                </label>
                <input type="text" id="db_username" name="db_username" value="{{ old('db_username', 'root') }}"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                    placeholder="root" required>
            </div>

            <!-- Database Password -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="db_password">
                    Database Password
                </label>
                <div class="relative">
                    <input type="password" id="db_password" name="db_password" value="{{ old('db_password', '') }}"
                        class="input-glass w-full px-4 py-3 pr-12 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                        placeholder="••••••••">
                    <button type="button" onclick="togglePassword()" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                        <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-gray-500 text-xs mt-1">ปล่อยว่างถ้าไม่มีรหัสผ่าน</p>
            </div>
        </div>

        <!-- Connection Test Button -->
        <div class="mt-6 p-4 rounded-xl bg-white/5 border border-white/10">
            <div class="flex items-center justify-between">
                <div>
                    <h4 class="text-white font-medium">ทดสอบการเชื่อมต่อ</h4>
                    <p class="text-gray-400 text-sm">ตรวจสอบว่าสามารถเชื่อมต่อฐานข้อมูลได้</p>
                </div>
                <button type="button" onclick="testConnection()" class="px-4 py-2 rounded-lg bg-white/10 text-white font-medium hover:bg-white/20 transition flex items-center gap-2">
                    <svg id="testIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <span id="testText">ทดสอบ</span>
                </button>
            </div>
            <div id="testResult" class="hidden mt-4 p-3 rounded-lg"></div>
        </div>

        <!-- Info Box -->
        <div class="mt-6 p-4 rounded-xl bg-blue-500/10 border border-blue-500/20">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-blue-300">
                    <p class="font-medium mb-1">ข้อมูล Laragon Default:</p>
                    <ul class="text-blue-400 space-y-0.5">
                        <li>• Host: 127.0.0.1</li>
                        <li>• Port: 3306</li>
                        <li>• Username: root</li>
                        <li>• Password: root (หรือ ว่าง)</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Error Display -->
        <div id="errorBox" class="hidden mt-6 p-4 rounded-xl bg-red-500/10 border border-red-500/20">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-red-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <p id="errorText" class="text-red-400 text-sm"></p>
            </div>
        </div>

        <div class="flex items-center justify-between gap-4 mt-8">
            <a href="{{ route('install.step', 2) }}" class="px-6 py-3 rounded-xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                ย้อนกลับ
            </a>

            <button type="button" onclick="startInstall()" id="submitBtn" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition flex items-center gap-2">
                <span id="submitText">เชื่อมต่อและติดตั้ง</span>
                <svg id="submitIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<script>
function togglePassword() {
    const input = document.getElementById('db_password');
    const icon = document.getElementById('eyeIcon');
    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}

function testConnection() {
    const btn = event.target.closest('button');
    const icon = document.getElementById('testIcon');
    const text = document.getElementById('testText');
    const result = document.getElementById('testResult');

    btn.disabled = true;
    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>';
    icon.classList.add('animate-spin');
    text.textContent = 'กำลังทดสอบ...';

    const formData = new FormData(document.getElementById('dbForm'));

    fetch('{{ route("install.test-db") }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        result.classList.remove('hidden');
        if (data.success) {
            result.className = 'mt-4 p-3 rounded-lg bg-green-500/20 border border-green-500/30 text-green-400';
            result.innerHTML = '<div class="flex items-center gap-2"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg><span>' + data.message + '</span></div>';
        } else {
            result.className = 'mt-4 p-3 rounded-lg bg-red-500/20 border border-red-500/30 text-red-400';
            result.innerHTML = '<div class="flex items-center gap-2"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>' + data.message + '</span></div>';
        }
    })
    .catch(error => {
        result.classList.remove('hidden');
        result.className = 'mt-4 p-3 rounded-lg bg-red-500/20 border border-red-500/30 text-red-400';
        result.innerHTML = '<div class="flex items-center gap-2"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg><span>เกิดข้อผิดพลาดในการเชื่อมต่อ</span></div>';
    })
    .finally(() => {
        btn.disabled = false;
        icon.classList.remove('animate-spin');
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>';
        text.textContent = 'ทดสอบ';
    });
}

function startInstall() {
    const modal = document.getElementById('installModal');
    const progress = document.getElementById('installProgress');
    const status = document.getElementById('installStatus');
    const errorBox = document.getElementById('errorBox');
    const errorText = document.getElementById('errorText');
    const submitBtn = document.getElementById('submitBtn');

    // Validate form
    const form = document.getElementById('dbForm');
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Hide error box
    errorBox.classList.add('hidden');

    // Disable button
    submitBtn.disabled = true;

    // Show modal
    modal.classList.remove('hidden');

    // Simulate progress
    let progressValue = 0;
    const progressSteps = [
        { value: 10, text: 'กำลังเชื่อมต่อฐานข้อมูล...' },
        { value: 30, text: 'กำลังสร้างฐานข้อมูล...' },
        { value: 50, text: 'กำลังสร้างตาราง...' },
        { value: 70, text: 'กำลังเพิ่มข้อมูลเริ่มต้น...' },
        { value: 90, text: 'กำลังบันทึกการตั้งค่า...' },
    ];

    let stepIndex = 0;
    const progressInterval = setInterval(() => {
        if (stepIndex < progressSteps.length) {
            progress.style.width = progressSteps[stepIndex].value + '%';
            status.textContent = progressSteps[stepIndex].text;
            stepIndex++;
        }
    }, 2000);

    // Send AJAX request with AbortController for timeout
    const formData = new FormData(form);
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 300000); // 5 minutes timeout

    fetch('{{ route("install.process", 3) }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData,
        signal: controller.signal
    })
    .then(response => {
        clearTimeout(timeoutId);
        if (!response.ok) {
            return response.text().then(text => {
                try {
                    return JSON.parse(text);
                } catch (e) {
                    throw new Error('Server error: ' + (text.substring(0, 200) || response.status));
                }
            });
        }
        return response.json();
    })
    .then(data => {
        clearInterval(progressInterval);

        if (data.success) {
            progress.style.width = '100%';
            status.textContent = 'เสร็จสิ้น! กำลังไปขั้นตอนถัดไป...';
            setTimeout(() => {
                window.location.href = data.redirect || '{{ route("install.step", 4) }}';
            }, 1000);
        } else {
            modal.classList.add('hidden');
            errorBox.classList.remove('hidden');
            errorText.textContent = data.message || 'เกิดข้อผิดพลาดในการติดตั้ง';
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        clearInterval(progressInterval);
        modal.classList.add('hidden');
        errorBox.classList.remove('hidden');

        if (error.name === 'AbortError') {
            errorText.textContent = 'หมดเวลาการติดตั้ง กรุณาลองใหม่อีกครั้ง';
        } else {
            errorText.textContent = error.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง';
        }
        submitBtn.disabled = false;
        console.error('Install error:', error);
    });
}
</script>
@endsection
