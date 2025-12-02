@extends('layouts.admin')

@section('title', 'Referral Commission Settings')
@section('header', 'Referral Commission Settings')

@section('content')
<div class="max-w-4xl">
    <!-- Info Box -->
    <div class="bg-blue-600/20 border border-blue-500 rounded-lg p-4 mb-6">
        <div class="flex items-start gap-3">
            <i class="fas fa-info-circle text-blue-400 text-xl mt-0.5"></i>
            <div>
                <h4 class="font-semibold text-blue-400">Multi-Level Referral System</h4>
                <p class="text-gray-300 text-sm mt-1">
                    Configure commission rates for each referral level. When a user earns from GPU mining,
                    their referrers receive a percentage commission based on their level in the referral tree.
                </p>
            </div>
        </div>
    </div>

    <form id="settings-form" class="space-y-6">
        @csrf

        <!-- Commission Rates -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-percent text-purple-400 mr-2"></i>Commission Rates
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Level 1 -->
                <div class="bg-gray-750 rounded-lg p-4 border border-gray-600">
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 bg-green-600/20 text-green-400 rounded-full text-sm font-semibold">Level 1</span>
                        <span class="text-xs text-gray-400">Direct Referral</span>
                    </div>
                    <div class="relative">
                        <input type="number" name="referral_level_1_rate" id="referral_level_1_rate"
                            value="{{ $settings['referral_level_1_rate'] }}"
                            step="0.1" min="0" max="50"
                            class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white text-xl font-bold text-center focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">%</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 text-center">Max: 50%</p>
                </div>

                <!-- Level 2 -->
                <div class="bg-gray-750 rounded-lg p-4 border border-gray-600">
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 bg-blue-600/20 text-blue-400 rounded-full text-sm font-semibold">Level 2</span>
                        <span class="text-xs text-gray-400">2nd Tier</span>
                    </div>
                    <div class="relative">
                        <input type="number" name="referral_level_2_rate" id="referral_level_2_rate"
                            value="{{ $settings['referral_level_2_rate'] }}"
                            step="0.1" min="0" max="30"
                            class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white text-xl font-bold text-center focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">%</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 text-center">Max: 30%</p>
                </div>

                <!-- Level 3 -->
                <div class="bg-gray-750 rounded-lg p-4 border border-gray-600">
                    <div class="flex items-center justify-between mb-3">
                        <span class="px-3 py-1 bg-purple-600/20 text-purple-400 rounded-full text-sm font-semibold">Level 3</span>
                        <span class="text-xs text-gray-400">3rd Tier</span>
                    </div>
                    <div class="relative">
                        <input type="number" name="referral_level_3_rate" id="referral_level_3_rate"
                            value="{{ $settings['referral_level_3_rate'] }}"
                            step="0.1" min="0" max="20"
                            class="w-full px-4 py-3 bg-gray-700 border border-gray-600 rounded-lg text-white text-xl font-bold text-center focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        <span class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400">%</span>
                    </div>
                    <p class="text-xs text-gray-500 mt-2 text-center">Max: 20%</p>
                </div>
            </div>

            <!-- Total Commission Display -->
            <div class="mt-4 p-4 bg-gray-900 rounded-lg">
                <div class="flex items-center justify-between">
                    <span class="text-gray-400">Total Max Commission Per Earning:</span>
                    <span id="total-commission" class="text-xl font-bold text-purple-400">
                        {{ $settings['referral_level_1_rate'] + $settings['referral_level_2_rate'] + $settings['referral_level_3_rate'] }}%
                    </span>
                </div>
            </div>
        </div>

        <!-- General Settings -->
        <div class="bg-gray-800 rounded-lg border border-gray-700 p-6">
            <h3 class="text-lg font-semibold text-white mb-4">
                <i class="fas fa-cog text-gray-400 mr-2"></i>General Settings
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Max Referral Levels -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Max Referral Levels</label>
                    <select name="max_referral_levels" id="max_referral_levels"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                        @for($i = 1; $i <= 5; $i++)
                        <option value="{{ $i }}" {{ $settings['max_referral_levels'] == $i ? 'selected' : '' }}>{{ $i }} Level{{ $i > 1 ? 's' : '' }}</option>
                        @endfor
                    </select>
                    <p class="text-xs text-gray-500 mt-1">Number of levels that receive commission</p>
                </div>

                <!-- Min Withdrawal for Referral -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Min Withdrawal Amount ($)</label>
                    <input type="number" name="min_withdrawal_referral" id="min_withdrawal_referral"
                        value="{{ $settings['min_withdrawal_referral'] }}"
                        step="1" min="0"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Minimum amount to withdraw referral earnings</p>
                </div>

                <!-- Signup Bonus -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Signup Bonus ($)</label>
                    <input type="number" name="signup_bonus_amount" id="signup_bonus_amount"
                        value="{{ $settings['signup_bonus_amount'] }}"
                        step="0.01" min="0"
                        class="w-full px-4 py-2 bg-gray-700 border border-gray-600 rounded-lg text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500">
                    <p class="text-xs text-gray-500 mt-1">Bonus given to new users who signup with referral code</p>
                </div>

                <!-- Referral Bonus Enabled -->
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Referral System Status</label>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="referral_bonus_enabled" id="referral_bonus_enabled"
                            {{ $settings['referral_bonus_enabled'] ? 'checked' : '' }}
                            class="sr-only peer">
                        <div class="w-14 h-7 bg-gray-600 peer-focus:ring-2 peer-focus:ring-purple-500 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-0.5 after:left-[4px] after:bg-white after:rounded-full after:h-6 after:w-6 after:transition-all peer-checked:bg-purple-600"></div>
                        <span class="ml-3 text-sm text-gray-300" id="status-text">{{ $settings['referral_bonus_enabled'] ? 'Enabled' : 'Disabled' }}</span>
                    </label>
                    <p class="text-xs text-gray-500 mt-1">Enable/disable the entire referral system</p>
                </div>
            </div>
        </div>

        <!-- Save Button -->
        <div class="flex items-center justify-between">
            <div id="save-status" class="text-sm text-gray-400"></div>
            <button type="submit" id="save-btn" class="px-6 py-2 bg-purple-600 hover:bg-purple-700 text-white rounded-lg transition flex items-center gap-2">
                <i class="fas fa-save"></i>
                <span>Save Settings</span>
            </button>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

