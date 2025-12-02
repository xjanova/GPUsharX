/**
 * GPU Share Client - Main Application
 * Professional Windows GUI Dashboard with Login
 * Copyright (c) 2024 Xman Studio Thailand
 */

#define UNICODE
#define _UNICODE

#include <windows.h>
#include <windowsx.h>
#include <commctrl.h>
#include <shellapi.h>
#include <stdio.h>
#include <stdlib.h>
#include <string.h>

#include "../include/config.h"
#include "../include/types.h"
#include "../include/lang.h"
#include "../include/api.h"
#include "../include/worker.h"

#pragma comment(lib, "comctl32.lib")
#pragma comment(lib, "gdi32.lib")
#pragma comment(lib, "user32.lib")
#pragma comment(lib, "shell32.lib")

// UI Modes
typedef enum {
    UI_MODE_LOGIN,
    UI_MODE_DASHBOARD
} UIMode;

// Forward declarations
LRESULT CALLBACK WndProc(HWND, UINT, WPARAM, LPARAM);
void DrawLoginScreen(HDC hdc, RECT* rect);
void DrawDashboard(HDC hdc, RECT* rect);
void UpdateStats(void);

// External functions
extern bool GPU_Init(void);
extern void GPU_Shutdown(void);
extern bool GPU_GetInfo(GPUInfo* info);
extern bool GPU_IsAvailable(void);
extern COLORREF GPU_GetTempColor(int temp);
extern COLORREF GPU_GetLoadColor(int load);

extern void System_Init(void);
extern bool System_GetInfo(SystemInfo* info);
extern void System_FormatBytes(uint64_t bytes, char* buffer, size_t size);
extern void System_FormatSpeed(float speed, char* buffer, size_t size);
extern void System_FormatUptime(uint64_t seconds, char* buffer, size_t size, bool is_thai);

// Global state
static UIMode g_ui_mode = UI_MODE_LOGIN;
static WorkerContext g_worker = {0};
static GPUInfo g_gpu = {0};
static SystemInfo g_system = {0};

static HWND g_hwnd = NULL;
static HFONT g_font_title = NULL;
static HFONT g_font_large = NULL;
static HFONT g_font_normal = NULL;
static HFONT g_font_small = NULL;
static HBRUSH g_brush_bg = NULL;
static HBRUSH g_brush_card = NULL;

// Login controls
static HWND g_edit_email = NULL;
static HWND g_edit_password = NULL;
static HWND g_btn_login = NULL;
static HWND g_label_error = NULL;

// UI State
static int g_hover_button = 0;
static char g_login_error[256] = {0};
static bool g_logging_in = false;

// Button IDs
#define ID_BTN_LOGIN_SUBMIT 2001
#define ID_EDIT_EMAIL       2002
#define ID_EDIT_PASSWORD    2003

// Initialize application
void App_Init(void) {
    GPU_Init();
    System_Init();
    Worker_Init(&g_worker);
    API_Init();

    // Get initial GPU info
    GPU_GetInfo(&g_gpu);
}

// Cleanup application
void App_Cleanup(void) {
    Worker_Cleanup(&g_worker);
    API_Cleanup();
    GPU_Shutdown();

    if (g_font_title) DeleteObject(g_font_title);
    if (g_font_large) DeleteObject(g_font_large);
    if (g_font_normal) DeleteObject(g_font_normal);
    if (g_font_small) DeleteObject(g_font_small);
    if (g_brush_bg) DeleteObject(g_brush_bg);
    if (g_brush_card) DeleteObject(g_brush_card);
}

// Create fonts
void CreateFonts(void) {
    g_font_title = CreateFontW(28, 0, 0, 0, FW_BOLD, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_SWISS, L"Segoe UI");

    g_font_large = CreateFontW(24, 0, 0, 0, FW_SEMIBOLD, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_SWISS, L"Segoe UI");

    g_font_normal = CreateFontW(16, 0, 0, 0, FW_NORMAL, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_SWISS, L"Segoe UI");

    g_font_small = CreateFontW(13, 0, 0, 0, FW_NORMAL, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_SWISS, L"Segoe UI");

    g_brush_bg = CreateSolidBrush(COLOR_BG_DARK);
    g_brush_card = CreateSolidBrush(COLOR_BG_CARD);
}

