@extends('layouts.admin')

@section('title', 'Settings')
@section('header', 'Platform Settings')

@section('content')
<div class="max-w-3xl space-y-6">
    <!-- Platform Settings -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <h3 class="text-lg font-semibold mb-6">Platform Configuration</h3>
        <form class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Platform Fee (%)</label>
                    <input type="number" value="10" min="0" max="50" step="0.1"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                    <p class="text-gray-500 text-xs mt-1">Percentage taken from each earning</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Minimum Payout ($)</label>
                    <input type="number" value="10" min="1"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Minimum VRAM (MB)</label>
                    <input type="number" value="2048" min="1024"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                    <p class="text-gray-500 text-xs mt-1">Minimum GPU VRAM to participate</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Heartbeat Timeout (seconds)</label>
                    <input type="number" value="120" min="30"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Verification Frequency (%)</label>
                    <input type="number" value="5" min="1" max="100"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                    <p class="text-gray-500 text-xs mt-1">Random verification chance per heartbeat</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Max Failed Verifications</label>
                    <input type="number" value="5" min="1"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                    <p class="text-gray-500 text-xs mt-1">Before automatic ban</p>
                </div>
            </div>

            <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-lg">
                <i class="fas fa-save mr-2"></i>Save Settings
            </button>
        </form>
    </div>

    <!-- Referral Settings -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <h3 class="text-lg font-semibold mb-6">Referral Program</h3>
        <form class="space-y-6">
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Referral Bonus (%)</label>
                    <input type="number" value="5" min="0" max="50" step="0.1"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                    <p class="text-gray-500 text-xs mt-1">Percentage of referee's earnings</p>
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-2">Referral Duration (days)</label>
                    <input type="number" value="365" min="0"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white">
                    <p class="text-gray-500 text-xs mt-1">0 = permanent</p>
                </div>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" checked class="mr-3 rounded bg-gray-700 border-gray-600 text-purple-600">
                    <span>Enable Referral Program</span>
                </label>
            </div>

            <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-lg">
                <i class="fas fa-save mr-2"></i>Save Referral Settings
            </button>
        </form>
    </div>

    <!-- Payment Methods -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <h3 class="text-lg font-semibold mb-6">Payment Methods</h3>
        <div class="space-y-4">
            <label class="flex items-center justify-between p-4 bg-gray-700/50 rounded-lg">
                <div class="flex items-center">
                    <input type="checkbox" checked class="mr-4 rounded bg-gray-700 border-gray-600 text-purple-600">
                    <div>
                        <p class="font-medium">Bank Transfer</p>
                        <p class="text-sm text-gray-400">Direct bank transfer</p>
                    </div>
                </div>
                <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
            </label>

            <label class="flex items-center justify-between p-4 bg-gray-700/50 rounded-lg">
                <div class="flex items-center">
                    <input type="checkbox" checked class="mr-4 rounded bg-gray-700 border-gray-600 text-purple-600">
                    <div>
                        <p class="font-medium">PromptPay</p>
                        <p class="text-sm text-gray-400">Thai instant payment</p>
                    </div>
                </div>
                <span class="text-green-400"><i class="fas fa-check-circle"></i></span>
            </label>

            <label class="flex items-center justify-between p-4 bg-gray-700/50 rounded-lg">
                <div class="flex items-center">
                    <input type="checkbox" class="mr-4 rounded bg-gray-700 border-gray-600 text-purple-600">
                    <div>
                        <p class="font-medium">Cryptocurrency</p>
                        <p class="text-sm text-gray-400">BTC, ETH, USDT</p>
                    </div>
                </div>
                <span class="text-gray-400"><i class="fas fa-times-circle"></i></span>
            </label>
        </div>
    </div>

    <!-- Maintenance -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <h3 class="text-lg font-semibold mb-6">Maintenance</h3>
        <div class="space-y-4">
            <button class="w-full flex items-center justify-between p-4 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition">
                <div>
                    <p class="font-medium">Run Statistics Update</p>
                    <p class="text-sm text-gray-400">Update daily pool statistics</p>
                </div>
                <i class="fas fa-sync text-purple-400"></i>
            </button>

            <button class="w-full flex items-center justify-between p-4 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition">
                <div>
                    <p class="font-medium">Clear Stale Sessions</p>
                    <p class="text-sm text-gray-400">Remove orphaned node sessions</p>
                </div>
                <i class="fas fa-broom text-yellow-400"></i>
            </button>

            <button class="w-full flex items-center justify-between p-4 bg-gray-700/50 rounded-lg hover:bg-gray-700 transition">
                <div>
                    <p class="font-medium">Confirm Pending Earnings</p>
                    <p class="text-sm text-gray-400">Move confirmed earnings to balance</p>
                </div>
                <i class="fas fa-check-double text-green-400"></i>
            </button>
        </div>
    </div>
</div>
@endsection
