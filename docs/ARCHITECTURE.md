# GPU Sharing Platform - Architecture Documentation

**Version:** 1.0.0
**Last Updated:** December 2024
**Developer:** Xman Studio Thailand

---

## 📋 Table of Contents

1. [Overview](#1-overview)
2. [Architecture Comparison](#2-architecture-comparison)
3. [System Architecture](#3-system-architecture)
4. [Job Distribution Flow](#4-job-distribution-flow)
5. [Earnings Calculation](#5-earnings-calculation)
6. [Anti-Cheat System](#6-anti-cheat-system)
7. [API Reference](#7-api-reference)
8. [Data Models](#8-data-models)
9. [Configuration](#9-configuration)

---

## 1. Overview

GPU Sharing Platform เป็นระบบแชร์พลังงาน GPU สำหรับงาน AI Generation โดยใช้สถาปัตยกรรมแบบ **Distributed Computing Pool** คล้ายกับ NiceHash และ Mining Pool

### Core Concepts

| Component | Description |
|-----------|-------------|
| **Pool Server** | Laravel Backend ทำหน้าที่กระจายงานและจัดการ Node |
| **GPU Node** | Client Application ที่รันบนเครื่องผู้ใช้ |
| **Job** | งานที่ต้องประมวลผล (Image Generation, Rendering) |
| **Chunk** | ส่วนย่อยของ Job ที่แบ่งให้แต่ละ Node |
| **Earnings** | รายได้จากการทำงานสำเร็จ |

---

## 2. Architecture Comparison

### เปรียบเทียบกับ NiceHash

| Feature | NiceHash | GPU Sharing Platform |
|---------|----------|---------------------|
| **Work Type** | Crypto Mining | AI Generation / Rendering |
| **Job Distribution** | Stratum Protocol | REST API + HTTP Polling |
| **Reward Model** | Pay-per-Share (PPS) | Pay-per-Task + Bonuses |
| **Verification** | PoW + DAG | PoW + Benchmark + Result Hash |
| **Anti-Cheat** | Hash Rate Validation | Multi-layer (VM, Timing, Verification) |

### สถาปัตยกรรมที่ใช้: **Centralized Pool + Distributed Workers**

```
┌─────────────────────────────────────────────────────────────────┐
│                        POOL SERVER                               │
│  ┌─────────────┐  ┌──────────────┐  ┌────────────────┐          │
│  │ Job Queue   │  │ Distribution │  │ Anti-Cheat     │          │
│  │ (Priority)  │──│ Service      │──│ Service        │          │
│  └─────────────┘  └──────────────┘  └────────────────┘          │
│         │                │                  │                    │
│  ┌──────▼────────────────▼──────────────────▼──────┐            │
│  │              REST API Gateway                    │            │
│  └──────────────────────┬───────────────────────────┘            │
└─────────────────────────┼───────────────────────────────────────┘
                          │
          ┌───────────────┼───────────────┐
          │               │               │
    ┌─────▼─────┐   ┌─────▼─────┐   ┌─────▼─────┐
    │ GPU Node  │   │ GPU Node  │   │ GPU Node  │
    │ (Worker)  │   │ (Worker)  │   │ (Worker)  │
    │           │   │           │   │           │
    │ RTX 4090  │   │ RTX 3080  │   │ RTX 3060  │
    └───────────┘   └───────────┘   └───────────┘
```

---

## 3. System Architecture

### 3.1 Components

```
┌─────────────────────────────────────────────────────────────────┐
│                         LARAVEL BACKEND                          │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Controllers/Api/                                                │
│  ├── AuthController.php      # Login, Register, Logout          │
│  ├── NodeController.php      # Node registration, Heartbeat     │
│  ├── JobController.php       # Work distribution, Submit        │
│  └── EarningController.php   # Earnings, Payouts                │
│                                                                  │
│  Services/                                                       │
│  ├── JobDistributionService.php  # Job → Chunk → Node matching  │
│  ├── AntiCheatService.php        # Fraud detection              │
│  └── WalletService.php           # Earnings & Withdrawals       │
│                                                                  │
│  Models/                                                         │
│  ├── User.php                                                    │
│  ├── GpuNode.php                                                 │
│  ├── RenderJob.php                                               │
│  ├── JobChunk.php                                                │
│  ├── Earning.php                                                 │
│  └── VerificationTask.php                                        │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│                       WINDOWS CLIENT (C)                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  src/                                                            │
│  ├── main.c            # GUI + Main loop                        │
│  ├── api_client.c      # HTTP API communication                 │
│  ├── worker.c          # Job processing + Heartbeat threads     │
│  ├── gpu_monitor.c     # NVML GPU metrics                       │
│  └── system_monitor.c  # CPU/RAM/Network stats                  │
│                                                                  │
└─────────────────────────────────────────────────────────────────┘
```

### 3.2 Database Schema

```sql
-- Core Tables
users                 -- ผู้ใช้งาน (balance, pending_earnings, total_earned)
gpu_nodes             -- GPU Nodes (machine_id, benchmark_score, hashrate)
render_jobs           -- งานหลัก (priority, status, estimated_credits)
job_chunks            -- ชิ้นงานย่อย (assigned_node, result_hash, credits)
earnings              -- รายได้ (amount, platform_fee, net_amount)
verification_tasks    -- งาน Verify (task_type, expected_hash, is_valid)
node_sessions         -- Session tracking (work_seconds, credits_earned)
payouts               -- การถอนเงิน (amount, payment_method, status)
```

---

## 4. Job Distribution Flow

### 4.1 Job Lifecycle

```
┌──────────────────────────────────────────────────────────────────┐
│                        JOB LIFECYCLE                              │
└──────────────────────────────────────────────────────────────────┘

  [Job Created]
       │
       ▼
  ┌─────────┐     splitIntoChunks()      ┌────────────┐
  │ PENDING │ ──────────────────────────▶│  QUEUED    │
  └─────────┘                            └─────┬──────┘
                                               │
                         Job Distribution      │
                         Service assigns       │
                         chunks to nodes       │
                                               ▼
                                        ┌────────────┐
                                        │ PROCESSING │
                                        └─────┬──────┘
                                               │
                           All chunks          │
                           completed?          │
                                               ▼
                                        ┌────────────┐
                                        │ COMPLETED  │
                                        └────────────┘
```

### 4.2 Chunk Distribution Algorithm

```
┌──────────────────────────────────────────────────────────────────┐
│                    DISTRIBUTION ALGORITHM                         │
└──────────────────────────────────────────────────────────────────┘

1. GET PENDING CHUNKS (ordered by priority)
   ┌─────────────────────────────────────────┐
   │ Priority: urgent > high > normal > low  │
   │ Secondary: created_at ASC (FIFO)        │
   │ Limit: 100 chunks per batch             │
   └─────────────────────────────────────────┘

2. GET AVAILABLE NODES
   ┌─────────────────────────────────────────┐
   │ Status: 'online' OR 'idle'              │
   │ Heartbeat: within 2 minutes             │
   │ Order: benchmark_score DESC (best first)│
   └─────────────────────────────────────────┘

3. MATCH CHUNKS TO NODES
   ┌─────────────────────────────────────────┐
   │ FOR each pending_chunk:                 │
   │   FOR each available_node:              │
   │     IF node.gpu_vram >= chunk.required: │
   │       ASSIGN chunk to node              │
   │       REMOVE node from pool             │
   │       BREAK                             │
   └─────────────────────────────────────────┘
```

### 4.3 Chunk Processing Flow

```
┌──────────────────────────────────────────────────────────────────┐
│                     CHUNK PROCESSING FLOW                         │
└──────────────────────────────────────────────────────────────────┘

  CLIENT                                    SERVER
    │                                          │
    │  GET /jobs/work?node_id=XXX             │
    │ ────────────────────────────────────▶   │
    │                                          │
    │  { has_work: true, chunk: {...} }       │
    │ ◀────────────────────────────────────   │
    │                                          │
    │  POST /jobs/start                       │
    │  { node_id, chunk_id }                  │
    │ ────────────────────────────────────▶   │
    │                                          │
    │           [Processing...]                │
    │                                          │
    │  POST /jobs/progress                    │
    │  { progress: 50 }                       │
    │ ────────────────────────────────────▶   │
    │                                          │
    │           [Complete!]                    │
    │                                          │
    │  POST /jobs/submit                      │
    │  { result_hash: "abc123..." }           │
    │ ────────────────────────────────────▶   │
    │                                          │
    │  { earned: { net: 85.5 }, balance }     │
    │ ◀────────────────────────────────────   │
```

---

## 5. Earnings Calculation

### 5.1 Reward Formula

```
┌──────────────────────────────────────────────────────────────────┐
│                      REWARD CALCULATION                           │
└──────────────────────────────────────────────────────────────────┘

GROSS REWARD = BASE + SPEED_BONUS + POWER_BONUS

┌─────────────────────────────────────────────────────────────────┐
│ BASE REWARD                                                      │
│ ═══════════                                                      │
│ chunk.credits_earned (determined when job is split)             │
│                                                                  │
│ Formula: total_job_credits / num_chunks                         │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ SPEED BONUS (+10%)                                               │
│ ═══════════════════                                              │
│ Triggered when: actual_time < estimated_time × 0.8              │
│                                                                  │
│ speed_bonus = base_reward × 0.10                                │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ POWER BONUS (0-5%)                                               │
│ ═══════════════════                                              │
│ Based on node's contribution to total pool hashrate             │
│                                                                  │
│ node_share = node.hashrate / total_pool_hashrate                │
│ power_bonus = base_reward × node_share × 0.05                   │
└─────────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────────┐
│ PLATFORM FEE (-10%)                                              │
│ ════════════════════                                             │
│ platform_fee = gross_reward × 0.10                              │
│ net_amount = gross_reward - platform_fee                        │
└─────────────────────────────────────────────────────────────────┘
```

### 5.2 Example Calculation

```
Job: 1000 credits, split into 4 chunks (250 credits each)

Node: RTX 4090 (hashrate: 150)
Pool Total Hashrate: 500

Base Reward:    250 credits
Speed Bonus:    25 credits (completed 20% faster)
Power Bonus:    3.75 credits (150/500 × 250 × 0.05)
─────────────────────────────
Gross Total:    278.75 credits
Platform Fee:   -27.875 credits (10%)
─────────────────────────────
Net Earnings:   250.875 credits
```

### 5.3 Balance Flow

```
┌──────────────────────────────────────────────────────────────────┐
│                        BALANCE FLOW                               │
└──────────────────────────────────────────────────────────────────┘

  [Job Completed]
        │
        ▼
  ┌─────────────────┐
  │ pending_earnings│ ◀── Net amount added here first
  │ (awaiting       │
  │  confirmation)  │
  └────────┬────────┘
           │
           │  After verification (auto/manual)
           ▼
  ┌─────────────────┐
  │    balance      │ ◀── Moved here when confirmed
  │  (withdrawable) │
  └────────┬────────┘
           │
           │  Withdrawal request
           ▼
  ┌─────────────────┐
  │ total_withdrawn │ ◀── Cumulative record
  └─────────────────┘
```

---

## 6. Anti-Cheat System

### 6.1 Multi-Layer Protection

```
┌──────────────────────────────────────────────────────────────────┐
│                    ANTI-CHEAT LAYERS                              │
└──────────────────────────────────────────────────────────────────┘

Layer 1: REGISTRATION VALIDATION
├── Duplicate Machine ID Detection
├── VRAM Sanity Check (2GB - 128GB)
└── Virtual Machine Detection
    └── Blocks: VMware, VirtualBox, Hyper-V, QEMU, Xen, Parallels

Layer 2: HEARTBEAT MONITORING
├── GPU Model Consistency Check
├── Heartbeat Frequency Analysis (< 5s = suspicious)
└── Metric Anomaly Detection

Layer 3: WORK VALIDATION
├── Minimum Completion Time (> 5 seconds)
├── Result Hash Format (SHA-256, 64 chars)
└── Cross-reference with Verification Tasks

Layer 4: RANDOM VERIFICATION
├── 5% chance on every heartbeat
├── Mandatory every 4 hours
├── Types: Benchmark Task, Proof-of-Work
└── 5 failures in 24h = AUTO BAN
```

### 6.2 Verification Tasks

```
┌──────────────────────────────────────────────────────────────────┐
│                    VERIFICATION TASKS                             │
└──────────────────────────────────────────────────────────────────┘

TYPE 1: BENCHMARK TASK
═══════════════════════
{
  "type": "benchmark",
  "params": {
    "seed": 7234,           // Random seed
    "iterations": 1000,     // Fixed iterations
    "matrix_size": 1024     // Matrix computation
  },
  "time_limit": 120         // 2 minutes
}

Expected: Consistent GPU performance matching benchmark_score


TYPE 2: PROOF OF WORK
═══════════════════════
{
  "type": "proof_of_work",
  "params": {
    "challenge": "a1b2c3d4...",  // 16 random bytes (hex)
    "difficulty": 4               // Leading zeros required
  },
  "time_limit": 60               // 1 minute
}

Expected: Hash with 4 leading zeros
```

### 6.3 Ban Criteria

```
┌──────────────────────────────────────────────────────────────────┐
│                      BAN TRIGGERS                                 │
└──────────────────────────────────────────────────────────────────┘

AUTOMATIC BAN:
├── 5+ failed verification tasks in 24 hours
├── Detected virtual machine environment
└── Machine ID already registered to another account

SUSPICIOUS FLAGS (accumulated):
├── GPU model mismatch from registration
├── Heartbeat frequency < 5 seconds apart
├── Completion time < 5 seconds
├── Invalid result hash format
└── Multiple failed verifications

NODE STATUS: 'banned'
├── Cannot receive new work
├── Session invalidated (is_valid: false)
└── Requires admin review for unban
```

---

## 7. API Reference

### 7.1 Authentication

#### POST /api/register
สมัครสมาชิกใหม่

**Request:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "referral_code": "ABC123"  // optional
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Registration successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "referral_code": "XYZ789"
    },
    "token": "1|abc123..."
  }
}
```

#### POST /api/login
เข้าสู่ระบบ

**Request:**
```json
{
  "email": "john@example.com",
  "password": "password123"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "balance": 1250.50,
      "pending_earnings": 85.25,
      "total_earned": 5420.75
    },
    "token": "2|xyz789..."
  }
}
```

#### POST /api/logout
ออกจากระบบ (ต้อง Auth)

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

#### GET /api/me
ดึงข้อมูลโปรไฟล์ (ต้อง Auth)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "user": {
      "id": 1,
      "name": "John Doe",
      "email": "john@example.com",
      "role": "user",
      "referral_code": "XYZ789",
      "balance": 1250.50,
      "pending_earnings": 85.25,
      "total_earned": 5420.75,
      "total_withdrawn": 3000.00,
      "active_nodes_count": 2,
      "total_hashrate": 285.50,
      "gpu_nodes": [
        {
          "node_id": "NODE-ABC123DEF456",
          "gpu_model": "NVIDIA RTX 4090",
          "status": "working",
          "hashrate": 185.50
        }
      ]
    }
  }
}
```

---

### 7.2 Node Management

#### POST /api/nodes/register
ลงทะเบียน GPU Node

**Request:**
```json
{
  "gpu_model": "NVIDIA GeForce RTX 4090",
  "gpu_vram_mb": 24576,
  "machine_id": "sha256_hardware_fingerprint_64chars",
  "gpu_specs": {
    "compute_capability": "8.9",
    "cuda_cores": 16384,
    "memory_bandwidth": "1008 GB/s"
  },
  "client_version": "1.0.0"
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Node registered successfully",
  "data": {
    "node_id": "NODE-ABC123DEF456",
    "status": "online",
    "benchmark_required": true
  }
}
```

**Errors:**
- `400` - Invalid VRAM (must be 2048-128000 MB)
- `403` - VM detected / Account suspended
- `409` - Machine ID already registered to another account

#### POST /api/nodes/heartbeat
ส่ง Heartbeat (ทุก 30 วินาที)

**Request:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "status": "working",
  "gpu_temp": 65,
  "gpu_usage": 98,
  "memory_usage": 85
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "status": "working",
    "verification_task": null
  }
}
```

**With Verification Task (5% chance):**
```json
{
  "success": true,
  "data": {
    "status": "working",
    "verification_task": {
      "id": 123,
      "type": "benchmark",
      "params": {
        "seed": 7234,
        "iterations": 1000,
        "matrix_size": 1024
      },
      "time_limit": 120
    }
  }
}
```

#### POST /api/nodes/benchmark
ส่งผลการ Benchmark

**Request:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "benchmark_score": 18550,
  "benchmark_details": {
    "fp32_tflops": 82.6,
    "memory_bandwidth_test": 1008.5,
    "compute_test_score": 15200
  }
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Benchmark recorded",
  "data": {
    "benchmark_score": 18550,
    "hashrate": 185.50
  }
}
```

#### POST /api/nodes/verification
ส่งผลการ Verification Task

**Request:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "task_id": 123,
  "result_hash": "abc123def456...",
  "time_taken": 45
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "valid": true,
    "message": "Verification passed"
  }
}
```

#### POST /api/nodes/disconnect
Disconnect Node

**Request:**
```json
{
  "node_id": "NODE-ABC123DEF456"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Node disconnected"
}
```

#### GET /api/nodes
รายการ Node ของผู้ใช้

**Response (200):**
```json
{
  "success": true,
  "data": {
    "nodes": [
      {
        "node_id": "NODE-ABC123DEF456",
        "gpu_model": "NVIDIA RTX 4090",
        "gpu_vram_mb": 24576,
        "benchmark_score": 18550,
        "hashrate": 185.50,
        "status": "working",
        "last_heartbeat": "2024-12-02T10:30:45Z"
      }
    ]
  }
}
```

---

### 7.3 Job Distribution

#### GET /api/jobs/work
รับงานใหม่

**Request Query:**
```
GET /api/jobs/work?node_id=NODE-ABC123DEF456
```

**Response (200) - Has Work:**
```json
{
  "success": true,
  "data": {
    "has_work": true,
    "chunk": {
      "chunk_id": "job_12345_chunk_0",
      "job_id": "JOB-XYZ789ABC",
      "job_title": "Generate AI Portrait - Batch 1",
      "job_type": "image_generation",
      "chunk_index": 0,
      "total_chunks": 4,
      "status": "assigned",
      "params": {
        "model": "stable-diffusion-xl",
        "resolution": "1024x1024",
        "steps": 50
      },
      "job_params": {
        "prompt": "...",
        "negative_prompt": "...",
        "seed": 42
      },
      "credits": 250,
      "assigned_at": "2024-12-02T10:30:45Z"
    }
  }
}
```

**Response (200) - No Work:**
```json
{
  "success": true,
  "data": {
    "has_work": false,
    "message": "No work available"
  }
}
```

#### POST /api/jobs/start
เริ่มทำงาน

**Request:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "chunk_id": "job_12345_chunk_0"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Work started"
}
```

