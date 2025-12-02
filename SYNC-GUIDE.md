# Git Sync Guide - GPU Sharing Platform

คู่มือการ Sync ไฟล์ระหว่างเครื่องของคุณและ GitHub

## Quick Reference

| Action | Command |
|--------|---------|
| ดึงการเปลี่ยนแปลงใหม่ | `git pull origin main` |
| อัพโหลดการเปลี่ยนแปลง | `git add . && git commit -m "message" && git push` |
| ดูสถานะ | `git status` |
| ดูประวัติ | `git log --oneline -10` |

---

## การตั้งค่าครั้งแรก (First Time Setup)

### บนเครื่อง Windows (Laragon)

```bash
# 1. เปิด Terminal/Git Bash
cd C:\laragon\www\gpu-sharing-platform

# 2. ตรวจสอบว่าเชื่อมต่อ GitHub แล้ว
git remote -v

# ถ้ายังไม่มี remote ให้เพิ่ม:
git remote add origin https://github.com/xjanova/GPUsharX.git

# 3. ตั้งค่า main branch
git branch -M main
```

---

## การทำงานประจำวัน (Daily Workflow)

### 1. ก่อนเริ่มทำงาน - ดึง Changes ล่าสุด

```bash
cd C:\laragon\www\gpu-sharing-platform
git pull origin main
```

### 2. หลังแก้ไขโค้ด - อัพโหลดขึ้น GitHub

```bash
# ดูว่ามีไฟล์อะไรเปลี่ยนบ้าง
git status

# เพิ่มไฟล์ทั้งหมด
git add .

# Commit พร้อมข้อความอธิบาย
git commit -m "เพิ่มฟีเจอร์ xxx"

# Push ขึ้น GitHub
git push origin main
```

### 3. One-liner สำหรับ Quick Push

```bash
git add . && git commit -m "Update" && git push origin main
```

---

## การแก้ไขปัญหาที่พบบ่อย

### ปัญหา: Conflict เมื่อ Pull

```bash
# ดูไฟล์ที่ conflict
git status

# แก้ไขไฟล์ที่มี conflict แล้ว
git add .
git commit -m "Resolve conflicts"
git push origin main
```

### ปัญหา: Push ไม่ได้ (rejected)

```bash
# ดึง changes ก่อน แล้ว push ใหม่
git pull origin main --rebase
git push origin main
```

### ปัญหา: ต้องการยกเลิกการเปลี่ยนแปลง

```bash
# ยกเลิกไฟล์ที่ยังไม่ได้ commit
git checkout -- filename.php

# ยกเลิกทุกไฟล์
git checkout -- .
```

---

## โครงสร้างโปรเจค

```
gpu-sharing-platform/
├── app/                    # Laravel Application
│   ├── Http/Controllers/   # Controllers
│   ├── Models/             # Eloquent Models
│   └── Services/           # Business Logic
├── client/                 # Python Client (Cross-platform)
├── client-windows/         # C Client (Windows Native)
├── config/                 # Laravel Configuration
├── database/               # Migrations & Seeders
├── public/                 # Public Assets
├── resources/views/        # Blade Templates
├── routes/                 # Route Definitions
├── storage/                # Storage (logs, cache)
├── tests/                  # Unit & Feature Tests
├── .env.example            # Environment Template
├── composer.json           # PHP Dependencies
└── package.json            # Node Dependencies
```

---

## Git Branches

| Branch | Purpose |
|--------|---------|
| `main` | Production-ready code |
| `develop` | Development branch |
| `feature/*` | New features |
| `bugfix/*` | Bug fixes |

### สร้าง Feature Branch

```bash
# สร้าง branch ใหม่
git checkout -b feature/new-feature

# ทำงานเสร็จแล้ว merge กลับ main
git checkout main
git merge feature/new-feature
git push origin main
```

---

## .gitignore - ไฟล์ที่ไม่ upload

ไฟล์เหล่านี้จะไม่ถูก upload ขึ้น GitHub:

- `.env` - Environment variables (secrets)
- `vendor/` - PHP dependencies
- `node_modules/` - Node dependencies
- `storage/logs/` - Log files
- `.idea/`, `.vscode/` - IDE settings

---

## Useful Git Aliases

เพิ่มใน `~/.gitconfig` หรือ `C:\Users\{username}\.gitconfig`:

```ini
[alias]
    st = status
    co = checkout
    br = branch
    ci = commit
    p = push origin main
    pl = pull origin main
    lg = log --oneline -20
    last = log -1 HEAD
```

ใช้งาน:
```bash
git st      # = git status
git p       # = git push origin main
git pl      # = git pull origin main
```

---

## การทำงานกับ Claude Code

เมื่อทำงานร่วมกับ Claude Code บน GitHub:

1. **Claude แก้ไขโค้ด** → Push ไปยัง branch
2. **คุณ pull ลงเครื่อง**:
   ```bash
   git fetch origin
   git checkout branch-name
   git pull origin branch-name
   ```
3. **ทดสอบบนเครื่อง** (Laragon)
4. **Merge เข้า main** เมื่อพร้อม:
   ```bash
   git checkout main
   git merge branch-name
   git push origin main
   ```

---

## Links

- **GitHub Repository:** https://github.com/xjanova/GPUsharX
- **Laravel Documentation:** https://laravel.com/docs
- **Git Documentation:** https://git-scm.com/doc

---

*Last Updated: December 2024*
