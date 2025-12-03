@extends('layouts.admin')

@section('title', 'Settings')
@section('header', 'Platform Settings')

@section('content')
<div class="max-w-4xl space-y-6">

    @if(session('success'))
    <div class="bg-green-500/10 border border-green-500/30 rounded-xl p-4 flex items-center gap-3">
        <i class="fas fa-check-circle text-green-400"></i>
        <span class="text-green-400">{{ session('success') }}</span>
    </div>
    @endif

    @if($errors->any())
    <div class="bg-red-500/10 border border-red-500/30 rounded-xl p-4">
        <div class="flex items-center gap-3 mb-2">
            <i class="fas fa-exclamation-circle text-red-400"></i>
            <span class="text-red-400 font-medium">เกิดข้อผิดพลาด</span>
        </div>
        <ul class="list-disc list-inside text-red-400 text-sm">
            @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Site Identity & Branding -->
    <form action="{{ route('admin.settings.update') }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method('PUT')

        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-purple-500/20 flex items-center justify-center">
                    <i class="fas fa-palette text-purple-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Site Identity & Branding</h3>
                    <p class="text-sm text-gray-400">ตั้งค่าชื่อเว็บไซต์ โลโก้ และ favicon</p>
                </div>
            </div>

            <div class="space-y-6">
                <!-- Site Name & Tagline -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">
                            <i class="fas fa-heading mr-1"></i>ชื่อเว็บไซต์ <span class="text-red-400">*</span>
                        </label>
                        <input type="text" name="site_name"
                            value="{{ \App\Models\SiteSetting::get('site_name', 'GPU Share X') }}"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                            required>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">
                            <i class="fas fa-quote-left mr-1"></i>คำโปรย (Tagline)
                        </label>
                        <input type="text" name="site_tagline"
                            value="{{ \App\Models\SiteSetting::get('site_tagline') }}"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                            placeholder="แพลตฟอร์ม AI Generation แบบกระจาย">
                    </div>
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-align-left mr-1"></i>คำอธิบายเว็บไซต์
                    </label>
                    <textarea name="site_description" rows="2"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        placeholder="คำอธิบายสั้นๆ เกี่ยวกับเว็บไซต์">{{ \App\Models\SiteSetting::get('site_description') }}</textarea>
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-envelope mr-1"></i>อีเมลติดต่อ
                    </label>
                    <input type="email" name="contact_email"
                        value="{{ \App\Models\SiteSetting::get('contact_email') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        placeholder="contact@example.com">
                </div>

                <!-- Logo Upload -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">
                            <i class="fas fa-image mr-1"></i>โลโก้หลัก
                        </label>
                        <div class="flex items-start gap-4">
                            @php $currentLogo = \App\Models\SiteSetting::get('site_logo'); @endphp
                            <div class="w-24 h-24 bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-600" id="logoPreview">
                                @if($currentLogo)
                                <img src="{{ asset('storage/' . $currentLogo) }}" alt="Logo" class="max-w-full max-h-full object-contain">
                                @else
                                <i class="fas fa-image text-3xl text-gray-500"></i>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" name="site_logo" id="logoInput" accept="image/*" class="hidden">
                                <button type="button" onclick="document.getElementById('logoInput').click()"
                                    class="w-full px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition mb-2">
                                    <i class="fas fa-upload mr-2"></i>อัพโหลดโลโก้
                                </button>
                                @if($currentLogo)
                                <button type="button" onclick="deleteImage('site_logo')"
                                    class="w-full px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-sm transition">
                                    <i class="fas fa-trash mr-2"></i>ลบโลโก้
                                </button>
                                @endif
                                <p class="text-xs text-gray-500 mt-2">PNG, JPG, SVG (แนะนำ 512x512px)</p>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm text-gray-400 mb-2">
                            <i class="fas fa-star mr-1"></i>Favicon
                        </label>
                        <div class="flex items-start gap-4">
                            @php $currentFavicon = \App\Models\SiteSetting::get('site_favicon'); @endphp
                            <div class="w-24 h-24 bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-600" id="faviconPreview">
                                @if($currentFavicon)
                                <img src="{{ asset('storage/' . $currentFavicon) }}" alt="Favicon" class="max-w-full max-h-full object-contain">
                                @else
                                <i class="fas fa-star text-3xl text-gray-500"></i>
                                @endif
                            </div>
                            <div class="flex-1">
                                <input type="file" name="site_favicon" id="faviconInput" accept="image/png,image/x-icon,image/svg+xml" class="hidden">
                                <button type="button" onclick="document.getElementById('faviconInput').click()"
                                    class="w-full px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition mb-2">
                                    <i class="fas fa-upload mr-2"></i>อัพโหลด Favicon
                                </button>
                                @if($currentFavicon)
                                <button type="button" onclick="deleteImage('site_favicon')"
                                    class="w-full px-4 py-2 bg-red-500/20 hover:bg-red-500/30 text-red-400 rounded-lg text-sm transition">
                                    <i class="fas fa-trash mr-2"></i>ลบ Favicon
                                </button>
                                @endif
                                <p class="text-xs text-gray-500 mt-2">PNG, ICO, SVG (แนะนำ 32x32 หรือ 64x64px)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Colors -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">
                            <i class="fas fa-fill-drip mr-1"></i>สีหลัก (Primary Color)
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="primary_color" id="primaryColor"
                                value="{{ \App\Models\SiteSetting::get('primary_color', '#8B5CF6') }}"
                                class="w-12 h-12 rounded-lg cursor-pointer border-0 p-0">
                            <input type="text" id="primaryColorText"
                                value="{{ \App\Models\SiteSetting::get('primary_color', '#8B5CF6') }}"
                                class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono"
                                readonly>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm text-gray-400 mb-2">
                            <i class="fas fa-fill-drip mr-1"></i>สีรอง (Secondary Color)
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="color" name="secondary_color" id="secondaryColor"
                                value="{{ \App\Models\SiteSetting::get('secondary_color', '#EC4899') }}"
                                class="w-12 h-12 rounded-lg cursor-pointer border-0 p-0">
                            <input type="text" id="secondaryColorText"
                                value="{{ \App\Models\SiteSetting::get('secondary_color', '#EC4899') }}"
                                class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white font-mono"
                                readonly>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Links -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mt-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-blue-500/20 flex items-center justify-center">
                    <i class="fas fa-share-alt text-blue-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Social Links</h3>
                    <p class="text-sm text-gray-400">ลิงก์ไปยัง Social Media ต่างๆ</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fab fa-facebook mr-1"></i>Facebook URL
                    </label>
                    <input type="url" name="facebook_url"
                        value="{{ \App\Models\SiteSetting::get('facebook_url') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        placeholder="https://facebook.com/yourpage">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fab fa-twitter mr-1"></i>Twitter URL
                    </label>
                    <input type="url" name="twitter_url"
                        value="{{ \App\Models\SiteSetting::get('twitter_url') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        placeholder="https://twitter.com/yourhandle">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fab fa-discord mr-1"></i>Discord URL
                    </label>
                    <input type="url" name="discord_url"
                        value="{{ \App\Models\SiteSetting::get('discord_url') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        placeholder="https://discord.gg/invite">
                </div>
                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fab fa-github mr-1"></i>GitHub URL
                    </label>
                    <input type="url" name="github_url"
                        value="{{ \App\Models\SiteSetting::get('github_url') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        placeholder="https://github.com/yourrepo">
                </div>
            </div>
        </div>

        <!-- SEO Settings -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mt-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-green-500/20 flex items-center justify-center">
                    <i class="fas fa-search text-green-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">SEO Settings</h3>
                    <p class="text-sm text-gray-400">ตั้งค่าสำหรับ Search Engine และ Social Sharing</p>
                </div>
            </div>

            <div class="space-y-6">
                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-tags mr-1"></i>Meta Keywords
                    </label>
                    <input type="text" name="meta_keywords"
                        value="{{ \App\Models\SiteSetting::get('meta_keywords') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                        placeholder="AI, GPU, Image Generation, Video Generation">
                </div>

                <div>
                    <label class="block text-sm text-gray-400 mb-2">
                        <i class="fas fa-image mr-1"></i>OG Image (สำหรับ Social Sharing)
                    </label>
                    <div class="flex items-start gap-4">
                        @php $currentOgImage = \App\Models\SiteSetting::get('og_image'); @endphp
                        <div class="w-40 h-24 bg-gray-700 rounded-lg flex items-center justify-center overflow-hidden border-2 border-dashed border-gray-600" id="ogImagePreview">
                            @if($currentOgImage)
                            <img src="{{ asset('storage/' . $currentOgImage) }}" alt="OG Image" class="max-w-full max-h-full object-cover">
                            @else
                            <i class="fas fa-image text-3xl text-gray-500"></i>
                            @endif
                        </div>
                        <div class="flex-1">
                            <input type="file" name="og_image" id="ogImageInput" accept="image/*" class="hidden">
                            <button type="button" onclick="document.getElementById('ogImageInput').click()"
                                class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition">
                                <i class="fas fa-upload mr-2"></i>อัพโหลด OG Image
                            </button>
                            <p class="text-xs text-gray-500 mt-2">แนะนำขนาด 1200x630px สำหรับ Facebook/Twitter</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Settings -->
        <div class="bg-gray-800 rounded-xl border border-gray-700 p-6 mt-6">
            <div class="flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-lg bg-yellow-500/20 flex items-center justify-center">
                    <i class="fas fa-copyright text-yellow-400"></i>
                </div>
                <div>
                    <h3 class="text-lg font-semibold">Footer Settings</h3>
                    <p class="text-sm text-gray-400">ตั้งค่าข้อความ Footer</p>
                </div>
            </div>

            <div>
                <label class="block text-sm text-gray-400 mb-2">
                    <i class="fas fa-paragraph mr-1"></i>ข้อความ Footer
                </label>
                <input type="text" name="footer_text"
                    value="{{ \App\Models\SiteSetting::get('footer_text', '© ' . date('Y') . ' GPU Share X. All rights reserved.') }}"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500"
                    placeholder="© 2024 Your Company. All rights reserved.">
            </div>
        </div>

        <!-- Submit Button -->
        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-8 py-3 bg-gradient-to-r from-purple-600 to-pink-600 hover:from-purple-500 hover:to-pink-500 rounded-lg font-medium transition flex items-center gap-2">
                <i class="fas fa-save"></i>
                บันทึกการตั้งค่า
            </button>
        </div>
    </form>

    <!-- API Keys & Integrations -->
    <form action="{{ route('admin.settings.api-keys') }}" method="POST" class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        @csrf
        @method('PUT')
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-indigo-500/20 flex items-center justify-center">
                <i class="fas fa-key text-indigo-400"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold">API Keys & Integrations</h3>
                <p class="text-sm text-gray-400">ตั้งค่า API Keys สำหรับบริการต่างๆ</p>
            </div>
        </div>

        <div class="space-y-6">
            <!-- HuggingFace API Token -->
            <div>
                <label class="block text-sm text-gray-400 mb-2">
                    <i class="fas fa-robot mr-1"></i>HuggingFace API Token
                </label>
                <div class="flex gap-3">
                    <div class="relative flex-1">
                        <input type="password" name="huggingface_token" id="hf-token"
                            value="{{ \App\Models\SiteSetting::get('huggingface_token') }}"
                            class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 pr-12 font-mono"
                            placeholder="hf_xxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                        <button type="button" onclick="togglePasswordVisibility('hf-token', 'hf-toggle-icon')"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                            <i id="hf-toggle-icon" class="fas fa-eye"></i>
                        </button>
                    </div>
                    <button type="button" onclick="testHuggingFaceToken()"
                        class="px-4 py-3 bg-gray-700 hover:bg-gray-600 rounded-lg text-sm transition flex items-center gap-2">
                        <i class="fas fa-plug"></i>
                        ทดสอบ
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">
                    ใช้สำหรับค้นหาและดาวน์โหลด Model จาก HuggingFace |
                    <a href="https://huggingface.co/settings/tokens" target="_blank" class="text-purple-400 hover:text-purple-300">รับ Token ที่นี่</a>
                </p>
                <div id="hf-test-result" class="mt-2 hidden">
                    <span class="text-sm flex items-center gap-2"></span>
                </div>
            </div>

            <!-- OpenAI API Key (Optional) -->
            <div>
                <label class="block text-sm text-gray-400 mb-2">
                    <i class="fas fa-brain mr-1"></i>OpenAI API Key <span class="text-gray-600">(Optional)</span>
                </label>
                <div class="relative">
                    <input type="password" name="openai_api_key" id="openai-key"
                        value="{{ \App\Models\SiteSetting::get('openai_api_key') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 pr-12 font-mono"
                        placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    <button type="button" onclick="togglePasswordVisibility('openai-key', 'openai-toggle-icon')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                        <i id="openai-toggle-icon" class="fas fa-eye"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">สำหรับฟีเจอร์ AI เพิ่มเติม เช่น Prompt Enhancement</p>
            </div>

            <!-- Stability API Key (Optional) -->
            <div>
                <label class="block text-sm text-gray-400 mb-2">
                    <i class="fas fa-paint-brush mr-1"></i>Stability AI API Key <span class="text-gray-600">(Optional)</span>
                </label>
                <div class="relative">
                    <input type="password" name="stability_api_key" id="stability-key"
                        value="{{ \App\Models\SiteSetting::get('stability_api_key') }}"
                        class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-purple-500 focus:ring-1 focus:ring-purple-500 pr-12 font-mono"
                        placeholder="sk-xxxxxxxxxxxxxxxxxxxxxxxxxxxx">
                    <button type="button" onclick="togglePasswordVisibility('stability-key', 'stability-toggle-icon')"
                        class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-white">
                        <i id="stability-toggle-icon" class="fas fa-eye"></i>
                    </button>
                </div>
                <p class="text-xs text-gray-500 mt-2">สำหรับใช้ Stability AI API โดยตรง</p>
            </div>
        </div>

        <div class="mt-6 flex justify-end">
            <button type="submit" class="px-6 py-3 bg-indigo-600 hover:bg-indigo-700 rounded-lg font-medium transition flex items-center gap-2">
                <i class="fas fa-save"></i>
                บันทึก API Keys
            </button>
        </div>
    </form>

    <!-- Platform Configuration (Existing) -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-orange-500/20 flex items-center justify-center">
                <i class="fas fa-cogs text-orange-400"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold">Platform Configuration</h3>
                <p class="text-sm text-gray-400">ตั้งค่าระบบแพลตฟอร์ม</p>
            </div>
        </div>
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

            <button type="submit" class="bg-purple-600 hover:bg-purple-700 px-6 py-3 rounded-lg">
                <i class="fas fa-save mr-2"></i>Save Settings
            </button>
        </form>
    </div>

    <!-- Maintenance -->
    <div class="bg-gray-800 rounded-xl border border-gray-700 p-6">
        <div class="flex items-center gap-3 mb-6">
            <div class="w-10 h-10 rounded-lg bg-red-500/20 flex items-center justify-center">
                <i class="fas fa-tools text-red-400"></i>
            </div>
            <div>
                <h3 class="text-lg font-semibold">Maintenance</h3>
                <p class="text-sm text-gray-400">เครื่องมือดูแลระบบ</p>
            </div>
        </div>
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
                    <p class="font-medium">Clear Cache</p>
                    <p class="text-sm text-gray-400">Clear all cached data including settings</p>
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

@push('scripts')
<script>
    // Logo preview
    document.getElementById('logoInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('logoPreview').innerHTML = `<img src="${e.target.result}" class="max-w-full max-h-full object-contain">`;
            };
            reader.readAsDataURL(file);
        }
    });

    // Favicon preview
    document.getElementById('faviconInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('faviconPreview').innerHTML = `<img src="${e.target.result}" class="max-w-full max-h-full object-contain">`;
            };
            reader.readAsDataURL(file);
        }
    });

    // OG Image preview
    document.getElementById('ogImageInput').addEventListener('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('ogImagePreview').innerHTML = `<img src="${e.target.result}" class="max-w-full max-h-full object-cover">`;
            };
            reader.readAsDataURL(file);
        }
    });

    // Color picker sync
    document.getElementById('primaryColor').addEventListener('input', function(e) {
        document.getElementById('primaryColorText').value = e.target.value;
    });

    document.getElementById('secondaryColor').addEventListener('input', function(e) {
        document.getElementById('secondaryColorText').value = e.target.value;
    });

    // Delete image
    function deleteImage(key) {
        if (!confirm('คุณแน่ใจหรือไม่ที่จะลบรูปนี้?')) return;

        fetch('{{ route("admin.settings.delete-image") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ key: key }),
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('เกิดข้อผิดพลาดในการลบรูป');
            }
        });
    }

    // Toggle password visibility
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Test HuggingFace Token
    async function testHuggingFaceToken() {
        const token = document.getElementById('hf-token').value;
        const resultDiv = document.getElementById('hf-test-result');
        const resultSpan = resultDiv.querySelector('span');

        if (!token) {
            resultDiv.classList.remove('hidden');
            resultSpan.className = 'text-sm flex items-center gap-2 text-yellow-400';
            resultSpan.innerHTML = '<i class="fas fa-exclamation-triangle"></i> กรุณาใส่ Token ก่อน';
            return;
        }

        resultDiv.classList.remove('hidden');
        resultSpan.className = 'text-sm flex items-center gap-2 text-blue-400';
        resultSpan.innerHTML = '<i class="fas fa-spinner fa-spin"></i> กำลังทดสอบ...';

        try {
            const response = await fetch('{{ route("admin.settings.test-huggingface") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                },
                body: JSON.stringify({ token: token }),
            });

            const data = await response.json();

            if (data.success) {
                resultSpan.className = 'text-sm flex items-center gap-2 text-green-400';
                resultSpan.innerHTML = `<i class="fas fa-check-circle"></i> เชื่อมต่อสำเร็จ! ยินดีต้อนรับ ${data.username || 'User'}`;
            } else {
                resultSpan.className = 'text-sm flex items-center gap-2 text-red-400';
                resultSpan.innerHTML = `<i class="fas fa-times-circle"></i> ${data.message || 'Token ไม่ถูกต้อง'}`;
            }
        } catch (error) {
            resultSpan.className = 'text-sm flex items-center gap-2 text-red-400';
            resultSpan.innerHTML = `<i class="fas fa-times-circle"></i> เกิดข้อผิดพลาด: ${error.message}`;
        }
    }
</script>
@endpush
@endsection
