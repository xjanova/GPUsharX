<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - GPU Share</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <!-- Navigation -->
    <nav class="bg-gray-800 border-b border-gray-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="/" class="flex items-center text-xl font-bold">
                        <i class="fas fa-microchip text-purple-500 mr-2"></i>
                        GPU Share
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-400">{{ $user->name }}</span>
                    <form method="POST" action="/logout" class="inline">
                        @csrf
                        <button type="submit" class="text-gray-400 hover:text-white">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6 mb-8">
            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Balance</p>
                        <p class="text-2xl font-bold text-green-400">${{ number_format($stats['balance'], 2) }}</p>
                    </div>
                    <div class="bg-green-500/20 p-3 rounded-lg">
                        <i class="fas fa-wallet text-green-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Pending</p>
                        <p class="text-2xl font-bold text-yellow-400">${{ number_format($stats['pending'], 2) }}</p>
                    </div>
                    <div class="bg-yellow-500/20 p-3 rounded-lg">
                        <i class="fas fa-clock text-yellow-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Total Earned</p>
                        <p class="text-2xl font-bold text-purple-400">${{ number_format($stats['total_earned'], 2) }}</p>
                    </div>
                    <div class="bg-purple-500/20 p-3 rounded-lg">
                        <i class="fas fa-coins text-purple-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Active Nodes</p>
                        <p class="text-2xl font-bold text-blue-400">{{ $stats['active_nodes'] }}</p>
                    </div>
                    <div class="bg-blue-500/20 p-3 rounded-lg">
                        <i class="fas fa-server text-blue-400 text-xl"></i>
                    </div>
                </div>
            </div>

            <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-400 text-sm">Hashrate</p>
                        <p class="text-2xl font-bold text-cyan-400">{{ number_format($stats['total_hashrate'], 1) }}</p>
                    </div>
                    <div class="bg-cyan-500/20 p-3 rounded-lg">
                        <i class="fas fa-bolt text-cyan-400 text-xl"></i>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Getting Started -->
            <div class="lg:col-span-2">
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mb-8">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-rocket text-purple-500 mr-2"></i>
                        Getting Started
                    </h2>
                    <div class="space-y-4">
                        <div class="flex items-start">
                            <div class="bg-purple-500/20 p-2 rounded-lg mr-4">
                                <span class="text-purple-400 font-bold">1</span>
                            </div>
                            <div>
                                <h3 class="font-medium">Download the Client</h3>
                                <p class="text-gray-400 text-sm">Download and install the GPU Share client on your Windows PC.</p>
                                <a href="/downloads/gpu-share-client.exe" class="inline-block mt-2 bg-purple-600 hover:bg-purple-700 px-4 py-2 rounded-lg text-sm">
                                    <i class="fas fa-download mr-2"></i>Download Client
                                </a>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-purple-500/20 p-2 rounded-lg mr-4">
                                <span class="text-purple-400 font-bold">2</span>
                            </div>
                            <div>
                                <h3 class="font-medium">Get Your API Token</h3>
                                <p class="text-gray-400 text-sm">Copy your API token and paste it in the client.</p>
                                <div class="mt-2 flex items-center space-x-2">
                                    <code class="bg-gray-700 px-3 py-2 rounded text-sm text-green-400" id="api-token">{{ $user->createToken('client')->plainTextToken ?? 'Click to generate' }}</code>
                                    <button onclick="copyToken()" class="bg-gray-700 hover:bg-gray-600 px-3 py-2 rounded">
                                        <i class="fas fa-copy"></i>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-start">
                            <div class="bg-purple-500/20 p-2 rounded-lg mr-4">
                                <span class="text-purple-400 font-bold">3</span>
                            </div>
                            <div>
                                <h3 class="font-medium">Start Mining</h3>
                                <p class="text-gray-400 text-sm">Run the client and start earning by sharing your GPU power!</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- My GPU Nodes -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-server text-blue-500 mr-2"></i>
                        My GPU Nodes
                    </h2>
                    @if($user->gpuNodes->count() > 0)
                    <div class="space-y-4">
                        @foreach($user->gpuNodes as $node)
                        <div class="bg-gray-700/50 rounded-lg p-4 flex items-center justify-between">
                            <div class="flex items-center">
                                <div class="w-3 h-3 rounded-full mr-3 {{ $node->status === 'online' ? 'bg-green-500' : 'bg-gray-500' }}"></div>
                                <div>
                                    <h3 class="font-medium">{{ $node->gpu_model ?? 'Unknown GPU' }}</h3>
                                    <p class="text-sm text-gray-400">{{ $node->gpu_vram_mb ? ($node->gpu_vram_mb / 1024) . ' GB VRAM' : 'N/A' }}</p>
                                </div>
                            </div>
                            <div class="text-right">
                                <p class="text-sm text-gray-400">Hashrate</p>
                                <p class="font-bold text-cyan-400">{{ number_format($node->hashrate, 1) }}</p>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <div class="text-center py-8 text-gray-400">
                        <i class="fas fa-server text-4xl mb-4 opacity-50"></i>
                        <p>No GPU nodes connected yet.</p>
                        <p class="text-sm">Download the client to get started!</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Sidebar -->
            <div class="space-y-8">
                <!-- Referral -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-users text-green-500 mr-2"></i>
                        Referral Program
                    </h2>
                    <p class="text-gray-400 text-sm mb-4">Invite friends and earn 5% of their earnings!</p>
                    <div class="bg-gray-700 rounded-lg p-3">
                        <p class="text-xs text-gray-400 mb-1">Your Referral Code</p>
                        <div class="flex items-center justify-between">
                            <code class="text-green-400 font-mono">{{ $user->referral_code ?? 'N/A' }}</code>
                            <button onclick="copyReferral()" class="text-gray-400 hover:text-white">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Recent Earnings -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-history text-yellow-500 mr-2"></i>
                        Recent Earnings
                    </h2>
                    @if($recentEarnings->count() > 0)
                    <div class="space-y-3">
                        @foreach($recentEarnings as $earning)
                        <div class="flex items-center justify-between py-2 border-b border-gray-700 last:border-0">
                            <div>
                                <p class="text-sm">{{ $earning->type }}</p>
                                <p class="text-xs text-gray-400">{{ $earning->created_at->diffForHumans() }}</p>
                            </div>
                            <span class="text-green-400 font-medium">+${{ number_format($earning->amount, 2) }}</span>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-gray-400 text-sm text-center py-4">No earnings yet.</p>
                    @endif
                </div>

                <!-- Request Payout -->
                <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
                    <h2 class="text-xl font-bold mb-4">
                        <i class="fas fa-money-bill-wave text-green-500 mr-2"></i>
                        Request Payout
                    </h2>
                    <p class="text-gray-400 text-sm mb-4">Minimum payout: $10.00</p>
                    @if($stats['balance'] >= 10)
                    <button class="w-full bg-green-600 hover:bg-green-700 py-3 rounded-lg font-medium">
                        Request Payout
                    </button>
                    @else
                    <button disabled class="w-full bg-gray-700 py-3 rounded-lg font-medium text-gray-500 cursor-not-allowed">
                        Insufficient Balance
                    </button>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <script>
        function copyToken() {
            const token = document.getElementById('api-token').textContent;
            navigator.clipboard.writeText(token);
            alert('Token copied to clipboard!');
        }

        function copyReferral() {
            const code = '{{ $user->referral_code ?? '' }}';
            const url = window.location.origin + '/register?ref=' + code;
            navigator.clipboard.writeText(url);
            alert('Referral link copied to clipboard!');
        }
    </script>
</body>
</html>
