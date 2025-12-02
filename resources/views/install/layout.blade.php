<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Installation') - GPU Share Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }

        body {
            background: linear-gradient(135deg, #0f0f1a 0%, #1a1a2e 50%, #16213e 100%);
            min-height: 100vh;
        }

        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .gradient-text {
            background: linear-gradient(135deg, #a855f7, #ec4899, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        /* Floating atoms */
        .atoms-bg {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }

        .atom {
            position: absolute;
            width: 4px;
            height: 4px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.5) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatAtom 15s infinite ease-in-out;
        }

        @keyframes floatAtom {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.2; }
            50% { transform: translateY(-150px) translateX(80px); opacity: 0.6; }
        }

        /* Progress bar animation */
        .progress-fill {
            transition: width 0.5s ease-out;
        }

        /* Input focus */
        .input-glass:focus {
            box-shadow: 0 0 0 2px rgba(168, 85, 247, 0.5);
        }
    </style>
</head>
<body class="text-white">
    <!-- Floating Atoms Background -->
    <div class="atoms-bg">
        @for($i = 0; $i < 30; $i++)
        <div class="atom" style="left: {{ rand(0, 100) }}%; top: {{ rand(0, 100) }}%; animation-delay: {{ $i * 0.5 }}s; animation-duration: {{ rand(15, 25) }}s;"></div>
        @endfor
    </div>

    <div class="relative z-10 min-h-screen flex items-center justify-center p-4">
        <div class="w-full max-w-2xl">
            <!-- Logo & Header -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-20 h-20 rounded-2xl bg-gradient-to-br from-purple-600 to-pink-600 mb-4 shadow-lg shadow-purple-500/30">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                </div>
                <h1 class="text-3xl font-bold gradient-text mb-2">GPU Share Platform</h1>
                <p class="text-gray-400">Installation Wizard</p>
            </div>

            <!-- Progress Bar -->
            @if(isset($step) && isset($totalSteps))
            <div class="mb-8">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-sm text-gray-400">Step {{ $step }} of {{ $totalSteps }}</span>
                    <span class="text-sm text-purple-400 font-medium">{{ round(($step / $totalSteps) * 100) }}%</span>
                </div>
                <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                    <div class="progress-fill h-full bg-gradient-to-r from-purple-600 to-pink-600 rounded-full" style="width: {{ ($step / $totalSteps) * 100 }}%"></div>
                </div>
                <!-- Step indicators -->
                <div class="flex justify-between mt-4">
                    @php
                        $stepLabels = ['Welcome', 'Requirements', 'Database', 'Admin', 'Settings', 'Complete'];
                    @endphp
                    @foreach($stepLabels as $index => $label)
                    <div class="flex flex-col items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium mb-1
                            {{ ($index + 1) < $step ? 'bg-green-500 text-white' : '' }}
                            {{ ($index + 1) == $step ? 'bg-purple-600 text-white' : '' }}
                            {{ ($index + 1) > $step ? 'bg-white/10 text-gray-500' : '' }}">
                            @if(($index + 1) < $step)
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                            </svg>
                            @else
                            {{ $index + 1 }}
                            @endif
                        </div>
                        <span class="text-xs text-gray-500 hidden md:block">{{ $label }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Main Card -->
            <div class="glass rounded-3xl p-8 shadow-xl">
                <!-- Alerts -->
                @if(session('success'))
                <div class="mb-6 p-4 rounded-xl bg-green-500/20 border border-green-500/30 text-green-400 flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('success') }}</span>
                </div>
                @endif

                @if(session('error'))
                <div class="mb-6 p-4 rounded-xl bg-red-500/20 border border-red-500/30 text-red-400 flex items-center gap-3">
                    <svg class="w-5 h-5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <span>{{ session('error') }}</span>
                </div>
                @endif

                @if($errors->any())
                <div class="mb-6 p-4 rounded-xl bg-red-500/20 border border-red-500/30 text-red-400">
                    <ul class="list-disc list-inside space-y-1">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
                @endif

                @yield('content')
            </div>

            <!-- Footer -->
            <div class="text-center mt-8">
                <p class="text-gray-500 text-sm">&copy; {{ date('Y') }} Xman Studio Thailand. All rights reserved.</p>
            </div>
        </div>
    </div>
</body>
</html>