#### POST /api/jobs/progress
อัพเดท Progress

**Request:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "chunk_id": "job_12345_chunk_0",
  "progress": 65
}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "progress": 65
  }
}
```

#### POST /api/jobs/submit
ส่งงานที่เสร็จ

**Request:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "chunk_id": "job_12345_chunk_0",
  "result_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "result_file": "https://storage.example.com/results/job_12345_chunk_0.png",
  "metadata": {
    "processing_time": 45.5,
    "gpu_max_temp": 72,
    "vram_peak": 18500
  }
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Work submitted successfully",
  "data": {
    "earned": {
      "gross": 278.75,
      "platform_fee": 27.88,
      "net": 250.87
    },
    "user_balance": {
      "pending": 335.62,
      "available": 1250.50
    }
  }
}
```

**Errors:**
- `400` - Invalid result hash format
- `403` - Anti-cheat validation failed
- `404` - Chunk not found or not assigned to node

#### POST /api/jobs/error
รายงาน Error

**Request:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "chunk_id": "job_12345_chunk_0",
  "error_message": "CUDA out of memory"
}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Error reported, chunk returned to queue"
}
```

---

### 7.4 Earnings & Payouts

#### GET /api/earnings
รายการรายได้ (Paginated)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "earnings": [
      {
        "id": 1,
        "type": "job_reward",
        "amount": 278.75,
        "platform_fee": 27.88,
        "net_amount": 250.87,
        "status": "confirmed",
        "job_title": "Generate AI Portrait - Batch 1",
        "node_id": "NODE-ABC123DEF456",
        "created_at": "2024-12-02T10:35:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 156,
      "last_page": 8
    },
    "summary": {
      "balance": 1250.50,
      "pending_earnings": 335.62,
      "total_earned": 5420.75,
      "total_withdrawn": 3000.00
    }
  }
}
```

