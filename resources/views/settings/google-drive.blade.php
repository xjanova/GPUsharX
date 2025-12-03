@extends('layouts.app')

@section('title', 'Google Drive Settings')

@section('content')
<div class="min-h-screen py-8">
    <div class="max-w-3xl mx-auto px-4">
        <!-- Header -->
        <div class="mb-8">
            <a href="{{ url()->previous() }}" class="inline-flex items-center text-gray-400 hover:text-white mb-4">
                <i class="fas fa-arrow-left mr-2"></i>ย้อนกลับ
            </a>
            <h1 class="text-3xl font-bold text-white">Google Drive Settings</h1>
            <p class="text-gray-400 mt-2">เชื่อมต่อ Google Drive เพื่อบันทึกผลงานอัตโนมัติ</p>
        </div>

        <!-- Alert Messages -->
        @if(session('success'))
        <div class="mb-6 p-4 rounded-xl bg-green-500/10 border border-green-500/30">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-400 mr-3"></i>
                <span class="text-green-400">{{ session('success') }}</span>
            </div>
        </div>
        @endif

        @if(session('error'))
        <div class="mb-6 p-4 rounded-xl bg-red-500/10 border border-red-500/30">
            <div class="flex items-center">
                <i class="fas fa-exclamation-circle text-red-400 mr-3"></i>
                <span class="text-red-400">{{ session('error') }}</span>
            </div>
        </div>
        @endif

        @if(!($isAvailable ?? true))
        <!-- Library Not Installed Warning -->
        <div class="mb-6 p-5 rounded-2xl bg-yellow-500/10 border border-yellow-500/30">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-yellow-500/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-yellow-400">Google API Client ยังไม่ได้ติดตั้ง</h3>
                    <p class="mt-2 text-gray-400 text-sm">
                        เพื่อใช้งาน Google Drive กรุณาติดตั้ง library โดยรัน command:
                    </p>
                    <code class="block mt-2 p-3 bg-gray-900 rounded-lg text-green-400 text-sm">
                        composer require google/apiclient
                    </code>
                    <p class="mt-2 text-gray-500 text-xs">
                        หลังติดตั้งแล้ว refresh หน้านี้อีกครั้ง
                    </p>
                </div>
            </div>
        </div>
        @endif

        <!-- Info Card -->
        <div class="mb-6 p-5 rounded-2xl bg-blue-500/5 border border-blue-500/20">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-500/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-blue-400">ทำไมต้องเชื่อมต่อ Google Drive?</h3>
                    <ul class="mt-2 space-y-1 text-gray-400 text-sm">
                        <li><i class="fas fa-check text-green-400 mr-2"></i>บันทึกผลงานอัตโนมัติทันทีที่สร้างเสร็จ</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>ไม่ต้องกลัวสูญเสียผลงานหลัง 48 ชม.</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>เข้าถึงได้จากทุกที่ผ่าน Google Drive</li>
                        <li><i class="fas fa-check text-green-400 mr-2"></i>แชร์ผลงานให้คนอื่นได้ง่าย</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Warning Card -->
        <div class="mb-6 p-5 rounded-2xl bg-orange-500/5 border border-orange-500/20">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 rounded-xl bg-orange-500/20 flex items-center justify-center flex-shrink-0">
                    <i class="fas fa-clock text-orange-400 text-xl"></i>
                </div>
                <div>
                    <h3 class="font-semibold text-orange-400">นโยบายการเก็บไฟล์</h3>
                    <p class="mt-2 text-gray-400 text-sm">
                        ไฟล์ผลงานจะถูกเก็บในระบบของเราเพียง <strong class="text-orange-300">48 ชั่วโมง</strong> เท่านั้น
                        หลังจากนั้นจะถูกลบอัตโนมัติเพื่อประหยัดพื้นที่จัดเก็บ
                    </p>
                    <p class="mt-2 text-gray-500 text-xs">
                        หากไม่เชื่อมต่อ Google Drive กรุณาดาวน์โหลดผลงานก่อน 48 ชม.
                    </p>
                </div>
            </div>
        </div>

        <!-- Connection Status -->
        <div class="glass-card rounded-2xl p-6">
            <h2 class="text-lg font-semibold mb-6 flex items-center">
                <i class="fab fa-google-drive text-blue-400 mr-3"></i>
                สถานะการเชื่อมต่อ
            </h2>

            @if(auth()->user()->google_drive_connected)
            <!-- Connected State -->
            <div class="p-5 rounded-xl bg-green-500/10 border border-green-500/30 mb-6">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 rounded-full bg-green-500/20 flex items-center justify-center">
                            <i class="fas fa-check-circle text-green-400 text-2xl"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-green-400 text-lg">เชื่อมต่อแล้ว</p>
                            <p class="text-gray-400 text-sm">
                                <i class="fas fa-envelope mr-1"></i>{{ auth()->user()->google_email }}
                            </p>
                        </div>
                    </div>
                    <div class="text-right">
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs bg-green-500/20 text-green-400">
                            <span class="w-2 h-2 bg-green-400 rounded-full mr-2 animate-pulse"></span>
                            Active
                        </span>
                    </div>
                </div>
            </div>

            <!-- Connected Actions -->
            <div class="space-y-4">
                <div class="p-4 glass rounded-xl flex items-center justify-between">
                    <div>
                        <p class="font-medium">โฟลเดอร์ใน Google Drive</p>
                        <p class="text-gray-500 text-sm">GPU Share Platform</p>
                    </div>
                    @if(auth()->user()->google_drive_folder_id)
                    <a href="https://drive.google.com/drive/folders/{{ auth()->user()->google_drive_folder_id }}"
                       target="_blank"
                       class="text-purple-400 hover:text-purple-300 text-sm">
                        <i class="fas fa-external-link-alt mr-1"></i>เปิดโฟลเดอร์
                    </a>
                    @endif
                </div>

                <div class="flex flex-col sm:flex-row gap-3">
                    <form action="{{ route('settings.google-drive.test') }}" method="POST" class="flex-1">
                        @csrf
                        <button type="submit" class="w-full py-3 rounded-xl glass hover:bg-white/10 font-medium transition flex items-center justify-center gap-2">
                            <i class="fas fa-upload"></i>
                            ทดสอบอัพโหลด
                        </button>
                    </form>
                    <form action="{{ route('settings.google-drive.disconnect') }}" method="POST" class="flex-1"
                          onsubmit="return confirm('ยืนยันยกเลิกการเชื่อมต่อ? ผลงานใหม่จะไม่ถูกบันทึกอัตโนมัติ')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="w-full py-3 rounded-xl bg-red-500/10 hover:bg-red-500/20 text-red-400 font-medium transition flex items-center justify-center gap-2">
                            <i class="fas fa-unlink"></i>
                            ยกเลิกการเชื่อมต่อ
                        </button>
                    </form>
                </div>
            </div>

            @else
            <!-- Not Connected State -->
            <div class="text-center py-8">
                <div class="w-24 h-24 mx-auto mb-6 rounded-full bg-gray-800 flex items-center justify-center">
                    <i class="fab fa-google-drive text-gray-600 text-4xl"></i>
                </div>
                <h3 class="text-xl font-semibold text-white mb-2">ยังไม่ได้เชื่อมต่อ Google Drive</h3>
                <p class="text-gray-400 mb-8 max-w-md mx-auto">
                    เชื่อมต่อ Google Drive เพื่อให้ผลงานของคุณถูกบันทึกอัตโนมัติทุกครั้งที่สร้างเสร็จ
                </p>

                @if($isAvailable ?? true)
                <a href="{{ route('settings.google-drive.connect') }}"
                   class="inline-flex items-center gap-3 px-8 py-4 rounded-xl bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-500 hover:to-blue-600 text-white font-bold text-lg transition transform hover:scale-105">
                    <i class="fab fa-google text-xl"></i>
                    เชื่อมต่อด้วย Google
                </a>
                @else
                <button disabled class="inline-flex items-center gap-3 px-8 py-4 rounded-xl bg-gray-700 text-gray-400 font-bold text-lg cursor-not-allowed">
                    <i class="fab fa-google text-xl"></i>
                    ยังไม่พร้อมใช้งาน
                </button>
                <p class="text-yellow-500 text-sm mt-4">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    กรุณาติดตั้ง Google API Client ก่อน (ดูคำแนะนำด้านบน)
                </p>
                @endif

                <p class="text-gray-500 text-xs mt-4">
                    <i class="fas fa-lock mr-1"></i>
                    เราจะขอสิทธิ์เฉพาะการสร้างและอัพโหลดไฟล์เท่านั้น
                </p>
            </div>
            @endif
        </div>

        <!-- Auto Upload Settings -->
        @if(auth()->user()->google_drive_connected)
        <div class="glass-card rounded-2xl p-6 mt-6">
            <h2 class="text-lg font-semibold mb-6 flex items-center">
                <i class="fas fa-cog text-purple-400 mr-3"></i>
                ตั้งค่าการอัพโหลด
            </h2>

            <div class="space-y-4">
                <label class="flex items-center justify-between p-4 glass rounded-xl cursor-pointer hover:bg-white/5">
                    <div>
                        <p class="font-medium">อัพโหลดอัตโนมัติ</p>
                        <p class="text-gray-500 text-sm">บันทึกผลงานไปยัง Google Drive ทันทีที่สร้างเสร็จ</p>
                    </div>
                    <div class="relative">
                        <input type="checkbox" class="sr-only peer" checked disabled>
                        <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-green-500 transition"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition peer-checked:translate-x-5"></div>
                    </div>
                </label>

                <label class="flex items-center justify-between p-4 glass rounded-xl cursor-pointer hover:bg-white/5">
                    <div>
                        <p class="font-medium">จัดเรียงตามวันที่</p>
                        <p class="text-gray-500 text-sm">สร้าง subfolder ตามปี/เดือน</p>
                    </div>
                    <div class="relative">
                        <input type="checkbox" class="sr-only peer" disabled>
                        <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:bg-green-500 transition"></div>
                        <div class="absolute left-0.5 top-0.5 w-5 h-5 bg-white rounded-full transition peer-checked:translate-x-5"></div>
                    </div>
                </label>
            </div>

            <p class="text-gray-600 text-xs mt-4">
                <i class="fas fa-info-circle mr-1"></i>
                ตั้งค่าเพิ่มเติมจะเปิดให้ใช้งานเร็วๆ นี้
            </p>
        </div>
        @endif
    </div>
</div>
@endsection
