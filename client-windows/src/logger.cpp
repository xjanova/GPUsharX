/**
 * GPU Share Client - Logging System Implementation
 * Copyright (c) 2024 Xman Studio Thailand
 */

#include "../include/logger.h"
#include "../include/settings.h"
#include "../include/config.h"
#include <windows.h>
#include <stdio.h>
#include <stdarg.h>
#include <time.h>
#include <string.h>

// ============================================================================
// PRIVATE DATA
// ============================================================================
static FILE* g_log_file = NULL;
static CRITICAL_SECTION g_log_cs;
static bool g_initialized = false;
static bool g_enabled = true;
static LogLevel g_min_level = LOG_INFO;
static char g_current_log_path[MAX_PATH] = {0};
static int g_current_log_day = -1;

// ============================================================================
// HELPER FUNCTIONS
// ============================================================================
static const char* GetLevelString(LogLevel level) {
    switch (level) {
        case LOG_DEBUG:     return "DEBUG";
        case LOG_INFO:      return "INFO";
        case LOG_WARNING:   return "WARN";
        case LOG_ERROR:     return "ERROR";
        case LOG_CRITICAL:  return "CRIT";
        default:            return "UNKNOWN";
    }
}

static void GetCurrentLogFileName(char* path, size_t size) {
    char log_dir[MAX_PATH];
    GetLogPath(log_dir, sizeof(log_dir));

    time_t now = time(NULL);
    struct tm* tm_info = localtime(&now);

    snprintf(path, size, "%s\\gpu_share_%04d-%02d-%02d.log",
             log_dir,
             tm_info->tm_year + 1900,
             tm_info->tm_mon + 1,
             tm_info->tm_mday);
}

static bool EnsureLogFile(void) {
    time_t now = time(NULL);
    struct tm* tm_info = localtime(&now);
    int today = tm_info->tm_mday;

    // Check if we need to rotate (new day)
    if (g_current_log_day != today) {
        if (g_log_file) {
            fclose(g_log_file);
            g_log_file = NULL;
        }
        g_current_log_day = today;
    }

    if (!g_log_file) {
        GetCurrentLogFileName(g_current_log_path, sizeof(g_current_log_path));
        g_log_file = fopen(g_current_log_path, "a");

        if (g_log_file) {
            // Write header if new file
            fseek(g_log_file, 0, SEEK_END);
            if (ftell(g_log_file) == 0) {
                fprintf(g_log_file, "===============================================\n");
                fprintf(g_log_file, "GPU Share Client Log - %04d-%02d-%02d\n",
                        tm_info->tm_year + 1900, tm_info->tm_mon + 1, tm_info->tm_mday);
                fprintf(g_log_file, "===============================================\n\n");
            }
        }
    }

    return g_log_file != NULL;
}

// ============================================================================
// PUBLIC FUNCTIONS
// ============================================================================
bool Logger_Init(void) {
    if (g_initialized) return true;

    InitializeCriticalSection(&g_log_cs);

    // Get settings
    AppSettings* settings = Settings_Get();
    if (settings) {
        g_enabled = settings->logging_enabled;
    }

    g_initialized = true;

    // Write startup message
    LOG_INFO(LOG_CAT_APP, "=== GPU Share Client Started ===");
    LOG_INFO(LOG_CAT_APP, "Version: %s", APP_VERSION);

    return true;
}

void Logger_Cleanup(void) {
    if (!g_initialized) return;

    LOG_INFO(LOG_CAT_APP, "=== GPU Share Client Shutdown ===");

    EnterCriticalSection(&g_log_cs);

    if (g_log_file) {
        fflush(g_log_file);
        fclose(g_log_file);
        g_log_file = NULL;
    }

    LeaveCriticalSection(&g_log_cs);
    DeleteCriticalSection(&g_log_cs);

    g_initialized = false;
}