#### GET /api/earnings/summary
สรุปรายได้

**Response (200):**
```json
{
  "success": true,
  "data": {
    "balance": 1250.50,
    "pending_earnings": 335.62,
    "total_earned": 5420.75,
    "total_withdrawn": 3000.00,
    "periods": {
      "today": 125.50,
      "this_week": 850.25,
      "this_month": 2150.75
    },
    "by_node": [
      {
        "node_id": "NODE-ABC123DEF456",
        "gpu_model": "NVIDIA RTX 4090",
        "total_earned": 3500.50,
        "jobs_completed": 142
      }
    ]
  }
}
```

#### POST /api/payouts/request
ขอถอนเงิน

**Request:**
```json
{
  "amount": 500.00,
  "payment_method": "promptpay",
  "payment_details": {
    "phone": "0812345678"
  }
}
```

**Response (201):**
```json
{
  "success": true,
  "message": "Payout request submitted",
  "data": {
    "payout_id": "PAY-XYZ789ABC123",
    "amount": 500.00,
    "fee": 10.00,
    "net_amount": 490.00,
    "payment_method": "promptpay",
    "status": "pending",
    "new_balance": 750.50
  }
}
```

**Errors:**
- `400` - Minimum withdrawal is $10
- `400` - Insufficient balance
- `409` - Pending payout already exists

