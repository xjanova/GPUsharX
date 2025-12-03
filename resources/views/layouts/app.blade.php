<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php
        $siteName = \App\Models\SiteSetting::get('site_name', config('app.name', 'GPU Share X'));
        $siteDescription = \App\Models\SiteSetting::get('site_description', 'แพลตฟอร์ม AI Generation แบบกระจาย');
        $siteFavicon = \App\Models\SiteSetting::get('site_favicon');
        $ogImage = \App\Models\SiteSetting::get('og_image');
        $metaKeywords = \App\Models\SiteSetting::get('meta_keywords');
    @endphp
    <title>@yield('title', $siteName) - {{ $siteName }}</title>
    <meta name="description" content="{{ $siteDescription }}">
    @if($metaKeywords)
    <meta name="keywords" content="{{ $metaKeywords }}">
    @endif

    <!-- Favicon -->
    @if($siteFavicon)
    <link rel="icon" href="{{ asset('storage/' . $siteFavicon) }}" type="image/png">
    <link rel="shortcut icon" href="{{ asset('storage/' . $siteFavicon) }}">
    @else
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    @endif

    <!-- Open Graph -->
    <meta property="og:title" content="@yield('title', $siteName)">
    <meta property="og:description" content="{{ $siteDescription }}">
    <meta property="og:type" content="website">
    @if($ogImage)
    <meta property="og:image" content="{{ asset('storage/' . $ogImage) }}">
    @endif

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');

        * { font-family: 'Inter', sans-serif; }

        /* Glass morphism */
        .glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.1) 0%, rgba(255, 255, 255, 0.05) 100%);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .glass-input {
            background: rgba(255, 255, 255, 0.08);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.15);
        }

        .glass-input:focus {
            border-color: rgba(168, 85, 247, 0.5);
            box-shadow: 0 0 20px rgba(168, 85, 247, 0.3);
        }

        /* Floating atoms animation */
        .atoms-container {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
            z-index: 0;
        }

        .atom {
            position: absolute;
            width: 6px;
            height: 6px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.8) 0%, rgba(168, 85, 247, 0) 70%);
            border-radius: 50%;
            animation: float 15s infinite ease-in-out;
        }

        .atom::before {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            background: rgba(168, 85, 247, 0.3);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0) translateX(0) rotate(0deg); opacity: 0.3; }
            25% { transform: translateY(-100px) translateX(50px) rotate(90deg); opacity: 0.6; }
            50% { transform: translateY(-200px) translateX(-30px) rotate(180deg); opacity: 0.4; }
            75% { transform: translateY(-100px) translateX(-50px) rotate(270deg); opacity: 0.7; }
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); opacity: 0.5; }
            50% { transform: scale(2); opacity: 0.2; }
        }

        /* Gradient text */
        .gradient-text {
            background: linear-gradient(135deg, #a855f7, #ec4899, #3b82f6);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Glow effects */
        .glow-purple {
            box-shadow: 0 0 30px rgba(168, 85, 247, 0.4), 0 0 60px rgba(168, 85, 247, 0.2);
        }

        .glow-green {
            box-shadow: 0 0 30px rgba(34, 197, 94, 0.4);
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: rgba(0, 0, 0, 0.2); }
        ::-webkit-scrollbar-thumb { background: rgba(168, 85, 247, 0.5); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: rgba(168, 85, 247, 0.7); }

        /* Progress bar animation */
        .progress-animated {
            background: linear-gradient(90deg, #a855f7, #ec4899, #3b82f6, #a855f7);
            background-size: 300% 100%;
            animation: progressGradient 2s linear infinite;
        }

        @keyframes progressGradient {
            0% { background-position: 0% 0%; }
            100% { background-position: 300% 0%; }
        }

        /* Card hover effect */
        .card-hover {
            transition: all 0.3s ease;
        }

        .card-hover:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(168, 85, 247, 0.3);
        }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-950 text-white min-h-screen">
    <!-- Floating Atoms Background -->
    <div class="atoms-container" id="atoms"></div>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass border-b border-gray-800/50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                @php $siteLogo = \App\Models\SiteSetting::get('site_logo'); @endphp
                <div class="flex items-center space-x-8">
                    <a href="/" class="flex items-center">
                        @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-8 w-auto mr-3">
                        @else
                        <i class="fas fa-microchip text-purple-500 text-2xl mr-3"></i>
                        @endif
                        <span class="text-xl font-bold gradient-text">{{ $siteName }}</span>
                    </a>
                    <div class="hidden md:flex space-x-6">
                        <a href="{{ route('generate') }}" class="text-gray-300 hover:text-white transition flex items-center">
                            <i class="fas fa-wand-magic-sparkles mr-2"></i>Generate
                        </a>
                        <a href="{{ route('models') }}" class="text-gray-300 hover:text-white transition flex items-center">
                            <i class="fas fa-cube mr-2"></i>Models
                        </a>
                        <a href="{{ route('gallery') }}" class="text-gray-300 hover:text-white transition flex items-center">
                            <i class="fas fa-images mr-2"></i>Gallery
                        </a>
                        @auth
                        <a href="{{ route('referral') }}" class="text-gray-300 hover:text-white transition flex items-center">
                            <i class="fas fa-users mr-2"></i>Referral
                        </a>
                        @endauth
                        <a href="{{ route('download') }}" class="text-gray-300 hover:text-white transition flex items-center">
                            <i class="fas fa-download mr-2"></i>Download
                        </a>
                    </div>
                </div>
                <div class="flex items-center space-x-4">
                    @auth
                        <a href="{{ route('dashboard') }}" class="text-gray-300 hover:text-white">
                            <i class="fas fa-chart-line mr-2"></i>Dashboard
                        </a>
                        <a href="{{ route('wallet.index') }}" class="flex items-center glass-card px-4 py-2 rounded-full hover:bg-white/10 transition">
                            <i class="fas fa-wallet text-purple-400 mr-2"></i>
                            <span class="text-green-400 font-medium">${{ number_format(auth()->user()->balance, 2) }}</span>
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="inline">
                            @csrf
                            <button type="submit" class="text-gray-400 hover:text-red-400 transition">
                                <i class="fas fa-sign-out-alt"></i>
                            </button>
                        </form>
                    @else
                        <a href="{{ route('login') }}" class="text-gray-300 hover:text-white px-4 py-2">Login</a>
                        <a href="{{ route('register') }}" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg font-medium transition glow-purple">
                            Get Started
                        </a>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="relative z-10 pt-20">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="relative z-10 mt-20 border-t border-gray-800/50 glass">
        <div class="max-w-7xl mx-auto px-4 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        @if($siteLogo)
                        <img src="{{ asset('storage/' . $siteLogo) }}" alt="{{ $siteName }}" class="h-8 w-auto mr-3">
                        @else
                        <i class="fas fa-microchip text-purple-500 text-2xl mr-3"></i>
                        @endif
                        <span class="text-xl font-bold">{{ $siteName }}</span>
                    </div>
                    <p class="text-gray-400 text-sm">
                        {{ \App\Models\SiteSetting::get('site_tagline', 'Share your GPU power, generate AI content, and earn rewards.') }}
                    </p>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Platform</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="{{ route('generate') }}" class="hover:text-white transition">Generate</a></li>
                        <li><a href="{{ route('models') }}" class="hover:text-white transition">AI Models</a></li>
                        <li><a href="{{ route('gallery') }}" class="hover:text-white transition">Gallery</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Earn</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="/download" class="hover:text-white transition">Download Client</a></li>
                        <li><a href="{{ route('referral') }}" class="hover:text-white transition">Referral Program</a></li>
                        <li><a href="/leaderboard" class="hover:text-white transition">Leaderboard</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="font-semibold mb-4">Legal</h4>
                    <ul class="space-y-2 text-gray-400 text-sm">
                        <li><a href="/terms" class="hover:text-white transition">Terms of Service</a></li>
                        <li><a href="/privacy" class="hover:text-white transition">Privacy Policy</a></li>
                    </ul>
                </div>
            </div>
            @php
                $footerText = \App\Models\SiteSetting::get('footer_text', '© ' . date('Y') . ' ' . $siteName . '. All rights reserved.');
                $facebookUrl = \App\Models\SiteSetting::get('facebook_url');
                $twitterUrl = \App\Models\SiteSetting::get('twitter_url');
                $discordUrl = \App\Models\SiteSetting::get('discord_url');
                $githubUrl = \App\Models\SiteSetting::get('github_url');
            @endphp
            <div class="mt-12 pt-8 border-t border-gray-800/50 flex flex-col md:flex-row justify-between items-center">
                <p class="text-gray-500 text-sm">
                    {{ $footerText }}
                </p>
                <div class="flex space-x-4 mt-4 md:mt-0">
                    @if($facebookUrl)
                    <a href="{{ $facebookUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-purple-400 transition">
                        <i class="fab fa-facebook text-xl"></i>
                    </a>
                    @endif
                    @if($twitterUrl)
                    <a href="{{ $twitterUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-purple-400 transition">
                        <i class="fab fa-twitter text-xl"></i>
                    </a>
                    @endif
                    @if($discordUrl)
                    <a href="{{ $discordUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-purple-400 transition">
                        <i class="fab fa-discord text-xl"></i>
                    </a>
                    @endif
                    @if($githubUrl)
                    <a href="{{ $githubUrl }}" target="_blank" rel="noopener" class="text-gray-500 hover:text-purple-400 transition">
                        <i class="fab fa-github text-xl"></i>
                    </a>
                    @endif
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Generate floating atoms
        function createAtoms() {
            const container = document.getElementById('atoms');
            const atomCount = 50;

            for (let i = 0; i < atomCount; i++) {
                const atom = document.createElement('div');
                atom.className = 'atom';
                atom.style.left = Math.random() * 100 + '%';
                atom.style.top = Math.random() * 100 + '%';
                atom.style.animationDelay = Math.random() * 15 + 's';
                atom.style.animationDuration = (10 + Math.random() * 10) + 's';
                atom.style.width = (4 + Math.random() * 6) + 'px';
                atom.style.height = atom.style.width;
                container.appendChild(atom);
            }
        }
        createAtoms();
    </script>
    @stack('scripts')
</body>
</html>
