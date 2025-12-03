@extends('layouts.app')

@section('title', 'Wallet')

@section('content')
<div class="max-w-6xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-white mb-8">
        <i class="fas fa-wallet text-purple-400 mr-3"></i>My Wallet
    </h1>

    <!-- Balance Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <!-- Available Balance -->
        <div class="bg-gradient-to-br from-purple-600/30 to-blue-600/30 rounded-xl p-6 border border-purple-500/30">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm">Available Balance</span>
                <i class="fas fa-wallet text-purple-400"></i>
            </div>
            <p class="text-3xl font-bold text-white">${{ number_format($stats['balance'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Ready to withdraw</p>
        </div>

        <!-- Pending Earnings -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm">Pending Earnings</span>
                <i class="fas fa-clock text-yellow-400"></i>
            </div>
            <p class="text-3xl font-bold text-yellow-400">${{ number_format($stats['pending_earnings'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Min ${{ $settings['min_transfer'] }} to transfer</p>
        </div>

        <!-- Total Earned -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm">Total Earned</span>
                <i class="fas fa-coins text-green-400"></i>
            </div>
            <p class="text-3xl font-bold text-green-400">${{ number_format($stats['total_earned'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">All time earnings</p>
        </div>

        <!-- Total Withdrawn -->
        <div class="bg-gray-800 rounded-xl p-6 border border-gray-700">
            <div class="flex items-center justify-between mb-2">
                <span class="text-gray-400 text-sm">Total Withdrawn</span>
                <i class="fas fa-arrow-up text-blue-400"></i>
            </div>
            <p class="text-3xl font-bold text-blue-400">${{ number_format($stats['total_withdrawn'], 2) }}</p>
            <p class="text-xs text-gray-400 mt-1">Successfully withdrawn</p>
        </div>
    </div>

    <!-- KYC Status Alert -->
    @if(!$user->isKycApproved())
    <div class="bg-orange-600/20 border border-orange-500 rounded-lg p-4 mb-6">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <i class="fas fa-user-shield text-orange-400 text-xl"></i>
                <div>
                    <p class="font-semibold text-orange-400">
                        @if($user->kyc_status === 'pending')
                            กำลังตรวจสอบเอกสาร KYC
                        @elseif($user->kyc_status === 'rejected')
                            KYC ไม่ผ่านการอนุมัติ
                        @else
                            ต้องยืนยันตัวตนก่อนถอนเงิน
                        @endif
                    </p>
                    <p class="text-gray-300 text-sm">
                        @if($user->kyc_status === 'pending')
                            กรุณารอ 1-3 วันทำการเพื่อตรวจสอบเอกสาร
                        @elseif($user->kyc_status === 'rejected')
                            กรุณาส่งเอกสารใหม่เพื่อยืนยันตัวตน
                        @else
                            คุณต้องผ่านการยืนยันตัวตน (KYC) ก่อนจึงจะสามารถถอนเงินได้
                        @endif
                    </p>
                </div>
            </div>
            <a href="{{ route('kyc.index') }}" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-id-card"></i>
                <span>{{ $user->kyc_status === 'rejected' ? 'ส่งใหม่' : ($user->kyc_status === 'pending' ? 'ดูสถานะ' : 'ยืนยันตัวตน') }}</span>
            </a>
        </div>
    </div>
    @else
    <!-- KYC Verified Badge -->
    <div class="bg-green-600/20 border border-green-500 rounded-lg p-3 mb-6">
        <div class="flex items-center gap-3">
            <i class="fas fa-check-circle text-green-400 text-lg"></i>
            <span class="text-green-400 text-sm">ผ่านการยืนยันตัวตนแล้ว - สามารถถอนเงินได้</span>
            @if($kyc)
            <span class="text-gray-400 text-sm ml-auto">บัญชี: {{ $kyc->bank_display_name }} {{ $kyc->masked_account_number }}</span>
            @endif
        </div>
    </div>
    @endif

    <!-- Action Buttons -->
    <div class="flex flex-wrap gap-4 mb-8">
        <!-- Transfer Earnings Button -->
        @if($stats['pending_earnings'] >= $settings['min_transfer'])
        <button onclick="transferEarnings()" id="btn-transfer" class="px-6 py-3 bg-green-600 hover:bg-green-700 text-white rounded-lg flex items-center gap-2 transition">
            <i class="fas fa-exchange-alt"></i>
            <span>Transfer ${{ number_format($stats['pending_earnings'], 2) }} to Wallet</span>
        </button>
        @endif

        <!-- Withdraw Button -->
        @if($stats['balance'] >= $settings['min_withdrawal'])
            @if($user->isKycApproved())
            <button onclick="openWithdrawModal()" class="px-6 py-3 bg-purple-600 hover:bg-purple-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-money-bill-wave"></i>
                <span>Withdraw</span>
            </button>
            @else
            <a href="{{ route('kyc.index') }}" class="px-6 py-3 bg-orange-600 hover:bg-orange-700 text-white rounded-lg flex items-center gap-2 transition">
                <i class="fas fa-user-shield"></i>
                <span>ยืนยันตัวตนก่อนถอนเงิน</span>
            </a>
            @endif
        @else
        <button disabled class="px-6 py-3 bg-gray-700 text-gray-400 rounded-lg flex items-center gap-2 cursor-not-allowed">
            <i class="fas fa-money-bill-wave"></i>
            <span>Min ${{ $settings['min_withdrawal'] }} to withdraw</span>
        </button>
        @endif
    </div>

    <!-- Pending Withdrawals Alert -->
    @if($stats['pending_withdrawal'] > 0)
    <div class="bg-yellow-600/20 border border-yellow-500 rounded-lg p-4 mb-8">
        <div class="flex items-center gap-3">
            <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
            <div>
                <p class="font-semibold text-yellow-400">Pending Withdrawal</p>
                <p class="text-gray-300 text-sm">You have ${{ number_format($stats['pending_withdrawal'], 2) }} in pending withdrawals.</p>
            </div>
        </div>
    </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Transactions -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700 flex items-center justify-between">
                <h3 class="text-lg font-semibold text-white">
                    <i class="fas fa-history text-purple-400 mr-2"></i>Recent Transactions
                </h3>
                <a href="{{ route('wallet.transactions') }}" class="text-purple-400 hover:text-purple-300 text-sm">View All</a>
            </div>
            <div class="divide-y divide-gray-700 max-h-[400px] overflow-y-auto">
                @forelse($transactions->take(10) as $txn)
                <div class="p-4 hover:bg-gray-750">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center
                                {{ in_array($txn->type, ['deposit', 'earning', 'referral', 'bonus', 'refund']) ? 'bg-green-600/20 text-green-400' : 'bg-red-600/20 text-red-400' }}">
                                <i class="fas fa-{{ $txn->type_icon }}"></i>
                            </div>
                            <div>
                                <p class="text-white text-sm font-medium">{{ ucfirst($txn->type) }}</p>
                                <p class="text-gray-500 text-xs">{{ $txn->description ?? $txn->transaction_id }}</p>
                            </div>
                        </div>
                        <div class="text-right">
                            <p class="font-semibold {{ $txn->amount >= 0 ? 'text-green-400' : 'text-red-400' }}">
                                {{ $txn->amount >= 0 ? '+' : '' }}${{ number_format(abs($txn->amount), 2) }}
                            </p>
                            <p class="text-gray-500 text-xs">{{ $txn->created_at->diffForHumans() }}</p>
                        </div>
                    </div>
                </div>
                @empty
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-receipt text-4xl mb-3 text-gray-600"></i>
                    <p>No transactions yet</p>
                </div>
                @endforelse
            </div>
        </div>

        <!-- Withdrawal Requests -->
        <div class="bg-gray-800 rounded-xl border border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h3 class="text-lg font-semibold text-white">
                    <i class="fas fa-file-invoice-dollar text-green-400 mr-2"></i>Withdrawal Requests
                </h3>
            </div>
            <div class="divide-y divide-gray-700 max-h-[400px] overflow-y-auto">
                @forelse($withdrawals as $wd)
                <div class="p-4 hover:bg-gray-750" id="withdrawal-{{ $wd->id }}">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="text-white font-medium">${{ number_format($wd->amount, 2) }}</p>
                            <p class="text-gray-500 text-xs">{{ $wd->payment_method_label }} | {{ $wd->request_id }}</p>
                        </div>
                        <div class="text-right">
                            <span class="px-2 py-1 text-xs rounded-full
                                {{ $wd->status === 'completed' ? 'bg-green-600/20 text-green-400' :
                                   ($wd->status === 'pending' ? 'bg-yellow-600/20 text-yellow-400' :
                                   ($wd->status === 'processing' ? 'bg-blue-600/20 text-blue-400' :
                                   ($wd->status === 'rejected' ? 'bg-red-600/20 text-red-400' : 'bg-gray-600/20 text-gray-400'))) }}">
                                {{ ucfirst($wd->status) }}
                            </span>
                            @if($wd->status === 'pending')
                            <button onclick="cancelWithdrawal({{ $wd->id }})" class="ml-2 text-red-400 hover:text-red-300 text-xs">
                                Cancel
                            </button>
                            @endif
                        </div>
                    </div>
                    @if($wd->reject_reason)
                    <p class="text-red-400 text-xs mt-2">Reason: {{ $wd->reject_reason }}</p>
                    @endif
                    <p class="text-gray-600 text-xs mt-1">{{ $wd->created_at->diffForHumans() }}</p>
                </div>
                @empty
                <div class="p-8 text-center text-gray-400">
                    <i class="fas fa-file-invoice text-4xl mb-3 text-gray-600"></i>
                    <p>No withdrawal requests</p>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    <!-- Fee Info -->
    <div class="mt-8 bg-gray-800 rounded-xl p-6 border border-gray-700">
        <h3 class="text-lg font-semibold text-white mb-4">
            <i class="fas fa-info-circle text-blue-400 mr-2"></i>Withdrawal Information
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <p class="text-gray-400">Minimum Withdrawal</p>
                <p class="text-white font-medium">${{ number_format($settings['min_withdrawal'], 2) }}</p>
            </div>
            <div>
                <p class="text-gray-400">Withdrawal Fee</p>
                <p class="text-white font-medium">{{ $settings['withdrawal_fee_percent'] }}%{{ $settings['withdrawal_fee_fixed'] > 0 ? ' + $' . number_format($settings['withdrawal_fee_fixed'], 2) : '' }}</p>
            </div>
            <div>
                <p class="text-gray-400">Processing Time</p>
                <p class="text-white font-medium">1-3 Business Days</p>
            </div>
        </div>
    </div>
</div>

<!-- Withdraw Modal -->
<div id="withdraw-modal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-gray-800 rounded-xl p-6 max-w-md w-full mx-4 border border-gray-700">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-semibold text-white">Withdraw Funds</h3>
            <button onclick="closeWithdrawModal()" class="text-gray-400 hover:text-white">
                <i class="fas fa-times"></i>
            </button>
        </div>

        <form id="withdraw-form">
            @csrf
            <!-- Amount -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Amount ($)</label>
                <input type="number" name="amount" id="withdraw-amount" step="0.01" min="{{ $settings['min_withdrawal'] }}" max="{{ $stats['balance'] }}"
                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                    placeholder="Enter amount">
                <p class="text-xs text-gray-500 mt-1">Available: ${{ number_format($stats['balance'], 2) }}</p>
            </div>

            <!-- Payment Method -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">Payment Method</label>
                <select name="payment_method" id="payment-method" onchange="showPaymentFields()"
                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                    <option value="">Select method...</option>
                    <option value="promptpay">PromptPay</option>
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="truemoney">TrueMoney Wallet</option>
                    <option value="paypal">PayPal</option>
                    <option value="crypto">Cryptocurrency (USDT)</option>
                </select>
            </div>

            <!-- PromptPay Fields -->
            <div id="fields-promptpay" class="payment-fields hidden mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">PromptPay Number</label>
                <input type="text" name="promptpay_number" placeholder="0812345678 or 1234567890123"
                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
            </div>

            <!-- Bank Transfer Fields -->
            <div id="fields-bank_transfer" class="payment-fields hidden space-y-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Bank Name</label>
                    <select name="bank_name" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                        <option value="">Select bank...</option>
                        <option value="kbank">Kasikorn Bank</option>
                        <option value="scb">Siam Commercial Bank</option>
                        <option value="bbl">Bangkok Bank</option>
                        <option value="ktb">Krungthai Bank</option>
                        <option value="gsb">Government Savings Bank</option>
                        <option value="ttb">TTB Bank</option>
                        <option value="uob">UOB Thailand</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Account Number</label>
                    <input type="text" name="account_number" placeholder="1234567890"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Account Name</label>
                    <input type="text" name="account_name" placeholder="John Doe"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                </div>
            </div>

            <!-- TrueMoney Fields -->
            <div id="fields-truemoney" class="payment-fields hidden mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">TrueMoney Phone Number</label>
                <input type="text" name="truemoney_number" placeholder="0812345678"
                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
            </div>

            <!-- PayPal Fields -->
            <div id="fields-paypal" class="payment-fields hidden mb-4">
                <label class="block text-sm font-medium text-gray-300 mb-2">PayPal Email</label>
                <input type="email" name="paypal_email" placeholder="your@email.com"
                    class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
            </div>

            <!-- Crypto Fields -->
            <div id="fields-crypto" class="payment-fields hidden space-y-3 mb-4">
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Network</label>
                    <select name="crypto_network" class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                        <option value="trc20">USDT (TRC20)</option>
                        <option value="erc20">USDT (ERC20)</option>
                        <option value="bep20">USDT (BEP20)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Wallet Address</label>
                    <input type="text" name="crypto_address" placeholder="T..."
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white">
                </div>
            </div>

            <!-- Fee Display -->
            <div id="fee-display" class="hidden mb-4 p-3 bg-gray-700 rounded-lg">
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Amount:</span>
                    <span class="text-white" id="display-amount">$0.00</span>
                </div>
                <div class="flex justify-between text-sm">
                    <span class="text-gray-400">Fee ({{ $settings['withdrawal_fee_percent'] }}%):</span>
                    <span class="text-red-400" id="display-fee">-$0.00</span>
                </div>
                <div class="flex justify-between text-sm font-semibold border-t border-gray-600 pt-2 mt-2">
                    <span class="text-gray-300">You'll receive:</span>
                    <span class="text-green-400" id="display-net">$0.00</span>
                </div>
            </div>

            <!-- Error Display -->
            <div id="withdraw-error" class="hidden mb-4 p-3 bg-red-600/20 border border-red-500 rounded-lg text-red-400 text-sm"></div>

            <div class="flex gap-3">
                <button type="button" onclick="closeWithdrawModal()" class="flex-1 px-4 py-2 bg-gray-600 hover:bg-gray-500 text-white rounded-lg transition">
                    Cancel
                </button>
                <button type="submit" id="submit-withdraw" class="flex-1 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition">
                    Submit Withdrawal
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
const feePercent = {{ $settings['withdrawal_fee_percent'] }};
const feeFixed = {{ $settings['withdrawal_fee_fixed'] }};

function openWithdrawModal() {
    document.getElementById('withdraw-modal').classList.remove('hidden');
    document.getElementById('withdraw-modal').classList.add('flex');
}

function closeWithdrawModal() {
    document.getElementById('withdraw-modal').classList.add('hidden');
    document.getElementById('withdraw-modal').classList.remove('flex');
    document.getElementById('withdraw-form').reset();
    document.querySelectorAll('.payment-fields').forEach(f => f.classList.add('hidden'));
    document.getElementById('fee-display').classList.add('hidden');
    document.getElementById('withdraw-error').classList.add('hidden');
}

function showPaymentFields() {
    const method = document.getElementById('payment-method').value;
    document.querySelectorAll('.payment-fields').forEach(f => f.classList.add('hidden'));
    if (method) {
        document.getElementById('fields-' + method).classList.remove('hidden');
    }
}

// Calculate fees on amount change
document.getElementById('withdraw-amount').addEventListener('input', function() {
    const amount = parseFloat(this.value) || 0;
    if (amount > 0) {
        const fee = (amount * feePercent / 100) + feeFixed;
        const net = amount - fee;

        document.getElementById('display-amount').textContent = '$' + amount.toFixed(2);
        document.getElementById('display-fee').textContent = '-$' + fee.toFixed(2);
        document.getElementById('display-net').textContent = '$' + net.toFixed(2);
        document.getElementById('fee-display').classList.remove('hidden');
    } else {
        document.getElementById('fee-display').classList.add('hidden');
    }
});

// Submit withdrawal
document.getElementById('withdraw-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('submit-withdraw');
    const errorDiv = document.getElementById('withdraw-error');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i>Processing...';
    errorDiv.classList.add('hidden');

    const formData = new FormData(this);

    try {
        const response = await fetch('{{ route("wallet.withdraw") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            closeWithdrawModal();
            location.reload();
        } else {
            errorDiv.textContent = data.message;
            errorDiv.classList.remove('hidden');
        }
    } catch (error) {
        errorDiv.textContent = 'An error occurred. Please try again.';
        errorDiv.classList.remove('hidden');
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Submit Withdrawal';
    }
});

async function transferEarnings() {
    const btn = document.getElementById('btn-transfer');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin mr-2"></i>Transferring...';

    try {
        const response = await fetch('{{ route("wallet.transfer") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-exchange-alt mr-2"></i>Transfer to Wallet';
    }
}

async function cancelWithdrawal(id) {
    if (!confirm('Cancel this withdrawal request?')) return;

    try {
        const response = await fetch(`/wallet/withdrawal/${id}/cancel`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            }
        });

        const data = await response.json();

        if (data.success) {
            location.reload();
        } else {
            alert(data.message);
        }
    } catch (error) {
        alert('Error: ' + error.message);
    }
}

// Close modal on escape
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeWithdrawModal();
});
</script>
@endpush
