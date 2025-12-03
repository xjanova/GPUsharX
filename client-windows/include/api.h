/**
 * GPU Share Client - API Header
 * API communication definitions
 * Copyright (c) 2024 Xman Studio Thailand
 */

#ifndef API_H
#define API_H

// types.h includes common.h which includes stdbool.h
#include "types.h"

// API Response structure
typedef struct {
    bool success;
    int http_code;
    char message[256];
    char data[8192];
} ApiResponse;

// User/Auth data
typedef struct {
    int user_id;
    char name[128];
    char email[256];
    char token[512];
    char referral_code[32];
    double balance;
    double pending_earnings;
    double total_earned;
    bool is_logged_in;
} UserSession;

// Node registration data
typedef struct {
    char node_id[64];
    char status[32];
    bool benchmark_required;
    int benchmark_score;
    double hashrate;
} NodeInfo;

// Job chunk data
typedef struct {
    char chunk_id[128];
    char job_id[64];
    char job_title[256];
    char job_type[32];
    int chunk_index;
    int total_chunks;
    int credits;
    char params[4096];
    char job_params[4096];
} JobChunk;

// Earnings summary
typedef struct {
    double balance;
    double pending_earnings;
    double total_earned;
    double total_withdrawn;
    double today;
    double this_week;
    double this_month;
} EarningsSummary;

// ============ Authentication API ============
bool API_Register(const char* name, const char* email, const char* password,
                  const char* referral_code, UserSession* session, char* error_msg);
bool API_Login(const char* email, const char* password, UserSession* session, char* error_msg);
bool API_Logout(const char* token);
bool API_GetProfile(const char* token, UserSession* session);

// ============ Node Management API ============
bool API_RegisterNode(const char* token, const char* gpu_model, int gpu_vram_mb,
                      const char* machine_id, const char* gpu_specs,
                      NodeInfo* node, char* error_msg);
bool API_NodeHeartbeat(const char* token, const char* node_id, const char* status,
                       int gpu_temp, int gpu_usage, int memory_usage,
                       bool* has_verification, char* verification_task);
bool API_SubmitBenchmark(const char* token, const char* node_id,
                         int benchmark_score, const char* details,
                         NodeInfo* node);
bool API_DisconnectNode(const char* token, const char* node_id);

// ============ Job Distribution API ============
bool API_GetWork(const char* token, const char* node_id,
                 bool* has_work, JobChunk* chunk);
bool API_StartWork(const char* token, const char* node_id, const char* chunk_id);
bool API_UpdateProgress(const char* token, const char* node_id,
                        const char* chunk_id, int progress);
bool API_SubmitWork(const char* token, const char* node_id, const char* chunk_id,
                    const char* result_hash, const char* result_file,
                    double* earned_net, double* new_balance);
bool API_ReportError(const char* token, const char* node_id, const char* chunk_id,
                     const char* error_message);

// ============ Earnings API ============
bool API_GetEarningsSummary(const char* token, EarningsSummary* summary);

// ============ Utility ============
bool API_Ping(void);
void API_Init(void);
void API_Cleanup(void);

// Generate machine ID from hardware
void GenerateMachineId(char* machine_id, size_t size);

#endif // API_H
