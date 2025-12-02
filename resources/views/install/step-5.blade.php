@extends('install.layout')

@section('title', 'Platform Settings')

@section('content')
<div>
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-xl bg-pink-500/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">ตั้งค่าแพลตฟอร์ม</h2>
            <p class="text-gray-400 text-sm">กำหนดค่าพื้นฐานสำหรับระบบ</p>
        </div>
    </div>

    <!-- Saving Progress Modal -->
    <div id="saveModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="glass rounded-2xl p-8 max-w-md w-full mx-4 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-pink-500/20 flex items-center justify-center">
                <svg class="w-10 h-10 text-pink-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">กำลังบันทึกการตั้งค่า</h3>
            <p id="saveStatus" class="text-gray-400 mb-4">กำลังบันทึกการตั้งค่าแพลตฟอร์ม...</p>
            <div class="h-2 bg-white/10 rounded-full overflow-hidden mb-4">
                <div id="saveProgress" class="h-full bg-gradient-to-r from-purple-600 to-pink-600 rounded-full transition-all duration-500" style="width: 0%"></div>
            </div>
            <p class="text-gray-500 text-sm">กรุณารอสักครู่ อย่าปิดหน้านี้</p>
        </div>
    </div>

    <form id="settingsForm">

        <div class="space-y-5">
            <!-- Application Name -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="app_name">
                    ชื่อแอปพลิเคชัน <span class="text-red-400">*</span>
                </label>
                <input type="text" id="app_name" name="app_name" value="{{ old('app_name', 'GPU Share Platform') }}"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                    placeholder="GPU Share Platform" required>
            </div>

            <!-- Application URL -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="app_url">
                    URL ของเว็บไซต์ <span class="text-red-400">*</span>
                </label>
                <input type="url" id="app_url" name="app_url" value="{{ old('app_url', url('/')) }}"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                    placeholder="https://your-domain.com" required>
                <p class="text-gray-500 text-xs mt-1">URL หลักของเว็บไซต์ (ไม่ต้องมี / ท้าย)</p>
            </div>

            <!-- Timezone -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="timezone">
                    เขตเวลา <span class="text-red-400">*</span>
                </label>
                <select id="timezone" name="timezone"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white focus:border-purple-500 focus:outline-none transition">
                    <option value="Asia/Bangkok" {{ old('timezone') == 'Asia/Bangkok' ? 'selected' : '' }}>Asia/Bangkok (UTC+7)</option>
                    <option value="UTC" {{ old('timezone') == 'UTC' ? 'selected' : '' }}>UTC (UTC+0)</option>
                    <option value="Asia/Singapore" {{ old('timezone') == 'Asia/Singapore' ? 'selected' : '' }}>Asia/Singapore (UTC+8)</option>
                    <option value="Asia/Tokyo" {{ old('timezone') == 'Asia/Tokyo' ? 'selected' : '' }}>Asia/Tokyo (UTC+9)</option>
                    <option value="America/New_York" {{ old('timezone') == 'America/New_York' ? 'selected' : '' }}>America/New_York (UTC-5)</option>
                    <option value="America/Los_Angeles" {{ old('timezone') == 'America/Los_Angeles' ? 'selected' : '' }}>America/Los_Angeles (UTC-8)</option>
                    <option value="Europe/London" {{ old('timezone') == 'Europe/London' ? 'selected' : '' }}>Europe/London (UTC+0)</option>
                </select>
            </div>
        </div>

        <!-- Summary -->
        <div class="mt-8 p-6 rounded-xl bg-white/5 border border-white/10">
            <h3 class="text-white font-semibold mb-4">สรุปการติดตั้ง</h3>

            <div class="space-y-3">
                <div class="flex items-center justify-between py-2 border-b border-white/10">
                    <span class="text-gray-400">ฐานข้อมูล</span>
                    <span class="text-green-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        เชื่อมต่อแล้ว (MySQL)
                    </span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-white/10">
                    <span class="text-gray-400">ตาราง</span>
                    <span class="text-green-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        สร้างแล้ว
                    </span>
                </div>
                <div class="flex items-center justify-between py-2 border-b border-white/10">
                    <span class="text-gray-400">บัญชีแอดมิน</span>
                    <span class="text-green-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        สร้างแล้ว
                    </span>
                </div>
                <div class="flex items-center justify-between py-2">
                    <span class="text-gray-400">AI Models</span>
                    <span class="text-green-400 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        9 โมเดลพร้อมใช้
                    </span>
                </div>
            </div>
        </div>

        <!-- Features Enabled -->
        <div class="mt-6 grid grid-cols-2 md:grid-cols-3 gap-3">
            <div class="p-3 rounded-xl bg-purple-500/10 border border-purple-500/20 text-center">
                <svg class="w-6 h-6 text-purple-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
                <span class="text-gray-300 text-xs">GPU Sharing</span>
            </div>
            <div class="p-3 rounded-xl bg-pink-500/10 border border-pink-500/20 text-center">
                <svg class="w-6 h-6 text-pink-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
                <span class="text-gray-300 text-xs">AI Generation</span>
            </div>
            <div class="p-3 rounded-xl bg-green-500/10 border border-green-500/20 text-center">
                <svg class="w-6 h-6 text-green-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-gray-300 text-xs">Earnings</span>
            </div>
            <div class="p-3 rounded-xl bg-blue-500/10 border border-blue-500/20 text-center">
                <svg class="w-6 h-6 text-blue-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                </svg>
                <span class="text-gray-300 text-xs">Referral</span>
            </div>
            <div class="p-3 rounded-xl bg-yellow-500/10 border border-yellow-500/20 text-center">
                <svg class="w-6 h-6 text-yellow-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
                </svg>
                <span class="text-gray-300 text-xs">Ranking</span>
            </div>
            <div class="p-3 rounded-xl bg-cyan-500/10 border border-cyan-500/20 text-center">
                <svg class="w-6 h-6 text-cyan-400 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                </svg>
                <span class="text-gray-300 text-xs">Analytics</span>
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
            <a href="{{ route('install.step', 4) }}" class="px-6 py-3 rounded-xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                ย้อนกลับ
            </a>

            <button type="button" onclick="saveSettings()" id="submitBtn" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition flex items-center gap-2">
                <span id="submitText">เสร็จสิ้นการติดตั้ง</span>
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<script>
function saveSettings() {
    const modal = document.getElementById('saveModal');
    const progress = document.getElementById('saveProgress');
    const status = document.getElementById('saveStatus');
    const errorBox = document.getElementById('errorBox');
    const errorText = document.getElementById('errorText');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('settingsForm');

    // Validate form
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
    const progressSteps = [
        { value: 20, text: 'กำลังบันทึกการตั้งค่า...' },
        { value: 50, text: 'กำลังสร้างไฟล์ระบบ...' },
        { value: 80, text: 'กำลังล้างแคช...' },
    ];

    let stepIndex = 0;
    const progressInterval = setInterval(() => {
        if (stepIndex < progressSteps.length) {
            progress.style.width = progressSteps[stepIndex].value + '%';
            status.textContent = progressSteps[stepIndex].text;
            stepIndex++;
        }
    }, 1000);

    // Send AJAX request
    const formData = new FormData(form);
    const controller = new AbortController();
    const timeoutId = setTimeout(() => controller.abort(), 120000); // 2 minutes timeout

    fetch('{{ route("install.process", 5) }}', {
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
            status.textContent = 'เสร็จสิ้น! กำลังไปหน้าสรุป...';
            setTimeout(() => {
                window.location.href = data.redirect || '{{ route("install.step", 6) }}';
            }, 1000);
        } else {
            modal.classList.add('hidden');
            errorBox.classList.remove('hidden');
            errorText.textContent = data.message || 'เกิดข้อผิดพลาดในการบันทึก';
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        clearTimeout(timeoutId);
        clearInterval(progressInterval);
        modal.classList.add('hidden');
        errorBox.classList.remove('hidden');

        if (error.name === 'AbortError') {
            errorText.textContent = 'หมดเวลาการบันทึก กรุณาลองใหม่อีกครั้ง';
        } else {
            errorText.textContent = error.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ กรุณาลองใหม่อีกครั้ง';
        }
        submitBtn.disabled = false;
        console.error('Save error:', error);
    });
}
</script>
@endsection
