@extends('layouts.admin')

@section('title', 'Payment Settings')
@section('header', 'Payment Gateway Settings')

@section('content')
<div class="max-w-4xl space-y-6">

    @if(session('success'))
    <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-400"></i>
        <span class="text-green-400">{{ session('success') }}</span>
    </div>
    @endif

    @if(session('error'))
    <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-exclamation-circle text-red-400"></i>
        <span class="text-red-400">{{ session('error') }}</span>
    </div>
    @endif

    <form action="{{ route('admin.payment-settings.update') }}" method="POST">
        @csrf
        @method('PUT')

        <!-- Active Payment Gateway -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-credit-card text-green-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">เลือก Payment Gateway</h3>
                    <p class="text-sm text-gray-400">เลือกช่องทางการชำระเงินหลักที่ต้องการใช้</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                @php $activeGateway = \App\Models\SiteSetting::get('payment_gateway', 'stripe'); @endphp

                <!-- Stripe -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="payment_gateway" value="stripe" class="peer sr-only" {{ $activeGateway === 'stripe' ? 'checked' : '' }}>
                    <div class="p-4 rounded-xl border-2 border-gray-600 peer-checked:border-purple-500 peer-checked:bg-purple-500/10 hover:bg-gray-700/50 transition">
                        <div class="flex items-center justify-between mb-3">
                            <i class="fab fa-stripe text-3xl text-purple-400"></i>
                            <div class="w-4 h-4 rounded-full border-2 border-gray-500 peer-checked:border-purple-500 peer-checked:bg-purple-500"></div>
                        </div>
                        <p class="font-medium">Stripe</p>
                        <p class="text-xs text-gray-500">International</p>
                    </div>
                    <div class="absolute top-2 right-2 w-4 h-4 rounded-full bg-purple-500 hidden peer-checked:block">
                        <i class="fas fa-check text-white text-xs absolute top-0.5 left-0.5"></i>
                    </div>
                </label>

                <!-- Omise -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="payment_gateway" value="omise" class="peer sr-only" {{ $activeGateway === 'omise' ? 'checked' : '' }}>
                    <div class="p-4 rounded-xl border-2 border-gray-600 peer-checked:border-blue-500 peer-checked:bg-blue-500/10 hover:bg-gray-700/50 transition">
                        <div class="flex items-center justify-between mb-3">
                            <img src="https://cdn.omise.co/assets/omise-logo.png" alt="Omise" class="h-8 w-auto">
                            <div class="w-4 h-4 rounded-full border-2 border-gray-500"></div>
                        </div>
                        <p class="font-medium">Omise</p>
                        <p class="text-xs text-gray-500">Thailand</p>
                    </div>
                    <div class="absolute top-2 right-2 w-4 h-4 rounded-full bg-blue-500 hidden peer-checked:block">
                        <i class="fas fa-check text-white text-xs absolute top-0.5 left-0.5"></i>
                    </div>
                </label>

                <!-- GB Prime Pay -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="payment_gateway" value="gbprimepay" class="peer sr-only" {{ $activeGateway === 'gbprimepay' ? 'checked' : '' }}>
                    <div class="p-4 rounded-xl border-2 border-gray-600 peer-checked:border-orange-500 peer-checked:bg-orange-500/10 hover:bg-gray-700/50 transition">
                        <div class="flex items-center justify-between mb-3">
                            <span class="text-2xl font-bold text-orange-400">GB</span>
                            <div class="w-4 h-4 rounded-full border-2 border-gray-500"></div>
                        </div>
                        <p class="font-medium">GB Prime Pay</p>
                        <p class="text-xs text-gray-500">Thailand</p>
                    </div>
                    <div class="absolute top-2 right-2 w-4 h-4 rounded-full bg-orange-500 hidden peer-checked:block">
                        <i class="fas fa-check text-white text-xs absolute top-0.5 left-0.5"></i>
                    </div>
                </label>

                <!-- Manual -->
                <label class="relative cursor-pointer">
                    <input type="radio" name="payment_gateway" value="manual" class="peer sr-only" {{ $activeGateway === 'manual' ? 'checked' : '' }}>
                    <div class="p-4 rounded-xl border-2 border-gray-600 peer-checked:border-yellow-500 peer-checked:bg-yellow-500/10 hover:bg-gray-700/50 transition">
                        <div class="flex items-center justify-between mb-3">
                            <i class="fas fa-hand-holding-usd text-3xl text-yellow-400"></i>
                            <div class="w-4 h-4 rounded-full border-2 border-gray-500"></div>
                        </div>
                        <p class="font-medium">Manual</p>
                        <p class="text-xs text-gray-500">PromptPay/Bank</p>
                    </div>
                    <div class="absolute top-2 right-2 w-4 h-4 rounded-full bg-yellow-500 hidden peer-checked:block">
                        <i class="fas fa-check text-white text-xs absolute top-0.5 left-0.5"></i>
                    </div>
                </label>
            </div>
        </div>

        <!-- Stripe Settings -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6" id="stripeSettings">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <i class="fab fa-stripe text-purple-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Stripe Configuration</h3>
                    <p class="text-sm text-gray-400">ตั้งค่า API Keys จาก <a href="https://dashboard.stripe.com/apikeys" target="_blank" class="text-purple-400 hover:underline">Stripe Dashboard</a></p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-key mr-1"></i>Publishable Key
                    </label>
                    <input type="text" name="stripe_key"
                        value="{{ \App\Models\SiteSetting::get('stripe_key') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono text-sm"
                        placeholder="pk_test_...">
                    <p class="text-xs text-gray-500 mt-1">เริ่มต้นด้วย pk_test_ (ทดสอบ) หรือ pk_live_ (ใช้งานจริง)</p>
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-lock mr-1"></i>Secret Key
                    </label>
                    <div class="relative">
                        <input type="password" name="stripe_secret" id="stripeSecret"
                            value="{{ \App\Models\SiteSetting::get('stripe_secret') }}"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono text-sm pr-12"
                            placeholder="sk_test_...">
                        <button type="button" onclick="togglePassword('stripeSecret')" class="absolute right-3 top-3 text-gray-400 hover:text-white">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-webhook mr-1"></i>Webhook Secret <span class="text-gray-600">(Optional)</span>
                    </label>
                    <input type="text" name="stripe_webhook_secret"
                        value="{{ \App\Models\SiteSetting::get('stripe_webhook_secret') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono text-sm"
                        placeholder="whsec_...">
                    <p class="text-xs text-gray-500 mt-1">Webhook URL: <code class="bg-gray-700 px-2 py-1 rounded">{{ url('/webhook/stripe') }}</code></p>
                </div>
            </div>
        </div>

        <!-- Omise Settings -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6" id="omiseSettings">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <span class="text-blue-400 font-bold">O</span>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Omise Configuration</h3>
                    <p class="text-sm text-gray-400">ตั้งค่า API Keys จาก <a href="https://dashboard.omise.co/settings/keys" target="_blank" class="text-blue-400 hover:underline">Omise Dashboard</a></p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-key mr-1"></i>Public Key
                    </label>
                    <input type="text" name="omise_public_key"
                        value="{{ \App\Models\SiteSetting::get('omise_public_key') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono text-sm"
                        placeholder="pkey_test_...">
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-lock mr-1"></i>Secret Key
                    </label>
                    <div class="relative">
                        <input type="password" name="omise_secret_key" id="omiseSecret"
                            value="{{ \App\Models\SiteSetting::get('omise_secret_key') }}"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono text-sm pr-12"
                            placeholder="skey_test_...">
                        <button type="button" onclick="togglePassword('omiseSecret')" class="absolute right-3 top-3 text-gray-400 hover:text-white">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>

            <div class="mt-4 p-4 bg-blue-500/10 border border-blue-500/30 rounded-lg">
                <p class="text-sm text-blue-400">
                    <i class="fas fa-info-circle mr-1"></i>
                    Omise รองรับ: บัตรเครดิต/เดบิต, PromptPay, TrueMoney, Mobile Banking
                </p>
            </div>
        </div>

        <!-- GB Prime Pay Settings -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6" id="gbprimepaySettings">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center">
                    <span class="text-orange-400 font-bold">GB</span>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">GB Prime Pay Configuration</h3>
                    <p class="text-sm text-gray-400">ตั้งค่า API จาก <a href="https://www.gbprimepay.com" target="_blank" class="text-orange-400 hover:underline">GB Prime Pay</a></p>
                </div>
            </div>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-key mr-1"></i>Public Token
                    </label>
                    <input type="text" name="gbprimepay_token"
                        value="{{ \App\Models\SiteSetting::get('gbprimepay_token') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono text-sm"
                        placeholder="Token...">
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-lock mr-1"></i>Secret Key
                    </label>
                    <div class="relative">
                        <input type="password" name="gbprimepay_secret" id="gbSecret"
                            value="{{ \App\Models\SiteSetting::get('gbprimepay_secret') }}"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono text-sm pr-12"
                            placeholder="Secret...">
                        <button type="button" onclick="togglePassword('gbSecret')" class="absolute right-3 top-3 text-gray-400 hover:text-white">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Manual Payment Settings (PromptPay & Bank Transfer) -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6" id="manualSettings">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                    <i class="fas fa-qrcode text-yellow-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">PromptPay & Bank Transfer</h3>
                    <p class="text-sm text-gray-400">ตั้งค่าการชำระเงินแบบ Manual (ต้องตรวจสอบเอง)</p>
                </div>
            </div>

            <!-- PromptPay -->
            <div class="mb-6">
                <label class="flex items-center gap-3 mb-4">
                    <input type="checkbox" name="promptpay_enabled" value="1"
                        {{ \App\Models\SiteSetting::get('promptpay_enabled') ? 'checked' : '' }}
                        class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500">
                    <span class="font-medium">เปิดใช้งาน PromptPay QR</span>
                </label>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">
                            <i class="fas fa-mobile-alt mr-1"></i>PromptPay ID (เบอร์โทร/เลขบัตรประชาชน)
                        </label>
                        <input type="text" name="promptpay_id"
                            value="{{ \App\Models\SiteSetting::get('promptpay_id') }}"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white"
                            placeholder="0812345678 หรือ 1234567890123">
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">
                            <i class="fas fa-user mr-1"></i>ชื่อบัญชี
                        </label>
                        <input type="text" name="promptpay_name"
                            value="{{ \App\Models\SiteSetting::get('promptpay_name') }}"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white"
                            placeholder="ชื่อ นามสกุล">
                    </div>
                </div>
            </div>

            <!-- Bank Transfer -->
            <div class="pt-6 border-t border-gray-700">
                <label class="flex items-center gap-3 mb-4">
                    <input type="checkbox" name="bank_transfer_enabled" value="1"
                        {{ \App\Models\SiteSetting::get('bank_transfer_enabled') ? 'checked' : '' }}
                        class="w-5 h-5 rounded bg-gray-700 border-gray-600 text-purple-600 focus:ring-purple-500">
                    <span class="font-medium">เปิดใช้งาน โอนผ่านธนาคาร</span>
                </label>

                <div id="bankAccountsContainer">
                    @php
                        $bankAccounts = json_decode(\App\Models\SiteSetting::get('bank_accounts', '[]'), true) ?: [];
                    @endphp

                    @forelse($bankAccounts as $index => $account)
                    <div class="bank-account-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 bg-gray-700/50 rounded-lg">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">ธนาคาร</label>
                            <select name="bank_accounts[{{ $index }}][bank]" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
                                <option value="kbank" {{ ($account['bank'] ?? '') === 'kbank' ? 'selected' : '' }}>กสิกรไทย</option>
                                <option value="scb" {{ ($account['bank'] ?? '') === 'scb' ? 'selected' : '' }}>ไทยพาณิชย์</option>
                                <option value="bbl" {{ ($account['bank'] ?? '') === 'bbl' ? 'selected' : '' }}>กรุงเทพ</option>
                                <option value="ktb" {{ ($account['bank'] ?? '') === 'ktb' ? 'selected' : '' }}>กรุงไทย</option>
                                <option value="tmb" {{ ($account['bank'] ?? '') === 'tmb' ? 'selected' : '' }}>ทหารไทยธนชาต</option>
                                <option value="gsb" {{ ($account['bank'] ?? '') === 'gsb' ? 'selected' : '' }}>ออมสิน</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">เลขบัญชี</label>
                            <input type="text" name="bank_accounts[{{ $index }}][account_number]"
                                value="{{ $account['account_number'] ?? '' }}"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                                placeholder="xxx-x-xxxxx-x">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">ชื่อบัญชี</label>
                            <input type="text" name="bank_accounts[{{ $index }}][account_name]"
                                value="{{ $account['account_name'] ?? '' }}"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                                placeholder="ชื่อ นามสกุล">
                        </div>
                        <div class="flex items-end">
                            <button type="button" onclick="this.closest('.bank-account-row').remove()"
                                class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    @empty
                    <div class="bank-account-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 bg-gray-700/50 rounded-lg">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">ธนาคาร</label>
                            <select name="bank_accounts[0][bank]" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
                                <option value="kbank">กสิกรไทย</option>
                                <option value="scb">ไทยพาณิชย์</option>
                                <option value="bbl">กรุงเทพ</option>
                                <option value="ktb">กรุงไทย</option>
                                <option value="tmb">ทหารไทยธนชาต</option>
                                <option value="gsb">ออมสิน</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">เลขบัญชี</label>
                            <input type="text" name="bank_accounts[0][account_number]"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                                placeholder="xxx-x-xxxxx-x">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1">ชื่อบัญชี</label>
                            <input type="text" name="bank_accounts[0][account_name]"
                                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                                placeholder="ชื่อ นามสกุล">
                        </div>
                        <div class="flex items-end">
                            <button type="button" onclick="this.closest('.bank-account-row').remove()"
                                class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-sm">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                    @endforelse
                </div>

                <button type="button" onclick="addBankAccount()"
                    class="mt-2 px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm">
                    <i class="fas fa-plus mr-2"></i>เพิ่มบัญชีธนาคาร
                </button>
            </div>
        </div>

        <!-- Credit Packages -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-6">
            <div class="flex items-center justify-between mb-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-lg bg-pink-500/20 flex items-center justify-center">
                        <i class="fas fa-coins text-pink-400"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold">Credit Packages</h3>
                        <p class="text-sm text-gray-400">จัดการแพ็คเกจเครดิตที่ขาย</p>
                    </div>
                </div>
                <a href="{{ route('admin.packages') }}" class="px-4 py-2 bg-purple-600 hover:bg-purple-700 rounded-lg text-sm">
                    <i class="fas fa-cog mr-2"></i>จัดการแพ็คเกจ
                </a>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                @foreach(config('stripe.packages', []) as $key => $package)
                <div class="p-4 bg-gray-700/50 rounded-lg text-center">
                    <p class="text-2xl font-bold text-white">{{ $package['credits'] }}</p>
                    <p class="text-sm text-gray-400">Credits</p>
                    <p class="text-lg font-medium text-green-400 mt-2">฿{{ number_format($package['price_display']) }}</p>
                </div>
                @endforeach
            </div>
        </div>

        <!-- Submit -->
        <div class="flex justify-end gap-4">
            <button type="button" onclick="testPaymentConnection()" class="px-6 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg font-medium transition">
                <i class="fas fa-plug mr-2"></i>ทดสอบการเชื่อมต่อ
            </button>
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 rounded-lg font-medium transition">
                <i class="fas fa-save mr-2"></i>บันทึกการตั้งค่า
            </button>
        </div>
    </form>