void Logger_Log(LogLevel level, const char* category, const char* format, ...) {
    if (!g_initialized || !g_enabled) return;
    if (level < g_min_level) return;
    if (!format) return;

    EnterCriticalSection(&g_log_cs);

    if (!EnsureLogFile()) {
        LeaveCriticalSection(&g_log_cs);
        return;
    }

    // Get timestamp
    time_t now = time(NULL);
    struct tm* tm_info = localtime(&now);

    // Format message
    char message[512];
    va_list args;
    va_start(args, format);
    vsnprintf(message, sizeof(message), format, args);
    va_end(args);

    // Write to file
    fprintf(g_log_file, "[%02d:%02d:%02d] [%-5s] [%-8s] %s\n",
            tm_info->tm_hour, tm_info->tm_min, tm_info->tm_sec,
            GetLevelString(level),
            category ? category : "GENERAL",
            message);

    fflush(g_log_file);

    // Also output to debug console in debug builds
#ifdef _DEBUG
    char debug_msg[600];
    snprintf(debug_msg, sizeof(debug_msg), "[%s] [%s] %s\n",
             GetLevelString(level), category ? category : "GENERAL", message);
    OutputDebugStringA(debug_msg);
#endif

    LeaveCriticalSection(&g_log_cs);
}

void Logger_LogWithDetails(LogLevel level, const char* category, const char* message, const char* details) {
    if (!g_initialized || !g_enabled) return;
    if (level < g_min_level) return;

    EnterCriticalSection(&g_log_cs);

    if (!EnsureLogFile()) {
        LeaveCriticalSection(&g_log_cs);
        return;
    }

    time_t now = time(NULL);
    struct tm* tm_info = localtime(&now);

    fprintf(g_log_file, "[%02d:%02d:%02d] [%-5s] [%-8s] %s\n",
            tm_info->tm_hour, tm_info->tm_min, tm_info->tm_sec,
            GetLevelString(level),
            category ? category : "GENERAL",
            message ? message : "");

    if (details && strlen(details) > 0) {
        fprintf(g_log_file, "    Details: %s\n", details);
    }

    fflush(g_log_file);

    LeaveCriticalSection(&g_log_cs);
}

bool Logger_SetLevel(LogLevel minLevel) {
    g_min_level = minLevel;
    return true;
}

bool Logger_SetEnabled(bool enabled) {
    g_enabled = enabled;

    AppSettings* settings = Settings_Get();
    if (settings) {
        settings->logging_enabled = enabled;
        Settings_Save(settings);
    }

    return true;
}

bool Logger_RotateLogs(int keepDays) {
    if (keepDays < 1) keepDays = 7;

    char log_dir[MAX_PATH];
    GetLogPath(log_dir, sizeof(log_dir));

    WIN32_FIND_DATAA findData;
    char search_path[MAX_PATH];
    snprintf(search_path, sizeof(search_path), "%s\\gpu_share_*.log", log_dir);

    HANDLE hFind = FindFirstFileA(search_path, &findData);
    if (hFind == INVALID_HANDLE_VALUE) return true;

    time_t now = time(NULL);
    time_t cutoff = now - (keepDays * 86400);

    int deleted = 0;

    do {
        // Get file modification time
        char filepath[MAX_PATH];
        snprintf(filepath, sizeof(filepath), "%s\\%s", log_dir, findData.cFileName);

        FILETIME ft = findData.ftLastWriteTime;
        ULARGE_INTEGER ull;
        ull.LowPart = ft.dwLowDateTime;
        ull.HighPart = ft.dwHighDateTime;

        // Convert FILETIME to Unix timestamp
        time_t file_time = (time_t)((ull.QuadPart - 116444736000000000ULL) / 10000000ULL);

        if (file_time < cutoff) {
            // Don't delete current log file
            char current_log[MAX_PATH];
            GetCurrentLogFileName(current_log, sizeof(current_log));

            if (strcmp(filepath, current_log) != 0) {
                if (DeleteFileA(filepath)) {
                    deleted++;
                }
            }
        }
    } while (FindNextFileA(hFind, &findData));

    FindClose(hFind);

    if (deleted > 0) {
        LOG_INFO(LOG_CAT_APP, "Log rotation: deleted %d old log files", deleted);
    }

    return true;
}

