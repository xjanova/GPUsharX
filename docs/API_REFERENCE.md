# GPU Sharing Platform - API Reference

**Base URL:** `http://127.0.0.1:8000/api`
**Authentication:** Bearer Token (Laravel Sanctum)

---

## Quick Reference

| Category | Method | Endpoint | Auth | Description |
|----------|--------|----------|------|-------------|
| **Auth** | POST | `/register` | No | สมัครสมาชิก |
| | POST | `/login` | No | เข้าสู่ระบบ |
| | POST | `/logout` | Yes | ออกจากระบบ |
| | GET | `/me` | Yes | ข้อมูลโปรไฟล์ |
| **Node** | GET | `/nodes` | Yes | รายการ Node |
| | POST | `/nodes/register` | Yes | ลงทะเบียน Node |
| | POST | `/nodes/heartbeat` | Yes | ส่ง Heartbeat |
| | POST | `/nodes/benchmark` | Yes | ส่งผล Benchmark |
| | POST | `/nodes/verification` | Yes | ส่งผล Verification |
| | POST | `/nodes/disconnect` | Yes | Disconnect Node |
| **Jobs** | GET | `/jobs/work` | Yes | รับงาน |
| | POST | `/jobs/start` | Yes | เริ่มทำงาน |
| | POST | `/jobs/progress` | Yes | อัพเดท Progress |
| | POST | `/jobs/submit` | Yes | ส่งงานเสร็จ |
| | POST | `/jobs/error` | Yes | รายงาน Error |
| **Earnings** | GET | `/earnings` | Yes | รายการรายได้ |
| | GET | `/earnings/summary` | Yes | สรุปรายได้ |
| | POST | `/payouts/request` | Yes | ขอถอนเงิน |
| | GET | `/payouts/history` | Yes | ประวัติถอน |
| | GET | `/payouts/{id}` | Yes | สถานะการถอน |
| **Public** | GET | `/pool/stats` | No | สถิติ Pool |

---

## Headers

### Required Headers

```http
Content-Type: application/json
Accept: application/json
```

### Authentication Header (for protected routes)

```http
Authorization: Bearer {token}
```

### Optional Headers

```http
X-Client-Version: 1.0.0
```

---

## Response Format

### Success Response

```json
{
  "success": true,
  "message": "Operation completed",
  "data": {
    // Response data
  }
}
```

### Error Response

```json
{
  "success": false,
  "message": "Error description",
  "errors": {
    "field": ["Validation error message"]
  }
}
```

### HTTP Status Codes

| Code | Description |
|------|-------------|
| 200 | OK - Request successful |
| 201 | Created - Resource created |
| 400 | Bad Request - Validation error |
| 401 | Unauthorized - Invalid/missing token |
| 403 | Forbidden - Access denied |
| 404 | Not Found - Resource not found |
| 409 | Conflict - Duplicate resource |
| 422 | Unprocessable Entity - Validation failed |
| 500 | Server Error |

---

## Authentication Endpoints

### Register

```http
POST /api/register
```

**Request Body:**
```json
{
  "name": "John Doe",
  "email": "john@example.com",
  "password": "password123",
  "password_confirmation": "password123",
  "referral_code": "ABC123"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| name | string | Yes | ชื่อผู้ใช้ (max 255) |
| email | string | Yes | อีเมล (unique) |
| password | string | Yes | รหัสผ่าน (min 8) |
| password_confirmation | string | Yes | ยืนยันรหัสผ่าน |
| referral_code | string | No | รหัสผู้แนะนำ |

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
    "token": "1|abc123def456..."
  }
}
```

---

### Login

```http
POST /api/login
```

**Request Body:**
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
      "referral_code": "XYZ789",
      "balance": 1250.50,
      "pending_earnings": 85.25,
      "total_earned": 5420.75
    },
    "token": "2|xyz789abc123..."
  }
}
```

---

### Logout

```http
POST /api/logout
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "message": "Logged out successfully"
}
```

---

### Get Profile

```http
GET /api/me
Authorization: Bearer {token}
```

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
          "gpu_vram_mb": 24576,
          "benchmark_score": 18550,
          "hashrate": 185.50,
          "status": "working",
          "last_heartbeat": "2024-12-02T10:30:45Z"
        }
      ]
    }
  }
}
```

---

## Node Management Endpoints

### Register Node