</div>

<!-- Test Connection Modal -->
<div id="testModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm">
    <div class="bg-gray-800 rounded-2xl p-6 max-w-md w-full mx-4 border border-gray-700">
        <div class="text-center">
            <div id="testLoading" class="mb-4">
                <div class="w-16 h-16 mx-auto border-4 border-purple-500/30 border-t-purple-500 rounded-full animate-spin"></div>
                <p class="mt-4 text-gray-400">กำลังทดสอบการเชื่อมต่อ...</p>
            </div>
            <div id="testResult" class="hidden">
                <div id="testSuccess" class="hidden">
                    <div class="w-16 h-16 mx-auto bg-green-500/20 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-check text-green-400 text-3xl"></i>
                    </div>
                    <p class="text-green-400 font-medium">เชื่อมต่อสำเร็จ!</p>
                </div>
                <div id="testError" class="hidden">
                    <div class="w-16 h-16 mx-auto bg-red-500/20 rounded-full flex items-center justify-center mb-4">
                        <i class="fas fa-times text-red-400 text-3xl"></i>
                    </div>
                    <p class="text-red-400 font-medium">เชื่อมต่อไม่สำเร็จ</p>
                    <p id="testErrorMsg" class="text-gray-400 text-sm mt-2"></p>
                </div>
            </div>
            <button onclick="closeTestModal()" class="mt-6 px-6 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg">
                ปิด
            </button>
        </div>
    </div>
