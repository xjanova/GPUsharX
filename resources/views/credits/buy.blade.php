@extends('layouts.app')

@section('title', 'ซื้อเครดิต')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-6xl mx-auto px-4">

        <!-- Header -->
        <div class="text-center mb-12">
            <h1 class="text-3xl md:text-4xl font-bold mb-4">
                <i class="fas fa-coins text-yellow-400 mr-3"></i>
                <span class="gradient-text">เติมเครดิต</span>
            </h1>
            <p class="text-gray-400 max-w-2xl mx-auto">
                เลือกแพ็คเกจที่เหมาะกับคุณ เครดิตไม่มีวันหมดอายุ ใช้สร้างภาพและวีดีโอได้ไม่จำกัด
            </p>
        </div>

        <!-- Flash Messages -->
        @if(session('error'))
        <div class="max-w-md mx-auto mb-8 p-4 rounded-xl bg-red-500/10 border border-red-500/30">
            <div class="flex items-center gap-3">
                <i class="fas fa-exclamation-circle text-red-400"></i>
                <span class="text-red-400">{{ session('error') }}</span>
            </div>
        </div>
        @endif

        @if(session('info'))
        <div class="max-w-md mx-auto mb-8 p-4 rounded-xl bg-blue-500/10 border border-blue-500/30">
            <div class="flex items-center gap-3">
                <i class="fas fa-info-circle text-blue-400"></i>
                <span class="text-blue-400">{{ session('info') }}</span>
            </div>
        </div>
        @endif

        <!-- Current Balance -->
        <div class="glass-card rounded-2xl p-6 mb-8 max-w-md mx-auto">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 rounded-xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center">
                        <i class="fas fa-wallet text-white text-2xl"></i>
                    </div>
                    <div>
                        <p class="text-sm text-gray-400">ยอดเครดิตปัจจุบัน</p>
                        <p class="text-3xl font-bold text-white">{{ number_format(auth()->user()->credits ?? 0, 0) }}</p>
                    </div>
                </div>
                <a href="{{ route('credits.history') }}" class="text-purple-400 hover:text-purple-300 text-sm">
                    <i class="fas fa-history mr-1"></i>ประวัติ
                </a>
            </div>
        </div>

        <!-- Credit Packages -->
        @php $packages = config('stripe.packages'); @endphp
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-12">

            <!-- Starter Package -->
            <div class="glass-card rounded-2xl p-6 border border-gray-700 hover:border-purple-500/50 transition-all group">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-seedling text-3xl text-gray-300"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-1">{{ $packages['starter']['name'] }}</h3>
                    <p class="text-sm text-gray-500">{{ $packages['starter']['description'] }}</p>
                </div>

                <div class="text-center mb-6">
                    <div class="flex items-baseline justify-center gap-1">
                        <span class="text-4xl font-bold text-white">{{ $packages['starter']['credits'] }}</span>
                        <span class="text-gray-400">Credits</span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-green-400">฿{{ $packages['starter']['price_display'] }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">฿{{ number_format($packages['starter']['price_display'] / $packages['starter']['credits'], 2) }} / credit</p>
                </div>

                <ul class="space-y-2 mb-6 text-sm">
                    @foreach($packages['starter']['features'] as $feature)
                    <li class="flex items-center gap-2 text-gray-400">
                        <i class="fas fa-check text-green-400"></i>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                <button onclick="checkout('starter')" class="w-full py-3 rounded-xl bg-gray-700 hover:bg-gray-600 font-medium transition">
                    เลือกแพ็คเกจ
                </button>
            </div>

            <!-- Basic Package -->
            <div class="glass-card rounded-2xl p-6 border border-gray-700 hover:border-purple-500/50 transition-all group">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-bolt text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-1">{{ $packages['basic']['name'] }}</h3>
                    <p class="text-sm text-gray-500">{{ $packages['basic']['description'] }}</p>
                </div>

                <div class="text-center mb-6">
                    <div class="flex items-baseline justify-center gap-1">
                        <span class="text-4xl font-bold text-white">{{ $packages['basic']['credits'] }}</span>
                        <span class="text-gray-400">Credits</span>
                    </div>
                    <div class="mt-2">
                        <span class="text-2xl font-bold text-green-400">฿{{ $packages['basic']['price_display'] }}</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">฿{{ number_format($packages['basic']['price_display'] / $packages['basic']['credits'], 2) }} / credit</p>
                </div>

                <ul class="space-y-2 mb-6 text-sm">
                    @foreach($packages['basic']['features'] as $feature)
                    <li class="flex items-center gap-2 text-gray-400">
                        <i class="fas fa-check text-green-400"></i>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                <button onclick="checkout('basic')" class="w-full py-3 rounded-xl bg-blue-600 hover:bg-blue-500 font-medium transition">
                    เลือกแพ็คเกจ
                </button>
            </div>

            <!-- Pro Package (Popular) -->
            <div class="glass-card rounded-2xl p-6 border-2 border-purple-500 relative transition-all group scale-105">
                <div class="absolute -top-3 left-1/2 -translate-x-1/2">
                    <span class="px-4 py-1 rounded-full bg-gradient-to-r from-purple-600 to-pink-600 text-xs font-bold">
                        ยอดนิยม
                    </span>
                </div>

                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-rocket text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-1">{{ $packages['pro']['name'] }}</h3>
                    <p class="text-sm text-gray-500">{{ $packages['pro']['description'] }}</p>
                </div>

                <div class="text-center mb-6">
                    <div class="flex items-baseline justify-center gap-1">
                        <span class="text-4xl font-bold text-white">{{ $packages['pro']['credits'] }}</span>
                        <span class="text-gray-400">Credits</span>
                    </div>
                    <div class="mt-2 flex items-center justify-center gap-2">
                        @if(isset($packages['pro']['original_price']))
                        <span class="text-lg text-gray-500 line-through">฿{{ $packages['pro']['original_price'] }}</span>
                        @endif
                        <span class="text-2xl font-bold text-green-400">฿{{ $packages['pro']['price_display'] }}</span>
                    </div>
                    @if(isset($packages['pro']['discount']))
                    <p class="text-xs text-purple-400 mt-1">ประหยัด {{ $packages['pro']['discount'] }}%</p>
                    @endif
                </div>

                <ul class="space-y-2 mb-6 text-sm">
                    @foreach($packages['pro']['features'] as $feature)
                    <li class="flex items-center gap-2 {{ str_contains($feature, 'Priority') ? 'text-purple-400' : 'text-gray-400' }}">
                        <i class="fas {{ str_contains($feature, 'Priority') ? 'fa-star text-purple-400' : 'fa-check text-green-400' }}"></i>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                <button onclick="checkout('pro')" class="w-full py-3 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 font-medium transition glow-purple">
                    เลือกแพ็คเกจ
                </button>
            </div>

            <!-- Enterprise Package -->
            <div class="glass-card rounded-2xl p-6 border border-gray-700 hover:border-yellow-500/50 transition-all group">
                <div class="text-center mb-6">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-gradient-to-br from-yellow-500 to-orange-500 flex items-center justify-center group-hover:scale-110 transition-transform">
                        <i class="fas fa-crown text-3xl text-white"></i>
                    </div>
                    <h3 class="text-xl font-bold text-white mb-1">{{ $packages['enterprise']['name'] }}</h3>
                    <p class="text-sm text-gray-500">{{ $packages['enterprise']['description'] }}</p>
                </div>

                <div class="text-center mb-6">
                    <div class="flex items-baseline justify-center gap-1">
                        <span class="text-4xl font-bold text-white">{{ $packages['enterprise']['credits'] }}</span>
                        <span class="text-gray-400">Credits</span>
                    </div>
                    <div class="mt-2 flex items-center justify-center gap-2">
                        @if(isset($packages['enterprise']['original_price']))
                        <span class="text-lg text-gray-500 line-through">฿{{ number_format($packages['enterprise']['original_price']) }}</span>
                        @endif
                        <span class="text-2xl font-bold text-green-400">฿{{ $packages['enterprise']['price_display'] }}</span>
                    </div>
                    @if(isset($packages['enterprise']['discount']))
                    <p class="text-xs text-yellow-400 mt-1">ประหยัด {{ $packages['enterprise']['discount'] }}%</p>
                    @endif
                </div>

                <ul class="space-y-2 mb-6 text-sm">
                    @foreach($packages['enterprise']['features'] as $feature)
                    <li class="flex items-center gap-2 {{ str_contains($feature, 'VIP') ? 'text-yellow-400' : 'text-gray-400' }}">
                        <i class="fas {{ str_contains($feature, 'VIP') ? 'fa-crown text-yellow-400' : 'fa-check text-green-400' }}"></i>
                        {{ $feature }}
                    </li>
                    @endforeach
                </ul>

                <button onclick="checkout('enterprise')" class="w-full py-3 rounded-xl bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-500 hover:to-orange-500 font-medium transition">
                    เลือกแพ็คเกจ
                </button>
            </div>
        </div>

        <!-- Payment Methods Info -->
        <div class="glass-card rounded-2xl p-8 max-w-2xl mx-auto mb-8">
            <h2 class="text-xl font-bold mb-6 text-center">
                <i class="fas fa-shield-alt text-green-400 mr-2"></i>ชำระเงินปลอดภัยผ่าน Stripe
            </h2>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="glass rounded-xl p-4 text-center">
                    <i class="fab fa-cc-visa text-4xl text-blue-400 mb-2"></i>
                    <p class="text-sm text-gray-400">Visa</p>
                </div>
                <div class="glass rounded-xl p-4 text-center">
                    <i class="fab fa-cc-mastercard text-4xl text-orange-400 mb-2"></i>
                    <p class="text-sm text-gray-400">Mastercard</p>
                </div>
                <div class="glass rounded-xl p-4 text-center">
                    <i class="fas fa-qrcode text-4xl text-purple-400 mb-2"></i>
                    <p class="text-sm text-gray-400">PromptPay</p>
                </div>
                <div class="glass rounded-xl p-4 text-center">
                    <i class="fab fa-cc-jcb text-4xl text-green-400 mb-2"></i>
                    <p class="text-sm text-gray-400">JCB</p>
                </div>
            </div>

            <p class="text-center text-gray-500 text-sm mt-4">
                <i class="fas fa-lock mr-1"></i>
                การชำระเงินทั้งหมดถูกเข้ารหัสและประมวลผลอย่างปลอดภัยโดย Stripe
            </p>
        </div>

        <!-- FAQ Section -->
        <div class="max-w-2xl mx-auto">
            <h2 class="text-xl font-bold mb-6 text-center">
                <i class="fas fa-question-circle text-purple-400 mr-2"></i>คำถามที่พบบ่อย
            </h2>

            <div class="space-y-4">
                <div class="glass-card rounded-xl p-5">
                    <h3 class="font-medium text-white mb-2">เครดิตหมดอายุเมื่อไหร่?</h3>
                    <p class="text-gray-400 text-sm">เครดิตไม่มีวันหมดอายุ คุณสามารถใช้ได้ตลอดไป</p>
                </div>
                <div class="glass-card rounded-xl p-5">
                    <h3 class="font-medium text-white mb-2">1 เครดิตสร้างภาพได้กี่ภาพ?</h3>
                    <p class="text-gray-400 text-sm">1 เครดิต = 1 ภาพขนาด SD (512x512) หรือประมาณ 5 เครดิตสำหรับ HD (1024x1024)</p>
                </div>
                <div class="glass-card rounded-xl p-5">
                    <h3 class="font-medium text-white mb-2">สามารถขอคืนเงินได้หรือไม่?</h3>
                    <p class="text-gray-400 text-sm">เครดิตที่ซื้อแล้วไม่สามารถขอคืนเงินได้ แต่หากเกิดปัญหาในการใช้งาน ติดต่อทีมงานได้ตลอด</p>
                </div>
                <div class="glass-card rounded-xl p-5">
                    <h3 class="font-medium text-white mb-2">มีโปรโมชั่นพิเศษหรือไม่?</h3>
                    <p class="text-gray-400 text-sm">ติดตามโปรโมชั่นและส่วนลดพิเศษได้ทาง Discord และ Facebook ของเรา</p>
                </div>
            </div>
        </div>

        <!-- Support -->
        <div class="text-center mt-12">
            <p class="text-gray-500 text-sm">
                มีปัญหาในการชำระเงิน?
                <a href="#" class="text-purple-400 hover:underline">ติดต่อฝ่ายสนับสนุน</a>
            </p>
        </div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 border-4 border-purple-500/30 border-t-purple-500 rounded-full animate-spin"></div>
        <p class="text-white font-medium">กำลังเปลี่ยนเส้นทางไปหน้าชำระเงิน...</p>
        <p class="text-gray-400 text-sm mt-2">กรุณารอสักครู่</p>
    </div>
</div>

@push('scripts')
<script>
    function checkout(packageName) {
        // Show loading
        const overlay = document.getElementById('loadingOverlay');
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');

        // Create checkout session
        fetch('{{ route("payment.checkout") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ package: packageName }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.url) {
                // Redirect to Stripe Checkout
                window.location.href = data.url;
            } else if (data.error) {
                alert(data.error);
                overlay.classList.add('hidden');
                overlay.classList.remove('flex');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง');
            overlay.classList.add('hidden');
            overlay.classList.remove('flex');
        });
    }
</script>
@endpush
@endsection
