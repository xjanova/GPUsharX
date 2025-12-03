/**
 * GPU Share Client - API Client Module
 * HTTP communication with Laravel backend
 * Copyright (c) 2024 Xman Studio Thailand
 */

// Include common header first (handles winsock2/windows order)
#include "../include/common.h"
#include "../include/config.h"
#include "../include/types.h"
#include "../include/api.h"

// Additional headers for HTTP communication
#include <winhttp.h>
#include <wincrypt.h>

#pragma comment(lib, "winhttp.lib")
#pragma comment(lib, "crypt32.lib")

// ============ JSON Parser Helpers ============

static char* json_get_string(const char* json, const char* key, char* buffer, size_t size) {
    char search[256];
    snprintf(search, sizeof(search), "\"%s\":", key);

    const char* start = strstr(json, search);
    if (!start) return NULL;

    start += strlen(search);
    while (*start == ' ' || *start == '\t' || *start == '\n' || *start == '\r') start++;

    if (*start == '"') {
        start++;
        const char* end = start;
        while (*end && *end != '"') {
            if (*end == '\\' && *(end + 1)) end++; // Skip escaped chars
            end++;
        }
        if (*end == '"') {
            size_t len = end - start;
            if (len >= size) len = size - 1;
            strncpy(buffer, start, len);
            buffer[len] = '\0';
            return buffer;
        }
    }
    return NULL;
}

static double json_get_number(const char* json, const char* key) {
    char search[256];
    snprintf(search, sizeof(search), "\"%s\":", key);

    const char* start = strstr(json, search);
    if (!start) return 0.0;

    start += strlen(search);
    while (*start == ' ' || *start == '\t' || *start == '\n' || *start == '\r') start++;

    // Handle null
    if (strncmp(start, "null", 4) == 0) return 0.0;

    return atof(start);
}

static int json_get_int(const char* json, const char* key) {
    return (int)json_get_number(json, key);
}

static bool json_get_bool(const char* json, const char* key) {
    char search[256];
    snprintf(search, sizeof(search), "\"%s\":", key);

    const char* start = strstr(json, search);
    if (!start) return false;

    start += strlen(search);
    while (*start == ' ' || *start == '\t') start++;

    return (strncmp(start, "true", 4) == 0);
}

// Find nested object in JSON
static const char* json_find_object(const char* json, const char* key) {
    char search[256];
    snprintf(search, sizeof(search), "\"%s\":", key);

    const char* start = strstr(json, search);
    if (!start) return NULL;

    start += strlen(search);
    while (*start == ' ' || *start == '\t' || *start == '\n' || *start == '\r') start++;

    if (*start == '{') return start;
    return NULL;
}

// ============ HTTP Request Helper ============

typedef struct {
    const char* method;
    const char* path;
    const char* token;
    const char* body;
    char* response;
    size_t response_size;
    int* http_code;
} HttpRequest;