</div>

@push('scripts')
<script>
    let bankAccountIndex = {{ count($bankAccounts) ?: 1 }};

    function togglePassword(id) {
        const input = document.getElementById(id);
        input.type = input.type === 'password' ? 'text' : 'password';
    }

    function addBankAccount() {
        const container = document.getElementById('bankAccountsContainer');
        const html = `
            <div class="bank-account-row grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 p-4 bg-gray-700/50 rounded-lg">
                <div>
                    <label class="block text-xs text-gray-400 mb-1">ธนาคาร</label>
                    <select name="bank_accounts[${bankAccountIndex}][bank]" class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm">
                        <option value="kbank">กสิกรไทย</option>
                        <option value="scb">ไทยพาณิชย์</option>
                        <option value="bbl">กรุงเทพ</option>
                        <option value="ktb">กรุงไทย</option>
                        <option value="tmb">ทหารไทยธนชาต</option>
                        <option value="gsb">ออมสิน</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">เลขบัญชี</label>
                    <input type="text" name="bank_accounts[${bankAccountIndex}][account_number]"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                        placeholder="xxx-x-xxxxx-x">
                </div>
                <div>
                    <label class="block text-xs text-gray-400 mb-1">ชื่อบัญชี</label>
                    <input type="text" name="bank_accounts[${bankAccountIndex}][account_name]"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white text-sm"
                        placeholder="ชื่อ นามสกุล">
                </div>
                <div class="flex items-end">
                    <button type="button" onclick="this.closest('.bank-account-row').remove()"
                        class="px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-sm">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </div>
        `;
        container.insertAdjacentHTML('beforeend', html);
        bankAccountIndex++;
    }

    function testPaymentConnection() {
        const modal = document.getElementById('testModal');
        const loading = document.getElementById('testLoading');
        const result = document.getElementById('testResult');
        const success = document.getElementById('testSuccess');
        const error = document.getElementById('testError');

        modal.classList.remove('hidden');
        modal.classList.add('flex');
        loading.classList.remove('hidden');
        result.classList.add('hidden');

        fetch('{{ route("admin.payment-settings.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({
                gateway: document.querySelector('input[name="payment_gateway"]:checked').value,
            }),
        })
        .then(r => r.json())
        .then(data => {
            loading.classList.add('hidden');
            result.classList.remove('hidden');

            if (data.success) {
                success.classList.remove('hidden');
                error.classList.add('hidden');
            } else {
                success.classList.add('hidden');
                error.classList.remove('hidden');
                document.getElementById('testErrorMsg').textContent = data.message || 'Unknown error';
            }
        })
        .catch(err => {
            loading.classList.add('hidden');
            result.classList.remove('hidden');
            success.classList.add('hidden');
            error.classList.remove('hidden');
            document.getElementById('testErrorMsg').textContent = err.message;
        });
    }

    function closeTestModal() {
        document.getElementById('testModal').classList.add('hidden');
        document.getElementById('testModal').classList.remove('flex');
    }
</script>
@endpush
@endsection
