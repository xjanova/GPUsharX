# GPU Sharing Platform - Development Documentation

## Project Overview

แพลตฟอร์มแชร์พลังงาน GPU สำหรับงาน AI Generation พัฒนาด้วย Laravel 12 + MySQL

**Developer:** Xman Studio Thailand
**Last Updated:** December 2024
**Status:** In Development

---

## Technology Stack

| Component | Technology |
|-----------|------------|
| Backend | Laravel 12 (PHP 8.3) |
| Database | MySQL 8.4 |
| Frontend | Blade + Tailwind CSS |
| Client (Windows) | C (Win32 API) |
| Client (Python) | Python 3.10+ (CustomTkinter) |

---

## Features Completed

### 1. Installation Wizard (6 Steps)
- ตรวจสอบ requirements
- ตั้งค่า Database
- สร้างตาราง (Migrations)
- สร้าง Admin account
- ตั้งค่าระบบ
- เสร็จสิ้น

**Files:**
- `app/Http/Controllers/InstallController.php`
- `resources/views/install/*.blade.php`

### 2. Authentication System
- Login / Register / Logout
- Admin middleware
- User dashboard

**Files:**
- `app/Http/Controllers/AuthController.php`
- `app/Http/Middleware/AdminMiddleware.php`

### 3. AI Generation System
- รองรับหลาย AI Models (Stable Diffusion, DALL-E, Midjourney style)
- สร้างภาพจาก text prompt
- Gallery แสดงผลงาน
- My Generations (ผลงานของฉัน)

**Files:**
- `app/Http/Controllers/GenerationController.php`
- `app/Models/AiModel.php`
- `app/Models/GenerationJob.php`
- `resources/views/generate/*.blade.php`

### 4. Admin Panel
- Dashboard สถิติภาพรวม
- จัดการ Users
- จัดการ GPU Nodes
- จัดการ Jobs
- จัดการ Payouts
- จัดการ AI Models (เปิด/ปิด, Featured)
- จัดการ Generations (ลบ)
- Run Migrations / Seeders จากหน้าเว็บ

**Files:**
- `app/Http/Controllers/Admin/*.php`
- `resources/views/admin/*.blade.php`

### 5. Referral System
- สร้าง Referral Code อัตโนมัติ
- Multi-level commission (3 ระดับ)
- Admin จัดการ commission rates
- หน้า Referral สำหรับ User
- หน้า Admin Referral management

**Files:**
- `app/Http/Controllers/ReferralController.php`
- `app/Http/Controllers/Admin/AdminReferralController.php`
- `app/Models/ReferralEarning.php`
- `database/migrations/2025_01_01_000011_create_referral_system_table.php`

### 6. Wallet System
- Wallet balance สำหรับแต่ละ User
- โอนรายได้เข้า Wallet เมื่อถึงเกณฑ์
- ถอนเงินหลายช่องทาง (PromptPay, Bank, TrueMoney, PayPal, Crypto)
- ประวัติธุรกรรม

**Files:**
- `app/Http/Controllers/WalletController.php`
- `app/Services/WalletService.php`
- `app/Models/WalletTransaction.php`
- `app/Models/WithdrawalRequest.php`
- `app/Models/EarningTransfer.php`
- `database/migrations/2025_06_01_000002_create_wallet_system.php`
- `resources/views/wallet/index.blade.php`

### 7. Client Version Management
- ระบบจัดการเวอร์ชัน Client
- อัปโหลดไฟล์ผ่าน Admin panel
- ดาวน์โหลดแยกตาม Platform (Windows, Linux, Source)
- Checksum SHA256
- นับจำนวนดาวน์โหลด
- Version history

**Files:**
- `app/Http/Controllers/ClientController.php`
- `app/Http/Controllers/Admin/AdminClientController.php`
- `app/Models/ClientVersion.php`
- `database/migrations/2025_06_01_000003_create_client_versions_table.php`
- `database/seeders/ClientVersionSeeder.php`
- `resources/views/client/download.blade.php`
- `resources/views/admin/client-versions.blade.php`

### 8. GPU Client Application (Windows - C)
- Native Windows GUI ด้วย Win32 API
- Dark theme dashboard มืออาชีพ
- GPU Monitoring (NVML): Temperature, Power, Memory, Fan Speed, Load
- System Monitoring: CPU, RAM, Network bandwidth
- Job Processing with Status Indicators
- Real-time Earnings Display
- รองรับ 2 ภาษา (English/Thai)

**Files:**
```
client-windows/
├── include/
│   ├── config.h          # Configuration constants
│   ├── lang.h            # Localization (EN/TH)
│   └── types.h           # Type definitions
├── src/
│   ├── main.c            # Main GUI application
│   ├── gpu_monitor.c     # NVML GPU monitoring
│   ├── system_monitor.c  # CPU/RAM/Network monitoring
│   └── api_client.c      # HTTP API client
├── build.bat             # Visual Studio build script
├── build-mingw.bat       # MinGW build script
├── CMakeLists.txt        # CMake configuration
└── README.md             # Build instructions
```