bool Logger_GetLogFilePath(char* path, size_t size) {
    if (!path || size == 0) return false;

    GetCurrentLogFileName(path, size);
    return true;
}

int Logger_ReadRecent(LogEntry* entries, int maxEntries) {
    if (!entries || maxEntries <= 0) return 0;

    char log_path[MAX_PATH];
    GetCurrentLogFileName(log_path, sizeof(log_path));

    FILE* file = fopen(log_path, "r");
    if (!file) return 0;

    // Read all lines and keep last N entries
    char line[1024];
    int total_lines = 0;

    // First pass: count lines
    while (fgets(line, sizeof(line), file)) {
        if (line[0] == '[') total_lines++;
    }

    // Second pass: read last N lines
    rewind(file);

    int skip = total_lines > maxEntries ? total_lines - maxEntries : 0;
    int current = 0;
    int read = 0;

    while (fgets(line, sizeof(line), file)) {
        if (line[0] != '[') continue;

        if (current++ < skip) continue;

        // Parse line
        LogEntry* entry = &entries[read];
        memset(entry, 0, sizeof(LogEntry));

        // Parse: [HH:MM:SS] [LEVEL] [CATEGORY] Message
        int hour, min, sec;
        char level_str[16], category[32];

        if (sscanf(line, "[%d:%d:%d] [%15[^]]] [%31[^]]] %511[^\n]",
                   &hour, &min, &sec, level_str, category, entry->message) >= 6) {

            // Set level
            if (strcmp(level_str, "DEBUG") == 0) entry->level = LOG_DEBUG;
            else if (strcmp(level_str, "INFO") == 0) entry->level = LOG_INFO;
            else if (strcmp(level_str, "WARN") == 0) entry->level = LOG_WARNING;
            else if (strcmp(level_str, "ERROR") == 0) entry->level = LOG_ERROR;
            else if (strcmp(level_str, "CRIT") == 0) entry->level = LOG_CRITICAL;

            strncpy(entry->category, category, sizeof(entry->category) - 1);

            // Remove leading/trailing whitespace from message
            char* msg = entry->message;
            while (*msg == ' ') msg++;
            memmove(entry->message, msg, strlen(msg) + 1);

            read++;
        }

        if (read >= maxEntries) break;
    }

    fclose(file);
    return read;
}

bool Logger_GetStatistics(LogStatistics* stats) {
    if (!stats) return false;

    memset(stats, 0, sizeof(LogStatistics));

    char log_path[MAX_PATH];
    GetCurrentLogFileName(log_path, sizeof(log_path));

    // Get file size
    WIN32_FILE_ATTRIBUTE_DATA fileInfo;
    if (GetFileAttributesExA(log_path, GetFileExInfoStandard, &fileInfo)) {
        stats->log_file_size = ((uint64_t)fileInfo.nFileSizeHigh << 32) | fileInfo.nFileSizeLow;
    }

    FILE* file = fopen(log_path, "r");
    if (!file) return false;

    char line[1024];
    while (fgets(line, sizeof(line), file)) {
        if (line[0] != '[') continue;

        stats->total_entries++;

        // Count by level
        if (strstr(line, "[DEBUG]")) stats->debug_count++;
        else if (strstr(line, "[INFO]")) stats->info_count++;
        else if (strstr(line, "[WARN]")) stats->warning_count++;
        else if (strstr(line, "[ERROR]")) stats->error_count++;
        else if (strstr(line, "[CRIT]")) stats->critical_count++;
    }

    fclose(file);
    return true;
}
