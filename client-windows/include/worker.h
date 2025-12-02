/**
 * GPU Share Client - Worker Header
 * Job processing and heartbeat management
 * Copyright (c) 2024 Xman Studio Thailand
 */

#ifndef WORKER_H
#define WORKER_H

#include <stdbool.h>
#include "types.h"
#include "api.h"

// Worker states
typedef enum {
    WORKER_STOPPED = 0,
    WORKER_STARTING,
    WORKER_IDLE,
    WORKER_WORKING,
    WORKER_STOPPING
} WorkerState;

// Worker context
typedef struct {
    // Session
    UserSession session;
    NodeInfo node;

    // Current work
    JobChunk current_chunk;
    bool has_work;
    int job_progress;

    // State
    WorkerState state;
    bool should_stop;

    // Statistics
    int jobs_completed;
    int jobs_failed;
    double total_earned;
    double today_earned;
    uint64_t uptime_seconds;
    uint64_t start_time;

    // Threads
    HANDLE heartbeat_thread;
    HANDLE worker_thread;

    // Error
    char last_error[256];
} WorkerContext;

// Initialize worker context
void Worker_Init(WorkerContext* ctx);

// Cleanup worker
void Worker_Cleanup(WorkerContext* ctx);

// Start worker (login, register node, start threads)
bool Worker_Start(WorkerContext* ctx, const char* email, const char* password);

// Stop worker
void Worker_Stop(WorkerContext* ctx);

// Get current state
WorkerState Worker_GetState(WorkerContext* ctx);

// Get job status for UI
JobStatus Worker_GetJobStatus(WorkerContext* ctx);

// Check if connected
bool Worker_IsConnected(WorkerContext* ctx);

// Get error message
const char* Worker_GetError(WorkerContext* ctx);

// Force refresh earnings
void Worker_RefreshEarnings(WorkerContext* ctx);

#endif // WORKER_H
