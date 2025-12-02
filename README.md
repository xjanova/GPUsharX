# GPU Sharing Platform

ระบบแชร์พลังการ์ดจอ (GPU Pool) สำหรับการ generate ภาพและวิดีโอ คล้ายกับระบบ mining pool

## Features

### Web Platform (Laravel 12)
- ระบบสมาชิก (Registration/Login)
- Dashboard สำหรับ Admin
- ระบบจัดการ GPU Nodes
- Job Queue และ Distribution (คล้าย mining pool)
- ระบบคำนวณรายได้และปันผลตามกำลัง GPU
- ระบบ Payout/Withdrawal
- ระบบ Referral

### Windows Client (Python + PyQt6)
- GUI Application สำหรับแชร์ GPU
- ตรวจจับ Hardware อัตโนมัติ
- Benchmark GPU Performance
- รับงานและประมวลผลอัตโนมัติ
- ติดตามรายได้แบบ Real-time

### Anti-Cheat System
- Hardware Fingerprinting (Machine ID)
- Random Verification Tasks
- Proof of Work Challenges
- Benchmark Validation
- Suspicious Activity Detection

## Installation

### Web Server (Laravel)

```bash
cd gpu-sharing-platform

# Install dependencies
composer install

# Setup environment
cp .env.example .env
php artisan key:generate

# Configure database in .env then run migrations
php artisan migrate

# Start server
php artisan serve
```

### Windows Client

```bash
cd windows-client

# Install dependencies
pip install -r requirements.txt

# Copy environment file
cp .env.example .env

# Run client
python main.py

# Or build executable
build.bat
```

## API Endpoints

### Authentication
- `POST /api/register` - Register new user
- `POST /api/login` - Login
- `GET /api/me` - Get current user

### Nodes
- `POST /api/nodes/register` - Register GPU node
- `POST /api/nodes/heartbeat` - Send heartbeat
- `POST /api/nodes/benchmark` - Submit benchmark
- `POST /api/nodes/verification` - Submit verification

### Jobs
- `GET /api/jobs/work` - Get work assignment
- `POST /api/jobs/submit` - Submit completed work

### Earnings
- `GET /api/earnings/summary` - Earnings summary
- `POST /api/payouts/request` - Request payout

## How It Works

1. Users register and install Windows client
2. Client registers GPU with hardware fingerprint
3. Run benchmark to measure GPU performance
4. Start mining to accept work assignments
5. GPU processes render jobs
6. Earn credits based on work completed
7. Random verification tasks prevent cheating
8. Request payout when balance reaches minimum

## Revenue Distribution

- Platform takes 10% fee
- Remaining 90% goes to workers
- Credits distributed based on work + GPU power

## Tech Stack

- Backend: Laravel 12, PHP 8.3
- Frontend: Blade, TailwindCSS
- Client: Python 3.10+, PyQt6
- GPU Detection: GPUtil, WMI

## License

MIT License