// Create login controls
void CreateLoginControls(HWND parent) {
    RECT rect;
    GetClientRect(parent, &rect);
    int cx = rect.right / 2;
    int cy = rect.bottom / 2;

    int input_w = 300;
    int input_h = 35;
    int gap = 15;

    // Email input
    g_edit_email = CreateWindowExW(
        0, L"EDIT", L"",
        WS_CHILD | WS_VISIBLE | WS_BORDER | ES_AUTOHSCROLL,
        cx - input_w/2, cy - 60, input_w, input_h,
        parent, (HMENU)ID_EDIT_EMAIL, GetModuleHandle(NULL), NULL
    );
    SendMessage(g_edit_email, WM_SETFONT, (WPARAM)g_font_normal, TRUE);
    SendMessage(g_edit_email, EM_SETCUEBANNER, 0, (LPARAM)L"Email");

    // Password input
    g_edit_password = CreateWindowExW(
        0, L"EDIT", L"",
        WS_CHILD | WS_VISIBLE | WS_BORDER | ES_PASSWORD | ES_AUTOHSCROLL,
        cx - input_w/2, cy - 60 + input_h + gap, input_w, input_h,
        parent, (HMENU)ID_EDIT_PASSWORD, GetModuleHandle(NULL), NULL
    );
    SendMessage(g_edit_password, WM_SETFONT, (WPARAM)g_font_normal, TRUE);
    SendMessage(g_edit_password, EM_SETCUEBANNER, 0, (LPARAM)L"Password");

    // Login button
    g_btn_login = CreateWindowExW(
        0, L"BUTTON", L"Login",
        WS_CHILD | WS_VISIBLE | BS_PUSHBUTTON,
        cx - input_w/2, cy - 60 + (input_h + gap) * 2, input_w, 45,
        parent, (HMENU)ID_BTN_LOGIN_SUBMIT, GetModuleHandle(NULL), NULL
    );
    SendMessage(g_btn_login, WM_SETFONT, (WPARAM)g_font_normal, TRUE);
}

// Hide login controls
void HideLoginControls(void) {
    if (g_edit_email) ShowWindow(g_edit_email, SW_HIDE);
    if (g_edit_password) ShowWindow(g_edit_password, SW_HIDE);
    if (g_btn_login) ShowWindow(g_btn_login, SW_HIDE);
}

// Show login controls
void ShowLoginControls(void) {
    if (g_edit_email) ShowWindow(g_edit_email, SW_SHOW);
    if (g_edit_password) ShowWindow(g_edit_password, SW_SHOW);
    if (g_btn_login) ShowWindow(g_btn_login, SW_SHOW);
}

// Handle login
void DoLogin(void) {
    if (g_logging_in) return;

    wchar_t wemail[256], wpassword[256];
    char email[256], password[256];

    GetWindowTextW(g_edit_email, wemail, 256);
    GetWindowTextW(g_edit_password, wpassword, 256);

    WideCharToMultiByte(CP_UTF8, 0, wemail, -1, email, 256, NULL, NULL);
    WideCharToMultiByte(CP_UTF8, 0, wpassword, -1, password, 256, NULL, NULL);

    if (strlen(email) == 0 || strlen(password) == 0) {
        strcpy(g_login_error, "Please enter email and password");
        InvalidateRect(g_hwnd, NULL, FALSE);
        return;
    }

    g_logging_in = true;
    g_login_error[0] = '\0';
    InvalidateRect(g_hwnd, NULL, FALSE);

    // Try to start worker (login + register node)
    if (Worker_Start(&g_worker, email, password)) {
        // Success - switch to dashboard
        g_ui_mode = UI_MODE_DASHBOARD;
        HideLoginControls();
    } else {
        // Failed
        strcpy(g_login_error, Worker_GetError(&g_worker));
    }

    g_logging_in = false;
    InvalidateRect(g_hwnd, NULL, FALSE);
}

