@extends('layouts.app')

@section('title', 'ชำระเงินสำเร็จ')

@section('content')
<div class="min-h-screen py-16 flex items-center justify-center">
    <div class="max-w-lg mx-auto px-4 text-center">

        <!-- Success Animation -->
        <div class="relative w-32 h-32 mx-auto mb-8">
            <div class="absolute inset-0 bg-green-500/20 rounded-full animate-ping"></div>
            <div class="absolute inset-0 bg-gradient-to-br from-green-400 to-emerald-500 rounded-full flex items-center justify-center">
                <i class="fas fa-check text-white text-5xl"></i>
            </div>
        </div>

        <h1 class="text-3xl font-bold text-white mb-4">ชำระเงินสำเร็จ!</h1>

        <p class="text-gray-400 mb-8">
            ขอบคุณสำหรับการสนับสนุน เครดิตถูกเพิ่มเข้าบัญชีของคุณเรียบร้อยแล้ว
        </p>

        <!-- Credit Info -->
        <div class="glass-card rounded-2xl p-8 mb-8">
            <div class="flex items-center justify-center gap-4 mb-6">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                    <i class="fas fa-coins text-white text-3xl"></i>
                </div>
                <div class="text-left">
                    <p class="text-sm text-gray-400">เครดิตที่ได้รับ</p>
                    <p class="text-4xl font-bold text-white">+{{ number_format($credits) }}</p>
                </div>
            </div>

            @if($package)
            <div class="glass rounded-xl p-4">
                <div class="flex justify-between text-sm mb-2">
                    <span class="text-gray-400">แพ็คเกจ</span>
                    <span class="text-white font-medium">{{ $package['name'] }}</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">ราคา</span>
                    <span class="text-green-400 font-medium">฿{{ number_format($package['price_display']) }}</span>
                </div>
            </div>
            @endif
        </div>

        <!-- Current Balance -->
        <div class="glass rounded-xl p-4 mb-8">
            <p class="text-sm text-gray-400 mb-1">ยอดเครดิตปัจจุบัน</p>
            <p class="text-2xl font-bold text-purple-400">{{ number_format(auth()->user()->credits) }} Credits</p>
        </div>

        <!-- Actions -->
        <div class="flex flex-col sm:flex-row gap-4 justify-center">
            <a href="{{ route('generate') }}" class="px-8 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 font-medium transition flex items-center justify-center gap-2">
                <i class="fas fa-wand-magic-sparkles"></i>
                เริ่มสร้างผลงาน
            </a>
            <a href="{{ route('credits.buy') }}" class="px-8 py-3 rounded-xl glass hover:bg-white/10 font-medium transition flex items-center justify-center gap-2">
                <i class="fas fa-coins"></i>
                ซื้อเพิ่ม
            </a>
        </div>

        <!-- Receipt Link -->
        <p class="text-gray-500 text-sm mt-8">
            <i class="fas fa-receipt mr-1"></i>
            ใบเสร็จจะถูกส่งไปยังอีเมลของคุณ
        </p>
    </div>
</div>

<style>
@keyframes ping {
    75%, 100% {
        transform: scale(1.5);
        opacity: 0;
    }
}
</style>
@endsection