static bool HTTP_Request(HttpRequest* req) {
    HINTERNET hSession = NULL, hConnect = NULL, hRequest = NULL;
    bool success = false;
    DWORD timeout = API_TIMEOUT;
    DWORD flags = 0;
    DWORD body_len = 0;
    DWORD bytes_read = 0;
    DWORD total_read = 0;
    wchar_t whost[256];
    char full_path[1024];
    wchar_t wpath[1024];
    wchar_t wmethod[16];
    wchar_t version_header[128];
    wchar_t auth_header[1024];
    char buffer[4096];

    // Initialize response
    if (req->response && req->response_size > 0) {
        req->response[0] = '\0';
    }
    if (req->http_code) *req->http_code = 0;

    // Open session
    hSession = WinHttpOpen(L"GPUShareClient/1.0",
                          WINHTTP_ACCESS_TYPE_DEFAULT_PROXY,
                          WINHTTP_NO_PROXY_NAME,
                          WINHTTP_NO_PROXY_BYPASS, 0);
    if (!hSession) goto cleanup;

    // Set timeouts
    timeout = API_TIMEOUT;
    WinHttpSetOption(hSession, WINHTTP_OPTION_CONNECT_TIMEOUT, &timeout, sizeof(timeout));
    WinHttpSetOption(hSession, WINHTTP_OPTION_RECEIVE_TIMEOUT, &timeout, sizeof(timeout));

    // Connect to server
    MultiByteToWideChar(CP_UTF8, 0, API_HOST, -1, whost, 256);

    hConnect = WinHttpConnect(hSession, whost, API_PORT, 0);
    if (!hConnect) goto cleanup;

    // Build full path
    snprintf(full_path, sizeof(full_path), "%s%s", API_BASE_PATH, req->path);
    MultiByteToWideChar(CP_UTF8, 0, full_path, -1, wpath, 1024);
    MultiByteToWideChar(CP_UTF8, 0, req->method, -1, wmethod, 16);

    // Open request
    flags = API_USE_HTTPS ? WINHTTP_FLAG_SECURE : 0;
    hRequest = WinHttpOpenRequest(hConnect, wmethod, wpath,
                                  NULL, WINHTTP_NO_REFERER,
                                  WINHTTP_DEFAULT_ACCEPT_TYPES, flags);
    if (!hRequest) goto cleanup;

    // Add headers
    WinHttpAddRequestHeaders(hRequest, L"Content-Type: application/json", -1, WINHTTP_ADDREQ_FLAG_ADD);
    WinHttpAddRequestHeaders(hRequest, L"Accept: application/json", -1, WINHTTP_ADDREQ_FLAG_ADD);

    // Add client version header
    swprintf(version_header, 128, L"X-Client-Version: %hs", APP_VERSION);
    WinHttpAddRequestHeaders(hRequest, version_header, -1, WINHTTP_ADDREQ_FLAG_ADD);

    // Add authorization header if token provided
    if (req->token && strlen(req->token) > 0) {
        swprintf(auth_header, 1024, L"Authorization: Bearer %hs", req->token);
        WinHttpAddRequestHeaders(hRequest, auth_header, -1, WINHTTP_ADDREQ_FLAG_ADD);
    }

    // Send request
    body_len = req->body ? (DWORD)strlen(req->body) : 0;
    if (!WinHttpSendRequest(hRequest, WINHTTP_NO_ADDITIONAL_HEADERS, 0,
                           (LPVOID)req->body, body_len, body_len, 0)) {
        goto cleanup;
    }

    if (!WinHttpReceiveResponse(hRequest, NULL)) {
        goto cleanup;
    }

    // Get status code
    if (req->http_code) {
        DWORD status_code = 0;
        DWORD size = sizeof(status_code);
        WinHttpQueryHeaders(hRequest, WINHTTP_QUERY_STATUS_CODE | WINHTTP_QUERY_FLAG_NUMBER,
                           NULL, &status_code, &size, NULL);
        *req->http_code = (int)status_code;
    }

    // Read response
    while (WinHttpReadData(hRequest, buffer, sizeof(buffer) - 1, &bytes_read) && bytes_read > 0) {
        buffer[bytes_read] = '\0';
        if (total_read + bytes_read < req->response_size) {
            strcpy(req->response + total_read, buffer);
            total_read += bytes_read;
        }
        bytes_read = 0;
    }
    if (req->response) req->response[total_read] = '\0';
    success = true;

cleanup:
    if (hRequest) WinHttpCloseHandle(hRequest);
    if (hConnect) WinHttpCloseHandle(hConnect);
    if (hSession) WinHttpCloseHandle(hSession);
    return success;
}

// ============ Machine ID Generation ============