// Draw rounded rectangle
void DrawRoundRect(HDC hdc, int x, int y, int w, int h, int r, COLORREF fill, COLORREF border) {
    HBRUSH brush = CreateSolidBrush(fill);
    HPEN pen = CreatePen(PS_SOLID, 1, border);
    HBRUSH oldBrush = SelectObject(hdc, brush);
    HPEN oldPen = SelectObject(hdc, pen);

    RoundRect(hdc, x, y, x + w, y + h, r, r);

    SelectObject(hdc, oldBrush);
    SelectObject(hdc, oldPen);
    DeleteObject(brush);
    DeleteObject(pen);
}

// Draw text with color
void DrawTextColor(HDC hdc, const char* text, int x, int y, COLORREF color, HFONT font) {
    SetTextColor(hdc, color);
    SetBkMode(hdc, TRANSPARENT);
    if (font) SelectObject(hdc, font);

    wchar_t wtext[512];
    MultiByteToWideChar(CP_UTF8, 0, text, -1, wtext, 512);
    TextOutW(hdc, x, y, wtext, (int)wcslen(wtext));
}

// Draw progress bar
void DrawProgressBar(HDC hdc, int x, int y, int w, int h, int value, int max_val, COLORREF color) {
    DrawRoundRect(hdc, x, y, w, h, 4, COLOR_BG_HOVER, COLOR_BORDER);

    if (value > 0 && max_val > 0) {
        int fill_w = (w - 4) * value / max_val;
        if (fill_w > 0) {
            DrawRoundRect(hdc, x + 2, y + 2, fill_w, h - 4, 3, color, color);
        }
    }
}

// Draw stat card
void DrawStatCard(HDC hdc, int x, int y, int w, int h, const char* title, const char* value, const char* unit, COLORREF accent) {
    DrawRoundRect(hdc, x, y, w, h, 12, COLOR_BG_CARD, COLOR_BORDER);

    HBRUSH accent_brush = CreateSolidBrush(accent);
    RECT accent_rect = {x + 12, y + 12, x + 16, y + h - 12};
    FillRect(hdc, &accent_rect, accent_brush);
    DeleteObject(accent_brush);

    DrawTextColor(hdc, title, x + 24, y + 15, COLOR_TEXT_MUTED, g_font_small);
    DrawTextColor(hdc, value, x + 24, y + 35, COLOR_TEXT, g_font_large);

    if (unit && strlen(unit) > 0) {
        DrawTextColor(hdc, unit, x + 24, y + 65, COLOR_TEXT_MUTED, g_font_small);
    }
}

// Draw status indicator
void DrawStatusIndicator(HDC hdc, int x, int y, int size, COLORREF color, bool blink) {
    static int blink_state = 0;
    blink_state = (blink_state + 1) % 20;

    COLORREF draw_color = color;
    if (blink && blink_state < 10) {
        int r = GetRValue(color) / 2;
        int g = GetGValue(color) / 2;
        int b = GetBValue(color) / 2;
        draw_color = RGB(r, g, b);
    }

    HBRUSH brush = CreateSolidBrush(draw_color);
    HBRUSH old = SelectObject(hdc, brush);
    Ellipse(hdc, x, y, x + size, y + size);
    SelectObject(hdc, old);
    DeleteObject(brush);
}

// Get status color
COLORREF GetStatusColor(JobStatus status) {
    switch (status) {
        case JOB_IDLE: return COLOR_IDLE;
        case JOB_RECEIVING: return COLOR_RECEIVING;
        case JOB_PROCESSING: return COLOR_PROCESSING;
        case JOB_UPLOADING: return COLOR_UPLOADING;
        case JOB_COMPLETED: return COLOR_SUCCESS;
        case JOB_FAILED: return COLOR_ERROR;
        default: return COLOR_IDLE;
    }
}

// Get status text
const char* GetStatusText(JobStatus status) {
    switch (status) {
        case JOB_IDLE: return L(STR_IDLE);
        case JOB_RECEIVING: return L(STR_RECEIVING);
        case JOB_PROCESSING: return L(STR_PROCESSING);
        case JOB_UPLOADING: return L(STR_UPLOADING);
        case JOB_COMPLETED: return L(STR_COMPLETED);
        case JOB_FAILED: return L(STR_FAILED);
        default: return L(STR_IDLE);
    }
}

