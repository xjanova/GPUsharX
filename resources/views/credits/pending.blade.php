@extends('layouts.app')

@section('title', 'รายการรอตรวจสอบ')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-3xl font-bold text-white mb-2">รายการรอตรวจสอบ</h1>
                <p class="text-gray-400">รายการชำระเงินที่รอการตรวจสอบจากทีมงาน</p>
            </div>
            <a href="{{ route('credits.buy') }}"
               class="px-4 py-2 bg-blue-600 hover:bg-blue-500 text-white rounded-lg transition-colors">
                ซื้อเครดิตเพิ่ม
            </a>
        </div>

        @if(session('success'))
        <div class="mb-6 p-4 bg-green-500/10 border border-green-500/30 rounded-xl">
            <div class="flex items-center gap-3">
                <svg class="w-6 h-6 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-green-400">{{ session('success') }}</p>
            </div>
        </div>
        @endif

        @if($payments->isEmpty())
        <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 p-12 text-center">
            <svg class="w-16 h-16 mx-auto text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
            </svg>
            <p class="text-gray-400 text-lg">ไม่มีรายการรอตรวจสอบ</p>
            <a href="{{ route('credits.buy') }}" class="inline-block mt-4 text-blue-400 hover:text-blue-300">
                ไปหน้าซื้อเครดิต
            </a>
        </div>
        @else
        <div class="space-y-4">
            @foreach($payments as $payment)
            <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 overflow-hidden">
                <div class="p-6">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <div class="flex items-center gap-3 mb-3">
                                <h3 class="text-lg font-semibold text-white">
                                    {{ config("stripe.packages.{$payment->package}.name", $payment->package) }}
                                </h3>
                                @if($payment->isPending())
                                <span class="px-3 py-1 bg-yellow-500/10 text-yellow-400 text-sm rounded-full">
                                    รอตรวจสอบ
                                </span>
                                @elseif($payment->isApproved())
                                <span class="px-3 py-1 bg-green-500/10 text-green-400 text-sm rounded-full">
                                    อนุมัติแล้ว
                                </span>
                                @else
                                <span class="px-3 py-1 bg-red-500/10 text-red-400 text-sm rounded-full">
                                    ถูกปฏิเสธ
                                </span>
                                @endif
                            </div>

                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                <div>
                                    <p class="text-gray-400">ยอดโอน</p>
                                    <p class="text-white font-medium">฿{{ number_format($payment->amount, 2) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">เครดิต</p>
                                    <p class="text-white font-medium">{{ number_format($payment->credits) }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">วันที่โอน</p>
                                    <p class="text-white font-medium">{{ $payment->transfer_date->format('d/m/Y') }}</p>
                                </div>
                                <div>
                                    <p class="text-gray-400">เวลาที่โอน</p>
                                    <p class="text-white font-medium">{{ $payment->transfer_time }}</p>
                                </div>
                            </div>

                            @if($payment->note)
                            <div class="mt-3 p-3 bg-gray-700/30 rounded-lg">
                                <p class="text-sm text-gray-400">หมายเหตุ: {{ $payment->note }}</p>
                            </div>
                            @endif

                            @if($payment->isRejected() && $payment->reject_reason)
                            <div class="mt-3 p-3 bg-red-500/10 border border-red-500/30 rounded-lg">
                                <p class="text-sm text-red-400">
                                    <span class="font-medium">เหตุผลที่ปฏิเสธ:</span> {{ $payment->reject_reason }}
                                </p>
                            </div>
                            @endif

                            <div class="mt-3 text-xs text-gray-500">
                                แจ้งชำระเมื่อ {{ $payment->created_at->format('d/m/Y H:i') }}
                                @if($payment->processed_at)
                                    | ตรวจสอบเมื่อ {{ $payment->processed_at->format('d/m/Y H:i') }}
                                @endif
                            </div>
                        </div>

                        <!-- Slip Preview -->
                        <div class="ml-4 flex-shrink-0">
                            <a href="{{ $payment->slip_url }}" target="_blank" class="block">
                                <img src="{{ $payment->slip_url }}"
                                     alt="Slip"
                                     class="w-20 h-20 object-cover rounded-lg border border-gray-600 hover:border-blue-500 transition-colors">
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-6">
            {{ $payments->links() }}
        </div>
        @endif

        <!-- Info -->
        <div class="mt-8 bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-gray-300">
                    <p class="font-medium text-blue-400 mb-1">การตรวจสอบ</p>
                    <ul class="list-disc list-inside space-y-1 text-gray-400">
                        <li>รายการจะถูกตรวจสอบภายใน 15-30 นาที ในช่วงเวลาทำการ (09:00 - 22:00)</li>
                        <li>เครดิตจะถูกเพิ่มอัตโนมัติหลังการอนุมัติ</li>
                        <li>หากมีปัญหา กรุณาติดต่อฝ่ายสนับสนุน</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
