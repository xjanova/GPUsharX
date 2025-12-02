/**
 * GPU Share Client - Worker Module
 * Handles job processing, heartbeat, and background work
 * Copyright (c) 2024 Xman Studio Thailand
 */

#include <windows.h>
#include <wincrypt.h>
#include <stdio.h>
#include <string.h>
#include "../include/config.h"
#include "../include/types.h"
#include "../include/api.h"
#include "../include/worker.h"

#pragma comment(lib, "crypt32.lib")

// External GPU functions
extern bool GPU_GetInfo(GPUInfo* info);

// ============ Helper Functions ============

// Generate SHA256 hash of data
static void GenerateSHA256(const char* data, size_t len, char* hash_out) {
    HCRYPTPROV hProv = 0;
    HCRYPTHASH hHash = 0;
    BYTE hash[32];
    DWORD hash_len = 32;

    if (CryptAcquireContext(&hProv, NULL, NULL, PROV_RSA_AES, CRYPT_VERIFYCONTEXT)) {
        if (CryptCreateHash(hProv, CALG_SHA_256, 0, 0, &hHash)) {
            CryptHashData(hHash, (BYTE*)data, (DWORD)len, 0);
            CryptGetHashParam(hHash, HP_HASHVAL, hash, &hash_len, 0);
            CryptDestroyHash(hHash);
        }
        CryptReleaseContext(hProv, 0);
    }

    // Convert to hex
    char* ptr = hash_out;
    for (DWORD i = 0; i < hash_len; i++) {
        ptr += sprintf(ptr, "%02x", hash[i]);
    }
}

// Simulate work processing (in real implementation, this would do actual GPU work)
static bool ProcessJob(WorkerContext* ctx, JobChunk* chunk) {
    // Report start
    if (!API_StartWork(ctx->session.token, ctx->node.node_id, chunk->chunk_id)) {
        strcpy(ctx->last_error, "Failed to start work");
        return false;
    }

    // Simulate processing with progress updates
    for (int progress = 0; progress <= 100 && !ctx->should_stop; progress += 10) {
        ctx->job_progress = progress;

        // Update progress on server every 20%
        if (progress % 20 == 0) {
            API_UpdateProgress(ctx->session.token, ctx->node.node_id,
                             chunk->chunk_id, progress);
        }

        // Simulate work time (500ms per 10%)
        Sleep(500);
    }

    if (ctx->should_stop) {
        return false;
    }

    // Generate result hash (in real implementation, this would be hash of actual output)
    char result_data[1024];
    snprintf(result_data, sizeof(result_data), "%s-%s-%llu",
            chunk->chunk_id, chunk->job_id, GetTickCount64());

    char result_hash[65];
    GenerateSHA256(result_data, strlen(result_data), result_hash);

    // Submit result
    double earned = 0;
    double new_balance = 0;

    if (!API_SubmitWork(ctx->session.token, ctx->node.node_id, chunk->chunk_id,
                        result_hash, NULL, &earned, &new_balance)) {
        strcpy(ctx->last_error, "Failed to submit work");
        return false;
    }

    // Update statistics
    ctx->jobs_completed++;
    ctx->total_earned += earned;
    ctx->today_earned += earned;
    ctx->session.balance = new_balance;

    return true;
}

// ============ Heartbeat Thread ============