// Draw button
bool DrawButton(HDC hdc, int x, int y, int w, int h, const char* text, COLORREF bg, bool hover, int id) {
    COLORREF draw_bg = hover ? COLOR_BG_HOVER : bg;
    DrawRoundRect(hdc, x, y, w, h, 8, draw_bg, hover ? COLOR_PRIMARY : COLOR_BORDER);

    wchar_t wtext[64];
    MultiByteToWideChar(CP_UTF8, 0, text, -1, wtext, 64);

    SIZE text_size;
    SelectObject(hdc, g_font_normal);
    GetTextExtentPoint32W(hdc, wtext, (int)wcslen(wtext), &text_size);

    int tx = x + (w - text_size.cx) / 2;
    int ty = y + (h - text_size.cy) / 2;

    DrawTextColor(hdc, text, tx, ty, COLOR_TEXT, g_font_normal);
    return true;
}

// Draw login screen
void DrawLoginScreen(HDC hdc, RECT* rect) {
    int width = rect->right - rect->left;
    int height = rect->bottom - rect->top;

    FillRect(hdc, rect, g_brush_bg);

    int cx = width / 2;
    int cy = height / 2;

    // Title
    DrawTextColor(hdc, L(STR_APP_TITLE), cx - 100, cy - 180, COLOR_TEXT, g_font_title);

    // Subtitle
    DrawTextColor(hdc, "Share your GPU power and earn!", cx - 110, cy - 140, COLOR_TEXT_MUTED, g_font_normal);

    // GPU detected
    char gpu_text[512];
    snprintf(gpu_text, sizeof(gpu_text), "Detected: %s (%d MB)",
            g_gpu.name[0] ? g_gpu.name : "No GPU", g_gpu.memory_total);
    DrawTextColor(hdc, gpu_text, cx - 150, cy - 110, COLOR_INFO, g_font_small);

    // Error message
    if (strlen(g_login_error) > 0) {
        DrawTextColor(hdc, g_login_error, cx - 140, cy + 90, COLOR_DANGER, g_font_small);
    }

    // Loading indicator
    if (g_logging_in) {
        DrawTextColor(hdc, "Connecting...", cx - 50, cy + 90, COLOR_WARNING, g_font_small);
    }

    // Footer
    DrawTextColor(hdc, APP_AUTHOR, 20, height - 30, COLOR_TEXT_MUTED, g_font_small);

    char version[64];
    snprintf(version, sizeof(version), "v%s", APP_VERSION);
    DrawTextColor(hdc, version, width - 60, height - 30, COLOR_TEXT_MUTED, g_font_small);
}