### 9. GPU Client Application (Python - Cross-platform)
- GUI ด้วย CustomTkinter
- รองรับ Windows/Linux/Mac
- GPU Monitoring ผ่าน pynvml
- System Monitoring ผ่าน psutil
- WebSocket real-time updates

**Files:**
```
client/
├── src/
│   ├── config.py
│   ├── gpu_monitor.py
│   ├── system_monitor.py
│   ├── api_client.py
│   ├── job_worker.py
│   └── gui.py
├── main.py
├── requirements.txt
└── README.md
```

---

## Database Schema

### Core Tables
- `users` - ผู้ใช้งาน
- `gpu_nodes` - GPU Nodes ที่ลงทะเบียน
- `render_jobs` - งาน Render
- `job_chunks` - ชิ้นงานย่อย
- `earnings` - รายได้
- `payouts` - การจ่ายเงิน
- `node_sessions` - Session ของ Node

### AI Generation Tables
- `ai_models` - โมเดล AI ที่รองรับ
- `generation_jobs` - งาน Generate รูปภาพ

### Referral Tables
- `referral_codes` - รหัสแนะนำ
- `referral_earnings` - รายได้จากการแนะนำ

### Wallet Tables
- `wallet_transactions` - ธุรกรรม Wallet
- `withdrawal_requests` - คำขอถอนเงิน
- `earning_transfers` - การโอนรายได้เข้า Wallet

### Client Tables
- `client_versions` - เวอร์ชัน Client application

---

## Routes Summary

### Public Routes
| Route | Description |
|-------|-------------|
| `/` | หน้าแรก |
| `/login` | เข้าสู่ระบบ |
| `/register` | สมัครสมาชิก |
| `/generate` | สร้างรูปภาพ AI |
| `/gallery` | แกลเลอรี่ |
| `/models` | รายการ AI Models |
| `/download` | ดาวน์โหลด Client |

### Auth Routes
| Route | Description |
|-------|-------------|
| `/dashboard` | Dashboard ผู้ใช้ |
| `/my-generations` | ผลงานของฉัน |
| `/referral` | ระบบแนะนำ |
| `/wallet` | กระเป๋าเงิน |

### Admin Routes (`/admin/*`)
| Route | Description |
|-------|-------------|
| `/admin` | Admin Dashboard |
| `/admin/users` | จัดการผู้ใช้ |
| `/admin/nodes` | จัดการ GPU Nodes |
| `/admin/jobs` | จัดการงาน |
| `/admin/payouts` | จัดการการจ่ายเงิน |
| `/admin/ai-models` | จัดการ AI Models |
| `/admin/generations` | จัดการผลงาน |
| `/admin/referrals` | จัดการ Referral |
| `/admin/referral-settings` | ตั้งค่า Commission |
| `/admin/client-versions` | จัดการเวอร์ชัน Client |
| `/admin/settings` | ตั้งค่าระบบ |

---

## Pending / TODO

### High Priority
- [ ] Build Windows executable (.exe) - ต้องมี Visual Studio
- [ ] API endpoints สำหรับ Client (`/api/client/*`)
- [ ] WebSocket server สำหรับ real-time updates
- [ ] Job queue system สำหรับ GPU processing

### Medium Priority
- [ ] Email verification
- [ ] Password reset
- [ ] User profile settings
- [ ] Notification system
- [ ] Payment gateway integration

### Low Priority
- [ ] Linux client build
- [ ] macOS client
- [ ] Mobile app
- [ ] API documentation (Swagger)

---

## How to Continue Development

### 1. Setup Environment
```bash
# Clone/download project
cd gpu-sharing-platform

# Install PHP dependencies
composer install

# Copy environment file
cp .env.example .env

# Generate app key
php artisan key:generate

# Run migrations
php artisan migrate

# Run seeders
php artisan db:seed
```

### 2. Build Windows Client
```bash
cd client-windows

# Option 1: Visual Studio
# Open "Developer Command Prompt for VS"
build.bat

# Option 2: MinGW
build-mingw.bat
```

### 3. Run Development Server
```bash
php artisan serve
# Access: http://localhost:8000
```

---

## Configuration

### Environment Variables (.env)
```env
APP_NAME="GPU Sharing Platform"
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gpu_sharing
DB_USERNAME=root
DB_PASSWORD=

# Wallet Settings
WALLET_MIN_TRANSFER=100
WALLET_WITHDRAWAL_FEE=2.5

# Client API
CLIENT_API_SECRET=your-secret-key
```

---

## Notes for Developers

1. **UI Theme**: ใช้ Dark theme เป็นหลัก (bg-gray-900, text-white)
2. **Language**: รองรับ Thai และ English
3. **Icons**: ใช้ Font Awesome 6
4. **CSS**: Tailwind CSS (CDN)
5. **Client GUI**: Win32 API สำหรับ Windows, CustomTkinter สำหรับ Python

---

## Contact

**Developer:** Xman Studio Thailand
**Project:** GPU Sharing Platform
**Version:** 1.0.0 (Development)
