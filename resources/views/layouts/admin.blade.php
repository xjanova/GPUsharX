<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin Dashboard') - GPU Sharing Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Custom scrollbar for sidebar */
        .sidebar-scroll::-webkit-scrollbar {
            width: 4px;
        }
        .sidebar-scroll::-webkit-scrollbar-track {
            background: transparent;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb {
            background: #4b5563;
            border-radius: 2px;
        }
        .sidebar-scroll::-webkit-scrollbar-thumb:hover {
            background: #6b7280;
        }
    </style>
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 border-r border-gray-700 flex flex-col">
            <!-- Logo Header - Fixed -->
            <div class="p-4 border-b border-gray-700 flex-shrink-0">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center">
                    <h1 class="text-xl font-bold text-purple-400">
                        <i class="fas fa-microchip mr-2"></i>GPU Pool Admin
                    </h1>
                </a>
            </div>

            <!-- Navigation - Scrollable -->
            <nav class="flex-1 overflow-y-auto sidebar-scroll p-4 space-y-2">
                <a href="{{ route('admin.dashboard') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.dashboard') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-chart-line w-5 mr-3"></i> Dashboard
                </a>
                <a href="{{ route('admin.users') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.users*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-users w-5 mr-3"></i> Users
                </a>
                <a href="{{ route('admin.nodes') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.nodes*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-server w-5 mr-3"></i> GPU Nodes
                </a>
                <a href="{{ route('admin.jobs') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.jobs*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-tasks w-5 mr-3"></i> Jobs
                </a>

                <!-- AI Section -->
                <div class="pt-4 mt-4 border-t border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">AI Generation</p>
                </div>
                <a href="{{ route('admin.ai-models') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.ai-models') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-brain w-5 mr-3"></i> AI Models
                </a>
                <a href="{{ route('admin.model-store') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.model-store') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-store w-5 mr-3"></i> Model Store
                </a>
                <a href="{{ route('admin.generations') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.generations*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-wand-magic-sparkles w-5 mr-3"></i> Generations
                </a>
                <a href="{{ route('admin.analytics') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.analytics') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-chart-pie w-5 mr-3"></i> Analytics
                </a>
                <a href="{{ route('admin.playground') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.playground*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-flask w-5 mr-3"></i> Playground
                </a>

                <!-- Referral Section -->
                <div class="pt-4 mt-4 border-t border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Referral System</p>
                </div>
                <a href="{{ route('admin.referrals') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.referrals') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-users-rays w-5 mr-3"></i> Referrals
                </a>
                <a href="{{ route('admin.referral-tree') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.referral-tree') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-sitemap w-5 mr-3"></i> Referral Tree
                </a>
                <a href="{{ route('admin.referral-settings') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.referral-settings') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-percent w-5 mr-3"></i> Commission Settings
                </a>

                <!-- Client Section -->
                <div class="pt-4 mt-4 border-t border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Workers</p>
                </div>
                <a href="{{ route('admin.client-versions') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.client-versions*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-download w-5 mr-3"></i> Client Versions
                </a>
                <a href="{{ route('admin.vram') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.vram*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-memory w-5 mr-3"></i> VRAM Management
                </a>

                <!-- Settings Section -->
                <div class="pt-4 mt-4 border-t border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">System</p>
                </div>
                <a href="{{ route('admin.packages') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.packages*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-box w-5 mr-3"></i> Packages
                </a>
                <a href="{{ route('admin.credit-history') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.credit-history*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-coins w-5 mr-3"></i> Credit History
                </a>
                <a href="{{ route('admin.payouts') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.payouts*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-wallet w-5 mr-3"></i> Payouts
                </a>
                <a href="{{ route('admin.kyc.index') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.kyc*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-id-card w-5 mr-3"></i> KYC Verification
                </a>
                <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.settings') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-cog w-5 mr-3"></i> Site Settings
                </a>
                <a href="{{ route('admin.payment-settings') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.payment-settings') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-credit-card w-5 mr-3"></i> Payment Settings
                </a>
                <a href="{{ route('admin.manual-payments') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.manual-payments*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-receipt w-5 mr-3"></i> Manual Payments
                    @php $pendingPayments = \App\Models\ManualPayment::where('status', 'pending')->count(); @endphp
                    @if($pendingPayments > 0)
                    <span class="ml-auto px-2 py-0.5 bg-yellow-500 text-gray-900 text-xs font-bold rounded-full">{{ $pendingPayments }}</span>
                    @endif
                </a>
            </nav>

            <!-- Footer - Fixed -->
            <div class="p-4 border-t border-gray-700 flex-shrink-0">
                <div class="flex items-center justify-between text-sm">
                    <a href="/" class="text-gray-400 hover:text-white flex items-center gap-2" target="_blank">
                        <i class="fas fa-external-link-alt"></i> View Site
                    </a>
                    <span class="text-gray-600 text-xs">v1.0</span>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 flex flex-col overflow-hidden">
            <!-- Top Bar -->
            <header class="bg-gray-800 border-b border-gray-700 px-6 py-4 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">@yield('header', 'Dashboard')</h2>
                    <div class="flex items-center space-x-4">
                        <a href="/" class="text-gray-400 hover:text-white" title="View Site" target="_blank">
                            <i class="fas fa-globe"></i>
                        </a>
                        <span class="text-gray-400">{{ auth()->user()->name ?? 'Admin' }}</span>
                        <form action="{{ route('logout') ?? '#' }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-white" title="Logout">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content - Scrollable -->
            <div class="flex-1 overflow-y-auto">
                <div class="p-6">
                    @if(session('success'))
                    <div class="bg-green-600/20 border border-green-500 text-green-400 px-4 py-3 rounded-lg mb-6">
                        {{ session('success') }}
                    </div>
                    @endif

                    @if(session('error'))
                    <div class="bg-red-600/20 border border-red-500 text-red-400 px-4 py-3 rounded-lg mb-6">
                        {{ session('error') }}
                    </div>
                    @endif

                    @yield('content')
                </div>

                <!-- Admin Footer -->
                <footer class="border-t border-gray-800 bg-gray-900/50 px-6 py-4 mt-auto">
                    <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                        <div class="flex items-center gap-4 text-sm text-gray-500">
                            <span>&copy; {{ date('Y') }} GPU Sharing Platform</span>
                            <span class="hidden sm:inline">|</span>
                            <span class="text-purple-400">Admin Panel</span>
                        </div>
                        <div class="flex items-center gap-4 text-sm">
                            <a href="/" class="text-gray-400 hover:text-white" target="_blank">
                                <i class="fas fa-home mr-1"></i> Home
                            </a>
                            <a href="/docs" class="text-gray-400 hover:text-white" target="_blank">
                                <i class="fas fa-book mr-1"></i> Docs
                            </a>
                            <a href="https://github.com" class="text-gray-400 hover:text-white" target="_blank">
                                <i class="fab fa-github mr-1"></i> GitHub
                            </a>
                        </div>
                    </div>
                </footer>
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