static DWORD WINAPI HeartbeatThread(LPVOID param) {
    WorkerContext* ctx = (WorkerContext*)param;
    GPUInfo gpu;
    bool has_verification = false;
    char verification_task[4096];
    DWORD last_heartbeat = 0;
    DWORD last_earnings_refresh = 0;

    while (!ctx->should_stop) {
        DWORD now = GetTickCount();

        // Send heartbeat every HEARTBEAT_INTERVAL
        if (now - last_heartbeat >= HEARTBEAT_INTERVAL) {
            // Get GPU info
            GPU_GetInfo(&gpu);

            // Determine status
            const char* status = "idle";
            if (ctx->state == WORKER_WORKING) {
                status = "working";
            } else if (ctx->state == WORKER_IDLE) {
                status = "online";
            }

            // Send heartbeat
            int memory_percent = gpu.memory_total > 0 ?
                (gpu.memory_used * 100 / gpu.memory_total) : 0;

            if (API_NodeHeartbeat(ctx->session.token, ctx->node.node_id, status,
                                 gpu.temperature, gpu.gpu_load, memory_percent,
                                 &has_verification, verification_task)) {
                // Handle verification task if received
                if (has_verification) {
                    // TODO: Process verification task
                    // For now, just acknowledge
                }
            }

            last_heartbeat = now;
        }

        // Refresh earnings every 60 seconds
        if (now - last_earnings_refresh >= 60000) {
            EarningsSummary summary;
            if (API_GetEarningsSummary(ctx->session.token, &summary)) {
                ctx->session.balance = summary.balance;
                ctx->session.pending_earnings = summary.pending_earnings;
                ctx->session.total_earned = summary.total_earned;
                ctx->today_earned = summary.today;
            }
            last_earnings_refresh = now;
        }

        // Update uptime
        ctx->uptime_seconds = (GetTickCount64() - ctx->start_time) / 1000;

        Sleep(1000);
    }

    return 0;
}

// ============ Worker Thread ============

static DWORD WINAPI WorkerThread(LPVOID param) {
    WorkerContext* ctx = (WorkerContext*)param;
    DWORD last_poll = 0;
    const DWORD POLL_INTERVAL = 5000; // Poll for work every 5 seconds

    ctx->state = WORKER_IDLE;

    while (!ctx->should_stop) {
        DWORD now = GetTickCount();

        // Poll for work
        if (now - last_poll >= POLL_INTERVAL && ctx->state == WORKER_IDLE) {
            bool has_work = false;
            JobChunk chunk = {0};

            if (API_GetWork(ctx->session.token, ctx->node.node_id, &has_work, &chunk)) {
                if (has_work) {
                    // Copy chunk info
                    ctx->current_chunk = chunk;
                    ctx->has_work = true;
                    ctx->state = WORKER_WORKING;

                    // Process the job
                    if (ProcessJob(ctx, &chunk)) {
                        // Job completed successfully
                        ctx->has_work = false;
                        ctx->state = WORKER_IDLE;
                    } else {
                        // Job failed
                        ctx->jobs_failed++;
                        API_ReportError(ctx->session.token, ctx->node.node_id,
                                       chunk.chunk_id, ctx->last_error);
                        ctx->has_work = false;
                        ctx->state = WORKER_IDLE;
                    }
                }
            }
            last_poll = now;
        }

        Sleep(500);
    }

    return 0;
}

// ============ Public Functions ============

void Worker_Init(WorkerContext* ctx) {
    memset(ctx, 0, sizeof(WorkerContext));
    ctx->state = WORKER_STOPPED;
}

void Worker_Cleanup(WorkerContext* ctx) {
    Worker_Stop(ctx);
    memset(ctx, 0, sizeof(WorkerContext));
}

