@extends('install.layout')

@section('title', 'Create Admin Account')

@section('content')
<div>
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">สร้างบัญชีผู้ดูแลระบบ</h2>
            <p class="text-gray-400 text-sm">กรอกข้อมูลสำหรับบัญชีแอดมิน</p>
        </div>
    </div>

    <!-- Creating Admin Modal -->
    <div id="createModal" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/80 backdrop-blur-sm">
        <div class="glass rounded-2xl p-8 max-w-md w-full mx-4 text-center">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-green-500/20 flex items-center justify-center">
                <svg class="w-10 h-10 text-green-400 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-2">กำลังสร้างบัญชี</h3>
            <p class="text-gray-400 mb-4">กำลังสร้างบัญชีผู้ดูแลระบบ...</p>
            <p class="text-gray-500 text-sm">กรุณารอสักครู่</p>
        </div>
    </div>

    <form id="adminForm">

        <div class="space-y-5">
            <!-- Name -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="name">
                    ชื่อผู้ดูแลระบบ <span class="text-red-400">*</span>
                </label>
                <input type="text" id="name" name="name" value="{{ old('name', 'Administrator') }}"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                    placeholder="Administrator" required>
            </div>

            <!-- Email -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="email">
                    อีเมล <span class="text-red-400">*</span>
                </label>
                <input type="email" id="email" name="email" value="{{ old('email') }}"
                    class="input-glass w-full px-4 py-3 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                    placeholder="admin@example.com" required>
            </div>

            <!-- Password -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="password">
                    รหัสผ่าน <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <input type="password" id="password" name="password"
                        class="input-glass w-full px-4 py-3 pr-12 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                        placeholder="••••••••" required minlength="8">
                    <button type="button" onclick="togglePassword('password')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                        <svg id="eyeIcon1" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-gray-500 text-xs mt-1">อย่างน้อย 8 ตัวอักษร</p>
            </div>

            <!-- Confirm Password -->
            <div>
                <label class="block text-gray-300 text-sm font-medium mb-2" for="password_confirmation">
                    ยืนยันรหัสผ่าน <span class="text-red-400">*</span>
                </label>
                <div class="relative">
                    <input type="password" id="password_confirmation" name="password_confirmation"
                        class="input-glass w-full px-4 py-3 pr-12 rounded-xl bg-white/5 border border-white/10 text-white placeholder-gray-500 focus:border-purple-500 focus:outline-none transition"
                        placeholder="••••••••" required minlength="8">
                    <button type="button" onclick="togglePassword('password_confirmation')" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                        <svg id="eyeIcon2" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- Password Strength -->
        <div class="mt-4">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm">ความแข็งแรงของรหัสผ่าน</span>
                <span id="strengthText" class="text-sm text-gray-500">-</span>
            </div>
            <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                <div id="strengthBar" class="h-full transition-all duration-300 rounded-full" style="width: 0%"></div>
            </div>
        </div>

        <!-- Security Tips -->
        <div class="mt-6 p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20">
            <div class="flex items-start gap-3">
                <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                </svg>
                <div class="text-sm text-yellow-300">
                    <p class="font-medium mb-1">เคล็ดลับความปลอดภัย:</p>
                    <ul class="text-yellow-400 space-y-0.5">
                        <li>• ใช้ตัวอักษรพิมพ์เล็ก พิมพ์ใหญ่ ตัวเลข และสัญลักษณ์</li>
                        <li>• หลีกเลี่ยงการใช้ข้อมูลส่วนตัว</li>
                        <li>• เก็บรหัสผ่านนี้ไว้ในที่ปลอดภัย</li>
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
            <a href="{{ route('install.step', 3) }}" class="px-6 py-3 rounded-xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
                </svg>
                ย้อนกลับ
            </a>

            <button type="button" onclick="createAdmin()" id="submitBtn" class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition flex items-center gap-2">
                สร้างบัญชี
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>
        </div>
    </form>
</div>

<script>
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const iconId = fieldId === 'password' ? 'eyeIcon1' : 'eyeIcon2';
    const icon = document.getElementById(iconId);

    if (input.type === 'password') {
        input.type = 'text';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
    } else {
        input.type = 'password';
        icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>';
    }
}

document.getElementById('password').addEventListener('input', function() {
    const password = this.value;
    const bar = document.getElementById('strengthBar');
    const text = document.getElementById('strengthText');

    let strength = 0;
    if (password.length >= 8) strength++;
    if (password.length >= 12) strength++;
    if (/[a-z]/.test(password) && /[A-Z]/.test(password)) strength++;
    if (/\d/.test(password)) strength++;
    if (/[^a-zA-Z0-9]/.test(password)) strength++;

    const percentage = (strength / 5) * 100;
    bar.style.width = percentage + '%';

    if (strength <= 1) {
        bar.className = 'h-full transition-all duration-300 rounded-full bg-red-500';
        text.textContent = 'อ่อนแอ';
        text.className = 'text-sm text-red-400';
    } else if (strength <= 2) {
        bar.className = 'h-full transition-all duration-300 rounded-full bg-orange-500';
        text.textContent = 'พอใช้';
        text.className = 'text-sm text-orange-400';
    } else if (strength <= 3) {
        bar.className = 'h-full transition-all duration-300 rounded-full bg-yellow-500';
        text.textContent = 'ปานกลาง';
        text.className = 'text-sm text-yellow-400';
    } else if (strength <= 4) {
        bar.className = 'h-full transition-all duration-300 rounded-full bg-green-500';
        text.textContent = 'แข็งแรง';
        text.className = 'text-sm text-green-400';
    } else {
        bar.className = 'h-full transition-all duration-300 rounded-full bg-emerald-500';
        text.textContent = 'แข็งแรงมาก';
        text.className = 'text-sm text-emerald-400';
    }
});

function createAdmin() {
    const modal = document.getElementById('createModal');
    const errorBox = document.getElementById('errorBox');
    const errorText = document.getElementById('errorText');
    const submitBtn = document.getElementById('submitBtn');
    const form = document.getElementById('adminForm');

    // Validate form
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }

    // Check password match
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('password_confirmation').value;
    if (password !== confirmPassword) {
        errorBox.classList.remove('hidden');
        errorText.textContent = 'รหัสผ่านไม่ตรงกัน';
        return;
    }

    // Hide error box
    errorBox.classList.add('hidden');

    // Disable button
    submitBtn.disabled = true;

    // Show modal
    modal.classList.remove('hidden');

    // Send AJAX request
    const formData = new FormData(form);

    fetch('{{ route("install.process", 4) }}', {
        method: 'POST',
        headers: { 'Accept': 'application/json' },
        body: formData
    })
    .then(response => {
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
        if (data.success) {
            window.location.href = data.redirect || '{{ route("install.step", 5) }}';
        } else {
            modal.classList.add('hidden');
            errorBox.classList.remove('hidden');
            errorText.textContent = data.message || 'เกิดข้อผิดพลาดในการสร้างบัญชี';
            submitBtn.disabled = false;
        }
    })
    .catch(error => {
        modal.classList.add('hidden');
        errorBox.classList.remove('hidden');
        errorText.textContent = error.message || 'เกิดข้อผิดพลาดในการเชื่อมต่อ';
        submitBtn.disabled = false;
        console.error('Create admin error:', error);
    });
}
</script>
@endsection