// Update total commission display
function updateTotalCommission() {
    const l1 = parseFloat(document.getElementById('referral_level_1_rate').value) || 0;
    const l2 = parseFloat(document.getElementById('referral_level_2_rate').value) || 0;
    const l3 = parseFloat(document.getElementById('referral_level_3_rate').value) || 0;
    const total = l1 + l2 + l3;
    document.getElementById('total-commission').textContent = total.toFixed(1) + '%';
}

document.getElementById('referral_level_1_rate').addEventListener('input', updateTotalCommission);
document.getElementById('referral_level_2_rate').addEventListener('input', updateTotalCommission);
document.getElementById('referral_level_3_rate').addEventListener('input', updateTotalCommission);

// Toggle status text
document.getElementById('referral_bonus_enabled').addEventListener('change', function() {
    document.getElementById('status-text').textContent = this.checked ? 'Enabled' : 'Disabled';
});

// Form submit
document.getElementById('settings-form').addEventListener('submit', async function(e) {
    e.preventDefault();

    const btn = document.getElementById('save-btn');
    const statusDiv = document.getElementById('save-status');

    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner animate-spin"></i> Saving...';
    statusDiv.textContent = '';

    const formData = new FormData(this);
    const data = {};

    formData.forEach((value, key) => {
        if (key === 'referral_bonus_enabled') {
            data[key] = true;
        } else {
            data[key] = value;
        }
    });

    // Handle checkbox unchecked
    if (!document.getElementById('referral_bonus_enabled').checked) {
        data['referral_bonus_enabled'] = false;
    }

    try {
        const response = await fetch('{{ route("admin.referral-settings.update") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        });

        const result = await response.json();

        if (result.success) {
            statusDiv.innerHTML = '<span class="text-green-400"><i class="fas fa-check mr-1"></i>Settings saved successfully!</span>';
        } else {
            statusDiv.innerHTML = '<span class="text-red-400"><i class="fas fa-times mr-1"></i>' + (result.message || 'Failed to save') + '</span>';
        }
    } catch (error) {
        statusDiv.innerHTML = '<span class="text-red-400"><i class="fas fa-times mr-1"></i>Error: ' + error.message + '</span>';
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Settings';
    }
});
</script>
@endpush
