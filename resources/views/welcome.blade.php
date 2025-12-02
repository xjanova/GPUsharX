<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPU Share - Share Your GPU Power, Earn Daily Rewards | Xman Studio Thailand</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap');
        * { font-family: 'Inter', sans-serif; }

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

        .glow-purple { box-shadow: 0 0 40px rgba(168, 85, 247, 0.4); }

        /* Atoms Animation */
        .atoms-bg {
            position: fixed;
            inset: 0;
            overflow: hidden;
            z-index: 0;
            pointer-events: none;
        }
        .atom {
            position: absolute;
            width: 6px;
            height: 6px;
            background: radial-gradient(circle, rgba(168, 85, 247, 0.6) 0%, transparent 70%);
            border-radius: 50%;
            animation: floatAtom 20s infinite ease-in-out;
        }
        @keyframes floatAtom {
            0%, 100% { transform: translateY(0) translateX(0); opacity: 0.3; }
            50% { transform: translateY(-200px) translateX(100px); opacity: 0.8; }
        }

        /* Orbit Animation */
        .orbit {
            animation: orbit 20s linear infinite;
        }
        @keyframes orbit {
            from { transform: rotate(0deg) translateX(150px) rotate(0deg); }
            to { transform: rotate(360deg) translateX(150px) rotate(-360deg); }
        }

        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 60px rgba(168, 85, 247, 0.3);
        }

        /* Counter Animation */
        .counter {
            display: inline-block;
        }
    </style>
