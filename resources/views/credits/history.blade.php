@extends('layouts.app')

@section('title', 'ประวัติเครดิต')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-4xl mx-auto px-4">

        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <h1 class="text-2xl font-bold text-white mb-2">
                    <i class="fas fa-history text-purple-400 mr-2"></i>ประวัติเครดิต
                </h1>
                <p class="text-gray-400">ดูประวัติการเติมและใช้เครดิตทั้งหมด</p>
            </div>
            <a href="{{ route('credits.buy') }}" class="px-4 py-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 font-medium transition">
                <i class="fas fa-plus mr-2"></i>เติมเครดิต
            </a>
        </div>

        <!-- Current Balance Card -->
        <div class="glass-card rounded-2xl p-6 mb-8">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                        <i class="fas fa-wallet text-white text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">ยอดเครดิตปัจจุบัน</p>
                        <p class="text-3xl font-bold text-white">{{ number_format(auth()->user()->credits, 0) }}</p>
                    </div>
                </div>
                <span class="text-gray-500">Credits</span>
            </div>
        </div>

        <!-- Transactions List -->
        <div class="glass-card rounded-2xl overflow-hidden">
            @if($transactions->count() > 0)
            <div class="divide-y divide-gray-800">
                @foreach($transactions as $transaction)
                <div class="p-4 hover:bg-white/5 transition">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-4">
                            <div class="w-10 h-10 rounded-lg flex items-center justify-center
                                {{ $transaction->amount > 0 ? 'bg-green-500/20' : 'bg-red-500/20' }}">
                                <i class="fas {{ $transaction->amount > 0 ? 'fa-plus text-green-400' : 'fa-minus text-red-400' }}"></i>
                            </div>
                            <div>
                                <p class="font-medium text-white">{{ $transaction->description }}</p>
                                <p class="text-sm text-gray-500">{{ $transaction->created_at->format('d/m/Y H:i') }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-bold {{ $transaction->amount > 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $transaction->amount > 0 ? '+' : '' }}{{ number_format($transaction->amount) }}
                            </p>
                            <p class="text-xs text-gray-500">
                                คงเหลือ {{ number_format($transaction->balance_after) }}
                            </p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="p-4 border-t border-gray-800">
                {{ $transactions->links() }}
            </div>
            @else
            <div class="p-12 text-center">
                <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gray-800 flex items-center justify-center">
                    <i class="fas fa-receipt text-gray-600 text-3xl"></i>
                </div>
                <p class="text-gray-400 mb-4">ยังไม่มีประวัติการทำรายการ</p>
                <a href="{{ route('credits.buy') }}" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 font-medium transition">
                    <i class="fas fa-coins"></i>
                    เติมเครดิตเลย
                </a>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