bool Worker_Start(WorkerContext* ctx, const char* email, const char* password) {
    char error_msg[256] = {0};

    ctx->state = WORKER_STARTING;
    ctx->should_stop = false;

    // Login
    if (!API_Login(email, password, &ctx->session, error_msg)) {
        snprintf(ctx->last_error, sizeof(ctx->last_error), "Login failed: %s", error_msg);
        ctx->state = WORKER_STOPPED;
        return false;
    }

    // Get GPU info for registration
    GPUInfo gpu;
    GPU_GetInfo(&gpu);

    // Generate machine ID
    char machine_id[65];
    GenerateMachineId(machine_id, sizeof(machine_id));

    // Register node
    if (!API_RegisterNode(ctx->session.token, gpu.name, gpu.memory_total,
                         machine_id, NULL, &ctx->node, error_msg)) {
        snprintf(ctx->last_error, sizeof(ctx->last_error), "Node registration failed: %s", error_msg);
        API_Logout(ctx->session.token);
        ctx->state = WORKER_STOPPED;
        return false;
    }

    // Check if benchmark required
    if (ctx->node.benchmark_required) {
        // Submit a simple benchmark score based on GPU VRAM
        int benchmark_score = gpu.memory_total * 10; // Simple formula
        API_SubmitBenchmark(ctx->session.token, ctx->node.node_id,
                           benchmark_score, NULL, &ctx->node);
    }

    // Get initial earnings
    EarningsSummary summary;
    if (API_GetEarningsSummary(ctx->session.token, &summary)) {
        ctx->session.balance = summary.balance;
        ctx->session.pending_earnings = summary.pending_earnings;
        ctx->session.total_earned = summary.total_earned;
        ctx->today_earned = summary.today;
    }

    // Record start time
    ctx->start_time = GetTickCount64();

    // Start heartbeat thread
    ctx->heartbeat_thread = CreateThread(NULL, 0, HeartbeatThread, ctx, 0, NULL);
    if (!ctx->heartbeat_thread) {
        strcpy(ctx->last_error, "Failed to start heartbeat thread");
        API_DisconnectNode(ctx->session.token, ctx->node.node_id);
        API_Logout(ctx->session.token);
        ctx->state = WORKER_STOPPED;
        return false;
    }

    // Start worker thread
    ctx->worker_thread = CreateThread(NULL, 0, WorkerThread, ctx, 0, NULL);
    if (!ctx->worker_thread) {
        strcpy(ctx->last_error, "Failed to start worker thread");
        ctx->should_stop = true;
        WaitForSingleObject(ctx->heartbeat_thread, 5000);
        CloseHandle(ctx->heartbeat_thread);
        API_DisconnectNode(ctx->session.token, ctx->node.node_id);
        API_Logout(ctx->session.token);
        ctx->state = WORKER_STOPPED;
        return false;
    }

    return true;
}

void Worker_Stop(WorkerContext* ctx) {
    if (ctx->state == WORKER_STOPPED) return;

    ctx->state = WORKER_STOPPING;
    ctx->should_stop = true;

    // Wait for threads to finish
    if (ctx->worker_thread) {
        WaitForSingleObject(ctx->worker_thread, 10000);
        CloseHandle(ctx->worker_thread);
        ctx->worker_thread = NULL;
    }

    if (ctx->heartbeat_thread) {
        WaitForSingleObject(ctx->heartbeat_thread, 5000);
        CloseHandle(ctx->heartbeat_thread);
        ctx->heartbeat_thread = NULL;
    }

    // Disconnect node
    if (strlen(ctx->node.node_id) > 0) {
        API_DisconnectNode(ctx->session.token, ctx->node.node_id);
    }

    // Logout
    if (strlen(ctx->session.token) > 0) {
        API_Logout(ctx->session.token);
    }

    // Clear session
    memset(&ctx->session, 0, sizeof(ctx->session));
    memset(&ctx->node, 0, sizeof(ctx->node));

    ctx->state = WORKER_STOPPED;
}

WorkerState Worker_GetState(WorkerContext* ctx) {
    return ctx->state;
}

JobStatus Worker_GetJobStatus(WorkerContext* ctx) {
    switch (ctx->state) {
        case WORKER_STOPPED:
        case WORKER_STARTING:
        case WORKER_STOPPING:
            return JOB_IDLE;
        case WORKER_IDLE:
            return JOB_IDLE;
        case WORKER_WORKING:
            if (ctx->job_progress < 10) return JOB_RECEIVING;
            if (ctx->job_progress < 90) return JOB_PROCESSING;
            return JOB_UPLOADING;
        default:
            return JOB_IDLE;
    }
}

bool Worker_IsConnected(WorkerContext* ctx) {
    return ctx->session.is_logged_in &&
           strlen(ctx->node.node_id) > 0 &&
           ctx->state != WORKER_STOPPED;
}

const char* Worker_GetError(WorkerContext* ctx) {
    return ctx->last_error;
}

void Worker_RefreshEarnings(WorkerContext* ctx) {
    if (!ctx->session.is_logged_in) return;

    EarningsSummary summary;
    if (API_GetEarningsSummary(ctx->session.token, &summary)) {
        ctx->session.balance = summary.balance;
        ctx->session.pending_earnings = summary.pending_earnings;
        ctx->session.total_earned = summary.total_earned;
        ctx->today_earned = summary.today;
    }
}
