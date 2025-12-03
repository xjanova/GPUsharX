@extends('layouts.app')

@section('title', 'AI Generator')

@section('content')
<div class="min-h-screen py-6">
    <div class="max-w-7xl mx-auto px-4">

        <!-- Google Drive Connection Warning -->
        @auth
        @if(!auth()->user()->google_drive_connected)
        <div class="mb-6 p-4 rounded-2xl bg-gradient-to-r from-yellow-500/10 to-orange-500/10 border border-yellow-500/30">
            <div class="flex flex-col md:flex-row items-start md:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                    <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                        <i class="fab fa-google-drive text-yellow-400 text-xl"></i>
                    </div>
                    <div>
                        <h3 class="font-semibold text-yellow-400">กรุณาเชื่อมต่อ Google Drive</h3>
                        <p class="text-gray-400 text-sm mt-1">
                            ระบบจะบันทึกผลงานของคุณไปยัง Google Drive อัตโนมัติ เนื่องจากไฟล์จะถูก<strong class="text-red-400">ลบออกจากระบบภายใน 48 ชั่วโมง</strong>
                        </p>
                        <p class="text-gray-500 text-xs mt-1">
                            <i class="fas fa-info-circle mr-1"></i>หากไม่เชื่อมต่อ คุณอาจสูญเสียผลงานหลังจาก 48 ชม.
                        </p>
                    </div>
                </div>
                <a href="{{ route('settings.google-drive') }}" class="flex-shrink-0 inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-500 hover:to-orange-500 text-white font-medium transition">
                    <i class="fab fa-google"></i>
                    เชื่อมต่อ Google Drive
                </a>
            </div>
        </div>
        @endif
        @endauth

        <!-- Header -->
        <div class="text-center mb-8">
            <h1 class="text-3xl md:text-4xl font-bold mb-2">
                <span class="gradient-text">AI Generator</span>
            </h1>
            <p class="text-gray-400">สร้างภาพและวีดีโอด้วย AI บน GPU แบบกระจาย</p>
        </div>

        <!-- Main Content -->
        <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">

            <!-- Left: Generation Form -->
            <div class="lg:col-span-8">
                <div class="glass-card rounded-2xl overflow-hidden">
                    <!-- Tabs -->
                    <div class="flex border-b border-gray-800">
                        <button type="button" class="tab-btn active flex-1 py-4 text-center font-medium transition" data-tab="image">
                            <i class="fas fa-image mr-2 text-blue-400"></i>สร้างภาพ
                        </button>
                        <button type="button" class="tab-btn flex-1 py-4 text-center font-medium transition" data-tab="video">
                            <i class="fas fa-video mr-2 text-pink-400"></i>สร้างวีดีโอ
                        </button>
                    </div>

                    <form action="{{ route('generate.create') }}" method="POST" id="generateForm" class="p-6">
                        @csrf

                        <!-- Model Selection -->
                        <div class="mb-6">
                            <label class="flex items-center justify-between text-sm font-medium text-gray-300 mb-3">
                                <span><i class="fas fa-cube text-purple-400 mr-2"></i>เลือกโมเดล AI</span>
                                <a href="{{ route('models') }}" class="text-purple-400 hover:text-purple-300 text-xs">
                                    ดูทั้งหมด <i class="fas fa-external-link-alt ml-1"></i>
                                </a>
                            </label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3" id="modelGrid">
                                @forelse($featuredModels as $model)
                                <label class="model-card cursor-pointer group">
                                    <input type="radio" name="model_id" value="{{ $model->id }}"
                                        class="hidden" {{ $loop->first ? 'checked' : '' }}
                                        data-type="{{ $model->type }}"
                                        data-vram="{{ $model->vram_required_mb }}"
                                        data-credits="{{ $model->credits_per_generation ?? 10 }}">
                                    <div class="glass rounded-xl p-3 border-2 border-transparent transition-all group-hover:border-purple-500/50 model-option h-full">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-xs px-2 py-0.5 rounded-full {{ $model->type === 'image' ? 'bg-blue-500/20 text-blue-400' : 'bg-pink-500/20 text-pink-400' }}">
                                                {{ $model->type === 'image' ? 'ภาพ' : 'วีดีโอ' }}
                                            </span>
                                            <span class="text-xs text-gray-500 ml-auto">{{ $model->vram_required_gb }}GB</span>
                                        </div>
                                        <h4 class="font-medium text-sm text-white">{{ $model->name }}</h4>
                                        <p class="text-xs text-gray-500 mt-1 line-clamp-2">{{ $model->description }}</p>
                                        <div class="mt-2 flex items-center justify-between">
                                            <span class="text-xs text-purple-400">
                                                <i class="fas fa-coins mr-1"></i>{{ $model->credits_per_generation ?? 10 }} Credits
                                            </span>
                                        </div>
                                    </div>
                                </label>
                                @empty
                                <div class="col-span-full text-center py-8 text-gray-500">
                                    <i class="fas fa-cube text-4xl mb-3 opacity-50"></i>
                                    <p>ยังไม่มีโมเดลในระบบ</p>
                                </div>
                                @endforelse
                            </div>
                        </div>

                        <!-- Prompt Input -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-magic text-purple-400 mr-2"></i>Prompt <span class="text-red-400">*</span>
                            </label>
                            <div class="relative">
                                <textarea name="prompt" rows="3" required id="promptInput"
                                    class="w-full glass-input rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500/50 resize-none pr-20"
                                    placeholder="บรรยายสิ่งที่คุณต้องการสร้าง... เช่น 'มังกรบินเหนือทะเลสาบคริสตัล ยามพระอาทิตย์ตก, ultra detailed, 8k'">{{ old('prompt', request('prompt')) }}</textarea>
                                <div class="absolute bottom-3 right-3 flex items-center gap-2">
                                    <button type="button" onclick="clearPrompt()" class="text-gray-500 hover:text-gray-400 p-1" title="ล้าง">
                                        <i class="fas fa-times"></i>
                                    </button>
                                    <span class="text-xs text-gray-500" id="charCount">0</span>
                                </div>
                            </div>
                        </div>

                        <!-- Negative Prompt -->
                        <div class="mb-4">
                            <label class="block text-sm font-medium text-gray-300 mb-2">
                                <i class="fas fa-ban text-red-400 mr-2"></i>Negative Prompt
                                <span class="text-gray-500 font-normal">(ไม่บังคับ)</span>
                            </label>
                            <textarea name="negative_prompt" rows="2"
                                class="w-full glass-input rounded-xl px-4 py-3 text-white placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-purple-500/50 resize-none"
                                placeholder="สิ่งที่ไม่ต้องการ... เช่น 'blurry, low quality, distorted, watermark'">{{ old('negative_prompt') }}</textarea>
                        </div>

                        <!-- Quick Settings -->
                        <div class="mb-4 grid grid-cols-2 sm:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">ขนาด</label>
                                <select name="size" class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                    <option value="512x512">512 x 512</option>
                                    <option value="768x768">768 x 768</option>
                                    <option value="1024x1024" selected>1024 x 1024</option>
                                    <option value="1024x768">1024 x 768</option>
                                    <option value="768x1024">768 x 1024</option>
                                    <option value="1280x720">1280 x 720 (HD)</option>
                                    <option value="1920x1080">1920 x 1080 (FHD)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">คุณภาพ</label>
                                <select name="quality" class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                    <option value="draft">Draft (เร็ว)</option>
                                    <option value="normal" selected>Normal</option>
                                    <option value="high">High Quality</option>
                                    <option value="ultra">Ultra (ช้า)</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">สไตล์</label>
                                <select name="style" class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                    <option value="">ไม่ระบุ</option>
                                    <option value="anime">Anime</option>
                                    <option value="realistic">Realistic</option>
                                    <option value="artistic">Artistic</option>
                                    <option value="3d">3D Render</option>
                                    <option value="pixel">Pixel Art</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-gray-400 mb-1">การเผยแพร่</label>
                                <select name="visibility" class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                    <option value="private">ส่วนตัว</option>
                                    <option value="unlisted">ไม่แสดงสาธารณะ</option>
                                    <option value="public">สาธารณะ</option>
                                </select>
                            </div>
                        </div>

                        <!-- Advanced Settings Toggle -->
                        <div class="mb-6">
                            <button type="button" onclick="toggleAdvanced()" class="text-purple-400 hover:text-purple-300 text-sm flex items-center gap-2">
                                <i class="fas fa-sliders-h"></i>
                                ตั้งค่าขั้นสูง
                                <i class="fas fa-chevron-down transition-transform" id="advancedIcon"></i>
                            </button>

                            <div id="advancedSettings" class="hidden mt-4 p-4 glass rounded-xl space-y-4">
                                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Steps</label>
                                        <input type="number" name="steps" value="30" min="10" max="150"
                                            class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                        <p class="text-xs text-gray-600 mt-1">10-150</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">CFG Scale</label>
                                        <input type="number" name="cfg_scale" value="7.5" min="1" max="30" step="0.5"
                                            class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                        <p class="text-xs text-gray-600 mt-1">1-30</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Seed</label>
                                        <input type="number" name="seed" placeholder="สุ่ม"
                                            class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                        <p class="text-xs text-gray-600 mt-1">ว่าง = สุ่ม</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs text-gray-400 mb-1">Sampler</label>
                                        <select name="sampler" class="w-full glass-input rounded-lg px-3 py-2 text-sm focus:outline-none">
                                            <option value="euler_a">Euler A</option>
                                            <option value="euler">Euler</option>
                                            <option value="dpm++_2m" selected>DPM++ 2M</option>
                                            <option value="dpm++_sde">DPM++ SDE</option>
                                            <option value="ddim">DDIM</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Error Message -->
                        @if(session('error'))
                        <div class="mb-4 p-4 rounded-xl {{ session('no_workers') ? 'bg-red-500/10 border border-red-500/30' : 'bg-yellow-500/10 border border-yellow-500/30' }}">
                            <div class="flex items-center">
                                <i class="fas {{ session('no_workers') ? 'fa-exclamation-triangle text-red-400' : 'fa-info-circle text-yellow-400' }} mr-3"></i>
                                <span class="{{ session('no_workers') ? 'text-red-400' : 'text-yellow-400' }}">{{ session('error') }}</span>
                            </div>
                        </div>
                        @endif

                        <!-- Cost Summary -->
                        <div class="mb-4 p-4 glass rounded-xl">
                            <div class="flex items-center justify-between">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                                        <i class="fas fa-coins text-purple-400"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm text-gray-400">ค่าใช้จ่ายโดยประมาณ</p>
                                        <p class="text-xl font-bold text-white" id="estimatedCost">10 Credits</p>
                                    </div>
                                </div>
                                @auth
                                <div class="text-right">
                                    <p class="text-xs text-gray-500">ยอดคงเหลือ</p>
                                    <p class="text-lg font-semibold {{ auth()->user()->credits >= 10 ? 'text-green-400' : 'text-red-400' }}">
                                        {{ number_format(auth()->user()->credits, 0) }} Credits
                                    </p>
                                </div>
                                @endauth
                            </div>
                        </div>

                        <!-- Submit Button -->
                        @guest
                        <div class="text-center py-4">
                            <p class="text-gray-400 mb-4">กรุณาเข้าสู่ระบบเพื่อสร้างผลงาน</p>
                            <div class="flex justify-center gap-4">
                                <a href="{{ route('login') }}" class="px-6 py-3 rounded-xl bg-purple-600 hover:bg-purple-700 font-medium transition">
                                    เข้าสู่ระบบ
                                </a>
                                <a href="{{ route('register') }}" class="px-6 py-3 rounded-xl glass hover:bg-white/10 font-medium transition">
                                    สมัครสมาชิก
                                </a>
                            </div>
                        </div>
                        @else
                            @if($platformStats['has_workers'])
                            <button type="submit" id="generateBtn"
                                class="w-full bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 py-4 rounded-xl font-bold text-lg transition glow-purple flex items-center justify-center gap-3"
                                {{ auth()->user()->credits < 10 ? 'disabled' : '' }}>
                                <i class="fas fa-wand-magic-sparkles"></i>
                                สร้างผลงาน
                            </button>
                            @if(auth()->user()->credits < 10)
                            <p class="text-center text-red-400 text-sm mt-2">
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                เครดิตไม่เพียงพอ <a href="{{ route('credits.buy') }}" class="underline">ซื้อเครดิต</a>
                            </p>
                            @endif
                            @else
                            <button type="button" disabled
                                class="w-full bg-gray-700 cursor-not-allowed py-4 rounded-xl font-bold text-lg flex items-center justify-center gap-3 opacity-60">
                                <i class="fas fa-power-off"></i>
                                ไม่มี Worker ออนไลน์
                            </button>
                            <p class="text-center text-gray-500 text-sm mt-2">
                                กรุณารอ Worker เชื่อมต่อ หรือ <a href="{{ route('download') }}" class="text-purple-400 hover:underline">เป็น Worker เอง</a>
                            </p>
                            @endif
                        @endguest
                    </form>
                </div>

                <!-- File Retention Notice -->
                <div class="mt-4 p-4 rounded-xl bg-orange-500/5 border border-orange-500/20">
                    <div class="flex items-start gap-3">
                        <i class="fas fa-clock text-orange-400 mt-0.5"></i>
                        <div class="text-sm">
                            <p class="text-orange-400 font-medium">นโยบายการเก็บไฟล์</p>
                            <p class="text-gray-400 mt-1">
                                ไฟล์ผลงานจะถูกเก็บในระบบเพียง <strong class="text-orange-300">48 ชั่วโมง</strong> หลังจากนั้นจะถูกลบอัตโนมัติ
                                กรุณาดาวน์โหลดหรือเชื่อมต่อ Google Drive เพื่อบันทึกผลงานอัตโนมัติ
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar -->
            <div class="lg:col-span-4 space-y-4">

                <!-- Platform Status -->
                <div class="glass-card rounded-2xl p-5">
                    <h3 class="font-semibold mb-4 flex items-center justify-between">
                        <span><i class="fas fa-server text-purple-400 mr-2"></i>สถานะระบบ</span>
                        @if($platformStats['has_workers'])
                        <span class="flex items-center gap-1 text-xs text-green-400">
                            <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse"></span>
                            ออนไลน์
                        </span>
                        @else
                        <span class="flex items-center gap-1 text-xs text-red-400">
                            <span class="w-2 h-2 bg-red-400 rounded-full"></span>
                            ออฟไลน์
                        </span>
                        @endif
                    </h3>
                    <div class="space-y-3">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400">GPU Workers</span>
                            <span class="{{ $platformStats['workers']['online'] > 0 ? 'text-green-400' : 'text-red-400' }} font-semibold">
                                {{ $platformStats['workers']['online'] }} เครื่อง
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400">Hashrate รวม</span>
                            <span class="text-purple-400 font-semibold">{{ $platformStats['hashrate']['formatted'] }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400">งานในคิว</span>
                            <span class="{{ $platformStats['queue']['total'] > 10 ? 'text-yellow-400' : 'text-green-400' }} font-semibold">
                                {{ $platformStats['queue']['total'] }} งาน
                            </span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-400">เวลาเฉลี่ย</span>
                            <span class="text-blue-400 font-semibold">{{ $platformStats['performance']['avg_time_formatted'] }}</span>
                        </div>
                    </div>
                </div>

                <!-- User Credits -->
                @auth
                <div class="glass-card rounded-2xl p-5">
                    <h3 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-wallet text-yellow-400 mr-2"></i>กระเป๋าเงิน
                    </h3>
                    <div class="text-center py-4">
                        <p class="text-3xl font-bold text-white">{{ number_format(auth()->user()->credits, 0) }}</p>
                        <p class="text-gray-400 text-sm">Credits</p>
                    </div>
                    <a href="{{ route('credits.buy') }}" class="block w-full text-center py-2.5 rounded-xl bg-gradient-to-r from-yellow-600 to-orange-600 hover:from-yellow-500 hover:to-orange-500 font-medium transition">
                        <i class="fas fa-plus mr-2"></i>เติมเครดิต
                    </a>
                </div>

                <!-- Google Drive Status -->
                <div class="glass-card rounded-2xl p-5">
                    <h3 class="font-semibold mb-4 flex items-center">
                        <i class="fab fa-google-drive text-blue-400 mr-2"></i>Google Drive
                    </h3>
                    @if(auth()->user()->google_drive_connected)
                    <div class="flex items-center gap-3 p-3 glass rounded-xl">
                        <div class="w-10 h-10 rounded-full bg-green-500/20 flex items-center justify-center">
                            <i class="fas fa-check text-green-400"></i>
                        </div>
                        <div>
                            <p class="text-green-400 font-medium text-sm">เชื่อมต่อแล้ว</p>
                            <p class="text-gray-500 text-xs">{{ auth()->user()->google_email }}</p>
                        </div>
                    </div>
                    <a href="{{ route('settings.google-drive') }}" class="block text-center text-purple-400 text-sm mt-3 hover:underline">
                        <i class="fas fa-cog mr-1"></i>จัดการการเชื่อมต่อ
                    </a>
                    @else
                    <div class="text-center py-4">
                        <div class="w-16 h-16 mx-auto mb-3 rounded-full bg-gray-800 flex items-center justify-center">
                            <i class="fab fa-google-drive text-gray-600 text-2xl"></i>
                        </div>
                        <p class="text-gray-400 text-sm mb-3">ยังไม่ได้เชื่อมต่อ</p>
                        <a href="{{ route('settings.google-drive') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 hover:bg-blue-700 text-sm font-medium transition">
                            <i class="fab fa-google"></i>
                            เชื่อมต่อเลย
                        </a>
                    </div>
                    @endif
                </div>
                @endauth

                <!-- Recent Generations -->
                @auth
                @if($recentGenerations->count() > 0)
                <div class="glass-card rounded-2xl p-5">
                    <h3 class="font-semibold mb-4 flex items-center justify-between">
                        <span><i class="fas fa-history text-purple-400 mr-2"></i>ล่าสุดของคุณ</span>
                        <a href="{{ route('generate.my') }}" class="text-purple-400 text-xs hover:underline">ดูทั้งหมด</a>
                    </h3>
                    <div class="space-y-2">
                        @foreach($recentGenerations->take(4) as $gen)
                        <a href="{{ route('generate.status', $gen->job_id) }}"
                            class="block p-3 glass rounded-xl hover:bg-white/5 transition">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg bg-gray-800 flex items-center justify-center overflow-hidden flex-shrink-0">
                                    @if($gen->result_thumbnail && $gen->status === 'completed')
                                    <img src="{{ $gen->result_thumbnail }}" alt="" class="w-full h-full object-cover">
                                    @else
                                    <i class="fas fa-{{ $gen->status === 'processing' ? 'spinner fa-spin' : ($gen->status === 'completed' ? 'image' : 'clock') }} text-gray-600"></i>
                                    @endif
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-sm truncate text-gray-300">{{ Str::limit($gen->prompt, 25) }}</p>
                                    <p class="text-xs {{ $gen->status === 'completed' ? 'text-green-400' : ($gen->status === 'processing' ? 'text-yellow-400' : ($gen->status === 'failed' ? 'text-red-400' : 'text-gray-500')) }}">
                                        {{ $gen->status === 'completed' ? 'สำเร็จ' : ($gen->status === 'processing' ? 'กำลังสร้าง...' : ($gen->status === 'failed' ? 'ล้มเหลว' : 'รอคิว')) }}
                                    </p>
                                </div>
                            </div>
                        </a>
                        @endforeach
                    </div>
                </div>
                @endif
                @endauth

                <!-- Tips -->
                <div class="glass-card rounded-2xl p-5">
                    <h3 class="font-semibold mb-4 flex items-center">
                        <i class="fas fa-lightbulb text-yellow-400 mr-2"></i>เคล็ดลับ
                    </h3>
                    <ul class="space-y-2 text-sm text-gray-400">
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-400 mt-1 text-xs"></i>
                            <span>บรรยายให้ละเอียดและชัดเจน</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-400 mt-1 text-xs"></i>
                            <span>ใช้ Negative Prompt หลีกเลี่ยงสิ่งไม่ต้องการ</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-400 mt-1 text-xs"></i>
                            <span>Steps สูง = คุณภาพดีแต่ช้าขึ้น</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i class="fas fa-check text-green-400 mt-1 text-xs"></i>
                            <span>บันทึก Seed เพื่อทำซ้ำผลลัพธ์</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Gallery Preview -->
        @if($publicGallery->count() > 0)
        <div class="mt-12">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold">
                    <i class="fas fa-fire text-orange-400 mr-2"></i>ผลงานยอดนิยม
                </h2>
                <a href="{{ route('gallery') }}" class="text-purple-400 hover:text-purple-300 text-sm">
                    ดูทั้งหมด <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-3">
                @foreach($publicGallery->take(12) as $gen)
                <div class="glass-card rounded-xl overflow-hidden card-hover group">
                    <div class="aspect-square bg-gray-800 relative">
                        @if($gen->result_thumbnail)
                        <img src="{{ $gen->result_thumbnail }}" alt="" class="w-full h-full object-cover">
                        @else
                        <div class="w-full h-full flex items-center justify-center text-gray-700">
                            <i class="fas fa-image text-3xl"></i>
                        </div>
                        @endif
                        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition flex items-end p-2">
                            <p class="text-xs text-gray-300 line-clamp-2">{{ Str::limit($gen->prompt, 50) }}</p>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>

<style>
.tab-btn {
    color: #9ca3af;
    border-bottom: 2px solid transparent;
}
.tab-btn:hover {
    color: #d1d5db;
    background: rgba(255,255,255,0.02);
}
.tab-btn.active {
    color: white;
    border-bottom-color: #a855f7;
    background: rgba(168, 85, 247, 0.05);
}
.model-card input:checked + .model-option {
    border-color: #a855f7;
    background: rgba(168, 85, 247, 0.1);
}
</style>

@push('scripts')
<script>
    // Tab switching
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            const type = this.dataset.tab;
            // Filter models by type
            document.querySelectorAll('.model-card').forEach(card => {
                const modelType = card.querySelector('input').dataset.type;
                card.style.display = (type === 'video' && modelType === 'video') || (type === 'image' && modelType === 'image') || type === 'all' ? '' : 'none';
            });
        });
    });

    // Character count
    const promptInput = document.getElementById('promptInput');
    const charCount = document.getElementById('charCount');

    promptInput.addEventListener('input', function() {
        charCount.textContent = this.value.length;
    });
    charCount.textContent = promptInput.value.length;

    function clearPrompt() {
        promptInput.value = '';
        charCount.textContent = '0';
    }

    // Advanced settings toggle
    function toggleAdvanced() {
        const settings = document.getElementById('advancedSettings');
        const icon = document.getElementById('advancedIcon');
        settings.classList.toggle('hidden');
        icon.classList.toggle('rotate-180');
    }

    // Update estimated cost
    function updateCost() {
        const selectedModel = document.querySelector('.model-card input:checked');
        if (selectedModel) {
            const credits = selectedModel.dataset.credits || 10;
            document.getElementById('estimatedCost').textContent = credits + ' Credits';
        }
    }

    document.querySelectorAll('.model-card input').forEach(input => {
        input.addEventListener('change', updateCost);
    });
    updateCost();

    // Form submission
    document.getElementById('generateForm')?.addEventListener('submit', function(e) {
        const btn = document.getElementById('generateBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>กำลังสร้าง...';
        }
    });
</script>
@endpush
@endsection
