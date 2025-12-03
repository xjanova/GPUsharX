/**
 * GPU Share Client - System Monitoring Module
 * CPU, RAM, and Network monitoring
 */

// Include common header first (handles winsock2/windows order)
#include "../include/common.h"
#include "../include/types.h"
#include "../include/config.h"

// Additional headers for network monitoring
#include <psapi.h>
#include <iphlpapi.h>
#include <netioapi.h>

#pragma comment(lib, "psapi.lib")
#pragma comment(lib, "iphlpapi.lib")
#pragma comment(lib, "ws2_32.lib")

// Previous network stats for speed calculation
static uint64_t prev_bytes_sent = 0;
static uint64_t prev_bytes_recv = 0;
static ULONGLONG prev_tick = 0;

// Previous CPU times
static ULARGE_INTEGER prev_idle_time = {0};
static ULARGE_INTEGER prev_kernel_time = {0};
static ULARGE_INTEGER prev_user_time = {0};

// Initialize system monitor
void System_Init(void) {
    prev_tick = GetTickCount64();

    // Initialize CPU times
    FILETIME idle, kernel, user;
    if (GetSystemTimes(&idle, &kernel, &user)) {
        prev_idle_time.LowPart = idle.dwLowDateTime;
        prev_idle_time.HighPart = idle.dwHighDateTime;
        prev_kernel_time.LowPart = kernel.dwLowDateTime;
        prev_kernel_time.HighPart = kernel.dwHighDateTime;
        prev_user_time.LowPart = user.dwLowDateTime;
        prev_user_time.HighPart = user.dwHighDateTime;
    }

    // Initialize network stats
    MIB_IF_TABLE2* if_table = NULL;
    if (GetIfTable2(&if_table) == NO_ERROR) {
        for (ULONG i = 0; i < if_table->NumEntries; i++) {
            if (if_table->Table[i].Type == IF_TYPE_ETHERNET_CSMACD ||
                if_table->Table[i].Type == IF_TYPE_IEEE80211) {
                prev_bytes_sent += if_table->Table[i].OutOctets;
                prev_bytes_recv += if_table->Table[i].InOctets;
            }
        }
        FreeMibTable(if_table);
    }
}

// Get CPU usage percentage
static int GetCPUUsage(void) {
    FILETIME idle, kernel, user;

    if (!GetSystemTimes(&idle, &kernel, &user)) {
        return 0;
    }

    ULARGE_INTEGER curr_idle, curr_kernel, curr_user;
    curr_idle.LowPart = idle.dwLowDateTime;
    curr_idle.HighPart = idle.dwHighDateTime;
    curr_kernel.LowPart = kernel.dwLowDateTime;
    curr_kernel.HighPart = kernel.dwHighDateTime;
    curr_user.LowPart = user.dwLowDateTime;
    curr_user.HighPart = user.dwHighDateTime;

    uint64_t idle_diff = curr_idle.QuadPart - prev_idle_time.QuadPart;
    uint64_t kernel_diff = curr_kernel.QuadPart - prev_kernel_time.QuadPart;
    uint64_t user_diff = curr_user.QuadPart - prev_user_time.QuadPart;

    prev_idle_time = curr_idle;
    prev_kernel_time = curr_kernel;
    prev_user_time = curr_user;

    uint64_t total = kernel_diff + user_diff;
    if (total == 0) return 0;

    uint64_t busy = total - idle_diff;
    return (int)((busy * 100) / total);
}

// Get memory info
static void GetMemoryInfo(int* used_mb, int* total_mb) {
    MEMORYSTATUSEX mem_status;
    mem_status.dwLength = sizeof(mem_status);

    if (GlobalMemoryStatusEx(&mem_status)) {
        *total_mb = (int)(mem_status.ullTotalPhys / (1024 * 1024));
        *used_mb = *total_mb - (int)(mem_status.ullAvailPhys / (1024 * 1024));
    } else {
        *used_mb = 0;
        *total_mb = 0;
    }
}

// Get network statistics
static void GetNetworkStats(uint64_t* bytes_sent, uint64_t* bytes_recv,
                           float* upload_speed, float* download_speed) {
    *bytes_sent = 0;
    *bytes_recv = 0;

    MIB_IF_TABLE2* if_table = NULL;
    if (GetIfTable2(&if_table) != NO_ERROR) {
        *upload_speed = 0;
        *download_speed = 0;
        return;
    }

    for (ULONG i = 0; i < if_table->NumEntries; i++) {
        if (if_table->Table[i].Type == IF_TYPE_ETHERNET_CSMACD ||
            if_table->Table[i].Type == IF_TYPE_IEEE80211) {
            *bytes_sent += if_table->Table[i].OutOctets;
            *bytes_recv += if_table->Table[i].InOctets;
        }
    }
    FreeMibTable(if_table);

    // Calculate speed
    ULONGLONG curr_tick = GetTickCount64();
    ULONGLONG elapsed = curr_tick - prev_tick;

    if (elapsed > 0) {
        // Bytes per second, then convert to MB/s
        *upload_speed = (float)((*bytes_sent - prev_bytes_sent) * 1000.0 / elapsed / (1024.0 * 1024.0));
        *download_speed = (float)((*bytes_recv - prev_bytes_recv) * 1000.0 / elapsed / (1024.0 * 1024.0));
    } else {
        *upload_speed = 0;
        *download_speed = 0;
    }

    prev_bytes_sent = *bytes_sent;
    prev_bytes_recv = *bytes_recv;
    prev_tick = curr_tick;
}

// Get system information
bool System_GetInfo(SystemInfo* info) {
    if (!info) return false;

    info->cpu_usage = GetCPUUsage();
    GetMemoryInfo(&info->ram_used, &info->ram_total);
    GetNetworkStats(&info->total_upload, &info->total_download,
                   &info->upload_speed, &info->download_speed);

    return true;
}

// Format bytes to human readable string
void System_FormatBytes(uint64_t bytes, char* buffer, size_t buffer_size) {
    const char* units[] = {"B", "KB", "MB", "GB", "TB"};
    int unit_index = 0;
    double value = (double)bytes;

    while (value >= 1024.0 && unit_index < 4) {
        value /= 1024.0;
        unit_index++;
    }

    snprintf(buffer, buffer_size, "%.2f %s", value, units[unit_index]);
}

// Format speed to human readable string
void System_FormatSpeed(float speed_mbps, char* buffer, size_t buffer_size) {
    if (speed_mbps < 1.0f) {
        snprintf(buffer, buffer_size, "%.0f KB/s", speed_mbps * 1024.0f);
    } else {
        snprintf(buffer, buffer_size, "%.2f MB/s", speed_mbps);
    }
}

// Format uptime
void System_FormatUptime(uint64_t seconds, char* buffer, size_t buffer_size, bool is_thai) {
    int hours = (int)(seconds / 3600);
    int minutes = (int)((seconds % 3600) / 60);

    if (is_thai) {
        snprintf(buffer, buffer_size, "%d ชั่วโมง %d นาที", hours, minutes);
    } else {
        snprintf(buffer, buffer_size, "%dh %dm", hours, minutes);
    }
}