</head>
<body class="bg-gray-950 text-white overflow-x-hidden">
    <!-- Atoms Background -->
    <div class="atoms-bg" id="atoms"></div>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16 items-center">
                <div class="flex items-center">
                    <i class="fas fa-microchip text-purple-500 text-2xl mr-3"></i>
                    <span class="text-xl font-bold gradient-text">GPU Share</span>
                </div>
                <div class="hidden md:flex items-center space-x-8">
                    <a href="/generate" class="text-gray-300 hover:text-white transition">Generate</a>
                    <a href="/models" class="text-gray-300 hover:text-white transition">Models</a>
                    <a href="/gallery" class="text-gray-300 hover:text-white transition">Gallery</a>
                    <a href="#earnings" class="text-gray-300 hover:text-white transition">Earn</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="/login" class="text-gray-300 hover:text-white px-4 py-2">Login</a>
                    <a href="/register" class="bg-purple-600 hover:bg-purple-700 px-6 py-2 rounded-lg font-medium transition glow-purple">
                        Get Started
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="relative min-h-screen flex items-center justify-center pt-16 overflow-hidden">
        <!-- Animated GPU Icon -->
        <div class="absolute w-[400px] h-[400px] opacity-20">
            <div class="absolute inset-0 flex items-center justify-center">
                <i class="fas fa-microchip text-[200px] text-purple-500"></i>
            </div>
            <div class="orbit absolute w-4 h-4 bg-purple-500 rounded-full"></div>
            <div class="orbit absolute w-3 h-3 bg-pink-500 rounded-full" style="animation-delay: -5s;"></div>
            <div class="orbit absolute w-2 h-2 bg-blue-500 rounded-full" style="animation-delay: -10s;"></div>
        </div>

        <div class="relative z-10 text-center px-4 max-w-5xl mx-auto">
            <div class="inline-flex items-center px-4 py-2 rounded-full glass text-sm text-purple-300 mb-6">
                <i class="fas fa-bolt text-yellow-400 mr-2"></i>
                Powered by Distributed GPU Computing
            </div>

            <h1 class="text-5xl md:text-7xl font-extrabold mb-6 leading-tight">
                Share Your <span class="gradient-text">GPU Power</span><br>
                Earn <span class="text-green-400">Daily Rewards</span>
            </h1>

            <p class="text-xl text-gray-400 mb-10 max-w-3xl mx-auto">
                Join our GPU Pool to generate AI images and videos. Get paid based on your GPU power contribution - like crypto mining but for AI content creation!
            </p>

            <div class="flex flex-col sm:flex-row gap-4 justify-center mb-12">
                <a href="/register" class="bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 px-8 py-4 rounded-xl text-lg font-bold inline-flex items-center justify-center glow-purple transition">
                    <i class="fas fa-rocket mr-3"></i>Start Earning Now
                </a>
                <a href="/download" class="glass px-8 py-4 rounded-xl text-lg font-medium inline-flex items-center justify-center hover:bg-white/10 transition">
                    <i class="fas fa-download mr-3"></i>Download Client
                </a>
            </div>

            <!-- Live Stats -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 max-w-4xl mx-auto">
                <div class="glass rounded-2xl p-6 card-hover">
                    <div class="text-3xl font-bold text-purple-400 mb-1">
                        <span class="counter" data-target="1247">0</span>+
                    </div>
                    <div class="text-gray-400 text-sm">Active GPUs</div>
                </div>
                <div class="glass rounded-2xl p-6 card-hover">
                    <div class="text-3xl font-bold text-green-400 mb-1">
                        $<span class="counter" data-target="52847">0</span>
                    </div>
                    <div class="text-gray-400 text-sm">Paid to Users</div>
                </div>
                <div class="glass rounded-2xl p-6 card-hover">
                    <div class="text-3xl font-bold text-blue-400 mb-1">
                        <span class="counter" data-target="8934">0</span>
                    </div>
                    <div class="text-gray-400 text-sm">Members</div>
                </div>
                <div class="glass rounded-2xl p-6 card-hover">
                    <div class="text-3xl font-bold text-pink-400 mb-1">
                        <span class="counter" data-target="156">0</span>K
                    </div>
                    <div class="text-gray-400 text-sm">Images Generated</div>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-8 left-1/2 -translate-x-1/2 animate-bounce">
            <i class="fas fa-chevron-down text-2xl text-gray-600"></i>
        </div>
    </section>

    <!-- AI Generation Section -->
    <section class="relative py-24 px-4">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">
                    <span class="gradient-text">AI Art Generation</span>
                </h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                    Create stunning images and videos with state-of-the-art AI models
                </p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 items-center">
                <!-- Preview Grid -->
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div class="glass rounded-2xl overflow-hidden card-hover aspect-square">
                            <div class="w-full h-full bg-gradient-to-br from-purple-600/30 to-pink-600/30 flex items-center justify-center">
                                <i class="fas fa-image text-6xl text-purple-400/50"></i>
                            </div>
                        </div>
                        <div class="glass rounded-2xl overflow-hidden card-hover aspect-video">
                            <div class="w-full h-full bg-gradient-to-br from-blue-600/30 to-cyan-600/30 flex items-center justify-center">
                                <i class="fas fa-video text-4xl text-blue-400/50"></i>
                            </div>
                        </div>
                    </div>
                    <div class="space-y-4 mt-8">
                        <div class="glass rounded-2xl overflow-hidden card-hover aspect-video">
                            <div class="w-full h-full bg-gradient-to-br from-green-600/30 to-emerald-600/30 flex items-center justify-center">
                                <i class="fas fa-wand-magic-sparkles text-4xl text-green-400/50"></i>
                            </div>
                        </div>
                        <div class="glass rounded-2xl overflow-hidden card-hover aspect-square">
                            <div class="w-full h-full bg-gradient-to-br from-orange-600/30 to-yellow-600/30 flex items-center justify-center">
                                <i class="fas fa-fire text-6xl text-orange-400/50"></i>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Features -->
                <div class="space-y-6">
                    <div class="glass rounded-2xl p-6 card-hover">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-purple-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-bolt text-purple-400 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2">Lightning Fast</h3>
                                <p class="text-gray-400">Generate images in seconds using distributed GPU power from our global network.</p>
                            </div>
                        </div>
                    </div>
                    <div class="glass rounded-2xl p-6 card-hover">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-cube text-blue-400 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2">Multiple Models</h3>
                                <p class="text-gray-400">Access SDXL, FLUX, AnimateDiff, CogVideoX and more from HuggingFace.</p>
                            </div>
                        </div>
                    </div>
                    <div class="glass rounded-2xl p-6 card-hover">
                        <div class="flex items-start gap-4">
                            <div class="w-12 h-12 rounded-xl bg-green-500/20 flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-video text-green-400 text-xl"></i>
                            </div>
                            <div>
                                <h3 class="text-xl font-bold mb-2">Video Generation</h3>
                                <p class="text-gray-400">Create stunning AI videos with AnimateDiff and Stable Video Diffusion.</p>
                            </div>
                        </div>
                    </div>
                    <a href="/generate" class="inline-block w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 py-4 rounded-xl font-bold text-center transition glow-purple">
                        <i class="fas fa-wand-magic-sparkles mr-2"></i>Try Generation Now
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Earnings Section -->
    <section id="earnings" class="relative py-24 px-4 bg-gradient-to-b from-transparent to-purple-950/20">
        <div class="max-w-7xl mx-auto">
            <div class="text-center mb-16">
                <h2 class="text-4xl font-bold mb-4">
                    <span class="gradient-text">Earn While You Sleep</span>
                </h2>
                <p class="text-gray-400 text-lg max-w-2xl mx-auto">
                    Share your GPU power and get paid daily based on your contribution
                </p>
            </div>

            <!-- Earnings Showcase -->
            <div class="glass rounded-3xl p-8 mb-12">
                <h3 class="text-xl font-bold mb-6 flex items-center">
                    <i class="fas fa-trophy text-yellow-400 mr-3"></i>
                    Top Earners This Month
                </h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="glass rounded-2xl p-6 text-center border border-yellow-500/30">
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-yellow-400 to-orange-500 flex items-center justify-center">
                            <i class="fas fa-crown text-3xl text-white"></i>
                        </div>
                        <p class="font-bold text-lg mb-1">CryptoKing_TH</p>
                        <p class="text-3xl font-bold text-green-400">$2,847</p>
                        <p class="text-gray-500 text-sm mt-1">RTX 4090 x 4</p>
                    </div>
                    <div class="glass rounded-2xl p-6 text-center border border-gray-500/30">
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-gray-300 to-gray-500 flex items-center justify-center">
                            <i class="fas fa-medal text-3xl text-white"></i>
                        </div>
                        <p class="font-bold text-lg mb-1">GPUmaster99</p>
                        <p class="text-3xl font-bold text-green-400">$1,923</p>
                        <p class="text-gray-500 text-sm mt-1">RTX 3090 x 2</p>
                    </div>
                    <div class="glass rounded-2xl p-6 text-center border border-orange-700/30">
                        <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-orange-600 to-orange-800 flex items-center justify-center">
                            <i class="fas fa-award text-3xl text-white"></i>
                        </div>
                        <p class="font-bold text-lg mb-1">AIrenderer</p>
                        <p class="text-3xl font-bold text-green-400">$1,456</p>
                        <p class="text-gray-500 text-sm mt-1">RTX 4080</p>
                    </div>
                </div>
            </div>

            <!-- How It Works -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-purple-500/20 flex items-center justify-center">
                        <span class="text-2xl font-bold text-purple-400">1</span>
                    </div>
                    <h4 class="font-bold mb-2">Register</h4>
                    <p class="text-gray-500 text-sm">Create your free account in seconds</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-blue-500/20 flex items-center justify-center">
                        <span class="text-2xl font-bold text-blue-400">2</span>
                    </div>
                    <h4 class="font-bold mb-2">Install Client</h4>
                    <p class="text-gray-500 text-sm">Download and run our Windows client</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-green-500/20 flex items-center justify-center">
                        <span class="text-2xl font-bold text-green-400">3</span>
                    </div>
                    <h4 class="font-bold mb-2">Share GPU</h4>
                    <p class="text-gray-500 text-sm">Let your GPU process AI jobs automatically</p>
                </div>
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto mb-4 rounded-2xl bg-yellow-500/20 flex items-center justify-center">
                        <span class="text-2xl font-bold text-yellow-400">4</span>
                    </div>
                    <h4 class="font-bold mb-2">Get Paid</h4>
                    <p class="text-gray-500 text-sm">Earn credits and withdraw daily</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Referral Section -->
    <section class="relative py-24 px-4">
        <div class="max-w-5xl mx-auto">
            <div class="glass rounded-3xl p-8 md:p-12 text-center">
                <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-gradient-to-br from-purple-500 to-pink-500 flex items-center justify-center">
                    <i class="fas fa-users text-3xl"></i>
                </div>
                <h2 class="text-3xl font-bold mb-4">
                    <span class="gradient-text">Referral Program</span>
                </h2>
                <p class="text-gray-400 text-lg mb-8 max-w-2xl mx-auto">
                    Invite friends and earn up to <span class="text-green-400 font-bold">5%</span> commission on their earnings - forever!
                    Up to 3 levels deep.
                </p>
                <div class="grid grid-cols-3 gap-4 max-w-lg mx-auto mb-8">
                    <div class="glass rounded-xl p-4">
                        <p class="text-2xl font-bold text-purple-400">5%</p>
                        <p class="text-xs text-gray-500">Level 1</p>
                    </div>
                    <div class="glass rounded-xl p-4">
                        <p class="text-2xl font-bold text-blue-400">2%</p>
                        <p class="text-xs text-gray-500">Level 2</p>
                    </div>
                    <div class="glass rounded-xl p-4">
                        <p class="text-2xl font-bold text-green-400">1%</p>
                        <p class="text-xs text-gray-500">Level 3</p>
                    </div>
                </div>
                <a href="/register" class="inline-block bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 px-8 py-4 rounded-xl font-bold transition glow-purple">
                    <i class="fas fa-rocket mr-2"></i>Join & Start Earning
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="relative py-12 px-4 border-t border-gray-800/50">
        <div class="max-w-7xl mx-auto">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="flex items-center mb-4 md:mb-0">
                    <i class="fas fa-microchip text-purple-500 text-xl mr-2"></i>
                    <span class="font-bold">GPU Share</span>
                </div>
                <div class="text-center md:text-right">
                    <p class="text-gray-500 text-sm">
                        © 2025 <span class="text-purple-400">Xman Studio Thailand</span>. All rights reserved.
                    </p>
                </div>
            </div>
        </div>
    </footer>

    <script>
        // Create atoms
        const atomsContainer = document.getElementById('atoms');
        for (let i = 0; i < 50; i++) {
            const atom = document.createElement('div');
            atom.className = 'atom';
            atom.style.left = Math.random() * 100 + '%';
            atom.style.top = Math.random() * 100 + '%';
            atom.style.animationDelay = Math.random() * 20 + 's';
            atom.style.animationDuration = (15 + Math.random() * 10) + 's';
            atomsContainer.appendChild(atom);
        }

        // Counter animation
        const counters = document.querySelectorAll('.counter');
        const speed = 200;

        const animateCounter = (counter) => {
            const target = +counter.getAttribute('data-target');
            const inc = target / speed;
            let count = 0;

            const updateCount = () => {
                count += inc;
                if (count < target) {
                    counter.innerText = Math.ceil(count).toLocaleString();
                    requestAnimationFrame(updateCount);
                } else {
                    counter.innerText = target.toLocaleString();
                }
            };
            updateCount();
        };

        // Intersection Observer for counters
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounter(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        counters.forEach(counter => observer.observe(counter));
    </script>
</body>
</html>
