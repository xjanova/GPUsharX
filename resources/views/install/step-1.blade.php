@extends('install.layout')

@section('title', 'Welcome')

@section('content')
<div class="text-center">
    <div class="w-24 h-24 mx-auto mb-6 rounded-2xl bg-gradient-to-br from-purple-600 to-pink-600 flex items-center justify-center">
        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
    </div>

    <h2 class="text-2xl font-bold text-white mb-4">ยินดีต้อนรับสู่ GPU Share Platform</h2>

    <p class="text-gray-400 mb-8 max-w-md mx-auto">
        ขอบคุณที่เลือกใช้ GPU Share Platform!
        ระบบนี้จะช่วยให้คุณสามารถแชร์พลังการประมวลผล GPU และสร้างรายได้ได้ง่ายๆ
    </p>

    <div class="grid md:grid-cols-3 gap-4 mb-8">
        <div class="p-4 rounded-xl bg-white/5">
            <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-purple-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                </svg>
            </div>
            <h3 class="text-white font-medium mb-1">GPU Sharing</h3>
            <p class="text-gray-400 text-sm">แชร์พลัง GPU ของคุณ</p>
        </div>

        <div class="p-4 rounded-xl bg-white/5">
            <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-green-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <h3 class="text-white font-medium mb-1">Earn Rewards</h3>
            <p class="text-gray-400 text-sm">รับรางวัลทุกวัน</p>
        </div>

        <div class="p-4 rounded-xl bg-white/5">
            <div class="w-10 h-10 mx-auto mb-3 rounded-lg bg-pink-500/20 flex items-center justify-center">
                <svg class="w-5 h-5 text-pink-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                </svg>
            </div>
            <h3 class="text-white font-medium mb-1">AI Generation</h3>
            <p class="text-gray-400 text-sm">สร้างภาพ AI สวยๆ</p>
        </div>
    </div>

    <div class="p-4 rounded-xl bg-yellow-500/10 border border-yellow-500/20 mb-8">
        <p class="text-yellow-400 text-sm">
            <strong>หมายเหตุ:</strong> กรุณาเตรียมข้อมูลการเชื่อมต่อ MySQL ก่อนเริ่มการติดตั้ง
        </p>
    </div>

    <form action="{{ route('install.process', 1) }}" method="POST">
        @csrf
        <button type="submit" class="w-full py-4 px-6 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-semibold text-lg hover:opacity-90 transition flex items-center justify-center gap-2">
            เริ่มการติดตั้ง
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
            </svg>
        </button>
    </form>
</div>
@endsection
