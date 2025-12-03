<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบ - GPU Share Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
        body { font-family: 'Inter', sans-serif; }

        .gradient-bg {
            background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
        }

        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glow-purple {
            box-shadow: 0 0 40px rgba(139, 92, 246, 0.3);
        }

        .gpu-icon {
            animation: pulse-glow 2s ease-in-out infinite;
        }

        @keyframes pulse-glow {
            0%, 100% { filter: drop-shadow(0 0 10px rgba(139, 92, 246, 0.5)); }
            50% { filter: drop-shadow(0 0 25px rgba(139, 92, 246, 0.8)); }
        }

        .float-animation {
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }

        .input-glow:focus {
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.3);
        }

        .btn-gradient {
            background: linear-gradient(135deg, #8B5CF6 0%, #6366F1 100%);
            transition: all 0.3s ease;
        }

        .btn-gradient:hover {
            background: linear-gradient(135deg, #9F7AEA 0%, #7C3AED 100%);
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(139, 92, 246, 0.4);
        }

        .particle {
            position: absolute;
            width: 4px;
            height: 4px;
            background: rgba(139, 92, 246, 0.5);
            border-radius: 50%;
            animation: particle-float 15s linear infinite;
        }

        @keyframes particle-float {
            0% { transform: translateY(100vh) rotate(0deg); opacity: 0; }
            10% { opacity: 1; }
            90% { opacity: 1; }
            100% { transform: translateY(-100vh) rotate(720deg); opacity: 0; }
        }
    </style>
</head>
<body class="gradient-bg text-white min-h-screen flex items-center justify-center p-4 relative overflow-hidden">
    <!-- Animated Particles Background -->
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        @for($i = 0; $i < 20; $i++)
        <div class="particle" style="left: {{ rand(0, 100) }}%; animation-delay: {{ $i * 0.5 }}s; animation-duration: {{ rand(10, 20) }}s;"></div>
        @endfor
    </div>

    <!-- Decorative Elements -->
    <div class="absolute top-20 left-20 w-72 h-72 bg-purple-600/20 rounded-full filter blur-3xl"></div>
    <div class="absolute bottom-20 right-20 w-96 h-96 bg-blue-600/20 rounded-full filter blur-3xl"></div>

    <div class="w-full max-w-5xl grid lg:grid-cols-2 gap-8 items-center relative z-10">
        <!-- Left Side - Branding -->
        <div class="hidden lg:block text-center lg:text-left">
            <div class="float-animation mb-8">
                <div class="inline-flex items-center justify-center w-32 h-32 rounded-3xl bg-gradient-to-br from-purple-600 to-indigo-600 glow-purple">
                    <i class="fas fa-microchip text-5xl gpu-icon"></i>
                </div>
            </div>

            <h1 class="text-4xl lg:text-5xl font-bold mb-4 leading-tight">
                <span class="bg-gradient-to-r from-purple-400 via-pink-400 to-indigo-400 bg-clip-text text-transparent">
                    GPU Share Platform
                </span>
            </h1>

            <p class="text-gray-400 text-lg mb-8 leading-relaxed">
                แชร์พลัง GPU ของคุณ สร้างรายได้แบบ Passive Income
                <br>เข้าร่วมเครือข่าย AI Computing ที่ใหญ่ที่สุดในประเทศไทย
            </p>

            <div class="grid grid-cols-3 gap-4">
                <div class="glass-card rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-purple-400">1000+</div>
                    <div class="text-gray-500 text-sm">GPU Nodes</div>
                </div>
                <div class="glass-card rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-green-400">฿50K+</div>
                    <div class="text-gray-500 text-sm">จ่ายไปแล้ว</div>
                </div>
                <div class="glass-card rounded-xl p-4 text-center">
                    <div class="text-2xl font-bold text-blue-400">24/7</div>
                    <div class="text-gray-500 text-sm">ออนไลน์</div>
                </div>
            </div>
        </div>

        <!-- Right Side - Login Form -->
        <div class="w-full max-w-md mx-auto lg:mx-0 lg:ml-auto">
            <!-- Mobile Logo -->
            <div class="lg:hidden text-center mb-8">
                <div class="inline-flex items-center text-3xl font-bold">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-purple-600 to-indigo-600 flex items-center justify-center mr-3">
                        <i class="fas fa-microchip text-xl"></i>
                    </div>
                    GPU Share
                </div>
            </div>

            <div class="glass-card rounded-3xl p-8 glow-purple">
                <div class="text-center mb-8">
                    <h2 class="text-2xl font-bold mb-2">ยินดีต้อนรับกลับ</h2>
                    <p class="text-gray-400">เข้าสู่ระบบเพื่อจัดการ GPU ของคุณ</p>
                </div>

                @if($errors->any())
                <div class="bg-red-500/20 border border-red-500/50 text-red-400 px-4 py-3 rounded-xl mb-6 flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    {{ $errors->first() }}
                </div>
                @endif

                @if(session('success'))
                <div class="bg-green-500/20 border border-green-500/50 text-green-400 px-4 py-3 rounded-xl mb-6 flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    {{ session('success') }}
                </div>
                @endif

                <form method="POST" action="/login">
                    @csrf
                    <div class="mb-5">
                        <label class="block text-gray-300 text-sm font-medium mb-2">
                            <i class="fas fa-envelope mr-2 text-purple-400"></i>อีเมล
                        </label>
                        <input type="email" name="email" value="{{ old('email') }}" required
                            class="w-full bg-gray-900/50 border border-gray-600/50 rounded-xl px-4 py-3.5 text-white placeholder-gray-500 focus:ring-0 focus:border-purple-500 input-glow transition-all duration-300"
                            placeholder="your@email.com">
                    </div>

                    <div class="mb-6">
                        <label class="block text-gray-300 text-sm font-medium mb-2">
                            <i class="fas fa-lock mr-2 text-purple-400"></i>รหัสผ่าน
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="password" required
                                class="w-full bg-gray-900/50 border border-gray-600/50 rounded-xl px-4 py-3.5 text-white placeholder-gray-500 focus:ring-0 focus:border-purple-500 input-glow transition-all duration-300 pr-12"
                                placeholder="••••••••">
                            <button type="button" onclick="togglePassword()" class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-500 hover:text-purple-400 transition-colors">
                                <i class="fas fa-eye" id="eye-icon"></i>
                            </button>
                        </div>
                    </div>

                    <div class="flex items-center justify-between mb-6">
                        <label class="flex items-center cursor-pointer">
                            <input type="checkbox" name="remember" class="w-5 h-5 rounded bg-gray-900/50 border-gray-600 text-purple-600 focus:ring-purple-500 focus:ring-offset-0 cursor-pointer">
                            <span class="text-gray-400 text-sm ml-2">จดจำฉัน</span>
                        </label>
                        <a href="#" class="text-purple-400 hover:text-purple-300 text-sm font-medium transition-colors">
                            ลืมรหัสผ่าน?
                        </a>
                    </div>

                    <button type="submit" class="w-full btn-gradient py-4 rounded-xl font-semibold text-lg shadow-lg">
                        <i class="fas fa-sign-in-alt mr-2"></i>เข้าสู่ระบบ
                    </button>
                </form>

                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-transparent text-gray-500">หรือ</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <button class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 border border-gray-700/50 transition-all duration-300">
                        <i class="fab fa-google text-red-400"></i>
                        <span class="text-sm">Google</span>
                    </button>
                    <button class="flex items-center justify-center gap-2 py-3 px-4 rounded-xl bg-gray-800/50 hover:bg-gray-700/50 border border-gray-700/50 transition-all duration-300">
                        <i class="fab fa-github"></i>
                        <span class="text-sm">GitHub</span>
                    </button>
                </div>

                <div class="mt-8 text-center">
                    <p class="text-gray-400">
                        ยังไม่มีบัญชี?
                        <a href="/register" class="text-purple-400 hover:text-purple-300 font-semibold ml-1 transition-colors">
                            สมัครสมาชิก
                        </a>
                    </p>
                </div>
            </div>

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <a href="/" class="inline-flex items-center text-gray-500 hover:text-gray-300 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i>กลับหน้าหลัก
                </a>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const eyeIcon = document.getElementById('eye-icon');

            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                eyeIcon.classList.remove('fa-eye');
                eyeIcon.classList.add('fa-eye-slash');
            } else {
                passwordInput.type = 'password';
                eyeIcon.classList.remove('fa-eye-slash');
                eyeIcon.classList.add('fa-eye');
            }
        }
    </script>
</body>
</html>
