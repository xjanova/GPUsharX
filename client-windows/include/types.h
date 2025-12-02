/**
 * GPU Share Client - Type Definitions
 */

#ifndef TYPES_H
#define TYPES_H

#include <windows.h>
#include <stdint.h>
#include <stdbool.h>

// Job Status enum
typedef enum {
    JOB_IDLE = 0,
    JOB_RECEIVING,
    JOB_PROCESSING,
    JOB_UPLOADING,
    JOB_COMPLETED,
    JOB_FAILED
} JobStatus;

// GPU Information structure
typedef struct {
    char name[256];
    int temperature;        // Celsius
    int power_usage;        // Watts
    int power_limit;        // Watts
    int memory_used;        // MB
    int memory_total;       // MB
    int fan_speed;          // Percentage
    int gpu_load;           // Percentage
    int memory_load;        // Percentage
    bool is_available;
} GPUInfo;

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
} CurrentJob;

// Settings structure
typedef struct {
    char api_key[256];
    char server_url[512];
    int temp_limit;
    int power_limit;
    int fan_speed_target;
    bool auto_start;
    bool minimize_to_tray;
    int language;           // 0 = EN, 1 = TH
    bool is_running;
} Settings;

// Application State
typedef struct {
    GPUInfo gpu;
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
#define ID_CHECK_AUTOSTART  1014
#define ID_CHECK_TRAY       1015
#define ID_TIMER_UPDATE     1100
#define ID_TRAY_ICON        1200

// Window Messages
#define WM_TRAYICON         (WM_USER + 1)
#define WM_UPDATE_UI        (WM_USER + 2)

#endif // TYPES_H
