@extends('install.layout')

@section('title', 'System Requirements')

@section('content')
<div>
    <div class="flex items-center gap-3 mb-6">
        <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center">
            <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <h2 class="text-xl font-bold text-white">ตรวจสอบข้อกำหนดระบบ</h2>
            <p class="text-gray-400 text-sm">ตรวจสอบว่าเซิร์ฟเวอร์พร้อมสำหรับการติดตั้ง</p>
        </div>
    </div>

    @php
        $allPassed = !in_array(false, array_column($requirements, 'passed'));
    @endphp

    <div class="space-y-3 mb-8">
        @foreach($requirements as $req)
        <div class="flex items-center justify-between p-4 rounded-xl {{ $req['passed'] ? 'bg-green-500/10 border border-green-500/20' : 'bg-red-500/10 border border-red-500/20' }}">
            <div class="flex items-center gap-3">
                @if($req['passed'])
                <div class="w-8 h-8 rounded-lg bg-green-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                    </svg>
                </div>
                @else
                <div class="w-8 h-8 rounded-lg bg-red-500 flex items-center justify-center">
                    <svg class="w-5 h-5 text-white" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                    </svg>
                </div>
                @endif
                <span class="text-white font-medium">{{ $req['name'] }}</span>
            </div>
            <div class="text-right">
                <span class="text-sm {{ $req['passed'] ? 'text-green-400' : 'text-red-400' }}">{{ $req['current'] }}</span>
                <span class="text-gray-500 text-sm ml-2">({{ $req['required'] }})</span>
            </div>
        </div>
        @endforeach
    </div>

    @if($allPassed)
    <div class="p-4 rounded-xl bg-green-500/10 border border-green-500/20 mb-6">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
            </svg>
            <span class="text-green-400 font-medium">ข้อกำหนดทั้งหมดผ่านแล้ว! พร้อมดำเนินการต่อ</span>
        </div>
    </div>
    @else
    <div class="p-4 rounded-xl bg-red-500/10 border border-red-500/20 mb-6">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span class="text-red-400 font-medium">กรุณาแก้ไขข้อกำหนดที่ยังไม่ผ่านก่อนดำเนินการต่อ</span>
        </div>
    </div>
    @endif

    <div class="flex items-center justify-between gap-4">
        <a href="{{ route('install.step', 1) }}" class="px-6 py-3 rounded-xl bg-white/5 border border-white/10 text-white font-medium hover:bg-white/10 transition flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 17l-5-5m0 0l5-5m-5 5h12"/>
            </svg>
            ย้อนกลับ
        </a>

        <form action="{{ route('install.process', 2) }}" method="POST">
            @csrf
            <button type="submit" {{ !$allPassed ? 'disabled' : '' }} class="px-6 py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 text-white font-medium hover:opacity-90 transition flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed">
                ดำเนินการต่อ
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                </svg>
            </button>
        </form>
    </div>
</div>
@endsection
