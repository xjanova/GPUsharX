<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>GPU Sharing Platform - Investment Pitch</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Prompt', sans-serif; }
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .gradient-text {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .card-hover {
            transition: all 0.3s ease;
        }
        .card-hover:hover {
            transform: translateY(-10px);
            box-shadow: 0 25px 50px -12px rgba(102, 126, 234, 0.25);
        }
        .animate-float {
            animation: float 6s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .animate-pulse-slow {
            animation: pulse 3s ease-in-out infinite;
        }
        .scroll-section {
            scroll-margin-top: 80px;
        }
        .stat-counter {
            transition: all 0.5s ease;
        }
        .glass-card {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-gray-900 text-white">
    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 bg-gray-900/90 backdrop-blur-lg border-b border-gray-800">
        <div class="max-w-7xl mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                        <i class="fas fa-microchip text-white"></i>
                    </div>
                    <span class="text-xl font-bold">GPU Share</span>
                </div>
                <div class="hidden md:flex items-center gap-8">
                    <a href="#problem" class="text-gray-400 hover:text-white transition">Problem</a>
                    <a href="#solution" class="text-gray-400 hover:text-white transition">Solution</a>
                    <a href="#features" class="text-gray-400 hover:text-white transition">Features</a>
                    <a href="#technology" class="text-gray-400 hover:text-white transition">Technology</a>
                    <a href="#business" class="text-gray-400 hover:text-white transition">Business Model</a>
                    <a href="#roadmap" class="text-gray-400 hover:text-white transition">Roadmap</a>
                    <a href="#team" class="text-gray-400 hover:text-white transition">Team</a>
                    <a href="#contact" class="gradient-bg px-6 py-2 rounded-full font-medium hover:opacity-90 transition">Contact Us</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="min-h-screen flex items-center justify-center relative overflow-hidden pt-20">
        <!-- Background Effects -->
        <div class="absolute inset-0">
            <div class="absolute top-20 left-10 w-72 h-72 bg-purple-500/30 rounded-full blur-3xl animate-pulse-slow"></div>
            <div class="absolute bottom-20 right-10 w-96 h-96 bg-blue-500/20 rounded-full blur-3xl animate-pulse-slow" style="animation-delay: 1s;"></div>
            <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[600px] bg-indigo-500/10 rounded-full blur-3xl"></div>
        </div>

        <div class="max-w-7xl mx-auto px-6 py-20 relative z-10">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div>
                    <div class="inline-flex items-center gap-2 bg-purple-500/20 border border-purple-500/30 rounded-full px-4 py-2 mb-6">
                        <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                        <span class="text-sm text-purple-300">Now Live - Beta Version</span>
                    </div>
                    <h1 class="text-5xl lg:text-7xl font-bold mb-6 leading-tight">
                        <span class="gradient-text">GPU Sharing</span><br>
                        <span class="text-white">Platform</span>
                    </h1>
                    <p class="text-xl text-gray-400 mb-8 leading-relaxed">
                        แพลตฟอร์มแบ่งปันพลังประมวลผล GPU แห่งแรกของไทย
                        สร้างรายได้จาก GPU ที่ไม่ได้ใช้งาน เปิดโอกาสให้ทุกคนเข้าถึง AI Generation
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <a href="#solution" class="gradient-bg px-8 py-4 rounded-xl font-semibold text-lg hover:opacity-90 transition inline-flex items-center gap-2">
                            <span>เริ่มต้นเลย</span>
                            <i class="fas fa-arrow-right"></i>
                        </a>
                        <a href="#demo" class="bg-white/10 border border-white/20 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white/20 transition inline-flex items-center gap-2">
                            <i class="fas fa-play-circle"></i>
                            <span>ดู Demo</span>
                        </a>
                    </div>
                </div>
                <div class="relative">
                    <div class="animate-float">
                        <div class="glass-card rounded-3xl p-8">
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-gradient-to-br from-purple-600/20 to-purple-800/20 rounded-2xl p-6 text-center">
                                    <i class="fas fa-server text-4xl text-purple-400 mb-3"></i>
                                    <p class="text-3xl font-bold">500+</p>
                                    <p class="text-gray-400 text-sm">GPU Nodes</p>
                                </div>
                                <div class="bg-gradient-to-br from-blue-600/20 to-blue-800/20 rounded-2xl p-6 text-center">
                                    <i class="fas fa-users text-4xl text-blue-400 mb-3"></i>
                                    <p class="text-3xl font-bold">10K+</p>
                                    <p class="text-gray-400 text-sm">Users</p>
                                </div>
                                <div class="bg-gradient-to-br from-green-600/20 to-green-800/20 rounded-2xl p-6 text-center">
                                    <i class="fas fa-image text-4xl text-green-400 mb-3"></i>
                                    <p class="text-3xl font-bold">1M+</p>
                                    <p class="text-gray-400 text-sm">Images Generated</p>
                                </div>
                                <div class="bg-gradient-to-br from-orange-600/20 to-orange-800/20 rounded-2xl p-6 text-center">
                                    <i class="fas fa-coins text-4xl text-orange-400 mb-3"></i>
                                    <p class="text-3xl font-bold">฿5M+</p>
                                    <p class="text-gray-400 text-sm">Revenue</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scroll Indicator -->
        <div class="absolute bottom-10 left-1/2 -translate-x-1/2 animate-bounce">
            <i class="fas fa-chevron-down text-2xl text-gray-500"></i>
        </div>
    </section>

    <!-- Problem Section -->
    <section id="problem" class="py-32 scroll-section relative">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-purple-400 font-semibold uppercase tracking-wider">The Problem</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">ปัญหาที่เราแก้ไข</h2>
                <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                    ตลาด AI และ GPU Computing กำลังเติบโตอย่างก้าวกระโดด แต่มีปัญหาสำคัญที่ต้องแก้ไข
                </p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-16 h-16 bg-red-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-dollar-sign text-3xl text-red-400"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">ต้นทุน GPU สูงมาก</h3>
                    <p class="text-gray-400 leading-relaxed">
                        GPU ระดับ Enterprise ราคาสูงถึง 2-5 ล้านบาท
                        Cloud GPU Services คิดค่าบริการ $1-5/ชั่วโมง
                        ทำให้ SME และ Startup เข้าถึงยาก
                    </p>
                    <div class="mt-6 p-4 bg-red-500/10 rounded-xl">
                        <p class="text-red-400 font-semibold">GPU RTX 4090 = ฿80,000+</p>
                        <p class="text-gray-500 text-sm">ต่อการ์ดจอ 1 ใบ</p>
                    </div>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-16 h-16 bg-yellow-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-battery-quarter text-3xl text-yellow-400"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">GPU ถูกใช้งานไม่เต็มที่</h3>
                    <p class="text-gray-400 leading-relaxed">
                        GPU ในบ้านส่วนใหญ่ถูกใช้งานจริงแค่ 10-20% ของเวลา
                        เกมเมอร์ใช้แค่ตอนเล่นเกม นักขุดหยุดขุดเพราะไม่คุ้ม
                        ทรัพยากรมหาศาลถูกทิ้งไว้เฉยๆ
                    </p>
                    <div class="mt-6 p-4 bg-yellow-500/10 rounded-xl">
                        <p class="text-yellow-400 font-semibold">80% Idle Time</p>
                        <p class="text-gray-500 text-sm">GPU ว่างงานส่วนใหญ่ของเวลา</p>
                    </div>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-16 h-16 bg-orange-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-clock text-3xl text-orange-400"></i>
                    </div>
                    <h3 class="text-2xl font-bold mb-4">คิวยาว รอนาน</h3>
                    <p class="text-gray-400 leading-relaxed">
                        บริการ AI ฟรียอดนิยมมีคิวยาวมาก
                        ต้องรอ 5-30 นาทีต่อภาพ
                        ไม่เหมาะกับการใช้งานเชิงพาณิชย์
                    </p>
                    <div class="mt-6 p-4 bg-orange-500/10 rounded-xl">
                        <p class="text-orange-400 font-semibold">30+ นาที/ภาพ</p>
                        <p class="text-gray-500 text-sm">เวลารอคิวบริการฟรี</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Solution Section -->
    <section id="solution" class="py-32 scroll-section relative bg-gradient-to-b from-gray-900 via-gray-800/50 to-gray-900">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-purple-400 font-semibold uppercase tracking-wider">Our Solution</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">GPU Sharing Platform</h2>
                <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                    เชื่อมต่อผู้ที่มี GPU กับผู้ที่ต้องการใช้ สร้างระบบนิเวศแบบ Win-Win
                </p>
            </div>

            <div class="grid lg:grid-cols-2 gap-16 items-center">
                <div>
                    <div class="space-y-8">
                        <div class="flex gap-6">
                            <div class="w-14 h-14 gradient-bg rounded-2xl flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-share-nodes text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold mb-2">Decentralized GPU Network</h3>
                                <p class="text-gray-400">รวมพลัง GPU จากทั่วประเทศ สร้างเครือข่ายประมวลผลขนาดใหญ่โดยไม่ต้องลงทุน Data Center</p>
                            </div>
                        </div>

                        <div class="flex gap-6">
                            <div class="w-14 h-14 gradient-bg rounded-2xl flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-robot text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold mb-2">AI Generation Service</h3>
                                <p class="text-gray-400">สร้างภาพ AI, Video, 3D Render ด้วยต้นทุนถูกกว่า Cloud Services ถึง 10 เท่า</p>
                            </div>
                        </div>

                        <div class="flex gap-6">
                            <div class="w-14 h-14 gradient-bg rounded-2xl flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-coins text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold mb-2">Passive Income for GPU Owners</h3>
                                <p class="text-gray-400">เปลี่ยน GPU ที่นอนอยู่ให้สร้างรายได้ 3,000-15,000 บาท/เดือน โดยไม่ต้องทำอะไร</p>
                            </div>
                        </div>

                        <div class="flex gap-6">
                            <div class="w-14 h-14 gradient-bg rounded-2xl flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-users text-2xl"></i>
                            </div>
                            <div>
                                <h3 class="text-2xl font-bold mb-2">Referral System MLM</h3>
                                <p class="text-gray-400">ระบบแนะนำ 5 ชั้น สร้างรายได้ Passive จากทีมงานไม่จำกัด</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="relative">
                    <div class="glass-card rounded-3xl p-8">
                        <h4 class="text-xl font-bold mb-6 text-center">How It Works</h4>
                        <div class="space-y-6">
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center font-bold text-purple-400">1</div>
                                <div class="flex-1">
                                    <p class="font-semibold">ติดตั้ง Client Software</p>
                                    <p class="text-gray-500 text-sm">ดาวน์โหลดและติดตั้งง่ายใน 5 นาที</p>
                                </div>
                            </div>
                            <div class="w-px h-8 bg-gray-700 ml-6"></div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center font-bold text-purple-400">2</div>
                                <div class="flex-1">
                                    <p class="font-semibold">GPU เชื่อมต่อเครือข่าย</p>
                                    <p class="text-gray-500 text-sm">ระบบจะรับงานอัตโนมัติเมื่อ GPU ว่าง</p>
                                </div>
                            </div>
                            <div class="w-px h-8 bg-gray-700 ml-6"></div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-purple-500/20 rounded-full flex items-center justify-center font-bold text-purple-400">3</div>
                                <div class="flex-1">
                                    <p class="font-semibold">ประมวลผลและรับเงิน</p>
                                    <p class="text-gray-500 text-sm">รับเงินอัตโนมัติตามงานที่ทำเสร็จ</p>
                                </div>
                            </div>
                            <div class="w-px h-8 bg-gray-700 ml-6"></div>
                            <div class="flex items-center gap-4">
                                <div class="w-12 h-12 bg-green-500/20 rounded-full flex items-center justify-center font-bold text-green-400">4</div>
                                <div class="flex-1">
                                    <p class="font-semibold">ถอนเงินได้ทันที</p>
                                    <p class="text-gray-500 text-sm">โอนเข้าบัญชีภายใน 24 ชั่วโมง</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-32 scroll-section">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-purple-400 font-semibold uppercase tracking-wider">Platform Features</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">ฟีเจอร์เด่นของแพลตฟอร์ม</h2>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8">
                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-14 h-14 bg-blue-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-wand-magic-sparkles text-2xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">AI Image Generation</h3>
                    <p class="text-gray-400">สร้างภาพจาก Text Prompt ด้วย Stable Diffusion, SDXL, Flux และโมเดลอื่นๆ อีกมากมาย</p>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-14 h-14 bg-green-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-store text-2xl text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Model Store</h3>
                    <p class="text-gray-400">เลือกโมเดลจาก HuggingFace กว่า 10,000+ โมเดล Import มาใช้งานได้ทันที</p>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-14 h-14 bg-purple-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-sitemap text-2xl text-purple-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Referral Network</h3>
                    <p class="text-gray-400">ระบบแนะนำ 5 ระดับ รับค่าคอมมิชชั่นจากทีมงานทุกชั้น สร้างรายได้ Passive Income</p>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-14 h-14 bg-orange-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-ranking-star text-2xl text-orange-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Performance Ranking</h3>
                    <p class="text-gray-400">ระบบจัดอันดับ GPU Node ตาม Performance รับงานมากขึ้นเมื่อคะแนนสูง</p>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-14 h-14 bg-red-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-shield-halved text-2xl text-red-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">KYC Verification</h3>
                    <p class="text-gray-400">ระบบยืนยันตัวตนเพื่อความปลอดภัย ถอนเงินได้ไม่จำกัดหลังผ่าน KYC</p>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8">
                    <div class="w-14 h-14 bg-cyan-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fab fa-google-drive text-2xl text-cyan-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Google Drive Sync</h3>
                    <p class="text-gray-400">ซิงค์ภาพที่สร้างขึ้น Google Drive อัตโนมัติ เข้าถึงได้จากทุกอุปกรณ์</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Proprietary Technology Section (IP Documentation) -->
    <section id="technology" class="py-32 scroll-section bg-gradient-to-b from-gray-900 via-indigo-900/20 to-gray-900">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-indigo-400 font-semibold uppercase tracking-wider">Intellectual Property</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">เทคโนโลยีเฉพาะ (Proprietary Technology)</h2>
                <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                    นวัตกรรมที่พัฒนาขึ้นเองโดยทีมงาน เป็นทรัพย์สินทางปัญญาของบริษัท
                </p>
            </div>

            <!-- Smart VRAM Chunking Technology -->
            <div class="glass-card rounded-3xl p-8 mb-12">
                <div class="flex items-center gap-4 mb-8">
                    <div class="w-16 h-16 gradient-bg rounded-2xl flex items-center justify-center">
                        <i class="fas fa-puzzle-piece text-3xl"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold">Smart VRAM Chunking Algorithm</h3>
                        <p class="text-gray-400">ระบบแบ่งงาน AI อัจฉริยะตามขนาด VRAM ของ GPU</p>
                    </div>
                </div>

                <div class="grid lg:grid-cols-2 gap-8 mb-8">
                    <div>
                        <h4 class="text-lg font-bold mb-4 text-purple-400">หลักการทำงาน</h4>
                        <p class="text-gray-400 mb-4">
                            อัลกอริทึมวิเคราะห์ขนาด VRAM ของ GPU แต่ละเครื่อง แล้วแบ่งงาน AI Generation ให้เหมาะสมกับความสามารถ
                            ทำให้ GPU ทุกขนาดตั้งแต่ 3GB ขึ้นไปสามารถเข้าร่วมเครือข่ายได้
                        </p>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span><strong>Tile-Based Chunking:</strong> แบ่งภาพขนาดใหญ่เป็น tiles ย่อย เช่น 256x256, 512x512</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span><strong>Step-Based Chunking:</strong> แบ่ง Denoising Steps ระหว่าง workers หลายตัว</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span><strong>Hybrid Strategy:</strong> รวม Tiles + Steps เพื่อประสิทธิภาพสูงสุด</span>
                            </li>
                            <li class="flex items-start gap-2">
                                <i class="fas fa-check-circle text-green-400 mt-1"></i>
                                <span><strong>Micro Chunking:</strong> สำหรับ GPU 2-3GB ใช้ tile 256x256 กับ 5 steps</span>
                            </li>
                        </ul>
                    </div>
                    <div>
                        <h4 class="text-lg font-bold mb-4 text-blue-400">VRAM Tier System</h4>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 bg-red-500/10 rounded-lg border-l-4 border-red-500">
                                <span>Ultra Low (2-3GB)</span>
                                <span class="text-sm text-gray-400">256x256 tiles, 5 steps</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-orange-500/10 rounded-lg border-l-4 border-orange-500">
                                <span>Very Low (3-4GB)</span>
                                <span class="text-sm text-gray-400">512x512 tiles, 8 steps</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-yellow-500/10 rounded-lg border-l-4 border-yellow-500">
                                <span>Low (4-6GB)</span>
                                <span class="text-sm text-gray-400">768x768, 12 steps</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-teal-500/10 rounded-lg border-l-4 border-teal-500">
                                <span>Medium (6-8GB)</span>
                                <span class="text-sm text-gray-400">1024x1024, 20 steps</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-blue-500/10 rounded-lg border-l-4 border-blue-500">
                                <span>High (8-12GB)</span>
                                <span class="text-sm text-gray-400">Full SDXL support</span>
                            </div>
                            <div class="flex items-center justify-between p-3 bg-purple-500/10 rounded-lg border-l-4 border-purple-500">
                                <span>Ultra (12GB+)</span>
                                <span class="text-sm text-gray-400">All models, no limits</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technical Workflow Diagram -->
                <div class="bg-gray-800/50 rounded-2xl p-6">
                    <h4 class="text-lg font-bold mb-6 text-center">Workflow Architecture</h4>
                    <div class="grid grid-cols-1 md:grid-cols-5 gap-4 items-center">
                        <div class="text-center p-4 bg-purple-500/20 rounded-xl">
                            <i class="fas fa-user text-3xl text-purple-400 mb-2"></i>
                            <p class="font-semibold">User Request</p>
                            <p class="text-xs text-gray-500">Submit AI Job</p>
                        </div>
                        <div class="hidden md:block text-center">
                            <i class="fas fa-arrow-right text-2xl text-gray-600"></i>
                        </div>
                        <div class="text-center p-4 bg-blue-500/20 rounded-xl">
                            <i class="fas fa-cogs text-3xl text-blue-400 mb-2"></i>
                            <p class="font-semibold">Smart Split</p>
                            <p class="text-xs text-gray-500">Analyze & Chunk</p>
                        </div>
                        <div class="hidden md:block text-center">
                            <i class="fas fa-arrow-right text-2xl text-gray-600"></i>
                        </div>
                        <div class="text-center p-4 bg-green-500/20 rounded-xl">
                            <i class="fas fa-server text-3xl text-green-400 mb-2"></i>
                            <p class="font-semibold">GPU Pool</p>
                            <p class="text-xs text-gray-500">Distributed Processing</p>
                        </div>
                    </div>
                    <div class="mt-4 grid grid-cols-1 md:grid-cols-5 gap-4 items-center">
                        <div class="md:col-start-5 text-center p-4 bg-orange-500/20 rounded-xl">
                            <i class="fas fa-puzzle-piece text-3xl text-orange-400 mb-2"></i>
                            <p class="font-semibold">Assembly</p>
                            <p class="text-xs text-gray-500">Merge Results</p>
                        </div>
                        <div class="hidden md:block md:col-start-4 text-center">
                            <i class="fas fa-arrow-left text-2xl text-gray-600"></i>
                        </div>
                        <div class="md:col-start-3 text-center p-4 bg-cyan-500/20 rounded-xl">
                            <i class="fas fa-check-double text-3xl text-cyan-400 mb-2"></i>
                            <p class="font-semibold">Verification</p>
                            <p class="text-xs text-gray-500">Hash Validation</p>
                        </div>
                        <div class="hidden md:block md:col-start-2 text-center">
                            <i class="fas fa-arrow-left text-2xl text-gray-600"></i>
                        </div>
                        <div class="md:col-start-1 text-center p-4 bg-pink-500/20 rounded-xl">
                            <i class="fas fa-image text-3xl text-pink-400 mb-2"></i>
                            <p class="font-semibold">Delivery</p>
                            <p class="text-xs text-gray-500">Return to User</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Model Pre-Download System -->
            <div class="glass-card rounded-3xl p-8 mb-12">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-green-500/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-download text-3xl text-green-400"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold">Model Pre-Download System</h3>
                        <p class="text-gray-400">ระบบดาวน์โหลดโมเดลล่วงหน้าเพื่อเพิ่มความเร็ว</p>
                    </div>
                </div>
                <div class="grid md:grid-cols-2 gap-8">
                    <div>
                        <p class="text-gray-400 mb-4">
                            Workers สามารถดาวน์โหลดโมเดล AI ไว้ล่วงหน้าในเครื่อง ทำให้ไม่ต้องโหลดโมเดลทุกครั้งที่รับงาน
                            ลดเวลาประมวลผลได้ 50-80%
                        </p>
                        <ul class="space-y-2 text-sm text-gray-300">
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-400"></i>
                                เชื่อมต่อกับ HuggingFace Hub โดยตรง
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-400"></i>
                                จับคู่งานกับ Worker ที่มีโมเดลติดตั้งแล้ว
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-400"></i>
                                ใช้ HuggingFace Token ส่วนตัวของผู้ใช้
                            </li>
                            <li class="flex items-center gap-2">
                                <i class="fas fa-check text-green-400"></i>
                                รองรับโมเดลทุกประเภท: SDXL, Flux, LoRA, etc.
                            </li>
                        </ul>
                    </div>
                    <div class="bg-gray-800/50 rounded-xl p-6">
                        <h5 class="font-semibold mb-4 text-green-400">Performance Improvement</h5>
                        <div class="space-y-4">
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>Without Pre-Download</span>
                                    <span class="text-gray-500">30-60 sec</span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div class="bg-red-500 h-2 rounded-full" style="width: 100%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="flex justify-between text-sm mb-1">
                                    <span>With Pre-Download</span>
                                    <span class="text-green-400">5-15 sec</span>
                                </div>
                                <div class="w-full bg-gray-700 rounded-full h-2">
                                    <div class="bg-green-500 h-2 rounded-full" style="width: 25%"></div>
                                </div>
                            </div>
                        </div>
                        <p class="text-center mt-4 text-2xl font-bold text-green-400">75% Faster</p>
                    </div>
                </div>
            </div>

            <!-- Parallel Processing & Assembly -->
            <div class="glass-card rounded-3xl p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="w-16 h-16 bg-orange-500/20 rounded-2xl flex items-center justify-center">
                        <i class="fas fa-layer-group text-3xl text-orange-400"></i>
                    </div>
                    <div>
                        <h3 class="text-2xl font-bold">Parallel Processing & Assembly Engine</h3>
                        <p class="text-gray-400">ระบบประมวลผลพร้อมกันและประกอบผลลัพธ์</p>
                    </div>
                </div>
                <div class="grid md:grid-cols-3 gap-6">
                    <div class="bg-gray-800/50 rounded-xl p-6">
                        <i class="fas fa-code-branch text-3xl text-blue-400 mb-4"></i>
                        <h5 class="font-semibold mb-2">Dependency Graph</h5>
                        <p class="text-sm text-gray-400">
                            ระบบจัดการ dependencies ระหว่าง chunks
                            รองรับทั้ง sequential และ parallel execution
                        </p>
                    </div>
                    <div class="bg-gray-800/50 rounded-xl p-6">
                        <i class="fas fa-object-group text-3xl text-green-400 mb-4"></i>
                        <h5 class="font-semibold mb-2">Image Stitching</h5>
                        <p class="text-sm text-gray-400">
                            อัลกอริทึมต่อภาพ tiles โดยไม่มีรอยต่อ
                            รองรับ overlap และ blending
                        </p>
                    </div>
                    <div class="bg-gray-800/50 rounded-xl p-6">
                        <i class="fas fa-fingerprint text-3xl text-purple-400 mb-4"></i>
                        <h5 class="font-semibold mb-2">Hash Verification</h5>
                        <p class="text-sm text-gray-400">
                            ตรวจสอบความถูกต้องของผลลัพธ์ด้วย hash
                            ป้องกันการส่งข้อมูลผิดพลาด
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Competitive Advantages Section -->
    <section id="advantages" class="py-32 scroll-section">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-green-400 font-semibold uppercase tracking-wider">Competitive Advantages</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">ข้อได้เปรียบทางการแข่งขัน</h2>
                <p class="text-xl text-gray-400 max-w-3xl mx-auto">
                    สิ่งที่ทำให้เราแตกต่างจากคู่แข่งในตลาด
                </p>
            </div>

            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-8 mb-12">
                <div class="card-hover glass-card rounded-2xl p-8 border-t-4 border-green-500">
                    <div class="w-14 h-14 bg-green-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-memory text-2xl text-green-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Low VRAM Support</h3>
                    <p class="text-gray-400 mb-4">
                        รองรับ GPU ตั้งแต่ 3GB ขึ้นไป ในขณะที่คู่แข่งต้องการ 8GB+
                        ขยายฐาน GPU Pool ได้มากกว่า 5 เท่า
                    </p>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded">Proprietary</span>
                        <span class="px-2 py-1 bg-purple-500/20 text-purple-400 rounded">Patent Pending</span>
                    </div>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8 border-t-4 border-blue-500">
                    <div class="w-14 h-14 bg-blue-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-bolt text-2xl text-blue-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">75% Faster Processing</h3>
                    <p class="text-gray-400 mb-4">
                        Model Pre-Download System ทำให้ไม่ต้องโหลดโมเดลทุกครั้ง
                        ลดเวลาประมวลผลจาก 60 วินาทีเหลือ 15 วินาที
                    </p>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded">Proprietary</span>
                    </div>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8 border-t-4 border-purple-500">
                    <div class="w-14 h-14 bg-purple-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-puzzle-piece text-2xl text-purple-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Smart Chunking</h3>
                    <p class="text-gray-400 mb-4">
                        อัลกอริทึมแบ่งงาน 4 รูปแบบ (Tile, Step, Hybrid, Micro)
                        เพิ่มประสิทธิภาพการใช้งาน GPU Pool ได้ 300%
                    </p>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="px-2 py-1 bg-green-500/20 text-green-400 rounded">Proprietary</span>
                        <span class="px-2 py-1 bg-purple-500/20 text-purple-400 rounded">Patent Pending</span>
                    </div>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8 border-t-4 border-orange-500">
                    <div class="w-14 h-14 bg-orange-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-coins text-2xl text-orange-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Low VRAM Bonus</h3>
                    <p class="text-gray-400 mb-4">
                        ระบบโบนัสสำหรับ GPU ขนาดเล็ก สร้างแรงจูงใจให้ GPU ทุกขนาดเข้าร่วม
                        เพิ่ม Supply ในเครือข่าย
                    </p>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded">Unique</span>
                    </div>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8 border-t-4 border-cyan-500">
                    <div class="w-14 h-14 bg-cyan-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-network-wired text-2xl text-cyan-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Decentralized Network</h3>
                    <p class="text-gray-400 mb-4">
                        ไม่ต้องลงทุน Data Center ใช้ GPU ของผู้ใช้ทั่วประเทศ
                        ลดต้นทุนโครงสร้างพื้นฐาน 90%
                    </p>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="px-2 py-1 bg-blue-500/20 text-blue-400 rounded">Scalable</span>
                    </div>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8 border-t-4 border-pink-500">
                    <div class="w-14 h-14 bg-pink-500/20 rounded-2xl flex items-center justify-center mb-6">
                        <i class="fas fa-users text-2xl text-pink-400"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-3">MLM Referral System</h3>
                    <p class="text-gray-400 mb-4">
                        ระบบแนะนำ 5 ระดับ สร้างการเติบโตแบบ Viral
                        ลด Customer Acquisition Cost ได้ 80%
                    </p>
                    <div class="flex items-center gap-2 text-sm">
                        <span class="px-2 py-1 bg-pink-500/20 text-pink-400 rounded">Viral Growth</span>
                    </div>
                </div>
            </div>

            <!-- Moat Summary -->
            <div class="glass-card rounded-3xl p-8 text-center">
                <h3 class="text-2xl font-bold mb-6">Technology Moat Summary</h3>
                <div class="grid md:grid-cols-4 gap-6">
                    <div class="p-4">
                        <p class="text-4xl font-bold text-green-400">3</p>
                        <p class="text-gray-400">Proprietary Algorithms</p>
                    </div>
                    <div class="p-4">
                        <p class="text-4xl font-bold text-purple-400">2</p>
                        <p class="text-gray-400">Patent Pending</p>
                    </div>
                    <div class="p-4">
                        <p class="text-4xl font-bold text-blue-400">6</p>
                        <p class="text-gray-400">VRAM Tiers Supported</p>
                    </div>
                    <div class="p-4">
                        <p class="text-4xl font-bold text-orange-400">75%</p>
                        <p class="text-gray-400">Speed Improvement</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Business Model Section -->
    <section id="business" class="py-32 scroll-section bg-gradient-to-b from-gray-900 via-purple-900/10 to-gray-900">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-purple-400 font-semibold uppercase tracking-wider">Business Model</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">โมเดลธุรกิจ</h2>
            </div>

            <div class="grid lg:grid-cols-2 gap-12 mb-16">
                <!-- Revenue Streams -->
                <div class="glass-card rounded-3xl p-8">
                    <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                        <i class="fas fa-chart-line text-green-400"></i>
                        แหล่งรายได้
                    </h3>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between p-4 bg-green-500/10 rounded-xl">
                            <div>
                                <p class="font-semibold">Platform Fee</p>
                                <p class="text-gray-500 text-sm">ค่าธรรมเนียมจากทุกงาน</p>
                            </div>
                            <span class="text-2xl font-bold text-green-400">20%</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-blue-500/10 rounded-xl">
                            <div>
                                <p class="font-semibold">Credit Package Sales</p>
                                <p class="text-gray-500 text-sm">ขายแพ็คเกจเครดิต</p>
                            </div>
                            <span class="text-2xl font-bold text-blue-400">30%</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-purple-500/10 rounded-xl">
                            <div>
                                <p class="font-semibold">Subscription Plans</p>
                                <p class="text-gray-500 text-sm">สมาชิกรายเดือน</p>
                            </div>
                            <span class="text-2xl font-bold text-purple-400">35%</span>
                        </div>
                        <div class="flex items-center justify-between p-4 bg-orange-500/10 rounded-xl">
                            <div>
                                <p class="font-semibold">Enterprise API</p>
                                <p class="text-gray-500 text-sm">บริการสำหรับองค์กร</p>
                            </div>
                            <span class="text-2xl font-bold text-orange-400">15%</span>
                        </div>
                    </div>
                </div>

                <!-- Unit Economics -->
                <div class="glass-card rounded-3xl p-8">
                    <h3 class="text-2xl font-bold mb-6 flex items-center gap-3">
                        <i class="fas fa-calculator text-blue-400"></i>
                        Unit Economics
                    </h3>
                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center p-4 bg-gray-800 rounded-xl">
                                <p class="text-3xl font-bold text-green-400">฿15</p>
                                <p class="text-gray-500 text-sm">ราคาต่อภาพ (เฉลี่ย)</p>
                            </div>
                            <div class="text-center p-4 bg-gray-800 rounded-xl">
                                <p class="text-3xl font-bold text-blue-400">฿10</p>
                                <p class="text-gray-500 text-sm">ต้นทุน GPU</p>
                            </div>
                            <div class="text-center p-4 bg-gray-800 rounded-xl">
                                <p class="text-3xl font-bold text-purple-400">฿5</p>
                                <p class="text-gray-500 text-sm">กำไรขั้นต้น</p>
                            </div>
                            <div class="text-center p-4 bg-gray-800 rounded-xl">
                                <p class="text-3xl font-bold text-orange-400">33%</p>
                                <p class="text-gray-500 text-sm">Gross Margin</p>
                            </div>
                        </div>
                        <div class="p-4 bg-gradient-to-r from-purple-500/20 to-blue-500/20 rounded-xl">
                            <div class="flex justify-between items-center">
                                <span class="text-gray-400">Customer Acquisition Cost</span>
                                <span class="font-bold">฿50</span>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-gray-400">Customer Lifetime Value</span>
                                <span class="font-bold text-green-400">฿2,500</span>
                            </div>
                            <div class="flex justify-between items-center mt-2">
                                <span class="text-gray-400">LTV:CAC Ratio</span>
                                <span class="font-bold text-yellow-400">50:1</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Market Size -->
            <div class="glass-card rounded-3xl p-8">
                <h3 class="text-2xl font-bold mb-8 text-center">Market Opportunity</h3>
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-32 h-32 mx-auto mb-4 rounded-full bg-gradient-to-br from-purple-500/20 to-purple-700/20 flex items-center justify-center">
                            <div>
                                <p class="text-3xl font-bold text-purple-400">$50B</p>
                                <p class="text-gray-500 text-sm">TAM</p>
                            </div>
                        </div>
                        <p class="font-semibold">Total Addressable Market</p>
                        <p class="text-gray-500 text-sm">Global GPU Cloud Market 2025</p>
                    </div>
                    <div class="text-center">
                        <div class="w-32 h-32 mx-auto mb-4 rounded-full bg-gradient-to-br from-blue-500/20 to-blue-700/20 flex items-center justify-center">
                            <div>
                                <p class="text-3xl font-bold text-blue-400">$5B</p>
                                <p class="text-gray-500 text-sm">SAM</p>
                            </div>
                        </div>
                        <p class="font-semibold">Serviceable Addressable Market</p>
                        <p class="text-gray-500 text-sm">Asia Pacific AI Generation</p>
                    </div>
                    <div class="text-center">
                        <div class="w-32 h-32 mx-auto mb-4 rounded-full bg-gradient-to-br from-green-500/20 to-green-700/20 flex items-center justify-center">
                            <div>
                                <p class="text-3xl font-bold text-green-400">$500M</p>
                                <p class="text-gray-500 text-sm">SOM</p>
                            </div>
                        </div>
                        <p class="font-semibold">Serviceable Obtainable Market</p>
                        <p class="text-gray-500 text-sm">Thailand & SEA (5 Years)</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Roadmap Section -->
    <section id="roadmap" class="py-32 scroll-section">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-purple-400 font-semibold uppercase tracking-wider">Roadmap</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">แผนการพัฒนา</h2>
            </div>

            <div class="relative">
                <!-- Timeline Line -->
                <div class="absolute left-1/2 top-0 bottom-0 w-1 bg-gradient-to-b from-purple-500 via-blue-500 to-green-500 hidden lg:block"></div>

                <div class="space-y-12">
                    <!-- Phase 1 -->
                    <div class="lg:grid lg:grid-cols-2 lg:gap-12 items-center">
                        <div class="lg:text-right lg:pr-12">
                            <div class="glass-card rounded-2xl p-6 inline-block">
                                <span class="text-purple-400 font-semibold">Q1 2025</span>
                                <h3 class="text-2xl font-bold mt-2 mb-4">Phase 1: Foundation</h3>
                                <ul class="text-gray-400 space-y-2">
                                    <li class="flex items-center gap-2 lg:justify-end"><i class="fas fa-check text-green-400"></i> Platform Development</li>
                                    <li class="flex items-center gap-2 lg:justify-end"><i class="fas fa-check text-green-400"></i> Windows Client Release</li>
                                    <li class="flex items-center gap-2 lg:justify-end"><i class="fas fa-check text-green-400"></i> Basic AI Models Integration</li>
                                    <li class="flex items-center gap-2 lg:justify-end"><i class="fas fa-check text-green-400"></i> Payment System</li>
                                </ul>
                            </div>
                        </div>
                        <div class="hidden lg:flex justify-start items-center">
                            <div class="w-6 h-6 bg-purple-500 rounded-full border-4 border-gray-900"></div>
                        </div>
                    </div>

                    <!-- Phase 2 -->
                    <div class="lg:grid lg:grid-cols-2 lg:gap-12 items-center">
                        <div class="hidden lg:flex justify-end items-center">
                            <div class="w-6 h-6 bg-blue-500 rounded-full border-4 border-gray-900"></div>
                        </div>
                        <div class="lg:pl-12">
                            <div class="glass-card rounded-2xl p-6 inline-block">
                                <span class="text-blue-400 font-semibold">Q2 2025</span>
                                <h3 class="text-2xl font-bold mt-2 mb-4">Phase 2: Growth</h3>
                                <ul class="text-gray-400 space-y-2">
                                    <li class="flex items-center gap-2"><i class="fas fa-spinner text-yellow-400 animate-spin"></i> Mobile App (iOS/Android)</li>
                                    <li class="flex items-center gap-2"><i class="fas fa-circle text-gray-600"></i> Video Generation</li>
                                    <li class="flex items-center gap-2"><i class="fas fa-circle text-gray-600"></i> Enterprise API</li>
                                    <li class="flex items-center gap-2"><i class="fas fa-circle text-gray-600"></i> 1,000 GPU Nodes</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Phase 3 -->
                    <div class="lg:grid lg:grid-cols-2 lg:gap-12 items-center">
                        <div class="lg:text-right lg:pr-12">
                            <div class="glass-card rounded-2xl p-6 inline-block">
                                <span class="text-cyan-400 font-semibold">Q3-Q4 2025</span>
                                <h3 class="text-2xl font-bold mt-2 mb-4">Phase 3: Expansion</h3>
                                <ul class="text-gray-400 space-y-2">
                                    <li class="flex items-center gap-2 lg:justify-end"><i class="fas fa-circle text-gray-600"></i> SEA Market Expansion</li>
                                    <li class="flex items-center gap-2 lg:justify-end"><i class="fas fa-circle text-gray-600"></i> 3D Generation</li>
                                    <li class="flex items-center gap-2 lg:justify-end"><i class="fas fa-circle text-gray-600"></i> Training & Fine-tuning</li>
                                    <li class="flex items-center gap-2 lg:justify-end"><i class="fas fa-circle text-gray-600"></i> 10,000 GPU Nodes</li>
                                </ul>
                            </div>
                        </div>
                        <div class="hidden lg:flex justify-start items-center">
                            <div class="w-6 h-6 bg-cyan-500 rounded-full border-4 border-gray-900"></div>
                        </div>
                    </div>

                    <!-- Phase 4 -->
                    <div class="lg:grid lg:grid-cols-2 lg:gap-12 items-center">
                        <div class="hidden lg:flex justify-end items-center">
                            <div class="w-6 h-6 bg-green-500 rounded-full border-4 border-gray-900"></div>
                        </div>
                        <div class="lg:pl-12">
                            <div class="glass-card rounded-2xl p-6 inline-block">
                                <span class="text-green-400 font-semibold">2026</span>
                                <h3 class="text-2xl font-bold mt-2 mb-4">Phase 4: Scale</h3>
                                <ul class="text-gray-400 space-y-2">
                                    <li class="flex items-center gap-2"><i class="fas fa-circle text-gray-600"></i> Global Expansion</li>
                                    <li class="flex items-center gap-2"><i class="fas fa-circle text-gray-600"></i> Blockchain Integration</li>
                                    <li class="flex items-center gap-2"><i class="fas fa-circle text-gray-600"></i> 100,000 GPU Nodes</li>
                                    <li class="flex items-center gap-2"><i class="fas fa-circle text-gray-600"></i> IPO Preparation</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Investment Section -->
    <section id="investment" class="py-32 scroll-section bg-gradient-to-b from-gray-900 via-purple-900/20 to-gray-900">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-purple-400 font-semibold uppercase tracking-wider">Investment Opportunity</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">โอกาสการลงทุน</h2>
            </div>

            <div class="grid lg:grid-cols-2 gap-12 mb-12">
                <div class="glass-card rounded-3xl p-8">
                    <h3 class="text-2xl font-bold mb-6">Funding Round: Seed</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center p-4 bg-gray-800 rounded-xl">
                            <span class="text-gray-400">Target Raise</span>
                            <span class="text-2xl font-bold text-green-400">฿10M - ฿30M</span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-gray-800 rounded-xl">
                            <span class="text-gray-400">Pre-money Valuation</span>
                            <span class="text-2xl font-bold">฿50M</span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-gray-800 rounded-xl">
                            <span class="text-gray-400">Equity Offered</span>
                            <span class="text-2xl font-bold text-purple-400">15-30%</span>
                        </div>
                        <div class="flex justify-between items-center p-4 bg-gray-800 rounded-xl">
                            <span class="text-gray-400">Minimum Investment</span>
                            <span class="text-2xl font-bold">฿1M</span>
                        </div>
                    </div>
                </div>

                <div class="glass-card rounded-3xl p-8">
                    <h3 class="text-2xl font-bold mb-6">Use of Funds</h3>
                    <div class="space-y-4">
                        <div>
                            <div class="flex justify-between mb-2">
                                <span>Product Development</span>
                                <span class="font-bold">40%</span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-3">
                                <div class="bg-purple-500 h-3 rounded-full" style="width: 40%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between mb-2">
                                <span>Marketing & Sales</span>
                                <span class="font-bold">30%</span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-3">
                                <div class="bg-blue-500 h-3 rounded-full" style="width: 30%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between mb-2">
                                <span>Operations & Infrastructure</span>
                                <span class="font-bold">20%</span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-3">
                                <div class="bg-green-500 h-3 rounded-full" style="width: 20%"></div>
                            </div>
                        </div>
                        <div>
                            <div class="flex justify-between mb-2">
                                <span>Reserve & Legal</span>
                                <span class="font-bold">10%</span>
                            </div>
                            <div class="w-full bg-gray-700 rounded-full h-3">
                                <div class="bg-orange-500 h-3 rounded-full" style="width: 10%"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Projections -->
            <div class="glass-card rounded-3xl p-8">
                <h3 class="text-2xl font-bold mb-8 text-center">Financial Projections</h3>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead>
                            <tr class="border-b border-gray-700">
                                <th class="text-left py-4 px-4 text-gray-400">Metrics</th>
                                <th class="text-right py-4 px-4">Year 1</th>
                                <th class="text-right py-4 px-4">Year 2</th>
                                <th class="text-right py-4 px-4">Year 3</th>
                                <th class="text-right py-4 px-4">Year 5</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr class="border-b border-gray-800">
                                <td class="py-4 px-4">GPU Nodes</td>
                                <td class="text-right py-4 px-4">500</td>
                                <td class="text-right py-4 px-4">2,000</td>
                                <td class="text-right py-4 px-4">10,000</td>
                                <td class="text-right py-4 px-4 text-green-400 font-bold">50,000</td>
                            </tr>
                            <tr class="border-b border-gray-800">
                                <td class="py-4 px-4">Active Users</td>
                                <td class="text-right py-4 px-4">10K</td>
                                <td class="text-right py-4 px-4">50K</td>
                                <td class="text-right py-4 px-4">200K</td>
                                <td class="text-right py-4 px-4 text-green-400 font-bold">1M</td>
                            </tr>
                            <tr class="border-b border-gray-800">
                                <td class="py-4 px-4">Revenue (฿)</td>
                                <td class="text-right py-4 px-4">5M</td>
                                <td class="text-right py-4 px-4">30M</td>
                                <td class="text-right py-4 px-4">150M</td>
                                <td class="text-right py-4 px-4 text-green-400 font-bold">800M</td>
                            </tr>
                            <tr>
                                <td class="py-4 px-4">Net Profit (฿)</td>
                                <td class="text-right py-4 px-4 text-red-400">-3M</td>
                                <td class="text-right py-4 px-4">5M</td>
                                <td class="text-right py-4 px-4">40M</td>
                                <td class="text-right py-4 px-4 text-green-400 font-bold">250M</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>

    <!-- Team Section -->
    <section id="team" class="py-32 scroll-section">
        <div class="max-w-7xl mx-auto px-6">
            <div class="text-center mb-16">
                <span class="text-purple-400 font-semibold uppercase tracking-wider">Our Team</span>
                <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">ทีมผู้ก่อตั้ง</h2>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <div class="card-hover glass-card rounded-2xl p-8 text-center">
                    <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gradient-to-br from-purple-500 to-blue-500 flex items-center justify-center">
                        <i class="fas fa-user text-5xl text-white/80"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">CEO & Founder</h3>
                    <p class="text-purple-400 mb-4">Chief Executive Officer</p>
                    <p class="text-gray-400 text-sm">10+ ปีประสบการณ์ด้าน Tech Startup, Ex-CTO บริษัทเทคโนโลยีชั้นนำ</p>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8 text-center">
                    <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gradient-to-br from-blue-500 to-cyan-500 flex items-center justify-center">
                        <i class="fas fa-user text-5xl text-white/80"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">CTO</h3>
                    <p class="text-blue-400 mb-4">Chief Technology Officer</p>
                    <p class="text-gray-400 text-sm">8+ ปีประสบการณ์ AI/ML Engineer, Ex-Google, Specialist in Distributed Computing</p>
                </div>

                <div class="card-hover glass-card rounded-2xl p-8 text-center">
                    <div class="w-32 h-32 mx-auto mb-6 rounded-full bg-gradient-to-br from-green-500 to-teal-500 flex items-center justify-center">
                        <i class="fas fa-user text-5xl text-white/80"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2">COO</h3>
                    <p class="text-green-400 mb-4">Chief Operating Officer</p>
                    <p class="text-gray-400 text-sm">12+ ปีประสบการณ์ Operations, Scale-up Expert, MBA from Top University</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-32 scroll-section bg-gradient-to-b from-gray-900 via-purple-900/10 to-gray-900">
        <div class="max-w-4xl mx-auto px-6 text-center">
            <span class="text-purple-400 font-semibold uppercase tracking-wider">Get In Touch</span>
            <h2 class="text-4xl lg:text-5xl font-bold mt-4 mb-6">พร้อมร่วมเป็นส่วนหนึ่ง?</h2>
            <p class="text-xl text-gray-400 mb-12">
                ติดต่อเราเพื่อรับ Pitch Deck ฉบับเต็ม หรือนัดประชุมเพื่อพูดคุยโอกาสการลงทุน
            </p>

            <div class="glass-card rounded-3xl p-8 mb-12">
                <div class="grid md:grid-cols-3 gap-8">
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-purple-500/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-envelope text-2xl text-purple-400"></i>
                        </div>
                        <p class="font-semibold">Email</p>
                        <a href="mailto:invest@gpushare.io" class="text-purple-400 hover:underline">invest@gpushare.io</a>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-green-500/20 rounded-2xl flex items-center justify-center">
                            <i class="fab fa-line text-2xl text-green-400"></i>
                        </div>
                        <p class="font-semibold">LINE</p>
                        <a href="#" class="text-green-400 hover:underline">@gpushare</a>
                    </div>
                    <div class="text-center">
                        <div class="w-16 h-16 mx-auto mb-4 bg-blue-500/20 rounded-2xl flex items-center justify-center">
                            <i class="fas fa-phone text-2xl text-blue-400"></i>
                        </div>
                        <p class="font-semibold">Phone</p>
                        <a href="tel:+66800000000" class="text-blue-400 hover:underline">080-000-0000</a>
                    </div>
                </div>
            </div>

            <div class="flex flex-wrap justify-center gap-4">
                <a href="#" class="gradient-bg px-8 py-4 rounded-xl font-semibold text-lg hover:opacity-90 transition inline-flex items-center gap-2">
                    <i class="fas fa-file-pdf"></i>
                    <span>Download Pitch Deck</span>
                </a>
                <a href="#" class="bg-white/10 border border-white/20 px-8 py-4 rounded-xl font-semibold text-lg hover:bg-white/20 transition inline-flex items-center gap-2">
                    <i class="fas fa-calendar"></i>
                    <span>Schedule Meeting</span>
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12 border-t border-gray-800">
        <div class="max-w-7xl mx-auto px-6">
            <div class="flex flex-col md:flex-row items-center justify-between gap-6">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 gradient-bg rounded-xl flex items-center justify-center">
                        <i class="fas fa-microchip text-white"></i>
                    </div>
                    <span class="text-xl font-bold">GPU Share</span>
                </div>
                <p class="text-gray-500">© 2025 GPU Sharing Platform. All rights reserved.</p>
                <div class="flex gap-4">
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition">
                        <i class="fab fa-linkedin-in"></i>
                    </a>
                    <a href="#" class="w-10 h-10 bg-gray-800 rounded-full flex items-center justify-center hover:bg-gray-700 transition">
                        <i class="fab fa-github"></i>
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <!-- Smooth Scroll Script -->
    <script>
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Navbar background on scroll
        window.addEventListener('scroll', function() {
            const nav = document.querySelector('nav');
            if (window.scrollY > 100) {
                nav.classList.add('bg-gray-900/95');
            } else {
                nav.classList.remove('bg-gray-900/95');
            }
        });

        // Animate stats on scroll
        const observerOptions = {
            threshold: 0.5
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.card-hover').forEach(card => {
            observer.observe(card);
        });
    </script>
</body>
</html>