// Draw dashboard
void DrawDashboard(HDC hdc, RECT* rect) {
    int width = rect->right - rect->left;
    int height = rect->bottom - rect->top;

    FillRect(hdc, rect, g_brush_bg);

    int padding = 20;
    int card_gap = 15;

    // ===== HEADER =====
    int header_y = padding;

    DrawTextColor(hdc, L(STR_APP_TITLE), padding, header_y, COLOR_TEXT, g_font_title);

    // User info
    char user_info[256];
    snprintf(user_info, sizeof(user_info), "%s | Node: %s",
            g_worker.session.name, g_worker.node.node_id);
    DrawTextColor(hdc, user_info, padding, header_y + 35, COLOR_TEXT_MUTED, g_font_small);

    // Language buttons
    int btn_w = 40;
    int btn_h = 30;
    bool en_hover = (g_hover_button == ID_BTN_LANG_EN);
    bool th_hover = (g_hover_button == ID_BTN_LANG_TH);

    DrawButton(hdc, width - padding - btn_w * 2 - 5, header_y, btn_w, btn_h, "EN",
              GetLanguage() == LANG_EN ? COLOR_PRIMARY : COLOR_BG_CARD, en_hover, ID_BTN_LANG_EN);
    DrawButton(hdc, width - padding - btn_w, header_y, btn_w, btn_h, "TH",
              GetLanguage() == LANG_TH ? COLOR_PRIMARY : COLOR_BG_CARD, th_hover, ID_BTN_LANG_TH);

    // ===== STATUS PANEL =====
    int status_y = header_y + 70;
    int status_h = 100;

    DrawRoundRect(hdc, padding, status_y, width - padding * 2, status_h, 12, COLOR_BG_CARD, COLOR_BORDER);

    JobStatus job_status = Worker_GetJobStatus(&g_worker);
    bool is_blinking = (job_status == JOB_RECEIVING ||
                       job_status == JOB_PROCESSING ||
                       job_status == JOB_UPLOADING);
    DrawStatusIndicator(hdc, padding + 20, status_y + 35, 30, GetStatusColor(job_status), is_blinking);

    DrawTextColor(hdc, L(STR_STATUS), padding + 65, status_y + 20, COLOR_TEXT_MUTED, g_font_small);
    DrawTextColor(hdc, GetStatusText(job_status), padding + 65, status_y + 40, COLOR_TEXT, g_font_large);

    // Progress if working
    if (g_worker.state == WORKER_WORKING) {
        char progress_text[64];
        snprintf(progress_text, sizeof(progress_text), "%d%%", g_worker.job_progress);
        DrawTextColor(hdc, progress_text, padding + 200, status_y + 45, COLOR_INFO, g_font_normal);
    }

    // Stop button
    int btn_stop_w = 120;
    int btn_stop_h = 45;
    int btn_stop_x = width - padding - btn_stop_w - 15;
    int btn_stop_y = status_y + (status_h - btn_stop_h) / 2;

    bool stop_hover = (g_hover_button == ID_BTN_STOP);
    DrawButton(hdc, btn_stop_x, btn_stop_y, btn_stop_w, btn_stop_h,
              L(STR_LOGOUT), COLOR_DANGER, stop_hover, ID_BTN_STOP);

    // ===== GPU STATUS CARD =====
    int cards_y = status_y + status_h + card_gap;
    int card_w = (width - padding * 2 - card_gap) / 2;
    int card_h = 200;

    DrawRoundRect(hdc, padding, cards_y, card_w, card_h, 12, COLOR_BG_CARD, COLOR_BORDER);

    DrawTextColor(hdc, L(STR_GPU_STATUS), padding + 15, cards_y + 15, COLOR_PRIMARY, g_font_normal);
    DrawTextColor(hdc, g_gpu.name, padding + 15, cards_y + 40, COLOR_TEXT_MUTED, g_font_small);

    int stat_y = cards_y + 65;
    char buf[64];

    // Temperature
    snprintf(buf, sizeof(buf), "%d°C", g_gpu.temperature);
    DrawTextColor(hdc, L(STR_TEMPERATURE), padding + 15, stat_y, COLOR_TEXT_MUTED, g_font_small);
    DrawTextColor(hdc, buf, padding + 120, stat_y, GPU_GetTempColor(g_gpu.temperature), g_font_normal);

    // Power
    stat_y += 28;
    snprintf(buf, sizeof(buf), "%dW / %dW", g_gpu.power_usage, g_gpu.power_limit);
    DrawTextColor(hdc, L(STR_POWER_USAGE), padding + 15, stat_y, COLOR_TEXT_MUTED, g_font_small);
    DrawTextColor(hdc, buf, padding + 120, stat_y, COLOR_TEXT, g_font_normal);

    // Memory
    stat_y += 28;
    snprintf(buf, sizeof(buf), "%d / %d MB", g_gpu.memory_used, g_gpu.memory_total);
    DrawTextColor(hdc, L(STR_MEMORY_USAGE), padding + 15, stat_y, COLOR_TEXT_MUTED, g_font_small);
    DrawTextColor(hdc, buf, padding + 120, stat_y, COLOR_TEXT, g_font_normal);

    // GPU Load bar
    stat_y += 28;
    DrawTextColor(hdc, L(STR_GPU_LOAD), padding + 15, stat_y, COLOR_TEXT_MUTED, g_font_small);
    DrawProgressBar(hdc, padding + 120, stat_y + 2, card_w - 150, 16, g_gpu.gpu_load, 100, GPU_GetLoadColor(g_gpu.gpu_load));
    snprintf(buf, sizeof(buf), "%d%%", g_gpu.gpu_load);
    DrawTextColor(hdc, buf, card_w - 15, stat_y, COLOR_TEXT, g_font_small);

    // ===== SYSTEM STATUS CARD =====
    int sys_x = padding + card_w + card_gap;
    DrawRoundRect(hdc, sys_x, cards_y, card_w, card_h, 12, COLOR_BG_CARD, COLOR_BORDER);

    DrawTextColor(hdc, L(STR_SYSTEM_STATUS), sys_x + 15, cards_y + 15, COLOR_INFO, g_font_normal);

    stat_y = cards_y + 50;

    // CPU Usage
    snprintf(buf, sizeof(buf), "%d%%", g_system.cpu_usage);
    DrawTextColor(hdc, L(STR_CPU_USAGE), sys_x + 15, stat_y, COLOR_TEXT_MUTED, g_font_small);
    DrawProgressBar(hdc, sys_x + 120, stat_y + 2, card_w - 180, 16, g_system.cpu_usage, 100, COLOR_INFO);
    DrawTextColor(hdc, buf, sys_x + card_w - 45, stat_y, COLOR_TEXT, g_font_small);

    // RAM Usage
    stat_y += 32;
    int ram_percent = g_system.ram_total > 0 ? (g_system.ram_used * 100 / g_system.ram_total) : 0;
    snprintf(buf, sizeof(buf), "%d%%", ram_percent);
    DrawTextColor(hdc, L(STR_RAM_USAGE), sys_x + 15, stat_y, COLOR_TEXT_MUTED, g_font_small);
    DrawProgressBar(hdc, sys_x + 120, stat_y + 2, card_w - 180, 16, ram_percent, 100, COLOR_WARNING);
    DrawTextColor(hdc, buf, sys_x + card_w - 45, stat_y, COLOR_TEXT, g_font_small);

    // Network
    stat_y += 40;
    DrawTextColor(hdc, L(STR_NETWORK), sys_x + 15, stat_y, COLOR_TEXT_MUTED, g_font_small);

    stat_y += 25;
    char speed_buf[32];
    System_FormatSpeed(g_system.upload_speed, speed_buf, sizeof(speed_buf));
    snprintf(buf, sizeof(buf), "%s: %s", L(STR_UPLOAD), speed_buf);
    DrawTextColor(hdc, buf, sys_x + 15, stat_y, COLOR_SUCCESS, g_font_small);

    System_FormatSpeed(g_system.download_speed, speed_buf, sizeof(speed_buf));
    snprintf(buf, sizeof(buf), "%s: %s", L(STR_DOWNLOAD), speed_buf);
    DrawTextColor(hdc, buf, sys_x + card_w / 2, stat_y, COLOR_INFO, g_font_small);

    // ===== EARNINGS SECTION =====
    int earn_y = cards_y + card_h + card_gap;
    int earn_card_w = (width - padding * 2 - card_gap * 3) / 4;
    int earn_card_h = 90;

    // Today's Earnings
    snprintf(buf, sizeof(buf), "%.2f", g_worker.today_earned);
    DrawStatCard(hdc, padding, earn_y, earn_card_w, earn_card_h,
                L(STR_TODAY_EARNINGS), buf, L(STR_THB), COLOR_SUCCESS);

    // Balance
    snprintf(buf, sizeof(buf), "%.2f", g_worker.session.balance);
    DrawStatCard(hdc, padding + earn_card_w + card_gap, earn_y, earn_card_w, earn_card_h,
                L(STR_TOTAL_EARNINGS), buf, L(STR_THB), COLOR_PRIMARY);

    // Jobs Completed
    snprintf(buf, sizeof(buf), "%d", g_worker.jobs_completed);
    DrawStatCard(hdc, padding + (earn_card_w + card_gap) * 2, earn_y, earn_card_w, earn_card_h,
                L(STR_JOBS_COMPLETED), buf, "", COLOR_INFO);

    // Uptime
    char uptime_buf[64];
    System_FormatUptime(g_worker.uptime_seconds, uptime_buf, sizeof(uptime_buf), GetLanguage() == LANG_TH);
    DrawStatCard(hdc, padding + (earn_card_w + card_gap) * 3, earn_y, earn_card_w, earn_card_h,
                L(STR_UPTIME), uptime_buf, "", COLOR_WARNING);

    // ===== FOOTER =====
    int footer_y = height - 35;
    DrawTextColor(hdc, APP_AUTHOR, padding, footer_y, COLOR_TEXT_MUTED, g_font_small);

    // Connection status
    bool connected = Worker_IsConnected(&g_worker);
    const char* conn_text = connected ? L(STR_CONNECTED) : L(STR_DISCONNECTED);
    COLORREF conn_color = connected ? COLOR_SUCCESS : COLOR_DANGER;

    SIZE text_size;
    wchar_t wconn[64];
    MultiByteToWideChar(CP_UTF8, 0, conn_text, -1, wconn, 64);
    SelectObject(hdc, g_font_small);
    GetTextExtentPoint32W(hdc, wconn, (int)wcslen(wconn), &text_size);

    DrawStatusIndicator(hdc, width - padding - text_size.cx - 20, footer_y + 3, 10, conn_color, false);
    DrawTextColor(hdc, conn_text, width - padding - text_size.cx, footer_y, conn_color, g_font_small);
}