```http
POST /api/nodes/register
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "gpu_model": "NVIDIA GeForce RTX 4090",
  "gpu_vram_mb": 24576,
  "machine_id": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "gpu_specs": {
    "compute_capability": "8.9",
    "cuda_cores": 16384,
    "memory_bandwidth": "1008 GB/s"
  },
  "client_version": "1.0.0"
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| gpu_model | string | Yes | ชื่อ GPU |
| gpu_vram_mb | int | Yes | VRAM (2048-128000 MB) |
| machine_id | string | Yes | Hardware fingerprint (SHA-256, 64 chars) |
| gpu_specs | object | No | รายละเอียด GPU เพิ่มเติม |
| client_version | string | Yes | เวอร์ชัน Client |

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
- `400` - VRAM must be between 2048 and 128000 MB
- `403` - Virtual machine detected
- `403` - Account is suspended
- `409` - Machine ID already registered to another account

---

### List Nodes

```http
GET /api/nodes
Authorization: Bearer {token}
```

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
        "ip_address": "192.168.1.100",
        "client_version": "1.0.0",
        "last_heartbeat": "2024-12-02T10:30:45Z",
        "created_at": "2024-12-01T08:00:00Z"
      }
    ]
  }
}
```

---

### Send Heartbeat

```http
POST /api/nodes/heartbeat
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "status": "working",
  "gpu_temp": 65,
  "gpu_usage": 98,
  "memory_usage": 85
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| node_id | string | Yes | Node ID |
| status | string | No | online/idle/working (default: online) |
| gpu_temp | int | No | อุณหภูมิ GPU (°C) |
| gpu_usage | int | No | GPU Usage (0-100%) |
| memory_usage | int | No | Memory Usage (0-100%) |

**Response (200) - Normal:**
```json
{
  "success": true,
  "data": {
    "status": "working",
    "verification_task": null
  }
}
```

**Response (200) - With Verification Task:**
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

**Verification Task Types:**

1. **Benchmark Task:**
```json
{
  "type": "benchmark",
  "params": {
    "seed": 7234,
    "iterations": 1000,
    "matrix_size": 1024
  },
  "time_limit": 120
}
```

2. **Proof of Work Task:**
```json
{
  "type": "proof_of_work",
  "params": {
    "challenge": "a1b2c3d4e5f6...",
    "difficulty": 4
  },
  "time_limit": 60
}
```

---

### Submit Benchmark

```http
POST /api/nodes/benchmark
Authorization: Bearer {token}
```

**Request Body:**
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

---

### Submit Verification

```http
POST /api/nodes/verification
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "task_id": 123,
  "result_hash": "abc123def456789...",
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

---

### Disconnect Node

```http
POST /api/nodes/disconnect
Authorization: Bearer {token}
```

**Request Body:**
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

---

## Job Distribution Endpoints

### Get Work

```http
GET /api/jobs/work?node_id=NODE-ABC123DEF456
Authorization: Bearer {token}
```

**Query Parameters:**
| Param | Type | Required | Description |
|-------|------|----------|-------------|
| node_id | string | Yes | Node ID |

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
        "prompt": "A beautiful sunset over mountains",
        "negative_prompt": "blurry, low quality",
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

---

### Start Work

```http
POST /api/jobs/start
Authorization: Bearer {token}
```

**Request Body:**
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

---

### Update Progress

```http
POST /api/jobs/progress
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "chunk_id": "job_12345_chunk_0",
  "progress": 65
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| node_id | string | Yes | Node ID |
| chunk_id | string | Yes | Chunk ID |
| progress | int | Yes | Progress (0-100) |

**Response (200):**
```json
{
  "success": true,
  "data": {
    "progress": 65
  }
}
```

---

### Submit Work

```http
POST /api/jobs/submit
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "node_id": "NODE-ABC123DEF456",
  "chunk_id": "job_12345_chunk_0",
  "result_hash": "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
  "result_file": "https://storage.example.com/results/output.png",
  "metadata": {
    "processing_time": 45.5,
    "gpu_max_temp": 72,
    "vram_peak": 18500
  }
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| node_id | string | Yes | Node ID |
| chunk_id | string | Yes | Chunk ID |
| result_hash | string | Yes | SHA-256 hash (64 chars) |
| result_file | string | No | URL ของผลลัพธ์ |
| metadata | object | No | ข้อมูลเพิ่มเติม |

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
- `400` - Invalid result hash format (must be 64 hex characters)
- `403` - Completion time too fast (< 5 seconds)
- `403` - Too many failed verifications
- `404` - Chunk not found or not assigned to node

---

### Report Error

```http
POST /api/jobs/error
Authorization: Bearer {token}
```

**Request Body:**
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

## Earnings Endpoints

### List Earnings

```http
GET /api/earnings?page=1
Authorization: Bearer {token}
```

