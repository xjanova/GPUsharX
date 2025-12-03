/**
 * GPU Share Client - Type Definitions
 * Support for multiple GPUs
 */

#ifndef TYPES_H
#define TYPES_H

#include "common.h"

// Maximum supported GPUs
#define MAX_GPUS 8

// Temperature history for graph
#define TEMP_HISTORY_SIZE 60  // 60 data points (1 minute at 1 second interval)

// Job Status enum
typedef enum {
    JOB_IDLE = 0,
    JOB_RECEIVING,
    JOB_PROCESSING,
    JOB_UPLOADING,
    JOB_COMPLETED,
    JOB_FAILED
} JobStatus;

// GPU Information structure (per GPU)
typedef struct {
    int index;                      // GPU index (0, 1, 2, ...)
    char name[256];                 // GPU name
    char uuid[64];                  // GPU UUID
    int temperature;                // Celsius
    int power_usage;                // Watts
    int power_limit;                // Watts
    int memory_used;                // MB
    int memory_total;               // MB
    int fan_speed;                  // Percentage (current)
    int fan_speed_target;           // Percentage (target/set)
    int gpu_load;                   // Percentage
    int memory_load;                // Percentage
    bool is_available;              // GPU available for work
    bool is_enabled;                // User enabled this GPU for sharing

    // User controls
    int performance_limit;          // 0-100% performance limit set by user
    bool auto_fan;                  // Auto fan control
    int manual_fan_speed;           // Manual fan speed if auto_fan is false

    // Temperature history for graph
    int temp_history[TEMP_HISTORY_SIZE];
    int temp_history_index;
    int temp_history_count;

    // Stats
    int jobs_completed;
    int jobs_failed;
    double earnings;
    uint64_t work_time_seconds;
} GPUInfo;

// Multi-GPU Manager structure
typedef struct {
    int gpu_count;                  // Number of detected GPUs
    GPUInfo gpus[MAX_GPUS];         // Array of GPU info
    int selected_gpu;               // Currently selected GPU in UI (for details view)
    int enabled_count;              // Number of GPUs enabled for sharing
} GPUManager;

// System Information structure
typedef struct {
    int cpu_usage;          // Percentage
    int ram_used;           // MB
    int ram_total;          // MB
    float upload_speed;     // MB/s
    float download_speed;   // MB/s
    uint64_t total_upload;  // Bytes
    uint64_t total_download;// Bytes
} SystemInfo;

// Earnings structure
typedef struct {
    double today;
    double total;
    double pending;
    int jobs_completed;
    int jobs_failed;
    int consecutive_failures;
    uint64_t uptime_seconds;
} Earnings;

// Current Job structure
typedef struct {
    char job_id[64];
    char job_type[32];
    int progress;           // 0-100
    JobStatus status;
    char status_message[256];
    uint64_t start_time;
    int assigned_gpu;       // Which GPU is processing this job
} CurrentJob;

// Settings structure
typedef struct {
    char api_key[256];
    char server_url[512];
    int temp_limit;         // Global temperature limit
    int power_limit;        // Global power limit
    bool auto_start;
    bool minimize_to_tray;
    int language;           // 0 = EN, 1 = TH
    bool is_running;

    // Per-GPU settings are stored in GPUInfo
} Settings;

// Application State
typedef struct {
    GPUManager gpu_manager;
    SystemInfo system;
    Earnings earnings;
    CurrentJob job;
    Settings settings;
    bool is_connected;
    char last_error[256];
} AppState;

// UI Control IDs
#define ID_BTN_START        1001
#define ID_BTN_STOP         1002
#define ID_BTN_SETTINGS     1003
#define ID_BTN_LANG_EN      1004
#define ID_BTN_LANG_TH      1005
#define ID_BTN_MINIMIZE     1006
#define ID_BTN_CLOSE        1007
#define ID_EDIT_APIKEY      1010
#define ID_EDIT_SERVER      1011
#define ID_SLIDER_TEMP      1012
#define ID_SLIDER_FAN       1013
#define ID_SLIDER_PERF      1014
#define ID_CHECK_AUTOSTART  1015
#define ID_CHECK_TRAY       1016
#define ID_CHECK_AUTO_FAN   1017
#define ID_CHECK_GPU_ENABLE 1018
#define ID_TIMER_UPDATE     1100
#define ID_TRAY_ICON        1200

// GPU Selection buttons (1300 + gpu_index)
#define ID_BTN_GPU_BASE     1300

// Window Messages
#define WM_TRAYICON         (WM_USER + 1)
#define WM_UPDATE_UI        (WM_USER + 2)
#define WM_GPU_SELECTED     (WM_USER + 3)

#endif // TYPES_H
