/**
 * GPU Share Client - Logging System
 * Copyright (c) 2024 Xman Studio Thailand
 */

#ifndef LOGGER_H
#define LOGGER_H

#include "common.h"

// Log levels
typedef enum {
    LOG_DEBUG = 0,
    LOG_INFO,
    LOG_WARNING,
    LOG_ERROR,
    LOG_CRITICAL
} LogLevel;

// Log entry structure
typedef struct {
    uint64_t timestamp;
    LogLevel level;
    char category[32];
    char message[512];
    char details[1024];
} LogEntry;

// Log file naming: gpu_share_YYYY-MM-DD.log

// ============================================================================
// FUNCTION DECLARATIONS
// ============================================================================

// Initialize/cleanup
bool Logger_Init(void);
void Logger_Cleanup(void);

// Core logging functions
void Logger_Log(LogLevel level, const char* category, const char* format, ...);
void Logger_LogWithDetails(LogLevel level, const char* category, const char* message, const char* details);

// Convenience macros
#define LOG_DEBUG(cat, fmt, ...)    Logger_Log(LOG_DEBUG, cat, fmt, ##__VA_ARGS__)
#define LOG_INFO(cat, fmt, ...)     Logger_Log(LOG_INFO, cat, fmt, ##__VA_ARGS__)
#define LOG_WARN(cat, fmt, ...)     Logger_Log(LOG_WARNING, cat, fmt, ##__VA_ARGS__)
#define LOG_ERROR(cat, fmt, ...)    Logger_Log(LOG_ERROR, cat, fmt, ##__VA_ARGS__)
#define LOG_CRITICAL(cat, fmt, ...) Logger_Log(LOG_CRITICAL, cat, fmt, ##__VA_ARGS__)

// Category constants
#define LOG_CAT_APP         "APP"
#define LOG_CAT_GPU         "GPU"
#define LOG_CAT_WORKER      "WORKER"
#define LOG_CAT_API         "API"
#define LOG_CAT_NETWORK     "NETWORK"
#define LOG_CAT_JOB         "JOB"
#define LOG_CAT_ELECTRICITY "ELEC"
#define LOG_CAT_SETTINGS    "SETTINGS"
#define LOG_CAT_AUTH        "AUTH"
#define LOG_CAT_UI          "UI"

// Log management
bool Logger_SetLevel(LogLevel minLevel);
bool Logger_SetEnabled(bool enabled);
bool Logger_RotateLogs(int keepDays);
bool Logger_GetLogFilePath(char* path, size_t size);

// Read logs (for UI display)
int Logger_ReadRecent(LogEntry* entries, int maxEntries);
int Logger_ReadByDate(int year, int month, int day, LogEntry* entries, int maxEntries);

// Statistics
typedef struct {
    int total_entries;
    int debug_count;
    int info_count;
    int warning_count;
    int error_count;
    int critical_count;
    uint64_t log_file_size;
    uint64_t oldest_entry;
    uint64_t newest_entry;
} LogStatistics;

bool Logger_GetStatistics(LogStatistics* stats);

#endif // LOGGER_H
