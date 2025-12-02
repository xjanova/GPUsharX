@extends('install.layout')

@section('title', 'Installation Complete')

@section('content')
<div class="text-center">
    <!-- Success Animation -->
    <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gradient-to-br from-green-500 to-emerald-500 flex items-center justify-center animate-bounce">
        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
        </svg>
    </div>

    <h2 class="text-3xl font-bold text-white mb-4">ติดตั้งสำเร็จแล้ว!</h2>

    <p class="text-gray-400 mb-8 max-w-md mx-auto">
        GPU Share Platform พร้อมใช้งานแล้ว ขอบคุณที่เลือกใช้บริการของเรา!
    </p>

    <!-- Quick Links -->
    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <a href="{{ url('/') }}" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition group">
            <div class="w-12 h-12 mx-auto mb-3 rounded-lg bg-purple-500/20 flex items-center justify-center group-hover:bg-purple-500/30 transition">
                <svg class="w-6 h-6 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
            </div>
            <h3 class="text-white font-medium mb-1">หน้าแรก</h3>
            <p class="text-gray-400 text-sm">เยี่ยมชมเว็บไซต์</p>
        </a>

        <a href="{{ url('/admin') }}" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition group">
            <div class="w-12 h-12 mx-auto mb-3 rounded-lg bg-pink-500/20 flex items-center justify-center group-hover:bg-pink-500/30 transition">
                <svg class="w-6 h-6 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
            <h3 class="text-white font-medium mb-1">Admin Panel</h3>
            <p class="text-gray-400 text-sm">จัดการระบบ</p>
        </a>

        <a href="{{ url('/generate') }}" class="p-4 rounded-xl bg-white/5 border border-white/10 hover:bg-white/10 transition group">
            <div class="w-12 h-12 mx-auto mb-3 rounded-lg bg-green-500/20 flex items-center justify-center group-hover:bg-green-500/30 transition">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h3 class="text-white font-medium mb-1">AI Generate</h3>
            <p class="text-gray-400 text-sm">สร้างภาพ AI</p>
        </a>
    </div>

    <!-- Next Steps -->
    <div class="text-left p-6 rounded-xl bg-white/5 border border-white/10 mb-8">
        <h3 class="text-white font-semibold mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
            </svg>
            ขั้นตอนถัดไป
        </h3>

        <ol class="space-y-3 text-gray-300">
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400 text-sm flex-shrink-0">1</span>
                <div>
                    <p class="font-medium text-white">ตั้งค่า SSL Certificate</p>
                    <p class="text-gray-400 text-sm">แนะนำให้ใช้ HTTPS สำหรับความปลอดภัย</p>
                </div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400 text-sm flex-shrink-0">2</span>
                <div>
                    <p class="font-medium text-white">ติดตั้ง Windows Client</p>
                    <p class="text-gray-400 text-sm">ดาวน์โหลดและติดตั้งโปรแกรมบนเครื่องที่มี GPU</p>
                </div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400 text-sm flex-shrink-0">3</span>
                <div>
                    <p class="font-medium text-white">ตั้งค่า AI Models</p>
                    <p class="text-gray-400 text-sm">เลือกและติดตั้งโมเดล AI ที่ต้องการใช้งาน</p>
                </div>
            </li>
            <li class="flex items-start gap-3">
                <span class="w-6 h-6 rounded-full bg-purple-500/20 flex items-center justify-center text-purple-400 text-sm flex-shrink-0">4</span>
                <div>
                    <p class="font-medium text-white">เชิญสมาชิก</p>
                    <p class="text-gray-400 text-sm">แชร์ลิงก์เชิญเพื่อสร้างเครือข่าย</p>
                </div>
            </li>
        </ol>
    </div>

    <!-- Important Info -->
    <div class="p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20 text-left mb-8">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-yellow-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <div class="text-sm text-yellow-300">
                <p class="font-medium mb-1">สำคัญ:</p>
                <p class="text-yellow-400">แนะนำให้ลบโฟลเดอร์ <code class="bg-black/30 px-1 rounded">/install</code> หลังการติดตั้งเสร็จสิ้นเพื่อความปลอดภัย</p>
            </div>
        </div>
    </div>

    <!-- CTA Button -->
    <a href="{{ url('/') }}" class="inline-flex items-center gap-2 px-8 py-4 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold text-lg hover:opacity-90 transition shadow-lg shadow-purple-500/30">
        เข้าสู่เว็บไซต์
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
        </svg>
    </a>

    <!-- Copyright -->
    <div class="mt-8 pt-6 border-t border-white/10">
        <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} Xman Studio Thailand. All rights reserved.</p>
    </div>
</div>

<style>
@keyframes bounce {
    0%, 100% {
        transform: translateY(-5%);
        animation-timing-function: cubic-bezier(0.8, 0, 1, 1);
    }
    50% {
        transform: translateY(0);
        animation-timing-function: cubic-bezier(0, 0, 0.2, 1);
    }
}

.animate-bounce {
    animation: bounce 1s infinite;
}
</style>
@endsection
