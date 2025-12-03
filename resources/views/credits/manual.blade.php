@extends('layouts.app')

@section('title', 'ชำระเงิน - ' . $packageData['name'])

@section('content')
<div class="min-h-screen bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 py-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-white mb-2">ชำระเงิน</h1>
            <p class="text-gray-400">กรุณาชำระเงินตามช่องทางด้านล่าง</p>
        </div>

        <!-- Order Summary -->
        <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 p-6 mb-8">
            <h2 class="text-xl font-semibold text-white mb-4">สรุปคำสั่งซื้อ</h2>
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-lg text-white font-medium">{{ $packageData['name'] }}</p>
                    <p class="text-gray-400">{{ number_format($packageData['credits']) }} เครดิต</p>
                </div>
                <div class="text-right">
                    <p class="text-2xl font-bold text-green-400">฿{{ number_format($packageData['price'] / 100, 2) }}</p>
                </div>
            </div>
        </div>

        <div class="grid md:grid-cols-2 gap-8">
            <!-- PromptPay Section -->
            @if($promptpayEnabled && $promptpayId)
            <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-blue-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">PromptPay QR</h3>
                        <p class="text-sm text-gray-400">สแกนจ่ายผ่าน Mobile Banking</p>
                    </div>
                </div>

                <!-- QR Code -->
                <div class="bg-white rounded-xl p-4 mb-4 flex items-center justify-center">
                    <div id="promptpay-qr" class="w-48 h-48 flex items-center justify-center">
                        <img src="https://promptpay.io/{{ $promptpayId }}/{{ $packageData['price'] / 100 }}.png"
                             alt="PromptPay QR Code"
                             class="w-full h-full object-contain"
                             onerror="this.parentElement.innerHTML='<div class=\'text-gray-500 text-center\'><svg class=\'w-12 h-12 mx-auto mb-2\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z\'/></svg>ไม่สามารถโหลด QR ได้</div>'">
                    </div>
                </div>

                <div class="text-center space-y-2 mb-4">
                    <p class="text-gray-400 text-sm">PromptPay ID</p>
                    <p class="text-white font-mono text-lg">{{ $promptpayId }}</p>
                    @if($promptpayName)
                    <p class="text-gray-400">{{ $promptpayName }}</p>
                    @endif
                </div>

                <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-lg p-3">
                    <p class="text-yellow-400 text-sm text-center">
                        <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        กรุณาโอนตามยอดที่แสดง เพื่อความสะดวกในการตรวจสอบ
                    </p>
                </div>
            </div>
            @endif

            <!-- Bank Transfer Section -->
            @if($bankEnabled && count($bankAccounts) > 0)
            <div class="bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 p-6">
                <div class="flex items-center gap-3 mb-6">
                    <div class="w-12 h-12 bg-green-500/10 rounded-xl flex items-center justify-center">
                        <svg class="w-7 h-7 text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-white">โอนผ่านธนาคาร</h3>
                        <p class="text-sm text-gray-400">โอนเงินไปยังบัญชีด้านล่าง</p>
                    </div>
                </div>

                <div class="space-y-4">
                    @foreach($bankAccounts as $account)
                    <div class="bg-gray-700/30 rounded-xl p-4 border border-gray-600/30">
                        <div class="flex items-center gap-3 mb-3">
                            @php
                                $bankLogos = [
                                    'kbank' => 'https://www.kasikornbank.com/SiteCollectionDocuments/about/img/logo/logo.png',
                                    'scb' => 'https://www.scb.co.th/content/dam/scb/about-scb/brand-logo/scb-logo.png',
                                    'ktb' => 'https://krungthai.com/content/dam/ktb/favicon.svg',
                                    'bbl' => 'https://www.bangkokbank.com/-/media/feature/globalnavigation/bbl-logo.png',
                                    'ttb' => 'https://www.ttbbank.com/images/logo.png',
                                ];
                                $bankColors = [
                                    'kbank' => 'bg-green-500',
                                    'scb' => 'bg-purple-500',
                                    'ktb' => 'bg-blue-500',
                                    'bbl' => 'bg-blue-600',
                                    'ttb' => 'bg-orange-500',
                                ];
                            @endphp
                            <div class="w-10 h-10 {{ $bankColors[$account['bank'] ?? ''] ?? 'bg-gray-500' }} rounded-lg flex items-center justify-center text-white font-bold">
                                {{ strtoupper(substr($account['bank'] ?? 'BK', 0, 2)) }}
                            </div>
                            <div>
                                <p class="text-white font-medium">{{ $account['bank_name'] ?? ucfirst($account['bank'] ?? 'ธนาคาร') }}</p>
                            </div>
                        </div>
                        <div class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-400">เลขบัญชี</span>
                                <span class="text-white font-mono">{{ $account['account_number'] ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-400">ชื่อบัญชี</span>
                                <span class="text-white">{{ $account['account_name'] ?? '-' }}</span>
                            </div>
                        </div>
                        <button onclick="copyToClipboard('{{ $account['account_number'] ?? '' }}')"
                                class="mt-3 w-full py-2 px-4 bg-gray-600/50 hover:bg-gray-600 text-white text-sm rounded-lg transition-colors flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3"/>
                            </svg>
                            คัดลอกเลขบัญชี
                        </button>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        <!-- Payment Confirmation Form -->
        <div class="mt-8 bg-gray-800/50 backdrop-blur rounded-2xl border border-gray-700/50 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">แจ้งชำระเงิน</h3>
            <form action="{{ route('payment.confirm') }}" method="POST" enctype="multipart/form-data" id="payment-confirm-form">
                @csrf
                <input type="hidden" name="package" value="{{ $package }}">

                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">วันที่โอน</label>
                        <input type="date" name="transfer_date" value="{{ date('Y-m-d') }}" required
                               class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-300 mb-2">เวลาที่โอน</label>
                        <input type="time" name="transfer_time" value="{{ date('H:i') }}" required
                               class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">ยอดที่โอน (บาท)</label>
                    <input type="number" name="amount" value="{{ $packageData['price'] / 100 }}" step="0.01" required readonly
                           class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">แนบสลิปการโอน</label>
                    <div class="relative">
                        <input type="file" name="slip" accept="image/*" required id="slip-input"
                               class="hidden">
                        <label for="slip-input"
                               class="flex flex-col items-center justify-center w-full h-32 border-2 border-dashed border-gray-600 rounded-lg cursor-pointer hover:border-blue-500 transition-colors">
                            <div id="slip-preview" class="hidden">
                                <img src="" alt="Preview" class="max-h-28 rounded">
                            </div>
                            <div id="slip-placeholder" class="text-center">
                                <svg class="w-8 h-8 mx-auto text-gray-500 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <p class="text-gray-400 text-sm">คลิกเพื่ออัพโหลดสลิป</p>
                            </div>
                        </label>
                    </div>
                </div>

                <div class="mt-4">
                    <label class="block text-sm font-medium text-gray-300 mb-2">หมายเหตุ (ถ้ามี)</label>
                    <textarea name="note" rows="2"
                              class="w-full bg-gray-700/50 border border-gray-600 rounded-lg px-4 py-3 text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="ข้อมูลเพิ่มเติม..."></textarea>
                </div>

                <button type="submit"
                        class="mt-6 w-full py-4 bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-500 hover:to-purple-500 text-white font-semibold rounded-xl transition-all transform hover:scale-[1.02] flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    แจ้งชำระเงิน
                </button>
            </form>
        </div>

        <!-- Notice -->
        <div class="mt-6 bg-blue-500/10 border border-blue-500/30 rounded-xl p-4">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <div class="text-sm text-gray-300">
                    <p class="font-medium text-blue-400 mb-1">หมายเหตุ</p>
                    <ul class="list-disc list-inside space-y-1 text-gray-400">
                        <li>เครดิตจะถูกเพิ่มภายใน 15 นาที หลังตรวจสอบการชำระเงิน</li>
                        <li>ในช่วงเวลา 09:00 - 22:00 น. การตรวจสอบจะเร็วขึ้น</li>
                        <li>หากมีปัญหา กรุณาติดต่อฝ่ายสนับสนุน</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Back Button -->
        <div class="mt-6 text-center">
            <a href="{{ route('credits.buy') }}" class="text-gray-400 hover:text-white transition-colors">
                <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                กลับไปหน้าซื้อเครดิต
            </a>
        </div>
    </div>
</div>

@push('scripts')
<script>
// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        // Show toast notification
        const toast = document.createElement('div');
        toast.className = 'fixed bottom-4 right-4 bg-green-500 text-white px-4 py-2 rounded-lg shadow-lg z-50 animate-fade-in';
        toast.textContent = 'คัดลอกแล้ว!';
        document.body.appendChild(toast);
        setTimeout(() => toast.remove(), 2000);
    });
}

// Slip preview
document.getElementById('slip-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('slip-preview');
            const placeholder = document.getElementById('slip-placeholder');
            preview.querySelector('img').src = e.target.result;
            preview.classList.remove('hidden');
            placeholder.classList.add('hidden');
        }
        reader.readAsDataURL(file);
    }
});
</script>
@endpush

<style>
@keyframes fade-in {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
.animate-fade-in {
    animation: fade-in 0.3s ease-out;
}
</style>
@endsection
