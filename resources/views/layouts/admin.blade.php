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
</head>
<body class="bg-gray-900 text-gray-100">
    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 border-r border-gray-700">
            <div class="p-4 border-b border-gray-700">
                <h1 class="text-xl font-bold text-purple-400">
                    <i class="fas fa-microchip mr-2"></i>GPU Pool Admin
                </h1>
            </div>
            <nav class="p-4 space-y-2">
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
                <a href="{{ route('admin.ai-models') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.ai-models*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-brain w-5 mr-3"></i> AI Models
                </a>
                <a href="{{ route('admin.generations') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.generations*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-wand-magic-sparkles w-5 mr-3"></i> Generations
                </a>

                <!-- Referral Section -->
                <div class="pt-4 mt-4 border-t border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Referral System</p>
                </div>
                <a href="{{ route('admin.referrals') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.referrals') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-users-rays w-5 mr-3"></i> Referrals
                </a>
                <a href="{{ route('admin.referral-settings') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.referral-settings') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-percent w-5 mr-3"></i> Commission Settings
                </a>

                <!-- Client Section -->
                <div class="pt-4 mt-4 border-t border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Client</p>
                </div>
                <a href="{{ route('admin.client-versions') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.client-versions*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-download w-5 mr-3"></i> Client Versions
                </a>

                <!-- Settings Section -->
                <div class="pt-4 mt-4 border-t border-gray-700">
                    <p class="px-4 text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">System</p>
                </div>
                <a href="{{ route('admin.payouts') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.payouts*') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-wallet w-5 mr-3"></i> Payouts
                </a>
                <a href="{{ route('admin.settings') }}" class="flex items-center px-4 py-2 rounded-lg hover:bg-gray-700 {{ request()->routeIs('admin.settings') ? 'bg-gray-700 text-purple-400' : '' }}">
                    <i class="fas fa-cog w-5 mr-3"></i> Settings
                </a>
            </nav>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 overflow-auto">
            <!-- Top Bar -->
            <header class="bg-gray-800 border-b border-gray-700 px-6 py-4">
                <div class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold">@yield('header', 'Dashboard')</h2>
                    <div class="flex items-center space-x-4">
                        <span class="text-gray-400">{{ auth()->user()->name ?? 'Admin' }}</span>
                        <form action="{{ route('logout') ?? '#' }}" method="POST" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-white">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
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
        </main>
    </div>

    @stack('scripts')
</body>
</html>