#### GET /api/payouts/history
ประวัติการถอน

**Response (200):**
```json
{
  "success": true,
  "data": {
    "payouts": [
      {
        "payout_id": "PAY-XYZ789ABC123",
        "amount": 500.00,
        "fee": 10.00,
        "net_amount": 490.00,
        "payment_method": "promptpay",
        "status": "completed",
        "transaction_id": "TXN123456",
        "created_at": "2024-12-01T08:00:00Z",
        "processed_at": "2024-12-01T10:30:00Z"
      }
    ],
    "pagination": {
      "current_page": 1,
      "per_page": 20,
      "total": 15,
      "last_page": 1
    }
  }
}
```

---

### 7.5 Public Endpoints

#### GET /api/pool/stats
สถิติ Pool (ไม่ต้อง Auth)

**Response (200):**
```json
{
  "success": true,
  "data": {
    "total_nodes": 1250,
    "active_nodes": 847,
    "total_hashrate": 125850.50,
    "pending_jobs": 45,
    "processing_jobs": 128,
    "pending_chunks": 180,
    "processing_chunks": 512,
    "total_paid_out": 2500000.00
  }
}
```

---

## 8. Data Models

### 8.1 Entity Relationship

```
┌──────────────────────────────────────────────────────────────────┐
│                    ENTITY RELATIONSHIPS                           │
└──────────────────────────────────────────────────────────────────┘

  User (1) ──────┬────── (*) GpuNode
                 │              │
                 │              └────── (*) NodeSession
                 │              │
                 │              └────── (*) VerificationTask
                 │
                 ├────── (*) RenderJob
                 │              │
                 │              └────── (*) JobChunk
                 │                         │
                 │              ┌──────────┘
                 │              ▼
                 ├────── (*) Earning
                 │
                 ├────── (*) Payout
                 │
                 └────── (*) WalletTransaction
```