// Update statistics
void UpdateStats(void) {
    GPU_GetInfo(&g_gpu);
    System_GetInfo(&g_system);
}

// Check if point is in button
int HitTestButton(int x, int y, RECT* rect) {
    int width = rect->right - rect->left;
    int padding = 20;

    // Language buttons
    int btn_w = 40;
    int btn_h = 30;
    int btn_y = padding;

    RECT en_rect = {width - padding - btn_w * 2 - 5, btn_y, width - padding - btn_w - 5, btn_y + btn_h};
    if (x >= en_rect.left && x <= en_rect.right && y >= en_rect.top && y <= en_rect.bottom) {
        return ID_BTN_LANG_EN;
    }

    RECT th_rect = {width - padding - btn_w, btn_y, width - padding, btn_y + btn_h};
    if (x >= th_rect.left && x <= th_rect.right && y >= th_rect.top && y <= th_rect.bottom) {
        return ID_BTN_LANG_TH;
    }

    // Stop/Logout button
    int status_y = padding + 70;
    int status_h = 100;
    int btn_stop_w = 120;
    int btn_stop_h = 45;
    int btn_stop_x = width - padding - btn_stop_w - 15;
    int btn_stop_y = status_y + (status_h - btn_stop_h) / 2;

    RECT stop_rect = {btn_stop_x, btn_stop_y, btn_stop_x + btn_stop_w, btn_stop_y + btn_stop_h};
    if (x >= stop_rect.left && x <= stop_rect.right && y >= stop_rect.top && y <= stop_rect.bottom) {
        return ID_BTN_STOP;
    }

    return 0;
}