**Query Parameters:**
| Param | Type | Default | Description |
|-------|------|---------|-------------|
| page | int | 1 | หน้าที่ต้องการ |

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
        "description": "Completed chunk job_12345_chunk_0",
        "job_title": "Generate AI Portrait",
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

---

### Earnings Summary

```http
GET /api/earnings/summary
Authorization: Bearer {token}
```

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
      },
      {
        "node_id": "NODE-DEF789GHI012",
        "gpu_model": "NVIDIA RTX 3080",
        "total_earned": 1920.25,
        "jobs_completed": 98
      }
    ]
  }
}
```

---

### Request Payout

```http
POST /api/payouts/request
Authorization: Bearer {token}
```

**Request Body:**
```json
{
  "amount": 500.00,
  "payment_method": "promptpay",
  "payment_details": {
    "phone": "0812345678"
  }
}
```

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| amount | decimal | Yes | จำนวนเงิน (min 10) |
| payment_method | string | Yes | bank_transfer/promptpay/crypto |
| payment_details | object | Yes | รายละเอียดการชำระ |

**Payment Details by Method:**

**Bank Transfer:**
```json
{
  "bank_name": "Bangkok Bank",
  "account_number": "1234567890",
  "account_name": "John Doe"
}
```

**PromptPay:**
```json
{
  "phone": "0812345678"
}
```

**Crypto:**
```json
{
  "wallet_address": "0x1234...",
  "network": "ethereum"
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
- `409` - You already have a pending payout

---

### Payout History

```http
GET /api/payouts/history?page=1
Authorization: Bearer {token}
```

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
        "payment_details": {
          "phone": "0812345678"
        },
        "status": "completed",
        "transaction_id": "TXN123456789",
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

### Get Payout Status

```http
GET /api/payouts/{payoutId}
Authorization: Bearer {token}
```

**Response (200):**
```json
{
  "success": true,
  "data": {
    "payout_id": "PAY-XYZ789ABC123",
    "amount": 500.00,
    "fee": 10.00,
    "net_amount": 490.00,
    "payment_method": "promptpay",
    "status": "processing",
    "transaction_id": null,
    "created_at": "2024-12-01T08:00:00Z",
    "processed_at": null
  }
}
```

**Payout Statuses:**
| Status | Description |
|--------|-------------|
| pending | รอดำเนินการ |
| processing | กำลังดำเนินการ |
| completed | เสร็จสิ้น |
| failed | ล้มเหลว |
| cancelled | ยกเลิก |

---

## Public Endpoints

### Pool Statistics

```http
GET /api/pool/stats
```

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
    "completed_jobs_24h": 1580,
    "total_paid_out": 2500000.00
  }
}
```

---

## Error Codes Reference

| Code | HTTP Status | Description |
|------|-------------|-------------|
| AUTH_INVALID_CREDENTIALS | 401 | Email หรือรหัสผ่านไม่ถูกต้อง |
| AUTH_TOKEN_EXPIRED | 401 | Token หมดอายุ |
| AUTH_ACCOUNT_SUSPENDED | 403 | บัญชีถูกระงับ |
| NODE_ALREADY_REGISTERED | 409 | Machine ID ลงทะเบียนแล้ว |
| NODE_VM_DETECTED | 403 | ตรวจพบ Virtual Machine |
| NODE_INVALID_VRAM | 400 | VRAM ไม่ถูกต้อง |
| NODE_BANNED | 403 | Node ถูกแบน |
| JOB_NOT_FOUND | 404 | ไม่พบงาน |
| JOB_NOT_ASSIGNED | 400 | งานไม่ได้ assign ให้ Node นี้ |
| JOB_INVALID_HASH | 400 | Result hash ไม่ถูกต้อง |
| JOB_TOO_FAST | 403 | ทำงานเร็วเกินไป |
| PAYOUT_MIN_AMOUNT | 400 | ยอดถอนต่ำกว่าขั้นต่ำ |
| PAYOUT_INSUFFICIENT | 400 | ยอดเงินไม่เพียงพอ |
| PAYOUT_PENDING_EXISTS | 409 | มีรายการถอนรออยู่แล้ว |

---

## Rate Limits

| Endpoint | Limit |
|----------|-------|
| `/login`, `/register` | 5 requests/minute |
| `/nodes/heartbeat` | 60 requests/minute |
| `/jobs/*` | 120 requests/minute |
| Other endpoints | 60 requests/minute |

---

## Webhooks (Coming Soon)

จะรองรับ Webhooks สำหรับ:
- Job completed
- Payout processed
- Node status changed
- Verification required