### 8.2 Key Models

#### GpuNode
```php
[
    'id'              => int,
    'user_id'         => int (FK),
    'node_id'         => string (unique, "NODE-XXXX"),
    'machine_id'      => string (unique, SHA-256),
    'gpu_model'       => string,
    'gpu_vram_mb'     => int (2048-128000),
    'benchmark_score' => int (default 0),
    'hashrate'        => decimal(12,4),
    'status'          => enum ['online','offline','idle','working','banned'],
    'ip_address'      => string,
    'client_version'  => string,
    'gpu_specs'       => json (nullable),
    'last_heartbeat'  => timestamp,
    'last_benchmark'  => timestamp,
]
```

#### JobChunk
```php
[
    'id'              => int,
    'chunk_id'        => string (unique, "job_X_chunk_Y"),
    'render_job_id'   => int (FK),
    'gpu_node_id'     => int (FK, nullable),
    'chunk_index'     => int,
    'status'          => enum ['pending','assigned','processing','completed','failed'],
    'progress'        => int (0-100),
    'credits_earned'  => int,
    'result_hash'     => string (64 chars, nullable),
    'result_file'     => string (nullable),
    'retry_count'     => int (max 3),
    'error_message'   => text (nullable),
    'assigned_at'     => timestamp,
    'started_at'      => timestamp,
    'completed_at'    => timestamp,
]
```