// Window procedure
LRESULT CALLBACK WndProc(HWND hwnd, UINT msg, WPARAM wParam, LPARAM lParam) {
    switch (msg) {
        case WM_CREATE:
            CreateFonts();
            CreateLoginControls(hwnd);
            SetTimer(hwnd, ID_TIMER_UPDATE, UPDATE_INTERVAL_GPU, NULL);
            return 0;

        case WM_TIMER:
            if (wParam == ID_TIMER_UPDATE) {
                UpdateStats();
                InvalidateRect(hwnd, NULL, FALSE);
            }
            return 0;

        case WM_COMMAND:
            if (LOWORD(wParam) == ID_BTN_LOGIN_SUBMIT) {
                DoLogin();
            }
            return 0;

        case WM_PAINT: {
            PAINTSTRUCT ps;
            HDC hdc = BeginPaint(hwnd, &ps);

            RECT rect;
            GetClientRect(hwnd, &rect);
            HDC memDC = CreateCompatibleDC(hdc);
            HBITMAP memBitmap = CreateCompatibleBitmap(hdc, rect.right, rect.bottom);
            HBITMAP oldBitmap = SelectObject(memDC, memBitmap);

            if (g_ui_mode == UI_MODE_LOGIN) {
                DrawLoginScreen(memDC, &rect);
            } else {
                DrawDashboard(memDC, &rect);
            }

            BitBlt(hdc, 0, 0, rect.right, rect.bottom, memDC, 0, 0, SRCCOPY);

            SelectObject(memDC, oldBitmap);
            DeleteObject(memBitmap);
            DeleteDC(memDC);

            EndPaint(hwnd, &ps);
            return 0;
        }

        case WM_MOUSEMOVE:
            if (g_ui_mode == UI_MODE_DASHBOARD) {
                RECT rect;
                GetClientRect(hwnd, &rect);
                int new_hover = HitTestButton(GET_X_LPARAM(lParam), GET_Y_LPARAM(lParam), &rect);
                if (new_hover != g_hover_button) {
                    g_hover_button = new_hover;
                    InvalidateRect(hwnd, NULL, FALSE);
                }
            }
            return 0;

        case WM_LBUTTONDOWN:
            if (g_ui_mode == UI_MODE_DASHBOARD) {
                RECT rect;
                GetClientRect(hwnd, &rect);
                int btn = HitTestButton(GET_X_LPARAM(lParam), GET_Y_LPARAM(lParam), &rect);

                switch (btn) {
                    case ID_BTN_LANG_EN:
                        SetLanguage(LANG_EN);
                        InvalidateRect(hwnd, NULL, FALSE);
                        break;
                    case ID_BTN_LANG_TH:
                        SetLanguage(LANG_TH);
                        InvalidateRect(hwnd, NULL, FALSE);
                        break;
                    case ID_BTN_STOP:
                        Worker_Stop(&g_worker);
                        g_ui_mode = UI_MODE_LOGIN;
                        ShowLoginControls();
                        InvalidateRect(hwnd, NULL, FALSE);
                        break;
                }
            }
            return 0;

        case WM_GETMINMAXINFO: {
            MINMAXINFO* mmi = (MINMAXINFO*)lParam;
            mmi->ptMinTrackSize.x = WINDOW_MIN_WIDTH;
            mmi->ptMinTrackSize.y = WINDOW_MIN_HEIGHT;
            return 0;
        }

        case WM_SIZE:
            InvalidateRect(hwnd, NULL, FALSE);
            return 0;

        case WM_DESTROY:
            KillTimer(hwnd, ID_TIMER_UPDATE);
            PostQuitMessage(0);
            return 0;

        case WM_CTLCOLOREDIT: {
            HDC hdcEdit = (HDC)wParam;
            SetTextColor(hdcEdit, COLOR_TEXT);
            SetBkColor(hdcEdit, COLOR_BG_CARD);
            return (LRESULT)g_brush_card;
        }
    }

    return DefWindowProcW(hwnd, msg, wParam, lParam);
}