void GenerateMachineId(char* machine_id, size_t size) {
    // Get volume serial number as part of machine ID
    DWORD volume_serial = 0;
    GetVolumeInformationA("C:\\", NULL, 0, &volume_serial, NULL, NULL, NULL, 0);

    // Get computer name
    char computer_name[MAX_COMPUTERNAME_LENGTH + 1] = {0};
    DWORD name_size = sizeof(computer_name);
    GetComputerNameA(computer_name, &name_size);

    // Get user name
    char user_name[256] = {0};
    DWORD user_size = sizeof(user_name);
    GetUserNameA(user_name, &user_size);

    // Combine into a string
    char combined[1024];
    snprintf(combined, sizeof(combined), "%s-%s-%08X", computer_name, user_name, volume_serial);

    // Hash with SHA256
    HCRYPTPROV hProv = 0;
    HCRYPTHASH hHash = 0;
    BYTE hash[32];
    DWORD hash_len = 32;

    if (CryptAcquireContext(&hProv, NULL, NULL, PROV_RSA_AES, CRYPT_VERIFYCONTEXT)) {
        if (CryptCreateHash(hProv, CALG_SHA_256, 0, 0, &hHash)) {
            CryptHashData(hHash, (BYTE*)combined, (DWORD)strlen(combined), 0);
            CryptGetHashParam(hHash, HP_HASHVAL, hash, &hash_len, 0);
            CryptDestroyHash(hHash);
        }
        CryptReleaseContext(hProv, 0);
    }

    // Convert to hex string
    char* ptr = machine_id;
    for (DWORD i = 0; i < hash_len && (size_t)(ptr - machine_id) < size - 2; i++) {
        ptr += sprintf(ptr, "%02x", hash[i]);
    }
}

// ============ Authentication API ============

bool API_Register(const char* name, const char* email, const char* password,
                  const char* referral_code, UserSession* session, char* error_msg) {
    char body[2048];
    char response[8192] = {0};
    int http_code = 0;

    // Build JSON body
    if (referral_code && strlen(referral_code) > 0) {
        snprintf(body, sizeof(body),
                "{\"name\":\"%s\",\"email\":\"%s\",\"password\":\"%s\",\"password_confirmation\":\"%s\",\"referral_code\":\"%s\"}",
                name, email, password, password, referral_code);
    } else {
        snprintf(body, sizeof(body),
                "{\"name\":\"%s\",\"email\":\"%s\",\"password\":\"%s\",\"password_confirmation\":\"%s\"}",
                name, email, password, password);
    }

    HttpRequest req = {
        .method = "POST",
        .path = "/register",
        .token = NULL,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) {
        if (error_msg) strcpy(error_msg, "Connection failed");
        return false;
    }

    if (!json_get_bool(response, "success")) {
        if (error_msg) {
            json_get_string(response, "message", error_msg, 256);
        }
        return false;
    }

    // Parse user data
    const char* data = json_find_object(response, "data");
    if (data) {
        const char* user = json_find_object(data, "user");
        if (user) {
            session->user_id = json_get_int(user, "id");
            json_get_string(user, "name", session->name, sizeof(session->name));
            json_get_string(user, "email", session->email, sizeof(session->email));
            json_get_string(user, "referral_code", session->referral_code, sizeof(session->referral_code));
        }
        json_get_string(data, "token", session->token, sizeof(session->token));
    }

    session->is_logged_in = true;
    return true;
}

bool API_Login(const char* email, const char* password, UserSession* session, char* error_msg) {
    char body[1024];
    char response[8192] = {0};
    int http_code = 0;

    snprintf(body, sizeof(body), "{\"email\":\"%s\",\"password\":\"%s\"}", email, password);

    HttpRequest req = {
        .method = "POST",
        .path = "/login",
        .token = NULL,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) {
        if (error_msg) strcpy(error_msg, "Connection failed");
        return false;
    }

    if (!json_get_bool(response, "success")) {
        if (error_msg) {
            json_get_string(response, "message", error_msg, 256);
        }
        return false;
    }

    // Parse user data
    const char* data = json_find_object(response, "data");
    if (data) {
        const char* user = json_find_object(data, "user");
        if (user) {
            session->user_id = json_get_int(user, "id");
            json_get_string(user, "name", session->name, sizeof(session->name));
            json_get_string(user, "email", session->email, sizeof(session->email));
            json_get_string(user, "referral_code", session->referral_code, sizeof(session->referral_code));
            session->balance = json_get_number(user, "balance");
            session->pending_earnings = json_get_number(user, "pending_earnings");
            session->total_earned = json_get_number(user, "total_earned");
        }
        json_get_string(data, "token", session->token, sizeof(session->token));
    }

    session->is_logged_in = true;
    return true;
}