#### Earning
```php
[
    'id'              => int,
    'user_id'         => int (FK),
    'gpu_node_id'     => int (FK, nullable),
    'job_chunk_id'    => int (FK, nullable),
    'type'            => enum ['job_reward','bonus','referral','adjustment'],
    'amount'          => decimal(12,2),
    'platform_fee'    => decimal(12,2),
    'net_amount'      => decimal(12,2),
    'status'          => enum ['pending','confirmed','cancelled'],
    'description'     => string,
]
```

---

## 9. Configuration

### 9.1 Platform Settings

| Setting | Default | Description |
|---------|---------|-------------|
| `platform_fee_percent` | 10% | ค่าธรรมเนียมแพลตฟอร์ม |
| `min_withdrawal` | $10 | ยอดถอนขั้นต่ำ |
| `withdrawal_fee_percent` | 2% | ค่าธรรมเนียมถอน |
| `speed_bonus_threshold` | 80% | เกณฑ์โบนัสความเร็ว |
| `speed_bonus_percent` | 10% | โบนัสความเร็ว |
| `max_power_bonus_percent` | 5% | โบนัสพลังงานสูงสุด |
| `heartbeat_timeout` | 120s | หมดเวลา Heartbeat |
| `verification_interval` | 4h | ความถี่ Verification |
| `verification_random_chance` | 5% | โอกาส Random Verify |
| `max_failed_verifications` | 5 | จำนวนครั้งที่ล้มเหลวก่อน Ban |
| `min_work_time` | 5s | เวลาทำงานขั้นต่ำ |
| `max_chunk_retries` | 3 | จำนวนครั้ง Retry สูงสุด |

