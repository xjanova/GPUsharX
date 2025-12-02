/**
 * GPU Share Client - Configuration Header
 * Copyright (c) 2024 Xman Studio Thailand
 */

#ifndef CONFIG_H
#define CONFIG_H

// Application Info
#define APP_NAME            "GPU Share Client"
#define APP_VERSION         "1.0.0"
#define APP_AUTHOR          "Xman Studio Thailand"

// API Configuration - Local Development
#define API_HOST            "127.0.0.1"
#define API_PORT            8000
#define API_BASE_PATH       "/api"
#define API_USE_HTTPS       0       // 0 = HTTP, 1 = HTTPS
#define API_TIMEOUT         30000   // milliseconds

// Heartbeat Configuration
#define HEARTBEAT_INTERVAL  30000   // 30 seconds

// Update Interval (milliseconds)
#define UPDATE_INTERVAL_GPU     1000
#define UPDATE_INTERVAL_SYSTEM  2000
#define UPDATE_INTERVAL_JOB     5000

// GPU Limits
#define MAX_GPU_TEMP        85
#define MAX_GPU_POWER       300
#define MIN_FAN_SPEED       30
#define MAX_FAN_SPEED       100

// Window Dimensions
#define WINDOW_WIDTH        900
#define WINDOW_HEIGHT       650
#define WINDOW_MIN_WIDTH    800
#define WINDOW_MIN_HEIGHT   600

// Colors (RGB format for GDI)
#define COLOR_BG_DARK       RGB(17, 24, 39)      // #111827
#define COLOR_BG_CARD       RGB(31, 41, 55)      // #1F2937
#define COLOR_BG_HOVER      RGB(55, 65, 81)      // #374151
#define COLOR_PRIMARY       RGB(139, 92, 246)    // #8B5CF6 purple
#define COLOR_SUCCESS       RGB(34, 197, 94)     // #22C55E green
#define COLOR_WARNING       RGB(234, 179, 8)     // #EAB308 yellow
#define COLOR_DANGER        RGB(239, 68, 68)     // #EF4444 red
#define COLOR_INFO          RGB(59, 130, 246)    // #3B82F6 blue
#define COLOR_TEXT          RGB(255, 255, 255)   // white
#define COLOR_TEXT_MUTED    RGB(156, 163, 175)   // #9CA3AF
#define COLOR_BORDER        RGB(75, 85, 99)      // #4B5563

// Status Colors
#define COLOR_IDLE          RGB(107, 114, 128)   // gray
#define COLOR_RECEIVING     RGB(59, 130, 246)    // blue
#define COLOR_PROCESSING    RGB(34, 197, 94)     // green
#define COLOR_UPLOADING     RGB(139, 92, 246)    // purple
#define COLOR_ERROR         RGB(239, 68, 68)     // red

#endif // CONFIG_H