bool API_Logout(const char* token) {
    char response[1024] = {0};
    int http_code = 0;

    HttpRequest req = {
        .method = "POST",
        .path = "/logout",
        .token = token,
        .body = NULL,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    return HTTP_Request(&req);
}

bool API_GetProfile(const char* token, UserSession* session) {
    char response[8192] = {0};
    int http_code = 0;

    HttpRequest req = {
        .method = "GET",
        .path = "/me",
        .token = token,
        .body = NULL,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) return false;
    if (!json_get_bool(response, "success")) return false;

    const char* data = json_find_object(response, "data");
    if (data) {
        const char* user = json_find_object(data, "user");
        if (user) {
            session->user_id = json_get_int(user, "id");
            json_get_string(user, "name", session->name, sizeof(session->name));
            json_get_string(user, "email", session->email, sizeof(session->email));
            session->balance = json_get_number(user, "balance");
            session->pending_earnings = json_get_number(user, "pending_earnings");
            session->total_earned = json_get_number(user, "total_earned");
        }
    }

    return true;
}

// ============ Node Management API ============

bool API_RegisterNode(const char* token, const char* gpu_model, int gpu_vram_mb,
                      const char* machine_id, const char* gpu_specs,
                      NodeInfo* node, char* error_msg) {
    char body[4096];
    char response[8192] = {0};
    int http_code = 0;

    if (gpu_specs && strlen(gpu_specs) > 0) {
        snprintf(body, sizeof(body),
                "{\"gpu_model\":\"%s\",\"gpu_vram_mb\":%d,\"machine_id\":\"%s\",\"gpu_specs\":%s,\"client_version\":\"%s\"}",
                gpu_model, gpu_vram_mb, machine_id, gpu_specs, APP_VERSION);
    } else {
        snprintf(body, sizeof(body),
                "{\"gpu_model\":\"%s\",\"gpu_vram_mb\":%d,\"machine_id\":\"%s\",\"client_version\":\"%s\"}",
                gpu_model, gpu_vram_mb, machine_id, APP_VERSION);
    }

    HttpRequest req = {
        .method = "POST",
        .path = "/nodes/register",
        .token = token,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) {
        if (error_msg) strcpy(error_msg, "Connection failed");
        return false;
    }

    if (!json_get_bool(response, "success")) {
        if (error_msg) json_get_string(response, "message", error_msg, 256);
        return false;
    }

    const char* data = json_find_object(response, "data");
    if (data) {
        json_get_string(data, "node_id", node->node_id, sizeof(node->node_id));
        json_get_string(data, "status", node->status, sizeof(node->status));
        node->benchmark_required = json_get_bool(data, "benchmark_required");
    }

    return true;
}

bool API_NodeHeartbeat(const char* token, const char* node_id, const char* status,
                       int gpu_temp, int gpu_usage, int memory_usage,
                       bool* has_verification, char* verification_task) {
    char body[1024];
    char response[8192] = {0};
    int http_code = 0;

    snprintf(body, sizeof(body),
            "{\"node_id\":\"%s\",\"status\":\"%s\",\"gpu_temp\":%d,\"gpu_usage\":%d,\"memory_usage\":%d}",
            node_id, status, gpu_temp, gpu_usage, memory_usage);

    HttpRequest req = {
        .method = "POST",
        .path = "/nodes/heartbeat",
        .token = token,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) return false;
    if (!json_get_bool(response, "success")) return false;

    const char* data = json_find_object(response, "data");
    if (data && has_verification) {
        const char* veri = json_find_object(data, "verification_task");
        *has_verification = (veri != NULL);
        if (veri && verification_task) {
            // Copy the verification task JSON
            const char* end = veri;
            int depth = 0;
            do {
                if (*end == '{') depth++;
                else if (*end == '}') depth--;
                end++;
            } while (depth > 0 && *end);
            size_t len = end - veri;
            strncpy(verification_task, veri, len);
            verification_task[len] = '\0';
        }
    }

    return true;
}

bool API_SubmitBenchmark(const char* token, const char* node_id,
                         int benchmark_score, const char* details,
                         NodeInfo* node) {
    char body[2048];
    char response[4096] = {0};
    int http_code = 0;

    if (details && strlen(details) > 0) {
        snprintf(body, sizeof(body),
                "{\"node_id\":\"%s\",\"benchmark_score\":%d,\"benchmark_details\":%s}",
                node_id, benchmark_score, details);
    } else {
        snprintf(body, sizeof(body),
                "{\"node_id\":\"%s\",\"benchmark_score\":%d}",
                node_id, benchmark_score);
    }

    HttpRequest req = {
        .method = "POST",
        .path = "/nodes/benchmark",
        .token = token,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) return false;
    if (!json_get_bool(response, "success")) return false;

    const char* data = json_find_object(response, "data");
    if (data && node) {
        node->benchmark_score = json_get_int(data, "benchmark_score");
        node->hashrate = json_get_number(data, "hashrate");
    }

    return true;
}

bool API_DisconnectNode(const char* token, const char* node_id) {
    char body[256];
    char response[1024] = {0};
    int http_code = 0;

    snprintf(body, sizeof(body), "{\"node_id\":\"%s\"}", node_id);

    HttpRequest req = {
        .method = "POST",
        .path = "/nodes/disconnect",
        .token = token,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    return HTTP_Request(&req);
}

// ============ Job Distribution API ============

bool API_GetWork(const char* token, const char* node_id,
                 bool* has_work, JobChunk* chunk) {
    char path[256];
    char response[16384] = {0};
    int http_code = 0;

    snprintf(path, sizeof(path), "/jobs/work?node_id=%s", node_id);

    HttpRequest req = {
        .method = "GET",
        .path = path,
        .token = token,
        .body = NULL,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) return false;
    if (!json_get_bool(response, "success")) return false;

    const char* data = json_find_object(response, "data");
    if (data) {
        *has_work = json_get_bool(data, "has_work");

        if (*has_work) {
            const char* chunk_data = json_find_object(data, "chunk");
            if (chunk_data && chunk) {
                json_get_string(chunk_data, "chunk_id", chunk->chunk_id, sizeof(chunk->chunk_id));
                json_get_string(chunk_data, "job_id", chunk->job_id, sizeof(chunk->job_id));
                json_get_string(chunk_data, "job_title", chunk->job_title, sizeof(chunk->job_title));
                json_get_string(chunk_data, "job_type", chunk->job_type, sizeof(chunk->job_type));
                chunk->chunk_index = json_get_int(chunk_data, "chunk_index");
                chunk->total_chunks = json_get_int(chunk_data, "total_chunks");
                chunk->credits = json_get_int(chunk_data, "credits");
            }
        }
    }

    return true;
}

bool API_StartWork(const char* token, const char* node_id, const char* chunk_id) {
    char body[512];
    char response[1024] = {0};
    int http_code = 0;

    snprintf(body, sizeof(body), "{\"node_id\":\"%s\",\"chunk_id\":\"%s\"}", node_id, chunk_id);

    HttpRequest req = {
        .method = "POST",
        .path = "/jobs/start",
        .token = token,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    return HTTP_Request(&req) && json_get_bool(response, "success");
}

bool API_UpdateProgress(const char* token, const char* node_id,
                        const char* chunk_id, int progress) {
    char body[512];
    char response[1024] = {0};
    int http_code = 0;

    snprintf(body, sizeof(body),
            "{\"node_id\":\"%s\",\"chunk_id\":\"%s\",\"progress\":%d}",
            node_id, chunk_id, progress);

    HttpRequest req = {
        .method = "POST",
        .path = "/jobs/progress",
        .token = token,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    return HTTP_Request(&req);
}

bool API_SubmitWork(const char* token, const char* node_id, const char* chunk_id,
                    const char* result_hash, const char* result_file,
                    double* earned_net, double* new_balance) {
    char body[2048];
    char response[4096] = {0};
    int http_code = 0;

    if (result_file && strlen(result_file) > 0) {
        snprintf(body, sizeof(body),
                "{\"node_id\":\"%s\",\"chunk_id\":\"%s\",\"result_hash\":\"%s\",\"result_file\":\"%s\"}",
                node_id, chunk_id, result_hash, result_file);
    } else {
        snprintf(body, sizeof(body),
                "{\"node_id\":\"%s\",\"chunk_id\":\"%s\",\"result_hash\":\"%s\"}",
                node_id, chunk_id, result_hash);
    }

    HttpRequest req = {
        .method = "POST",
        .path = "/jobs/submit",
        .token = token,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) return false;
    if (!json_get_bool(response, "success")) return false;

    const char* data = json_find_object(response, "data");
    if (data) {
        const char* earned = json_find_object(data, "earned");
        if (earned && earned_net) {
            *earned_net = json_get_number(earned, "net");
        }

        const char* balance = json_find_object(data, "user_balance");
        if (balance && new_balance) {
            *new_balance = json_get_number(balance, "available");
        }
    }

    return true;
}

bool API_ReportError(const char* token, const char* node_id, const char* chunk_id,
                     const char* error_message) {
    char body[2048];
    char response[1024] = {0};
    int http_code = 0;

    snprintf(body, sizeof(body),
            "{\"node_id\":\"%s\",\"chunk_id\":\"%s\",\"error_message\":\"%s\"}",
            node_id, chunk_id, error_message);

    HttpRequest req = {
        .method = "POST",
        .path = "/jobs/error",
        .token = token,
        .body = body,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    return HTTP_Request(&req);
}

// ============ Earnings API ============

bool API_GetEarningsSummary(const char* token, EarningsSummary* summary) {
    char response[8192] = {0};
    int http_code = 0;

    HttpRequest req = {
        .method = "GET",
        .path = "/earnings/summary",
        .token = token,
        .body = NULL,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    if (!HTTP_Request(&req)) return false;
    if (!json_get_bool(response, "success")) return false;

    const char* data = json_find_object(response, "data");
    if (data && summary) {
        summary->balance = json_get_number(data, "balance");
        summary->pending_earnings = json_get_number(data, "pending_earnings");
        summary->total_earned = json_get_number(data, "total_earned");
        summary->total_withdrawn = json_get_number(data, "total_withdrawn");

        const char* periods = json_find_object(data, "periods");
        if (periods) {
            summary->today = json_get_number(periods, "today");
            summary->this_week = json_get_number(periods, "this_week");
            summary->this_month = json_get_number(periods, "this_month");
        }
    }

    return true;
}

// ============ Utility ============

bool API_Ping(void) {
    char response[1024] = {0};
    int http_code = 0;

    HttpRequest req = {
        .method = "GET",
        .path = "/pool/stats",
        .token = NULL,
        .body = NULL,
        .response = response,
        .response_size = sizeof(response),
        .http_code = &http_code
    };

    return HTTP_Request(&req) && http_code == 200;
}

void API_Init(void) {
    // Nothing special needed
}

void API_Cleanup(void) {
    // Nothing special needed
}