### 9.2 Client Configuration (config.h)

```c
// API Configuration
#define API_HOST            "127.0.0.1"     // Server host
#define API_PORT            8000            // Server port
#define API_BASE_PATH       "/api"          // API base path
#define API_USE_HTTPS       0               // 0=HTTP, 1=HTTPS
#define API_TIMEOUT         30000           // 30 seconds

// Heartbeat
#define HEARTBEAT_INTERVAL  30000           // 30 seconds

// Update Intervals (milliseconds)
#define UPDATE_INTERVAL_GPU     1000        // 1 second
#define UPDATE_INTERVAL_SYSTEM  2000        // 2 seconds
#define UPDATE_INTERVAL_JOB     5000        // 5 seconds
```

---

## Summary

GPU Sharing Platform ใช้สถาปัตยกรรม **Centralized Pool** ที่:

1. **Job Distribution**: คล้าย Mining Pool โดยแบ่งงานเป็น Chunks และกระจายตาม Priority + GPU Capability
2. **Earnings**: คำนวณแบบ Pay-per-Task พร้อม Speed/Power Bonuses
3. **Anti-Cheat**: Multi-layer protection รวมถึง VM detection, PoW verification, และ Timing analysis
4. **API Design**: RESTful API ที่ใช้ Bearer Token authentication

ระบบออกแบบมาให้รองรับการ Scale และป้องกันการโกงได้อย่างมีประสิทธิภาพ