// WinMain entry point
int WINAPI wWinMain(HINSTANCE hInstance, HINSTANCE hPrevInstance, LPWSTR lpCmdLine, int nCmdShow) {
    INITCOMMONCONTROLSEX icex;
    icex.dwSize = sizeof(icex);
    icex.dwICC = ICC_WIN95_CLASSES;
    InitCommonControlsEx(&icex);

    App_Init();

    WNDCLASSEXW wc = {0};
    wc.cbSize = sizeof(wc);
    wc.style = CS_HREDRAW | CS_VREDRAW;
    wc.lpfnWndProc = WndProc;
    wc.hInstance = hInstance;
    wc.hCursor = LoadCursor(NULL, IDC_ARROW);
    wc.hbrBackground = g_brush_bg;
    wc.lpszClassName = L"GPUShareClient";
    wc.hIcon = LoadIcon(NULL, IDI_APPLICATION);
    wc.hIconSm = LoadIcon(NULL, IDI_APPLICATION);

    if (!RegisterClassExW(&wc)) {
        MessageBoxW(NULL, L"Failed to register window class", L"Error", MB_ICONERROR);
        return 1;
    }

    int screen_w = GetSystemMetrics(SM_CXSCREEN);
    int screen_h = GetSystemMetrics(SM_CYSCREEN);
    int win_x = (screen_w - WINDOW_WIDTH) / 2;
    int win_y = (screen_h - WINDOW_HEIGHT) / 2;

    g_hwnd = CreateWindowExW(
        0,
        L"GPUShareClient",
        L"GPU Share Client",
        WS_OVERLAPPEDWINDOW,
        win_x, win_y, WINDOW_WIDTH, WINDOW_HEIGHT,
        NULL, NULL, hInstance, NULL
    );

    if (!g_hwnd) {
        MessageBoxW(NULL, L"Failed to create window", L"Error", MB_ICONERROR);
        return 1;
    }

    ShowWindow(g_hwnd, nCmdShow);
    UpdateWindow(g_hwnd);

    MSG msg;
    while (GetMessage(&msg, NULL, 0, 0)) {
        TranslateMessage(&msg);
        DispatchMessage(&msg);
    }

    App_Cleanup();
    return (int)msg.wParam;
}
