/**
 * GPU Share Client - Main Application
 * SPACESHIP COCKPIT UI - Futuristic Sci-Fi Theme
 * Features: 3D panels, holographic displays, neon accents
 * Auto-start, Remember password, Electricity calculator, Logging
 * Copyright (c) 2024 Xman Studio Thailand
 */

#include "../include/common.h"
#include "../include/config.h"
#include "../include/types.h"
#include "../include/lang.h"
#include "../include/api.h"
#include "../include/worker.h"
#include "../include/settings.h"
#include "../include/logger.h"

#include <windowsx.h>
#include <commctrl.h>
#include <shellapi.h>
#include <math.h>
#include <time.h>
#include <objidl.h>
#include <gdiplus.h>

#pragma comment(lib, "comctl32.lib")
#pragma comment(lib, "gdi32.lib")
#pragma comment(lib, "user32.lib")
#pragma comment(lib, "shell32.lib")
#pragma comment(lib, "msimg32.lib")
#pragma comment(lib, "advapi32.lib")
#pragma comment(lib, "gdiplus.lib")

using namespace Gdiplus;

// ============================================================================
// SPACESHIP COLOR SCHEME - Deep Space with Neon Accents
// ============================================================================
#define COLOR_SPACE_BLACK       RGB(8, 10, 18)
#define COLOR_SPACE_DEEP        RGB(12, 15, 28)
#define COLOR_SPACE_DARK        RGB(18, 22, 38)
#define COLOR_SPACE_MID         RGB(25, 30, 52)

#define COLOR_PANEL_DARK        RGB(20, 25, 45)
#define COLOR_PANEL_MID         RGB(30, 38, 65)
#define COLOR_PANEL_LIGHT       RGB(45, 55, 85)
#define COLOR_PANEL_HIGHLIGHT   RGB(60, 72, 105)

#define COLOR_FRAME_OUTER       RGB(15, 18, 32)
#define COLOR_FRAME_INNER       RGB(50, 60, 90)
#define COLOR_FRAME_SHINE       RGB(80, 95, 140)

#define COLOR_NEON_CYAN         RGB(0, 255, 255)
#define COLOR_NEON_BLUE         RGB(30, 144, 255)
#define COLOR_NEON_PURPLE       RGB(180, 100, 255)
#define COLOR_NEON_PINK         RGB(255, 50, 150)
#define COLOR_NEON_GREEN        RGB(0, 255, 128)
#define COLOR_NEON_ORANGE       RGB(255, 150, 0)
#define COLOR_NEON_RED          RGB(255, 60, 80)
#define COLOR_NEON_YELLOW       RGB(255, 230, 0)

#define COLOR_TEXT_BRIGHT       RGB(255, 255, 255)
#define COLOR_TEXT_PRIMARY      RGB(200, 220, 255)
#define COLOR_TEXT_SECONDARY    RGB(130, 150, 190)
#define COLOR_TEXT_MUTED        RGB(80, 95, 130)
#define COLOR_TEXT_NEON         RGB(0, 230, 255)

#define COLOR_STATUS_ONLINE     RGB(0, 255, 150)
#define COLOR_STATUS_BUSY       RGB(255, 200, 0)
#define COLOR_STATUS_OFFLINE    RGB(255, 80, 100)

// ============================================================================
// UI MODES & STATES
// ============================================================================
typedef enum {
    UI_MODE_LOGIN,
    UI_MODE_DASHBOARD,
    UI_MODE_SETTINGS,
    UI_MODE_MODELS
} UIMode;

#define ID_BTN_LOGIN        1001
#define ID_BTN_LOGOUT       1002
#define ID_BTN_LANG_EN      1003
#define ID_BTN_LANG_TH      1004
#define ID_BTN_SETTINGS     1005
#define ID_EDIT_EMAIL       1006
#define ID_EDIT_PASSWORD    1007
#define ID_CHECK_REMEMBER   1008
#define ID_CHECK_AUTOSTART  1009
#define ID_SLIDER_PERF      1010
#define ID_SLIDER_FAN       1011
#define ID_CHECK_AUTO_FAN   1012
#define ID_BTN_BACK         1013
#define ID_EDIT_ELEC_RATE   1014
#define ID_BTN_MODELS       1015

// Model toggle button IDs (1100-1199 for up to 100 models)
#define ID_MODEL_TOGGLE_BASE 1100
#define MAX_MODELS           20

#define ID_TIMER_UPDATE     2001
#define ID_TIMER_ANIMATION  2002
#define ID_TIMER_ELEC       2003

#define PI 3.14159265358979323846

// ============================================================================
// EXTERNAL FUNCTIONS
// ============================================================================
extern bool GPU_Init(void);
extern void GPU_Shutdown(void);
extern bool GPU_GetInfo(GPUInfo* info);
extern bool GPU_GetAllInfo(GPUManager* manager);
extern int GPU_GetCount(void);
extern bool GPU_IsAvailable(void);
extern bool GPU_SetPerformanceLimit(int gpu_index, int limit_percent);
extern bool GPU_SetFanSpeed(int gpu_index, int speed_percent);
extern COLORREF GPU_GetTempColor(int temp);
extern COLORREF GPU_GetLoadColor(int load);
extern COLORREF GPU_GetFanColor(int speed);

extern void System_Init(void);
extern bool System_GetInfo(SystemInfo* info);
extern void System_FormatSpeed(float speed, char* buffer, size_t size);
extern void System_FormatUptime(uint64_t seconds, char* buffer, size_t size, bool is_thai);

// ============================================================================
// GLOBAL STATE
// ============================================================================
static UIMode g_ui_mode = UI_MODE_LOGIN;
static WorkerContext g_worker = {0};
static GPUManager g_gpu_manager = {0};
static SystemInfo g_system = {0};
static AppSettings* g_app_settings = NULL;

static HWND g_hwnd = NULL;
static HFONT g_font_title = NULL;
static HFONT g_font_large = NULL;
static HFONT g_font_normal = NULL;
static HFONT g_font_small = NULL;
static HFONT g_font_tech = NULL;

static HWND g_edit_email = NULL;
static HWND g_edit_password = NULL;
static HWND g_check_remember = NULL;

static int g_hover_button = 0;
static char g_login_error[256] = {0};
static bool g_logging_in = false;
static int g_anim_tick = 0;
static int g_selected_gpu = 0;

static int g_perf_slider_value = 100;
static int g_fan_slider_value = 50;
static bool g_auto_fan = true;
static bool g_dragging_perf = false;
static bool g_dragging_fan = false;
static bool g_remember_password = false;

// Electricity tracking
static float g_electricity_today_kwh = 0;
static float g_electricity_today_cost = 0;
static float g_electricity_month_kwh = 0;
static float g_electricity_month_cost = 0;
static uint64_t g_last_elec_record_time = 0;

// ============================================================================
// MODEL MANAGEMENT - Sci-Fi Toggle Switches
// ============================================================================
typedef struct {
    char model_id[64];
    char name[128];
    char category[32];
    int vram_required_mb;
    int size_mb;
    bool is_installed;
    bool is_enabled;      // Toggle state for accepting work
    bool is_downloading;
    int download_progress;
} ModelInfo;

static ModelInfo g_models[MAX_MODELS] = {0};
static int g_model_count = 0;
static int g_model_scroll_offset = 0;
static int g_model_hover = -1;

// Particle system
#define MAX_STARS 80
typedef struct {
    float x, y, z;
    float speed;
    int brightness;
} Star;
static Star g_stars[MAX_STARS];
static int g_scan_line_y = 0;

// Command line
static bool g_start_minimized = false;

// Network history for graph
#define NET_HISTORY_SIZE 60
static float g_net_up_history[NET_HISTORY_SIZE] = {0};
static float g_net_down_history[NET_HISTORY_SIZE] = {0};
static int g_net_history_index = 0;
static int g_net_history_count = 0;

// GDI+ for logo
static ULONG_PTR g_gdiplusToken = 0;
static Image* g_logoImage = nullptr;
static bool g_logoLoaded = false;

// ============================================================================
// FORWARD DECLARATIONS
// ============================================================================
LRESULT CALLBACK WndProc(HWND, UINT, WPARAM, LPARAM);
LRESULT CALLBACK EditSubclassProc(HWND, UINT, WPARAM, LPARAM, UINT_PTR, DWORD_PTR);
void DrawLoginScreen(HDC hdc, RECT* rect);
void DrawDashboard(HDC hdc, RECT* rect);
void DrawSettingsScreen(HDC hdc, RECT* rect);
void DrawModelsScreen(HDC hdc, RECT* rect);
void DrawSciFiToggle(HDC hdc, int x, int y, int w, int h, bool enabled, bool hover);
void DrawNetworkGraph(HDC hdc, int x, int y, int w, int h);

// ============================================================================
// GDI+ LOGO FUNCTIONS
// ============================================================================
void InitGDIPlus() {
    GdiplusStartupInput gdiplusStartupInput;
    GdiplusStartup(&g_gdiplusToken, &gdiplusStartupInput, NULL);
}

void ShutdownGDIPlus() {
    if (g_logoImage) {
        delete g_logoImage;
        g_logoImage = nullptr;
    }
    if (g_gdiplusToken) {
        GdiplusShutdown(g_gdiplusToken);
        g_gdiplusToken = 0;
    }
}

void LoadLogoImage() {
    // Try to load logo from resources folder
    wchar_t exePath[MAX_PATH];
    GetModuleFileNameW(NULL, exePath, MAX_PATH);

    // Get directory of exe
    wchar_t* lastSlash = wcsrchr(exePath, L'\\');
    if (lastSlash) *lastSlash = L'\0';

    // Try different paths
    wchar_t logoPath[MAX_PATH];

    // Path 1: resources/Images/logo.jpeg (relative to exe)
    swprintf(logoPath, MAX_PATH, L"%s\\..\\..\\..\\resources\\Images\\logo.jpeg", exePath);
    g_logoImage = Image::FromFile(logoPath);
    if (g_logoImage && g_logoImage->GetLastStatus() == Ok) {
        g_logoLoaded = true;
        return;
    }
    if (g_logoImage) { delete g_logoImage; g_logoImage = nullptr; }

    // Path 2: Direct path in project
    g_logoImage = Image::FromFile(L"C:\\laragon\\www\\gpu-sharing-platform\\client-windows\\resources\\Images\\logo.jpeg");
    if (g_logoImage && g_logoImage->GetLastStatus() == Ok) {
        g_logoLoaded = true;
        return;
    }
    if (g_logoImage) { delete g_logoImage; g_logoImage = nullptr; }

    g_logoLoaded = false;
}

void DrawLogo(HDC hdc, int x, int y, int width, int height) {
    if (!g_logoLoaded || !g_logoImage) return;

    Graphics graphics(hdc);
    graphics.SetInterpolationMode(InterpolationModeHighQualityBicubic);
    graphics.SetSmoothingMode(SmoothingModeAntiAlias);

    // Draw with transparency
    graphics.DrawImage(g_logoImage, x, y, width, height);
}

// ============================================================================
// INITIALIZATION
// ============================================================================
void App_Init(void) {
    // Initialize GDI+ first
    InitGDIPlus();
    LoadLogoImage();

    // Initialize settings first
    Settings_Init();
    g_app_settings = Settings_Get();

    // Initialize logger
    Logger_Init();
    LOG_INFO(LOG_CAT_APP, "Application initializing...");

    // Set language from settings
    SetLanguage((Language)g_app_settings->language);

    GPU_Init();
    System_Init();
    Worker_Init(&g_worker);
    API_Init();
    GPU_GetAllInfo(&g_gpu_manager);

    // Load saved credentials if remember is enabled
    if (g_app_settings->remember_password && Credentials_HasSaved()) {
        char email[256] = {0}, password[256] = {0};
        if (Credentials_Load(email, sizeof(email), password, sizeof(password))) {
            g_remember_password = true;
            LOG_INFO(LOG_CAT_AUTH, "Loaded saved credentials for: %s", email);
        }
    }

    // Initialize 3D star field
    for (int i = 0; i < MAX_STARS; i++) {
        g_stars[i].x = (float)(rand() % WINDOW_WIDTH);
        g_stars[i].y = (float)(rand() % WINDOW_HEIGHT);
        g_stars[i].z = (float)(rand() % 100) / 100.0f;
        g_stars[i].speed = 0.2f + (float)(rand() % 100) / 200.0f;
        g_stars[i].brightness = 50 + rand() % 150;
    }

    // Load electricity data
    g_electricity_today_cost = Electricity_GetTodayCost();
    g_electricity_month_cost = Electricity_GetMonthCost();

    LOG_INFO(LOG_CAT_APP, "Detected %d GPU(s)", g_gpu_manager.gpu_count);
}

void App_Cleanup(void) {
    LOG_INFO(LOG_CAT_APP, "Application shutting down...");

    Worker_Cleanup(&g_worker);
    API_Cleanup();
    GPU_Shutdown();

    // Save settings
    Settings_Cleanup();
    Logger_Cleanup();

    // Shutdown GDI+
    ShutdownGDIPlus();

    if (g_font_title) DeleteObject(g_font_title);
    if (g_font_large) DeleteObject(g_font_large);
    if (g_font_normal) DeleteObject(g_font_normal);
    if (g_font_small) DeleteObject(g_font_small);
    if (g_font_tech) DeleteObject(g_font_tech);
}

void CreateFonts(void) {
    g_font_title = CreateFontW(42, 0, 0, 0, FW_BOLD, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_SWISS, L"Segoe UI");
    g_font_large = CreateFontW(28, 0, 0, 0, FW_SEMIBOLD, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_SWISS, L"Segoe UI");
    g_font_normal = CreateFontW(18, 0, 0, 0, FW_NORMAL, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_SWISS, L"Segoe UI");
    g_font_small = CreateFontW(14, 0, 0, 0, FW_NORMAL, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_SWISS, L"Segoe UI");
    g_font_tech = CreateFontW(15, 0, 0, 0, FW_BOLD, FALSE, FALSE, FALSE,
        DEFAULT_CHARSET, OUT_DEFAULT_PRECIS, CLIP_DEFAULT_PRECIS,
        CLEARTYPE_QUALITY, DEFAULT_PITCH | FF_MODERN, L"Consolas");
}

// ============================================================================
// DRAWING UTILITIES
// ============================================================================
void DrawSpaceBackground(HDC hdc, RECT* rect) {
    int height = rect->bottom - rect->top;
    int width = rect->right - rect->left;

    for (int y = 0; y < height; y++) {
        float ratio = (float)y / height;
        int r = (int)(GetRValue(COLOR_SPACE_BLACK) * (1 - ratio) + GetRValue(COLOR_SPACE_DEEP) * ratio);
        int g = (int)(GetGValue(COLOR_SPACE_BLACK) * (1 - ratio) + GetGValue(COLOR_SPACE_DEEP) * ratio);
        int b = (int)(GetBValue(COLOR_SPACE_BLACK) * (1 - ratio) + GetBValue(COLOR_SPACE_DEEP) * ratio);

        HPEN pen = CreatePen(PS_SOLID, 1, RGB(r, g, b));
        HPEN oldPen = (HPEN)SelectObject(hdc, pen);
        MoveToEx(hdc, 0, y, NULL);
        LineTo(hdc, width, y);
        SelectObject(hdc, oldPen);
        DeleteObject(pen);
    }
}

void DrawStarField(HDC hdc, int width, int height) {
    for (int i = 0; i < MAX_STARS; i++) {
        int size = (int)(1 + g_stars[i].z * 2);
        int bright = (int)(g_stars[i].brightness * (0.3f + g_stars[i].z * 0.7f));
        float twinkle = (float)sin(g_anim_tick * 0.1f + i) * 0.3f + 0.7f;
        bright = (int)(bright * twinkle);

        COLORREF color = RGB(bright, bright, (int)(bright * 1.2));
        HBRUSH brush = CreateSolidBrush(color);
        SelectObject(hdc, brush);
        SelectObject(hdc, GetStockObject(NULL_PEN));
        Ellipse(hdc, (int)g_stars[i].x, (int)g_stars[i].y,
                (int)g_stars[i].x + size, (int)g_stars[i].y + size);
        DeleteObject(brush);
    }
}

void UpdateStars(int width, int height) {
    for (int i = 0; i < MAX_STARS; i++) {
        g_stars[i].y += g_stars[i].speed * (0.5f + g_stars[i].z);
        if (g_stars[i].y > height) {
            g_stars[i].y = -5;
            g_stars[i].x = (float)(rand() % width);
            g_stars[i].z = (float)(rand() % 100) / 100.0f;
        }
    }
}

void Draw3DPanel(HDC hdc, int x, int y, int w, int h, bool raised, bool glow, COLORREF glowColor) {
    HBRUSH frameBrush = CreateSolidBrush(COLOR_FRAME_OUTER);
    RECT frameRect = {x - 2, y - 2, x + w + 2, y + h + 2};
    FillRect(hdc, &frameRect, frameBrush);
    DeleteObject(frameBrush);

    for (int i = 0; i < h; i++) {
        float ratio = (float)i / h;
        COLORREF c1 = raised ? COLOR_PANEL_LIGHT : COLOR_PANEL_DARK;
        COLORREF c2 = raised ? COLOR_PANEL_MID : COLOR_PANEL_DARK;

        int r = (int)(GetRValue(c1) * (1 - ratio) + GetRValue(c2) * ratio);
        int g = (int)(GetGValue(c1) * (1 - ratio) + GetGValue(c2) * ratio);
        int b = (int)(GetBValue(c1) * (1 - ratio) + GetBValue(c2) * ratio);

        HPEN pen = CreatePen(PS_SOLID, 1, RGB(r, g, b));
        SelectObject(hdc, pen);
        MoveToEx(hdc, x, y + i, NULL);
        LineTo(hdc, x + w, y + i);
        DeleteObject(pen);
    }

    HPEN topPen = CreatePen(PS_SOLID, 1, COLOR_FRAME_SHINE);
    SelectObject(hdc, topPen);
    MoveToEx(hdc, x, y, NULL);
    LineTo(hdc, x + w, y);
    DeleteObject(topPen);

    HPEN leftPen = CreatePen(PS_SOLID, 1, COLOR_FRAME_INNER);
    SelectObject(hdc, leftPen);
    MoveToEx(hdc, x, y, NULL);
    LineTo(hdc, x, y + h);
    DeleteObject(leftPen);

    if (glow) {
        for (int i = 6; i > 0; i--) {
            int alpha = (6 - i) * 8;
            COLORREF gc = RGB(
                (GetRValue(glowColor) * alpha) / 100,
                (GetGValue(glowColor) * alpha) / 100,
                (GetBValue(glowColor) * alpha) / 100
            );
            HPEN glowPen = CreatePen(PS_SOLID, 1, gc);
            SelectObject(hdc, glowPen);
            SelectObject(hdc, GetStockObject(NULL_BRUSH));
            Rectangle(hdc, x - i, y - i, x + w + i, y + h + i);
            DeleteObject(glowPen);
        }
    }

    HPEN accentPen = CreatePen(PS_SOLID, 2, glow ? glowColor : COLOR_NEON_CYAN);
    SelectObject(hdc, accentPen);
    MoveToEx(hdc, x, y + 8, NULL);
    LineTo(hdc, x, y);
    LineTo(hdc, x + 8, y);
    MoveToEx(hdc, x + w - 8, y, NULL);
    LineTo(hdc, x + w, y);
    LineTo(hdc, x + w, y + 8);
    MoveToEx(hdc, x, y + h - 8, NULL);
    LineTo(hdc, x, y + h);
    LineTo(hdc, x + 8, y + h);
    MoveToEx(hdc, x + w - 8, y + h, NULL);
    LineTo(hdc, x + w, y + h);
    LineTo(hdc, x + w, y + h - 8);
    DeleteObject(accentPen);
}

void DrawHexBorder(HDC hdc, int x, int y, int w, int h, COLORREF color) {
    int cut = 15;
    POINT points[8] = {
        {x + cut, y}, {x + w - cut, y}, {x + w, y + cut}, {x + w, y + h - cut},
        {x + w - cut, y + h}, {x + cut, y + h}, {x, y + h - cut}, {x, y + cut}
    };
    HPEN pen = CreatePen(PS_SOLID, 2, color);
    SelectObject(hdc, pen);
    SelectObject(hdc, GetStockObject(NULL_BRUSH));
    Polygon(hdc, points, 8);
    DeleteObject(pen);
}

void DrawText_(HDC hdc, const char* text, int x, int y, COLORREF color, HFONT font) {
    SetTextColor(hdc, color);
    SetBkMode(hdc, TRANSPARENT);
    if (font) SelectObject(hdc, font);
    wchar_t wtext[512];
    MultiByteToWideChar(CP_UTF8, 0, text, -1, wtext, 512);
    TextOutW(hdc, x, y, wtext, (int)wcslen(wtext));
}

void DrawTextCenter(HDC hdc, const char* text, int x, int y, int w, COLORREF color, HFONT font) {
    SetTextColor(hdc, color);
    SetBkMode(hdc, TRANSPARENT);
    if (font) SelectObject(hdc, font);
    wchar_t wtext[512];
    MultiByteToWideChar(CP_UTF8, 0, text, -1, wtext, 512);
    SIZE size;
    GetTextExtentPoint32W(hdc, wtext, (int)wcslen(wtext), &size);
    TextOutW(hdc, x + (w - size.cx) / 2, y, wtext, (int)wcslen(wtext));
}

void DrawSpaceButton(HDC hdc, int x, int y, int w, int h, const char* text,
                     COLORREF color, bool hover, bool isSmall) {
    int cut = isSmall ? 6 : 10;
    POINT points[6] = {
        {x + cut, y}, {x + w - cut, y}, {x + w, y + h/2},
        {x + w - cut, y + h}, {x + cut, y + h}, {x, y + h/2}
    };

    POINT shadowPoints[6];
    for (int i = 0; i < 6; i++) {
        shadowPoints[i].x = points[i].x + 2;
        shadowPoints[i].y = points[i].y + 2;
    }
    HBRUSH shadowBrush = CreateSolidBrush(RGB(0, 0, 0));
    SelectObject(hdc, shadowBrush);
    SelectObject(hdc, GetStockObject(NULL_PEN));
    Polygon(hdc, shadowPoints, 6);
    DeleteObject(shadowBrush);

    COLORREF btnColor = hover ?
        RGB(min(255, GetRValue(color) + 30), min(255, GetGValue(color) + 30), min(255, GetBValue(color) + 30))
        : color;
    HBRUSH btnBrush = CreateSolidBrush(btnColor);
    SelectObject(hdc, btnBrush);
    Polygon(hdc, points, 6);
    DeleteObject(btnBrush);

    HPEN hlPen = CreatePen(PS_SOLID, 1, RGB(
        min(255, GetRValue(color) + 60),
        min(255, GetGValue(color) + 60),
        min(255, GetBValue(color) + 60)
    ));
    SelectObject(hdc, hlPen);
    MoveToEx(hdc, points[0].x, points[0].y, NULL);
    LineTo(hdc, points[1].x, points[1].y);
    LineTo(hdc, points[2].x, points[2].y);
    DeleteObject(hlPen);

    HPEN borderPen = CreatePen(PS_SOLID, 1, RGB(
        min(255, GetRValue(color) + 40),
        min(255, GetGValue(color) + 40),
        min(255, GetBValue(color) + 40)
    ));
    SelectObject(hdc, borderPen);
    SelectObject(hdc, GetStockObject(NULL_BRUSH));
    Polygon(hdc, points, 6);
    DeleteObject(borderPen);

    SetTextColor(hdc, COLOR_TEXT_BRIGHT);
    SetBkMode(hdc, TRANSPARENT);
    SelectObject(hdc, isSmall ? g_font_small : g_font_normal);

    wchar_t wtext[64];
    MultiByteToWideChar(CP_UTF8, 0, text, -1, wtext, 64);
    SIZE size;
    GetTextExtentPoint32W(hdc, wtext, (int)wcslen(wtext), &size);
    TextOutW(hdc, x + (w - size.cx) / 2, y + (h - size.cy) / 2, wtext, (int)wcslen(wtext));
}

void DrawCheckbox(HDC hdc, int x, int y, int size, bool checked, bool hover, const char* label) {
    COLORREF borderColor = hover ? COLOR_NEON_CYAN : COLOR_FRAME_INNER;
    COLORREF bgColor = checked ? COLOR_NEON_CYAN : COLOR_SPACE_BLACK;

    HBRUSH bgBrush = CreateSolidBrush(bgColor);
    HPEN borderPen = CreatePen(PS_SOLID, 2, borderColor);
    SelectObject(hdc, bgBrush);
    SelectObject(hdc, borderPen);
    RoundRect(hdc, x, y, x + size, y + size, 4, 4);
    DeleteObject(bgBrush);
    DeleteObject(borderPen);

    if (checked) {
        HPEN checkPen = CreatePen(PS_SOLID, 2, COLOR_SPACE_BLACK);
        SelectObject(hdc, checkPen);
        MoveToEx(hdc, x + 3, y + size/2, NULL);
        LineTo(hdc, x + size/3, y + size - 4);
        LineTo(hdc, x + size - 3, y + 3);
        DeleteObject(checkPen);
    }

    if (label) {
        DrawText_(hdc, label, x + size + 8, y + (size - 11) / 2, COLOR_TEXT_SECONDARY, g_font_small);
    }
}

void DrawCircularGauge(HDC hdc, int cx, int cy, int radius, int value, int maxVal,
                       COLORREF bgColor, COLORREF fgColor, const char* label) {
    for (int i = radius + 3; i >= radius; i--) {
        int shade = (i - radius) * 10;
        HBRUSH brush = CreateSolidBrush(RGB(shade, shade + 2, shade + 5));
        SelectObject(hdc, brush);
        SelectObject(hdc, GetStockObject(NULL_PEN));
        Ellipse(hdc, cx - i, cy - i, cx + i, cy + i);
        DeleteObject(brush);
    }

    HBRUSH bgBrush = CreateSolidBrush(bgColor);
    SelectObject(hdc, bgBrush);
    Ellipse(hdc, cx - radius, cy - radius, cx + radius, cy + radius);
    DeleteObject(bgBrush);

    float angle = (float)value / maxVal * 270.0f - 135.0f;
    float startAngle = -135.0f;
    float a, radian1, radian2;
    int x1, y1, x2, y2;

    HPEN arcPen = CreatePen(PS_SOLID, 4, fgColor);
    SelectObject(hdc, arcPen);

    for (a = startAngle; a <= angle; a += 3) {
        radian1 = a * PI / 180.0f;
        radian2 = (a + 3) * PI / 180.0f;
        x1 = cx + (int)((radius - 8) * cos(radian1));
        y1 = cy + (int)((radius - 8) * sin(radian1));
        x2 = cx + (int)((radius - 8) * cos(radian2));
        y2 = cy + (int)((radius - 8) * sin(radian2));
        MoveToEx(hdc, x1, y1, NULL);
        LineTo(hdc, x2, y2);
    }
    DeleteObject(arcPen);

    char valStr[16];
    snprintf(valStr, sizeof(valStr), "%d", value);
    SetTextColor(hdc, COLOR_TEXT_BRIGHT);
    SetBkMode(hdc, TRANSPARENT);
    SelectObject(hdc, g_font_large);

    wchar_t wtext[16];
    MultiByteToWideChar(CP_UTF8, 0, valStr, -1, wtext, 16);
    SIZE size;
    GetTextExtentPoint32W(hdc, wtext, (int)wcslen(wtext), &size);
    TextOutW(hdc, cx - size.cx / 2, cy - size.cy / 2 - 5, wtext, (int)wcslen(wtext));

    if (label) {
        SetTextColor(hdc, COLOR_TEXT_SECONDARY);
        SelectObject(hdc, g_font_small);
        MultiByteToWideChar(CP_UTF8, 0, label, -1, wtext, 64);
        GetTextExtentPoint32W(hdc, wtext, (int)wcslen(wtext), &size);
        TextOutW(hdc, cx - size.cx / 2, cy + 12, wtext, (int)wcslen(wtext));
    }
}

void DrawNeonSlider(HDC hdc, int x, int y, int w, int h, int value, int minVal, int maxVal,
                    COLORREF neonColor, bool isDragging, const char* label) {
    int trackH = 8;
    int trackY = y + (h - trackH) / 2;

    HPEN framePen = CreatePen(PS_SOLID, 1, COLOR_FRAME_OUTER);
    SelectObject(hdc, framePen);
    SelectObject(hdc, GetStockObject(NULL_BRUSH));
    RoundRect(hdc, x - 1, trackY - 1, x + w + 1, trackY + trackH + 1, 4, 4);
    DeleteObject(framePen);

    HBRUSH trackBrush = CreateSolidBrush(COLOR_SPACE_BLACK);
    SelectObject(hdc, trackBrush);
    SelectObject(hdc, GetStockObject(NULL_PEN));
    RoundRect(hdc, x, trackY, x + w, trackY + trackH, 4, 4);
    DeleteObject(trackBrush);

    int fillW = (w - 4) * (value - minVal) / (maxVal - minVal);
    if (fillW > 0) {
        for (int i = 4; i > 0; i--) {
            int alpha = (4 - i) * 15;
            COLORREF gc = RGB(
                (GetRValue(neonColor) * alpha) / 100,
                (GetGValue(neonColor) * alpha) / 100,
                (GetBValue(neonColor) * alpha) / 100
            );
            HBRUSH glowBrush = CreateSolidBrush(gc);
            SelectObject(hdc, glowBrush);
            RoundRect(hdc, x + 2 - i, trackY + 2 - i, x + 2 + fillW + i, trackY + trackH - 2 + i, 4, 4);
            DeleteObject(glowBrush);
        }

        HBRUSH fillBrush = CreateSolidBrush(neonColor);
        SelectObject(hdc, fillBrush);
        RoundRect(hdc, x + 2, trackY + 2, x + 2 + fillW, trackY + trackH - 2, 2, 2);
        DeleteObject(fillBrush);
    }

    int thumbW = isDragging ? 16 : 14;
    int thumbH = isDragging ? 22 : 20;
    int thumbX = x + fillW - thumbW / 2 + 2;
    int thumbY = y + h / 2 - thumbH / 2;

    if (isDragging) {
        for (int i = 10; i > 0; i -= 2) {
            int alpha = (10 - i) * 8;
            COLORREF gc = RGB(
                (GetRValue(neonColor) * alpha) / 100,
                (GetGValue(neonColor) * alpha) / 100,
                (GetBValue(neonColor) * alpha) / 100
            );
            HBRUSH glowBrush = CreateSolidBrush(gc);
            SelectObject(hdc, glowBrush);
            RoundRect(hdc, thumbX - i, thumbY - i, thumbX + thumbW + i, thumbY + thumbH + i, 6, 6);
            DeleteObject(glowBrush);
        }
    }

    for (int i = 0; i < thumbW; i++) {
        float ratio = (float)i / thumbW;
        int shade = (int)(80 + 60 * sin(ratio * PI));
        HPEN linePen = CreatePen(PS_SOLID, 1, RGB(shade, shade + 10, shade + 20));
        SelectObject(hdc, linePen);
        MoveToEx(hdc, thumbX + i, thumbY + 2, NULL);
        LineTo(hdc, thumbX + i, thumbY + thumbH - 2);
        DeleteObject(linePen);
    }

    HPEN thumbPen = CreatePen(PS_SOLID, 1, neonColor);
    SelectObject(hdc, thumbPen);
    SelectObject(hdc, GetStockObject(NULL_BRUSH));
    RoundRect(hdc, thumbX, thumbY, thumbX + thumbW, thumbY + thumbH, 4, 4);
    DeleteObject(thumbPen);

    HPEN centerPen = CreatePen(PS_SOLID, 2, neonColor);
    SelectObject(hdc, centerPen);
    MoveToEx(hdc, thumbX + thumbW / 2, thumbY + 4, NULL);
    LineTo(hdc, thumbX + thumbW / 2, thumbY + thumbH - 4);
    DeleteObject(centerPen);

    if (label) {
        SetTextColor(hdc, COLOR_TEXT_SECONDARY);
        SetBkMode(hdc, TRANSPARENT);
        SelectObject(hdc, g_font_small);
        wchar_t wtext[64];
        MultiByteToWideChar(CP_UTF8, 0, label, -1, wtext, 64);
        TextOutW(hdc, x, y - 2, wtext, (int)wcslen(wtext));
    }
}

void DrawHoloGraph(HDC hdc, int x, int y, int w, int h, GPUInfo* gpu, COLORREF lineColor) {
    Draw3DPanel(hdc, x, y, w, h, false, false, 0);

    int padding = 5;
    int titleH = 16;   // พื้นที่สำหรับ title
    int labelW = 25;   // พื้นที่สำหรับ Y-axis labels (ลดลง)
    int graphX = x + padding + labelW;
    int graphY = y + padding + titleH;
    int graphW = w - padding * 2 - labelW - 2;
    int graphH = h - padding * 2 - titleH - 2;

    // Find min/max temperature from history for auto-scaling
    int minTemp = 1000, maxTemp = 0;
    if (gpu->temp_history_count > 0) {
        int startIdx = (gpu->temp_history_index - gpu->temp_history_count + TEMP_HISTORY_SIZE) % TEMP_HISTORY_SIZE;
        for (int i = 0; i < gpu->temp_history_count; i++) {
            int idx = (startIdx + i) % TEMP_HISTORY_SIZE;
            int temp = gpu->temp_history[idx];
            if (temp > 0) {
                if (temp < minTemp) minTemp = temp;
                if (temp > maxTemp) maxTemp = temp;
            }
        }
    }

    // Also consider current temp
    if (gpu->temperature > 0) {
        if (gpu->temperature < minTemp) minTemp = gpu->temperature;
        if (gpu->temperature > maxTemp) maxTemp = gpu->temperature;
    }

    // Default range if no data
    if (minTemp > maxTemp || minTemp == 1000) {
        minTemp = 30;
        maxTemp = 80;
    }

    // Add padding and round to nice numbers
    int range = maxTemp - minTemp;
    if (range < 10) range = 10;
    int padding_val = range / 5;
    if (padding_val < 2) padding_val = 2;

    minTemp = ((minTemp - padding_val) / 10) * 10;
    maxTemp = ((maxTemp + padding_val + 9) / 10) * 10;
    if (minTemp < 0) minTemp = 0;
    if (maxTemp < minTemp + 20) maxTemp = minTemp + 20;
    range = maxTemp - minTemp;

    // Draw title on left, current temp on right (same line)
    char tempStr[16];
    snprintf(tempStr, sizeof(tempStr), "%d C", gpu->temperature);
    DrawText_(hdc, "TEMP", x + padding, y + 2, COLOR_TEXT_SECONDARY, g_font_small);
    SetTextColor(hdc, GPU_GetTempColor(gpu->temperature));
    SetBkMode(hdc, TRANSPARENT);
    SelectObject(hdc, g_font_tech);
    wchar_t wtempStr[16];
    MultiByteToWideChar(CP_UTF8, 0, tempStr, -1, wtempStr, 16);
    TextOutW(hdc, x + w - 40, y + 2, wtempStr, (int)wcslen(wtempStr));

    // Draw only 2 grid lines to avoid crowding (top, bottom)
    for (int i = 0; i <= 2; i++) {
        int lineY = graphY + (graphH * i / 2);
        HPEN gridPen = CreatePen(PS_DOT, 1, RGB(40, 50, 70));
        SelectObject(hdc, gridPen);
        MoveToEx(hdc, graphX, lineY, NULL);
        LineTo(hdc, graphX + graphW, lineY);
        DeleteObject(gridPen);

        // Draw scale label (ใช้ font เล็ก)
        int tempVal = maxTemp - (range * i / 2);
        char label[8];
        snprintf(label, sizeof(label), "%d", tempVal);
        SetTextColor(hdc, COLOR_TEXT_MUTED);
        SetBkMode(hdc, TRANSPARENT);
        SelectObject(hdc, g_font_small);
        wchar_t wlabel[8];
        MultiByteToWideChar(CP_UTF8, 0, label, -1, wlabel, 8);
        TextOutW(hdc, x + 2, lineY - 5, wlabel, (int)wcslen(wlabel));
    }

    if (gpu->temp_history_count > 1) {
        // Draw glow effect
        for (int glow = 3; glow > 0; glow--) {
            int alpha = (3 - glow) * 20;
            COLORREF gc = RGB(
                (GetRValue(lineColor) * alpha) / 100,
                (GetGValue(lineColor) * alpha) / 100,
                (GetBValue(lineColor) * alpha) / 100
            );
            HPEN glowPen = CreatePen(PS_SOLID, 3 + glow * 2, gc);
            SelectObject(hdc, glowPen);

            int startIdx = (gpu->temp_history_index - gpu->temp_history_count + TEMP_HISTORY_SIZE) % TEMP_HISTORY_SIZE;

            for (int i = 0; i < gpu->temp_history_count - 1; i++) {
                int idx1 = (startIdx + i) % TEMP_HISTORY_SIZE;
                int idx2 = (startIdx + i + 1) % TEMP_HISTORY_SIZE;

                int temp1 = gpu->temp_history[idx1];
                int temp2 = gpu->temp_history[idx2];

                // Auto-scale calculation
                int y1 = graphY + graphH - ((temp1 - minTemp) * graphH / range);
                int y2 = graphY + graphH - ((temp2 - minTemp) * graphH / range);
                y1 = max(graphY, min(graphY + graphH, y1));
                y2 = max(graphY, min(graphY + graphH, y2));

                int x1 = graphX + (i * graphW / (TEMP_HISTORY_SIZE - 1));
                int x2 = graphX + ((i + 1) * graphW / (TEMP_HISTORY_SIZE - 1));

                MoveToEx(hdc, x1, y1, NULL);
                LineTo(hdc, x2, y2);
            }
            DeleteObject(glowPen);
        }

        // Draw main line
        HPEN linePen = CreatePen(PS_SOLID, 2, lineColor);
        SelectObject(hdc, linePen);

        int startIdx = (gpu->temp_history_index - gpu->temp_history_count + TEMP_HISTORY_SIZE) % TEMP_HISTORY_SIZE;

        for (int i = 0; i < gpu->temp_history_count - 1; i++) {
            int idx1 = (startIdx + i) % TEMP_HISTORY_SIZE;
            int idx2 = (startIdx + i + 1) % TEMP_HISTORY_SIZE;

            int temp1 = gpu->temp_history[idx1];
            int temp2 = gpu->temp_history[idx2];

            // Auto-scale calculation
            int y1 = graphY + graphH - ((temp1 - minTemp) * graphH / range);
            int y2 = graphY + graphH - ((temp2 - minTemp) * graphH / range);
            y1 = max(graphY, min(graphY + graphH, y1));
            y2 = max(graphY, min(graphY + graphH, y2));

            int x1 = graphX + (i * graphW / (TEMP_HISTORY_SIZE - 1));
            int x2 = graphX + ((i + 1) * graphW / (TEMP_HISTORY_SIZE - 1));

            MoveToEx(hdc, x1, y1, NULL);
            LineTo(hdc, x2, y2);
        }
        DeleteObject(linePen);
    }
}

void DrawSpaceshipTab(HDC hdc, int x, int y, int w, int h, const char* label,
                      bool selected, bool hover, COLORREF accentColor) {
    if (selected) {
        Draw3DPanel(hdc, x, y, w, h, true, true, accentColor);
    } else {
        HBRUSH bgBrush = CreateSolidBrush(hover ? COLOR_PANEL_MID : COLOR_PANEL_DARK);
        SelectObject(hdc, bgBrush);
        SelectObject(hdc, GetStockObject(NULL_PEN));
        RoundRect(hdc, x, y, x + w, y + h, 6, 6);
        DeleteObject(bgBrush);

        HPEN borderPen = CreatePen(PS_SOLID, 1, hover ? COLOR_FRAME_SHINE : COLOR_FRAME_INNER);
        SelectObject(hdc, borderPen);
        SelectObject(hdc, GetStockObject(NULL_BRUSH));
        RoundRect(hdc, x, y, x + w, y + h, 6, 6);
        DeleteObject(borderPen);
    }

    SetTextColor(hdc, selected ? COLOR_TEXT_BRIGHT : COLOR_TEXT_SECONDARY);
    SetBkMode(hdc, TRANSPARENT);
    SelectObject(hdc, g_font_small);

    wchar_t wtext[32];
    MultiByteToWideChar(CP_UTF8, 0, label, -1, wtext, 32);
    SIZE size;
    GetTextExtentPoint32W(hdc, wtext, (int)wcslen(wtext), &size);
    TextOutW(hdc, x + (w - size.cx) / 2, y + (h - size.cy) / 2, wtext, (int)wcslen(wtext));

    if (selected) {
        HBRUSH indBrush = CreateSolidBrush(accentColor);
        RECT indRect = {x + w / 2 - 15, y + h - 3, x + w / 2 + 15, y + h};
        FillRect(hdc, &indRect, indBrush);
        DeleteObject(indBrush);
    }
}

void DrawStatusIndicator(HDC hdc, int x, int y, int size, COLORREF color, bool pulse) {
    if (pulse) {
        int glowSize = size + (int)(4 * sin(g_anim_tick * 0.15f) + 4);
        for (int i = glowSize; i >= size; i -= 2) {
            int alpha = (glowSize - i) * 15;
            COLORREF gc = RGB(
                (GetRValue(color) * alpha) / 100,
                (GetGValue(color) * alpha) / 100,
                (GetBValue(color) * alpha) / 100
            );
            HBRUSH brush = CreateSolidBrush(gc);
            SelectObject(hdc, brush);
            SelectObject(hdc, GetStockObject(NULL_PEN));
            Ellipse(hdc, x - i/2 + size/2, y - i/2 + size/2,
                    x + i/2 + size/2, y + i/2 + size/2);
            DeleteObject(brush);
        }
    }

    HBRUSH brush = CreateSolidBrush(color);
    SelectObject(hdc, brush);
    SelectObject(hdc, GetStockObject(NULL_PEN));
    Ellipse(hdc, x, y, x + size, y + size);
    DeleteObject(brush);

    HBRUSH hlBrush = CreateSolidBrush(RGB(
        min(255, GetRValue(color) + 80),
        min(255, GetGValue(color) + 80),
        min(255, GetBValue(color) + 80)
    ));
    SelectObject(hdc, hlBrush);
    Ellipse(hdc, x + 2, y + 2, x + size/3, y + size/3);
    DeleteObject(hlBrush);
}

void DrawHoloStat(HDC hdc, int x, int y, int w, int h, const char* title,
                  const char* value, const char* unit, COLORREF accent) {
    Draw3DPanel(hdc, x, y, w, h, false, false, 0);

    HBRUSH accentBrush = CreateSolidBrush(accent);
    RECT accentRect = {x + 4, y + 4, x + 7, y + h - 4};
    FillRect(hdc, &accentRect, accentBrush);
    DeleteObject(accentBrush);

    for (int i = 3; i > 0; i--) {
        int alpha = (3 - i) * 10;
        COLORREF gc = RGB(
            (GetRValue(accent) * alpha) / 100,
            (GetGValue(accent) * alpha) / 100,
            (GetBValue(accent) * alpha) / 100
        );
        HBRUSH glowBrush = CreateSolidBrush(gc);
        RECT glowRect = {x + 7, y + 4, x + 7 + i * 8, y + h - 4};
        FillRect(hdc, &glowRect, glowBrush);
        DeleteObject(glowBrush);
    }

    SetBkMode(hdc, TRANSPARENT);

    SetTextColor(hdc, COLOR_TEXT_SECONDARY);
    SelectObject(hdc, g_font_small);
    wchar_t wtext[64];
    MultiByteToWideChar(CP_UTF8, 0, title, -1, wtext, 64);
    TextOutW(hdc, x + 14, y + 6, wtext, (int)wcslen(wtext));

    SetTextColor(hdc, COLOR_TEXT_BRIGHT);
    SelectObject(hdc, g_font_large);
    MultiByteToWideChar(CP_UTF8, 0, value, -1, wtext, 64);
    TextOutW(hdc, x + 14, y + 22, wtext, (int)wcslen(wtext));

    if (unit && strlen(unit) > 0) {
        SetTextColor(hdc, COLOR_TEXT_MUTED);
        SelectObject(hdc, g_font_small);
        MultiByteToWideChar(CP_UTF8, 0, unit, -1, wtext, 64);
        TextOutW(hdc, x + 14, y + 46, wtext, (int)wcslen(wtext));
    }
}

void DrawSpaceProgress(HDC hdc, int x, int y, int w, int h, int value, int maxVal, COLORREF color) {
    HPEN framePen = CreatePen(PS_SOLID, 1, COLOR_FRAME_OUTER);
    SelectObject(hdc, framePen);
    SelectObject(hdc, GetStockObject(NULL_BRUSH));
    RoundRect(hdc, x - 1, y - 1, x + w + 1, y + h + 1, h/2, h/2);
    DeleteObject(framePen);

    HBRUSH bgBrush = CreateSolidBrush(COLOR_SPACE_BLACK);
    SelectObject(hdc, bgBrush);
    SelectObject(hdc, GetStockObject(NULL_PEN));
    RoundRect(hdc, x, y, x + w, y + h, h/2, h/2);
    DeleteObject(bgBrush);

    if (value > 0 && maxVal > 0) {
        int fillW = (w - 4) * value / maxVal;
        if (fillW > 0) {
            for (int i = 3; i > 0; i--) {
                int alpha = (3 - i) * 20;
                COLORREF gc = RGB(
                    (GetRValue(color) * alpha) / 100,
                    (GetGValue(color) * alpha) / 100,
                    (GetBValue(color) * alpha) / 100
                );
                HBRUSH glowBrush = CreateSolidBrush(gc);
                SelectObject(hdc, glowBrush);
                RoundRect(hdc, x + 2 - i, y + 2 - i, x + 2 + fillW + i, y + h - 2 + i, (h-4)/2, (h-4)/2);
                DeleteObject(glowBrush);
            }

            HBRUSH fillBrush = CreateSolidBrush(color);
            SelectObject(hdc, fillBrush);
            RoundRect(hdc, x + 2, y + 2, x + 2 + fillW, y + h - 2, (h-4)/2, (h-4)/2);
            DeleteObject(fillBrush);
        }
    }
}

// ============================================================================
// LOGIN CONTROLS - Modern Split Layout
// ============================================================================
void CreateLoginControls(HWND parent) {
    RECT rect;
    GetClientRect(parent, &rect);
    int width = rect.right;
    int height = rect.bottom;
    int splitX = width / 2;

    // Glass card on right side (matching DrawLoginScreen layout)
    int cardW = 320;
    int cardH = 340;
    int cardX = splitX + (splitX - cardW) / 2;
    int cardY = (height - cardH) / 2;

    // Input positions (matching DrawModernInput positions)
    int inputX = cardX + 25 + 38;  // +38 for icon area offset
    int inputY = cardY + 95;
    int inputW = cardW - 50 - 40;  // Subtract icon area
    int inputH = 38;
    int passY = inputY + 42 + 35;  // 42 = inputH

    g_edit_email = CreateWindowExW(0, L"EDIT", L"",
        WS_CHILD | WS_VISIBLE | ES_AUTOHSCROLL,
        inputX, inputY + 2, inputW, inputH,
        parent, (HMENU)ID_EDIT_EMAIL, GetModuleHandle(NULL), NULL);
    SendMessage(g_edit_email, WM_SETFONT, (WPARAM)g_font_normal, TRUE);
    SetWindowSubclass(g_edit_email, EditSubclassProc, 0, 0);

    g_edit_password = CreateWindowExW(0, L"EDIT", L"",
        WS_CHILD | WS_VISIBLE | ES_PASSWORD | ES_AUTOHSCROLL,
        inputX, passY + 2, inputW, inputH,
        parent, (HMENU)ID_EDIT_PASSWORD, GetModuleHandle(NULL), NULL);
    SendMessage(g_edit_password, WM_SETFONT, (WPARAM)g_font_normal, TRUE);
    SetWindowSubclass(g_edit_password, EditSubclassProc, 0, 0);

    // Load saved credentials
    if (g_app_settings->remember_password && Credentials_HasSaved()) {
        char email[256] = {0}, password[256] = {0};
        if (Credentials_Load(email, sizeof(email), password, sizeof(password))) {
            wchar_t wemail[256], wpassword[256];
            MultiByteToWideChar(CP_UTF8, 0, email, -1, wemail, 256);
            MultiByteToWideChar(CP_UTF8, 0, password, -1, wpassword, 256);
            SetWindowTextW(g_edit_email, wemail);
            SetWindowTextW(g_edit_password, wpassword);
            g_remember_password = true;
        }
    }
}

void RepositionLoginControls(void) {
    if (!g_hwnd) return;
    RECT rect;
    GetClientRect(g_hwnd, &rect);
    int width = rect.right;
    int height = rect.bottom;
    int splitX = width / 2;

    // Glass card on right side
    int cardW = 320;
    int cardH = 340;
    int cardX = splitX + (splitX - cardW) / 2;
    int cardY = (height - cardH) / 2;

    // Input positions
    int inputX = cardX + 25 + 38;
    int inputY = cardY + 95;
    int inputW = cardW - 50 - 40;
    int inputH = 38;
    int passY = inputY + 42 + 35;

    if (g_edit_email) SetWindowPos(g_edit_email, NULL, inputX, inputY + 2, inputW, inputH, SWP_NOZORDER);
    if (g_edit_password) SetWindowPos(g_edit_password, NULL, inputX, passY + 2, inputW, inputH, SWP_NOZORDER);
}

void HideLoginControls(void) {
    if (g_edit_email) ShowWindow(g_edit_email, SW_HIDE);
    if (g_edit_password) ShowWindow(g_edit_password, SW_HIDE);
}

void ShowLoginControls(void) {
    if (g_edit_email) ShowWindow(g_edit_email, SW_SHOW);
    if (g_edit_password) ShowWindow(g_edit_password, SW_SHOW);
}

LRESULT CALLBACK EditSubclassProc(HWND hwnd, UINT msg, WPARAM wParam, LPARAM lParam,
                                   UINT_PTR uIdSubclass, DWORD_PTR dwRefData) {
    switch (msg) {
        case WM_NCPAINT:
        case WM_PAINT: {
            LRESULT result = DefSubclassProc(hwnd, msg, wParam, lParam);
            HDC hdc = GetWindowDC(hwnd);
            RECT rect;
            GetWindowRect(hwnd, &rect);
            rect.right -= rect.left; rect.bottom -= rect.top;
            rect.left = 0; rect.top = 0;

            COLORREF borderColor = GetFocus() == hwnd ? COLOR_NEON_CYAN : COLOR_FRAME_INNER;
            HPEN pen = CreatePen(PS_SOLID, 2, borderColor);
            SelectObject(hdc, pen);
            SelectObject(hdc, GetStockObject(NULL_BRUSH));
            RoundRect(hdc, rect.left, rect.top, rect.right, rect.bottom, 8, 8);
            DeleteObject(pen);

            if (GetFocus() == hwnd) {
                HPEN accentPen = CreatePen(PS_SOLID, 2, COLOR_NEON_CYAN);
                SelectObject(hdc, accentPen);
                MoveToEx(hdc, 0, 6, NULL);
                LineTo(hdc, 0, 0);
                LineTo(hdc, 6, 0);
                MoveToEx(hdc, rect.right - 6, 0, NULL);
                LineTo(hdc, rect.right, 0);
                LineTo(hdc, rect.right, 6);
                DeleteObject(accentPen);
            }

            ReleaseDC(hwnd, hdc);
            return result;
        }
    }
    return DefSubclassProc(hwnd, msg, wParam, lParam);
}

// ============================================================================
// LOGIN HANDLER
// ============================================================================
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

    LOG_INFO(LOG_CAT_AUTH, "Attempting login for: %s", email);

    if (Worker_Start(&g_worker, email, password)) {
        LOG_INFO(LOG_CAT_AUTH, "Login successful for: %s", email);

        // Save credentials if remember is enabled
        if (g_remember_password) {
            Credentials_Save(email, password, true);
            LOG_INFO(LOG_CAT_AUTH, "Credentials saved");
        }

        // Load demo models for display
        g_model_count = 0;

        // Demo installed models
        strcpy(g_models[g_model_count].model_id, "sd15-base");
        strcpy(g_models[g_model_count].name, "Stable Diffusion 1.5");
        strcpy(g_models[g_model_count].category, "stable-diffusion");
        g_models[g_model_count].vram_required_mb = 4096;
        g_models[g_model_count].size_mb = 4200;
        g_models[g_model_count].is_installed = true;
        g_models[g_model_count].is_enabled = true;
        g_model_count++;

        strcpy(g_models[g_model_count].model_id, "sdxl-base");
        strcpy(g_models[g_model_count].name, "SDXL Base 1.0");
        strcpy(g_models[g_model_count].category, "stable-diffusion");
        g_models[g_model_count].vram_required_mb = 8192;
        g_models[g_model_count].size_mb = 6800;
        g_models[g_model_count].is_installed = true;
        g_models[g_model_count].is_enabled = false;
        g_model_count++;

        strcpy(g_models[g_model_count].model_id, "flux-schnell");
        strcpy(g_models[g_model_count].name, "FLUX.1 Schnell");
        strcpy(g_models[g_model_count].category, "flux");
        g_models[g_model_count].vram_required_mb = 12288;
        g_models[g_model_count].size_mb = 23000;
        g_models[g_model_count].is_installed = true;
        g_models[g_model_count].is_enabled = true;
        g_model_count++;

        // Demo available models (not installed)
        strcpy(g_models[g_model_count].model_id, "dreamshaper-8");
        strcpy(g_models[g_model_count].name, "DreamShaper 8");
        strcpy(g_models[g_model_count].category, "stable-diffusion");
        g_models[g_model_count].vram_required_mb = 4096;
        g_models[g_model_count].size_mb = 2100;
        g_models[g_model_count].is_installed = false;
        g_models[g_model_count].is_enabled = false;
        g_model_count++;

        strcpy(g_models[g_model_count].model_id, "realistic-vision");
        strcpy(g_models[g_model_count].name, "Realistic Vision V6");
        strcpy(g_models[g_model_count].category, "stable-diffusion");
        g_models[g_model_count].vram_required_mb = 4096;
        g_models[g_model_count].size_mb = 2000;
        g_models[g_model_count].is_installed = false;
        g_models[g_model_count].is_enabled = false;
        g_model_count++;

        LOG_INFO(LOG_CAT_SETTINGS, "Loaded %d models", g_model_count);

        g_ui_mode = UI_MODE_DASHBOARD;
        HideLoginControls();
    } else {
        LOG_ERROR(LOG_CAT_AUTH, "Login failed for: %s - %s", email, Worker_GetError(&g_worker));
        strcpy(g_login_error, Worker_GetError(&g_worker));
    }

    g_logging_in = false;
    InvalidateRect(g_hwnd, NULL, FALSE);
}

// ============================================================================
// STATUS HELPERS
// ============================================================================
COLORREF GetStatusColor(JobStatus status) {
    switch (status) {
        case JOB_IDLE: return COLOR_TEXT_MUTED;
        case JOB_RECEIVING: return COLOR_NEON_BLUE;
        case JOB_PROCESSING: return COLOR_NEON_GREEN;
        case JOB_UPLOADING: return COLOR_NEON_PURPLE;
        case JOB_COMPLETED: return COLOR_STATUS_ONLINE;
        case JOB_FAILED: return COLOR_STATUS_OFFLINE;
        default: return COLOR_TEXT_MUTED;
    }
}

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

// ============================================================================
// DRAW MODERN LOGIN SCREEN - Split Layout with Glassmorphism
// ============================================================================

// Draw circular orbit ring (decorative element)
void DrawOrbitRing(HDC hdc, int cx, int cy, int radius, float rotation, COLORREF color, int dotCount) {
    float baseAlpha = 0.3f + (float)sin(g_anim_tick * 0.05f) * 0.2f;

    for (int i = 0; i < dotCount; i++) {
        float angle = rotation + (i * 2.0f * (float)PI / dotCount);
        int x = cx + (int)(radius * cos(angle));
        int y = cy + (int)(radius * sin(angle));

        float dotAlpha = baseAlpha * (0.5f + 0.5f * (float)sin(angle * 2));
        COLORREF dotColor = RGB(
            (int)(GetRValue(color) * dotAlpha),
            (int)(GetGValue(color) * dotAlpha),
            (int)(GetBValue(color) * dotAlpha)
        );

        HBRUSH brush = CreateSolidBrush(dotColor);
        SelectObject(hdc, brush);
        SelectObject(hdc, GetStockObject(NULL_PEN));
        Ellipse(hdc, x - 2, y - 2, x + 3, y + 3);
        DeleteObject(brush);
    }
}

// Draw flowing wave line
void DrawFlowingWave(HDC hdc, int x, int y, int width, int amplitude, COLORREF color, float offset) {
    HPEN pen = CreatePen(PS_SOLID, 2, color);
    SelectObject(hdc, pen);

    for (int i = 0; i < width; i++) {
        float wave = (float)sin((i + offset) * 0.03f) * amplitude;
        float wave2 = (float)sin((i + offset) * 0.05f + 1.5f) * (amplitude * 0.5f);
        int py = y + (int)(wave + wave2);

        if (i == 0) {
            MoveToEx(hdc, x + i, py, NULL);
        } else {
            LineTo(hdc, x + i, py);
        }
    }
    DeleteObject(pen);
}

// Draw glassmorphism panel with blur effect simulation
void DrawGlassPanel(HDC hdc, int x, int y, int w, int h, float opacity, COLORREF tint, bool glow) {
    // Background with gradient
    for (int i = 0; i < h; i++) {
        float ratio = (float)i / h;
        int baseR = (int)(GetRValue(tint) * opacity * (1.0f - ratio * 0.3f));
        int baseG = (int)(GetGValue(tint) * opacity * (1.0f - ratio * 0.3f));
        int baseB = (int)(GetBValue(tint) * opacity * (1.0f - ratio * 0.3f));

        HPEN pen = CreatePen(PS_SOLID, 1, RGB(baseR, baseG, baseB));
        SelectObject(hdc, pen);
        MoveToEx(hdc, x, y + i, NULL);
        LineTo(hdc, x + w, y + i);
        DeleteObject(pen);
    }

    // Glass border - top/left highlight
    HPEN hlPen = CreatePen(PS_SOLID, 1, RGB(100, 120, 160));
    SelectObject(hdc, hlPen);
    MoveToEx(hdc, x, y + h, NULL);
    LineTo(hdc, x, y);
    LineTo(hdc, x + w, y);
    DeleteObject(hlPen);

    // Glass border - bottom/right shadow
    HPEN shPen = CreatePen(PS_SOLID, 1, RGB(20, 25, 40));
    SelectObject(hdc, shPen);
    MoveToEx(hdc, x + w, y, NULL);
    LineTo(hdc, x + w, y + h);
    LineTo(hdc, x, y + h);
    DeleteObject(shPen);

    // Glow effect
    if (glow) {
        for (int i = 8; i > 0; i--) {
            int alpha = (8 - i) * 4;
            COLORREF gc = RGB(
                (GetRValue(COLOR_NEON_CYAN) * alpha) / 100,
                (GetGValue(COLOR_NEON_CYAN) * alpha) / 100,
                (GetBValue(COLOR_NEON_CYAN) * alpha) / 100
            );
            HPEN glowPen = CreatePen(PS_SOLID, 1, gc);
            SelectObject(hdc, glowPen);
            SelectObject(hdc, GetStockObject(NULL_BRUSH));
            RoundRect(hdc, x - i, y - i, x + w + i, y + h + i, 4, 4);
            DeleteObject(glowPen);
        }
    }
}

// Draw modern rounded input field
void DrawModernInput(HDC hdc, int x, int y, int w, int h, const char* icon, bool focused) {
    // Background
    HBRUSH bgBrush = CreateSolidBrush(RGB(15, 18, 30));
    SelectObject(hdc, bgBrush);
    HPEN borderPen = CreatePen(PS_SOLID, 2, focused ? COLOR_NEON_CYAN : RGB(40, 50, 75));
    SelectObject(hdc, borderPen);
    RoundRect(hdc, x, y, x + w, y + h, 8, 8);
    DeleteObject(bgBrush);
    DeleteObject(borderPen);

    // Icon area background
    HBRUSH iconBg = CreateSolidBrush(RGB(25, 30, 50));
    RECT iconRect = {x + 2, y + 2, x + 35, y + h - 2};
    FillRect(hdc, &iconRect, iconBg);
    DeleteObject(iconBg);

    // Icon
    SetTextColor(hdc, focused ? COLOR_NEON_CYAN : COLOR_TEXT_SECONDARY);
    SetBkMode(hdc, TRANSPARENT);
    SelectObject(hdc, g_font_normal);

    wchar_t wicon[4];
    MultiByteToWideChar(CP_UTF8, 0, icon, -1, wicon, 4);
    TextOutW(hdc, x + 10, y + (h - 16) / 2, wicon, 1);

    // Glow when focused
    if (focused) {
        for (int i = 4; i > 0; i--) {
            COLORREF gc = RGB(0, 255 * (4 - i) / 20, 255 * (4 - i) / 20);
            HPEN glowPen = CreatePen(PS_SOLID, 1, gc);
            SelectObject(hdc, glowPen);
            SelectObject(hdc, GetStockObject(NULL_BRUSH));
            RoundRect(hdc, x - i, y - i, x + w + i, y + h + i, 8, 8);
            DeleteObject(glowPen);
        }
    }
}

// Draw modern gradient button
void DrawModernButton(HDC hdc, int x, int y, int w, int h, const char* text, bool hover, bool loading) {
    // Shadow
    HBRUSH shadowBrush = CreateSolidBrush(RGB(0, 0, 0));
    SelectObject(hdc, shadowBrush);
    SelectObject(hdc, GetStockObject(NULL_PEN));
    RoundRect(hdc, x + 3, y + 3, x + w + 3, y + h + 3, 10, 10);
    DeleteObject(shadowBrush);

    // Button gradient
    for (int i = 0; i < h; i++) {
        float ratio = (float)i / h;
        int r, g, b;

        if (hover) {
            r = (int)(50 + (200 - 50) * (1 - ratio));
            g = (int)(200 + (255 - 200) * (1 - ratio));
            b = (int)(255);
        } else {
            r = (int)(0 + (30 - 0) * (1 - ratio));
            g = (int)(180 + (220 - 180) * (1 - ratio));
            b = (int)(220 + (255 - 220) * (1 - ratio));
        }

        HPEN pen = CreatePen(PS_SOLID, 1, RGB(r, g, b));
        SelectObject(hdc, pen);

        // Draw line with rounded ends consideration
        if (i < 5 || i > h - 6) {
            int inset = (i < 5) ? (5 - i) : (i - (h - 6));
            MoveToEx(hdc, x + inset, y + i, NULL);
            LineTo(hdc, x + w - inset, y + i);
        } else {
            MoveToEx(hdc, x, y + i, NULL);
            LineTo(hdc, x + w, y + i);
        }
        DeleteObject(pen);
    }

    // Border
    HPEN borderPen = CreatePen(PS_SOLID, 2, hover ? COLOR_TEXT_BRIGHT : COLOR_NEON_CYAN);
    SelectObject(hdc, borderPen);
    SelectObject(hdc, GetStockObject(NULL_BRUSH));
    RoundRect(hdc, x, y, x + w, y + h, 10, 10);
    DeleteObject(borderPen);

    // Text
    SetTextColor(hdc, COLOR_SPACE_BLACK);
    SetBkMode(hdc, TRANSPARENT);
    SelectObject(hdc, g_font_large);

    const char* displayText = loading ? "CONNECTING..." : text;
    wchar_t wtext[64];
    MultiByteToWideChar(CP_UTF8, 0, displayText, -1, wtext, 64);
    SIZE size;
    GetTextExtentPoint32W(hdc, wtext, (int)wcslen(wtext), &size);

    // Loading spinner animation
    if (loading) {
        int spinnerX = x + (w - size.cx) / 2 - 20;
        int spinnerY = y + h / 2;
        float spinAngle = g_anim_tick * 0.2f;

        for (int i = 0; i < 8; i++) {
            float angle = spinAngle + i * (float)PI / 4;
            int sx = spinnerX + (int)(8 * cos(angle));
            int sy = spinnerY + (int)(8 * sin(angle));
            int alpha = 255 - i * 30;

            HBRUSH dotBrush = CreateSolidBrush(RGB(alpha/10, alpha/10, alpha/10));
            SelectObject(hdc, dotBrush);
            Ellipse(hdc, sx - 2, sy - 2, sx + 2, sy + 2);
            DeleteObject(dotBrush);
        }
    }

    TextOutW(hdc, x + (w - size.cx) / 2, y + (h - size.cy) / 2, wtext, (int)wcslen(wtext));
}

// Draw modern checkbox
void DrawModernCheckbox(HDC hdc, int x, int y, int size, bool checked, bool hover, const char* label) {
    // Background
    COLORREF bgColor = checked ? COLOR_NEON_CYAN : RGB(20, 25, 40);
    COLORREF borderColor = hover ? COLOR_NEON_CYAN : RGB(60, 70, 100);

    HBRUSH bgBrush = CreateSolidBrush(bgColor);
    HPEN borderPen = CreatePen(PS_SOLID, 2, borderColor);
    SelectObject(hdc, bgBrush);
    SelectObject(hdc, borderPen);
    RoundRect(hdc, x, y, x + size, y + size, 4, 4);
    DeleteObject(bgBrush);
    DeleteObject(borderPen);

    // Checkmark
    if (checked) {
        HPEN checkPen = CreatePen(PS_SOLID, 2, COLOR_SPACE_BLACK);
        SelectObject(hdc, checkPen);
        MoveToEx(hdc, x + 3, y + size/2, NULL);
        LineTo(hdc, x + size/3 + 1, y + size - 4);
        LineTo(hdc, x + size - 3, y + 4);
        DeleteObject(checkPen);
    }

    // Label
    if (label) {
        SetTextColor(hdc, hover ? COLOR_TEXT_PRIMARY : COLOR_TEXT_SECONDARY);
        SetBkMode(hdc, TRANSPARENT);
        SelectObject(hdc, g_font_small);

        wchar_t wlabel[128];
        MultiByteToWideChar(CP_UTF8, 0, label, -1, wlabel, 128);
        TextOutW(hdc, x + size + 10, y + (size - 11) / 2, wlabel, (int)wcslen(wlabel));
    }
}

void DrawLoginScreen(HDC hdc, RECT* rect) {
    int width = rect->right - rect->left;
    int height = rect->bottom - rect->top;

    // ========================================
    // BACKGROUND - Deep space gradient
    // ========================================
    for (int y = 0; y < height; y++) {
        float ratio = (float)y / height;
        int r = (int)(5 + 10 * ratio);
        int g = (int)(8 + 12 * ratio);
        int b = (int)(15 + 20 * ratio);

        HPEN pen = CreatePen(PS_SOLID, 1, RGB(r, g, b));
        SelectObject(hdc, pen);
        MoveToEx(hdc, 0, y, NULL);
        LineTo(hdc, width, y);
        DeleteObject(pen);
    }

    // ========================================
    // ANIMATED ELEMENTS - Background decorations
    // ========================================
    DrawStarField(hdc, width, height);

    // Flowing wave lines (decorative)
    float waveOffset = g_anim_tick * 2.0f;
    DrawFlowingWave(hdc, 0, height / 4, width, 30, RGB(20, 40, 80), waveOffset);
    DrawFlowingWave(hdc, 0, height / 2, width, 25, RGB(15, 35, 70), waveOffset * 0.7f);
    DrawFlowingWave(hdc, 0, height * 3 / 4, width, 35, RGB(25, 45, 85), waveOffset * 1.2f);

    // ========================================
    // SPLIT LAYOUT
    // ========================================
    int splitX = width / 2;

    // ========================================
    // LEFT SIDE - Branding & Visual
    // ========================================

    // Large floating logo with orbit rings
    int logoX = splitX / 2;
    int logoY = height / 2 - 30;
    int logoSize = 90;
    float floatOffset = (float)sin(g_anim_tick * 0.03f) * 12;
    int logoYAnimated = logoY + (int)floatOffset;

    // Orbit rings
    float rotation1 = g_anim_tick * 0.02f;
    float rotation2 = -g_anim_tick * 0.015f;
    float rotation3 = g_anim_tick * 0.01f;

    DrawOrbitRing(hdc, logoX, logoYAnimated, logoSize + 50, rotation1, COLOR_NEON_CYAN, 12);
    DrawOrbitRing(hdc, logoX, logoYAnimated, logoSize + 80, rotation2, COLOR_NEON_PURPLE, 16);
    DrawOrbitRing(hdc, logoX, logoYAnimated, logoSize + 110, rotation3, COLOR_NEON_BLUE, 20);

    // Pulsing glow behind logo
    for (int i = 40; i > 0; i -= 3) {
        float pulse = (float)sin(g_anim_tick * 0.06f) * 0.4f + 0.6f;
        int alpha = (int)((40 - i) * 2 * pulse);
        COLORREF glowColor = RGB(
            (GetRValue(COLOR_NEON_CYAN) * alpha) / 100,
            (GetGValue(COLOR_NEON_CYAN) * alpha) / 100,
            (GetBValue(COLOR_NEON_CYAN) * alpha) / 100
        );

        HBRUSH brush = CreateSolidBrush(glowColor);
        SelectObject(hdc, brush);
        SelectObject(hdc, GetStockObject(NULL_PEN));
        Ellipse(hdc, logoX - logoSize/2 - i, logoYAnimated - logoSize/2 - i,
                logoX + logoSize/2 + i, logoYAnimated + logoSize/2 + i);
        DeleteObject(brush);
    }

    // Draw real logo if available, otherwise fallback to hexagon
    int imgSize = 200;  // Logo display size
    int textY = logoY + logoSize/2 + 60;  // For status display below logo

    if (g_logoLoaded) {
        DrawLogo(hdc, logoX - imgSize/2, logoYAnimated - imgSize/2, imgSize, imgSize);
    } else {
        // Fallback: Main hexagonal logo
        POINT hexPoints[6];
        for (int i = 0; i < 6; i++) {
            float angle = (i * 60 - 30) * (float)PI / 180.0f;
            hexPoints[i].x = logoX + (int)((logoSize/2) * cos(angle));
            hexPoints[i].y = logoYAnimated + (int)((logoSize/2) * sin(angle));
        }

        HBRUSH hexBrush = CreateSolidBrush(RGB(25, 35, 60));
        SelectObject(hdc, hexBrush);
        HPEN hexPen = CreatePen(PS_SOLID, 3, COLOR_NEON_CYAN);
        SelectObject(hdc, hexPen);
        Polygon(hdc, hexPoints, 6);
        DeleteObject(hexBrush);
        DeleteObject(hexPen);

        // GPU icon in center
        SetTextColor(hdc, COLOR_NEON_CYAN);
        SetBkMode(hdc, TRANSPARENT);
        SelectObject(hdc, g_font_title);
        TextOutW(hdc, logoX - 14, logoYAnimated - 18, L"\x25C8", 1);

        // Brand text below logo

        // "GPU SHARE" with glow effect
        SelectObject(hdc, g_font_title);
        wchar_t brandText[] = L"GPU SHARE";
        SIZE textSize;
        GetTextExtentPoint32W(hdc, brandText, (int)wcslen(brandText), &textSize);
        int textX = logoX - textSize.cx / 2;

        for (int i = 4; i > 0; i--) {
            SetTextColor(hdc, RGB(0, 80 + i * 20, 100 + i * 20));
            TextOutW(hdc, textX - i, textY - i, brandText, (int)wcslen(brandText));
        }
        SetTextColor(hdc, COLOR_NEON_CYAN);
        TextOutW(hdc, textX, textY, brandText, (int)wcslen(brandText));

        // Tagline
        const char* tagline = "DISTRIBUTED COMPUTING NETWORK";
        DrawTextCenter(hdc, tagline, 0, textY + 45, splitX, COLOR_TEXT_SECONDARY, g_font_normal);
    }

    // GPU detection status
    char gpuInfo[128];
    snprintf(gpuInfo, sizeof(gpuInfo), "%d GPU(s) DETECTED", g_gpu_manager.gpu_count);

    // Status indicator dot
    int statusY = textY + 85;
    float statusPulse = (float)sin(g_anim_tick * 0.1f) * 0.3f + 0.7f;
    COLORREF statusColor = RGB(
        (int)(GetRValue(COLOR_NEON_GREEN) * statusPulse),
        (int)(GetGValue(COLOR_NEON_GREEN) * statusPulse),
        (int)(GetBValue(COLOR_NEON_GREEN) * statusPulse)
    );

    int dotX = logoX - 60;
    HBRUSH statusBrush = CreateSolidBrush(statusColor);
    SelectObject(hdc, statusBrush);
    Ellipse(hdc, dotX, statusY, dotX + 10, statusY + 10);
    DeleteObject(statusBrush);

    DrawText_(hdc, gpuInfo, dotX + 18, statusY - 2, COLOR_NEON_GREEN, g_font_tech);

    // ========================================
    // RIGHT SIDE - Login Form (Glassmorphism)
    // ========================================

    // Glass card
    int cardW = 320;
    int cardH = 340;
    int cardX = splitX + (splitX - cardW) / 2;
    int cardY = (height - cardH) / 2;

    DrawGlassPanel(hdc, cardX, cardY, cardW, cardH, 0.15f, RGB(40, 50, 80), true);

    // Card header
    DrawText_(hdc, "SECURE LOGIN", cardX + 25, cardY + 25, COLOR_TEXT_BRIGHT, g_font_large);
    DrawText_(hdc, "Access your mining dashboard", cardX + 25, cardY + 52, COLOR_TEXT_MUTED, g_font_small);

    // Divider line
    HPEN divPen = CreatePen(PS_SOLID, 1, RGB(50, 60, 90));
    SelectObject(hdc, divPen);
    MoveToEx(hdc, cardX + 25, cardY + 75, NULL);
    LineTo(hdc, cardX + cardW - 25, cardY + 75);
    DeleteObject(divPen);

    // Email input
    int inputX = cardX + 25;
    int inputY = cardY + 95;
    int inputW = cardW - 50;
    int inputH = 42;

    DrawText_(hdc, "EMAIL ADDRESS", inputX + 3, inputY - 18, COLOR_TEXT_SECONDARY, g_font_small);
    DrawModernInput(hdc, inputX, inputY, inputW, inputH, "@", false);

    // Password input
    int passY = inputY + inputH + 35;
    DrawText_(hdc, "PASSWORD", inputX + 3, passY - 18, COLOR_TEXT_SECONDARY, g_font_small);
    DrawModernInput(hdc, inputX, passY, inputW, inputH, "*", false);

    // Checkboxes
    int checkY = passY + inputH + 20;
    bool rememberHover = (g_hover_button == ID_CHECK_REMEMBER);
    bool autoStartHover = (g_hover_button == ID_CHECK_AUTOSTART);
    bool autoStartEnabled = AutoStart_IsEnabled();

    DrawModernCheckbox(hdc, inputX, checkY, 18, g_remember_password, rememberHover,
                       GetLanguage() == LANG_TH ? "จำรหัสผ่าน" : "Remember me");
    DrawModernCheckbox(hdc, inputX, checkY + 28, 18, autoStartEnabled, autoStartHover,
                       GetLanguage() == LANG_TH ? "เริ่มอัตโนมัติ" : "Start with Windows");

    // Login button
    int btnW = inputW;
    int btnH = 48;
    int btnX = inputX;
    int btnY = cardY + cardH - btnH - 30;
    bool btnHover = (g_hover_button == ID_BTN_LOGIN);

    DrawModernButton(hdc, btnX, btnY, btnW, btnH,
                     GetLanguage() == LANG_TH ? "LOGIN" : "SIGN IN",
                     btnHover, g_logging_in);

    // Error message
    if (strlen(g_login_error) > 0) {
        // Error background
        HBRUSH errBrush = CreateSolidBrush(RGB(80, 20, 30));
        RECT errRect = {cardX + 20, cardY + cardH + 10, cardX + cardW - 20, cardY + cardH + 35};
        FillRect(hdc, &errRect, errBrush);
        DeleteObject(errBrush);

        DrawTextCenter(hdc, g_login_error, cardX, cardY + cardH + 13, cardW, COLOR_NEON_RED, g_font_small);
    }

    // ========================================
    // TOP BAR - Language selector
    // ========================================

    // Language toggle (pill style)
    int langX = width - 100;
    int langY = 20;
    int langW = 80;
    int langH = 32;

    // Background pill
    HBRUSH langBgBrush = CreateSolidBrush(RGB(20, 25, 45));
    SelectObject(hdc, langBgBrush);
    HPEN langBgPen = CreatePen(PS_SOLID, 1, RGB(50, 60, 90));
    SelectObject(hdc, langBgPen);
    RoundRect(hdc, langX, langY, langX + langW, langY + langH, 16, 16);
    DeleteObject(langBgBrush);
    DeleteObject(langBgPen);

    // Active indicator
    bool isEnglish = (GetLanguage() == LANG_EN);
    int activeX = isEnglish ? langX + 2 : langX + langW/2;

    HBRUSH activeBrush = CreateSolidBrush(COLOR_NEON_CYAN);
    SelectObject(hdc, activeBrush);
    SelectObject(hdc, GetStockObject(NULL_PEN));
    RoundRect(hdc, activeX, langY + 2, activeX + langW/2 - 2, langY + langH - 2, 14, 14);
    DeleteObject(activeBrush);

    // Language text
    SetTextColor(hdc, isEnglish ? COLOR_SPACE_BLACK : COLOR_TEXT_SECONDARY);
    DrawTextCenter(hdc, "EN", langX, langY + 8, langW/2, isEnglish ? COLOR_SPACE_BLACK : COLOR_TEXT_SECONDARY, g_font_small);
    DrawTextCenter(hdc, "TH", langX + langW/2, langY + 8, langW/2, !isEnglish ? COLOR_SPACE_BLACK : COLOR_TEXT_SECONDARY, g_font_small);

    // ========================================
    // FOOTER
    // ========================================
    int footerY = height - 35;

    DrawText_(hdc, APP_AUTHOR, 25, footerY, COLOR_TEXT_MUTED, g_font_small);

    char version[64];
    snprintf(version, sizeof(version), "v%s", APP_VERSION);
    SetTextColor(hdc, COLOR_TEXT_MUTED);
    SelectObject(hdc, g_font_tech);
    wchar_t wver[64];
    MultiByteToWideChar(CP_UTF8, 0, version, -1, wver, 64);
    SIZE verSize;
    GetTextExtentPoint32W(hdc, wver, (int)wcslen(wver), &verSize);
    TextOutW(hdc, width - 25 - verSize.cx, footerY, wver, (int)wcslen(wver));

    // ========================================
    // SUBTLE SCAN LINE (less intrusive)
    // ========================================
    g_scan_line_y = (g_scan_line_y + 1) % height;
    int scanY = g_scan_line_y;

    HPEN scanPen = CreatePen(PS_SOLID, 1, RGB(0, 15, 25));
    SelectObject(hdc, scanPen);
    MoveToEx(hdc, 0, scanY, NULL);
    LineTo(hdc, width, scanY);
    DeleteObject(scanPen);
}

// ============================================================================
// DRAW DASHBOARD
// ============================================================================
void DrawDashboard(HDC hdc, RECT* rect) {
    int width = rect->right - rect->left;
    int height = rect->bottom - rect->top;
    int padding = 12;
    int cardGap = 8;

    DrawSpaceBackground(hdc, rect);

    // Header
    Draw3DPanel(hdc, 0, 0, width, 50, true, false, 0);

    int logoX = padding + 5;
    int logoY = 10;

    POINT miniHex[6];
    int hcx = logoX + 15, hcy = logoY + 15;
    for (int i = 0; i < 6; i++) {
        float angle = (i * 60 - 30) * PI / 180.0f;
        miniHex[i].x = hcx + (int)(14 * cos(angle));
        miniHex[i].y = hcy + (int)(14 * sin(angle));
    }
    HBRUSH hexBrush = CreateSolidBrush(COLOR_PANEL_DARK);
    HPEN hexPen = CreatePen(PS_SOLID, 2, COLOR_NEON_CYAN);
    SelectObject(hdc, hexBrush);
    SelectObject(hdc, hexPen);
    Polygon(hdc, miniHex, 6);
    DeleteObject(hexBrush);
    DeleteObject(hexPen);

    SetTextColor(hdc, COLOR_NEON_CYAN);
    SetBkMode(hdc, TRANSPARENT);
    SelectObject(hdc, g_font_small);
    TextOutW(hdc, hcx - 4, hcy - 6, L"\x25C8", 1);

    DrawText_(hdc, "GPU SHARE", logoX + 38, 8, COLOR_TEXT_NEON, g_font_large);

    char userInfo[256];
    snprintf(userInfo, sizeof(userInfo), "OPERATOR: %s | UNITS: %d", g_worker.session.name, g_gpu_manager.gpu_count);
    DrawText_(hdc, userInfo, logoX + 38, 30, COLOR_TEXT_MUTED, g_font_small);

    // Models button
    bool modelsHover = (g_hover_button == ID_BTN_MODELS);
    DrawSpaceButton(hdc, width - padding - 175, 13, 40, 24, "🤖",
                    COLOR_NEON_GREEN, modelsHover, true);

    // Settings button
    bool settingsHover = (g_hover_button == ID_BTN_SETTINGS);
    DrawSpaceButton(hdc, width - padding - 130, 13, 35, 24, "SET",
                    COLOR_PANEL_MID, settingsHover, true);

    // Language buttons
    int btnW = 38, btnH = 24;
    bool enHover = (g_hover_button == ID_BTN_LANG_EN);
    bool thHover = (g_hover_button == ID_BTN_LANG_TH);

    DrawSpaceButton(hdc, width - padding - btnW * 2 - 10, 13, btnW, btnH, "EN",
                    GetLanguage() == LANG_EN ? COLOR_NEON_CYAN : COLOR_PANEL_MID, enHover, true);
    DrawSpaceButton(hdc, width - padding - btnW - 5, 13, btnW, btnH, "TH",
                    GetLanguage() == LANG_TH ? COLOR_NEON_CYAN : COLOR_PANEL_MID, thHover, true);

    // GPU Tabs
    int tabsY = 58;
    int tabW = min(130, (width - padding * 2 - 10) / max(1, g_gpu_manager.gpu_count));
    int tabH = 28;

    for (int i = 0; i < g_gpu_manager.gpu_count; i++) {
        int tabX = padding + i * (tabW + 5);
        bool isSelected = (i == g_selected_gpu);
        bool isHover = (g_hover_button == ID_BTN_GPU_BASE + i);

        char tabLabel[32];
        snprintf(tabLabel, sizeof(tabLabel), "GPU %d", i);
        DrawSpaceshipTab(hdc, tabX, tabsY, tabW, tabH, tabLabel, isSelected, isHover, COLOR_NEON_PURPLE);
    }

    GPUInfo* gpu = &g_gpu_manager.gpus[g_selected_gpu];

    int leftW = (width - padding * 3) / 2;
    int contentY = tabsY + 40;

    // GPU Info Panel
    int gpuPanelH = 145;
    Draw3DPanel(hdc, padding, contentY, leftW, gpuPanelH, false, true, COLOR_NEON_PURPLE);

    HBRUSH titleBrush = CreateSolidBrush(COLOR_PANEL_MID);
    RECT titleRect = {padding + 2, contentY + 2, padding + leftW - 2, contentY + 22};
    FillRect(hdc, &titleRect, titleBrush);
    DeleteObject(titleBrush);
    DrawText_(hdc, "GPU STATUS", padding + 10, contentY + 5, COLOR_TEXT_NEON, g_font_small);

    DrawText_(hdc, gpu->name, padding + 10, contentY + 28, COLOR_TEXT_PRIMARY, g_font_normal);

    int statY = contentY + 50;
    char buf[64];

    DrawCircularGauge(hdc, padding + 45, statY + 35, 30, gpu->temperature, 100,
                      COLOR_SPACE_BLACK, GPU_GetTempColor(gpu->temperature), "TEMP");

    DrawCircularGauge(hdc, padding + 120, statY + 35, 30, gpu->power_usage, gpu->power_limit,
                      COLOR_SPACE_BLACK, COLOR_NEON_ORANGE, "POWER");

    snprintf(buf, sizeof(buf), "%d / %d MB", gpu->memory_used, gpu->memory_total);
    DrawText_(hdc, "VRAM:", padding + 170, statY + 10, COLOR_TEXT_SECONDARY, g_font_small);
    DrawText_(hdc, buf, padding + 210, statY + 10, COLOR_TEXT_PRIMARY, g_font_small);

    DrawText_(hdc, "LOAD:", padding + 170, statY + 35, COLOR_TEXT_SECONDARY, g_font_small);
    DrawSpaceProgress(hdc, padding + 210, statY + 37, leftW - 230, 12, gpu->gpu_load, 100, GPU_GetLoadColor(gpu->gpu_load));
    snprintf(buf, sizeof(buf), "%d%%", gpu->gpu_load);
    DrawText_(hdc, buf, padding + leftW - 35, statY + 33, COLOR_TEXT_PRIMARY, g_font_small);

    snprintf(buf, sizeof(buf), "%d RPM", gpu->fan_speed);
    DrawText_(hdc, "FAN:", padding + 170, statY + 60, COLOR_TEXT_SECONDARY, g_font_small);
    DrawText_(hdc, buf, padding + 210, statY + 60, COLOR_NEON_CYAN, g_font_small);

    // Controls Panel
    int ctrlY = contentY + gpuPanelH + cardGap;
    int ctrlH = 120;
    Draw3DPanel(hdc, padding, ctrlY, leftW, ctrlH, false, true, COLOR_NEON_GREEN);

    HBRUSH ctrlTitleBrush = CreateSolidBrush(COLOR_PANEL_MID);
    RECT ctrlTitleRect = {padding + 2, ctrlY + 2, padding + leftW - 2, ctrlY + 22};
    FillRect(hdc, &ctrlTitleRect, ctrlTitleBrush);
    DeleteObject(ctrlTitleBrush);
    DrawText_(hdc, "CONTROL INTERFACE", padding + 10, ctrlY + 5, COLOR_NEON_GREEN, g_font_small);

    int sliderY = ctrlY + 30;
    snprintf(buf, sizeof(buf), "PERFORMANCE: %d%%", g_perf_slider_value);
    DrawNeonSlider(hdc, padding + 15, sliderY + 14, leftW - 30, 24, g_perf_slider_value, 50, 100,
                   COLOR_NEON_GREEN, g_dragging_perf, buf);

    sliderY += 48;

    int toggleX = padding + leftW - 60;
    HBRUSH toggleBrush = CreateSolidBrush(g_auto_fan ? COLOR_NEON_GREEN : COLOR_PANEL_DARK);
    SelectObject(hdc, toggleBrush);
    HPEN togglePen = CreatePen(PS_SOLID, 1, g_auto_fan ? COLOR_NEON_GREEN : COLOR_FRAME_INNER);
    SelectObject(hdc, togglePen);
    RoundRect(hdc, toggleX, sliderY, toggleX + 50, sliderY + 18, 4, 4);
    DeleteObject(toggleBrush);
    DeleteObject(togglePen);
    DrawTextCenter(hdc, g_auto_fan ? "AUTO" : "MAN", toggleX, sliderY + 3, 50,
                   g_auto_fan ? COLOR_SPACE_BLACK : COLOR_TEXT_SECONDARY, g_font_small);

    if (!g_auto_fan) {
        snprintf(buf, sizeof(buf), "FAN SPEED: %d%%", g_fan_slider_value);
        DrawNeonSlider(hdc, padding + 15, sliderY + 22, leftW - 80, 24, g_fan_slider_value, 20, 100,
                       COLOR_NEON_CYAN, g_dragging_fan, buf);
    } else {
        DrawText_(hdc, "FAN: AUTO MODE", padding + 15, sliderY, COLOR_TEXT_SECONDARY, g_font_small);
    }

    // Temperature Graph
    int graphY = ctrlY + ctrlH + cardGap;
    int graphH = 70;
    DrawHoloGraph(hdc, padding, graphY, leftW, graphH, gpu, COLOR_NEON_CYAN);
    DrawText_(hdc, "THERMAL MONITOR", padding + 10, graphY + 3, COLOR_NEON_CYAN, g_font_small);

    // Network Graph
    int netGraphY = graphY + graphH + cardGap;
    int netGraphH = 70;
    DrawNetworkGraph(hdc, padding, netGraphY, leftW, netGraphH);
    DrawText_(hdc, "NETWORK", padding + 10, netGraphY + 3, COLOR_NEON_BLUE, g_font_small);

    // Right Column
    int rightX = padding * 2 + leftW;
    int rightW = leftW;

    // Status Panel
    int statusH = 70;
    Draw3DPanel(hdc, rightX, contentY, rightW, statusH, false, true, GetStatusColor(Worker_GetJobStatus(&g_worker)));

    JobStatus jobStatus = Worker_GetJobStatus(&g_worker);
    bool isPulsing = (jobStatus == JOB_RECEIVING || jobStatus == JOB_PROCESSING || jobStatus == JOB_UPLOADING);

    DrawStatusIndicator(hdc, rightX + 15, contentY + 25, 20, GetStatusColor(jobStatus), isPulsing);

    DrawText_(hdc, "SYSTEM STATUS", rightX + 45, contentY + 15, COLOR_TEXT_SECONDARY, g_font_small);
    DrawText_(hdc, GetStatusText(jobStatus), rightX + 45, contentY + 32, COLOR_TEXT_BRIGHT, g_font_large);

    int btnLogoutW = 80, btnLogoutH = 30;
    int btnLogoutX = rightX + rightW - btnLogoutW - 12;
    int btnLogoutY = contentY + (statusH - btnLogoutH) / 2;
    bool logoutHover = (g_hover_button == ID_BTN_LOGOUT);

    DrawSpaceButton(hdc, btnLogoutX, btnLogoutY, btnLogoutW, btnLogoutH,
                    L(STR_LOGOUT), COLOR_NEON_RED, logoutHover, true);

    // Earnings Cards
    int earnY = contentY + statusH + cardGap;
    int earnCardW = (rightW - cardGap) / 2;
    int earnCardH = 65;

    snprintf(buf, sizeof(buf), "%.2f", g_worker.today_earned);
    DrawHoloStat(hdc, rightX, earnY, earnCardW, earnCardH, L(STR_TODAY_EARNINGS), buf, L(STR_THB), COLOR_NEON_GREEN);

    snprintf(buf, sizeof(buf), "%.2f", g_worker.session.balance);
    DrawHoloStat(hdc, rightX + earnCardW + cardGap, earnY, earnCardW, earnCardH, L(STR_TOTAL_EARNINGS), buf, L(STR_THB), COLOR_NEON_PURPLE);

    earnY += earnCardH + cardGap;

    // Electricity cost cards
    snprintf(buf, sizeof(buf), "%.2f", g_electricity_today_cost);
    DrawHoloStat(hdc, rightX, earnY, earnCardW, earnCardH,
                 GetLanguage() == LANG_TH ? "ค่าไฟวันนี้" : "Today's Elec",
                 buf, L(STR_THB), COLOR_NEON_ORANGE);

    snprintf(buf, sizeof(buf), "%.2f", g_electricity_month_cost);
    DrawHoloStat(hdc, rightX + earnCardW + cardGap, earnY, earnCardW, earnCardH,
                 GetLanguage() == LANG_TH ? "ค่าไฟเดือนนี้" : "Month's Elec",
                 buf, L(STR_THB), COLOR_NEON_YELLOW);

    // Net profit
    earnY += earnCardH + cardGap;
    float net_profit = g_worker.today_earned - g_electricity_today_cost;
    snprintf(buf, sizeof(buf), "%.2f", net_profit);
    COLORREF profitColor = net_profit >= 0 ? COLOR_NEON_GREEN : COLOR_NEON_RED;
    DrawHoloStat(hdc, rightX, earnY, earnCardW, earnCardH,
                 GetLanguage() == LANG_TH ? "กำไรสุทธิวันนี้" : "Net Profit Today",
                 buf, L(STR_THB), profitColor);

    char uptimeBuf[64];
    System_FormatUptime(g_worker.uptime_seconds, uptimeBuf, sizeof(uptimeBuf), GetLanguage() == LANG_TH);
    DrawHoloStat(hdc, rightX + earnCardW + cardGap, earnY, earnCardW, earnCardH, L(STR_UPTIME), uptimeBuf, "", COLOR_NEON_BLUE);

    // Footer
    int footerY = height - 25;

    HPEN techPen = CreatePen(PS_SOLID, 1, COLOR_FRAME_INNER);
    SelectObject(hdc, techPen);
    MoveToEx(hdc, padding, footerY - 5, NULL);
    LineTo(hdc, width - padding, footerY - 5);
    DeleteObject(techPen);

    DrawText_(hdc, APP_AUTHOR, padding, footerY, COLOR_TEXT_MUTED, g_font_small);

    bool connected = Worker_IsConnected(&g_worker);
    const char* connText = connected ? L(STR_CONNECTED) : L(STR_DISCONNECTED);
    COLORREF connColor = connected ? COLOR_STATUS_ONLINE : COLOR_STATUS_OFFLINE;

    wchar_t wconn[64];
    MultiByteToWideChar(CP_UTF8, 0, connText, -1, wconn, 64);
    SIZE textSize;
    SelectObject(hdc, g_font_small);
    GetTextExtentPoint32W(hdc, wconn, (int)wcslen(wconn), &textSize);

    DrawStatusIndicator(hdc, width - padding - textSize.cx - 18, footerY + 2, 10, connColor, connected);
    DrawText_(hdc, connText, width - padding - textSize.cx, footerY, connColor, g_font_small);

    g_scan_line_y = (g_scan_line_y + 1) % height;
}

// ============================================================================
// SETTINGS SCREEN
// ============================================================================
void DrawSettingsScreen(HDC hdc, RECT* rect) {
    int width = rect->right - rect->left;
    int height = rect->bottom - rect->top;

    // Draw space background
    DrawSpaceBackground(hdc, rect);
    DrawStarField(hdc, width, height);

    int padding = 20;
    int headerH = 60;

    // Header
    Draw3DPanel(hdc, padding, padding, width - padding * 2, headerH, true, true, COLOR_NEON_PURPLE);
    DrawText_(hdc, GetLanguage() == LANG_TH ? "ตั้งค่า" : "SETTINGS", padding + 20, padding + 18, COLOR_TEXT_BRIGHT, g_font_large);

    // Back button
    int btnBackW = 100, btnBackH = 35;
    int btnBackX = width - padding - btnBackW - 15;
    int btnBackY = padding + (headerH - btnBackH) / 2;
    bool backHover = (g_hover_button == ID_BTN_BACK);
    DrawSpaceButton(hdc, btnBackX, btnBackY, btnBackW, btnBackH,
                    GetLanguage() == LANG_TH ? "กลับ" : "BACK", COLOR_NEON_CYAN, backHover, true);

    int contentY = padding + headerH + 15;
    int cardW = (width - padding * 3) / 2;
    int cardH = 180;

    // Language Settings Card
    Draw3DPanel(hdc, padding, contentY, cardW, cardH, false, false, 0);
    DrawText_(hdc, GetLanguage() == LANG_TH ? "ภาษา" : "LANGUAGE", padding + 15, contentY + 15, COLOR_NEON_CYAN, g_font_normal);

    // Language buttons
    int langBtnW = 100, langBtnH = 40;
    int langBtnY = contentY + 55;
    bool enHover = (g_hover_button == ID_BTN_LANG_EN);
    bool thHover = (g_hover_button == ID_BTN_LANG_TH);
    bool isEnglish = (GetLanguage() == LANG_EN);

    DrawSpaceButton(hdc, padding + 20, langBtnY, langBtnW, langBtnH, "English",
                    isEnglish ? COLOR_NEON_GREEN : COLOR_PANEL_MID, enHover, false);
    DrawSpaceButton(hdc, padding + 130, langBtnY, langBtnW, langBtnH, "ไทย",
                    !isEnglish ? COLOR_NEON_GREEN : COLOR_PANEL_MID, thHover, false);

    // Auto-start checkbox
    int checkY = langBtnY + langBtnH + 25;
    bool autostartHover = (g_hover_button == ID_CHECK_AUTOSTART);
    DrawCheckbox(hdc, padding + 20, checkY, 22, g_app_settings->auto_start_enabled, autostartHover,
                 GetLanguage() == LANG_TH ? "เปิดอัตโนมัติเมื่อเริ่ม Windows" : "Start with Windows");

    // Electricity Settings Card
    int rightX = padding * 2 + cardW;
    Draw3DPanel(hdc, rightX, contentY, cardW, cardH, false, false, 0);
    DrawText_(hdc, GetLanguage() == LANG_TH ? "ค่าไฟฟ้า" : "ELECTRICITY", rightX + 15, contentY + 15, COLOR_NEON_ORANGE, g_font_normal);

    char elecInfo[128];
    snprintf(elecInfo, sizeof(elecInfo), GetLanguage() == LANG_TH ?
             "อัตราค่าไฟ: %.2f บาท/หน่วย" : "Rate: %.2f THB/kWh",
             g_app_settings->use_custom_rate ? g_app_settings->electricity_cost_per_kwh_override : 4.0f);
    DrawText_(hdc, elecInfo, rightX + 20, contentY + 55, COLOR_TEXT_PRIMARY, g_font_small);

    snprintf(elecInfo, sizeof(elecInfo), GetLanguage() == LANG_TH ?
             "ใช้ไปวันนี้: %.3f kWh (%.2f บาท)" : "Today: %.3f kWh (%.2f THB)",
             g_electricity_today_kwh, g_electricity_today_cost);
    DrawText_(hdc, elecInfo, rightX + 20, contentY + 80, COLOR_TEXT_SECONDARY, g_font_small);

    snprintf(elecInfo, sizeof(elecInfo), GetLanguage() == LANG_TH ?
             "เดือนนี้: %.3f kWh (%.2f บาท)" : "This month: %.3f kWh (%.2f THB)",
             g_electricity_month_kwh, g_electricity_month_cost);
    DrawText_(hdc, elecInfo, rightX + 20, contentY + 105, COLOR_TEXT_SECONDARY, g_font_small);

    // GPU Node Info Card
    contentY += cardH + 15;
    cardH = 150;
    Draw3DPanel(hdc, padding, contentY, width - padding * 2, cardH, false, false, 0);
    DrawText_(hdc, GetLanguage() == LANG_TH ? "ข้อมูล Node" : "NODE INFO", padding + 15, contentY + 15, COLOR_NEON_BLUE, g_font_normal);

    char nodeInfo[256];
    if (Worker_IsConnected(&g_worker) && strlen(g_worker.node.node_id) > 0) {
        snprintf(nodeInfo, sizeof(nodeInfo), "Node ID: %s", g_worker.node.node_id);
        DrawText_(hdc, nodeInfo, padding + 20, contentY + 50, COLOR_TEXT_PRIMARY, g_font_small);

        snprintf(nodeInfo, sizeof(nodeInfo), "Status: %s", g_worker.node.status);
        DrawText_(hdc, nodeInfo, padding + 20, contentY + 75, COLOR_NEON_GREEN, g_font_small);

        snprintf(nodeInfo, sizeof(nodeInfo), "User: %s", g_worker.session.email);
        DrawText_(hdc, nodeInfo, padding + 20, contentY + 100, COLOR_TEXT_SECONDARY, g_font_small);
    } else {
        DrawText_(hdc, GetLanguage() == LANG_TH ? "ยังไม่ได้เชื่อมต่อ" : "Not connected",
                  padding + 20, contentY + 50, COLOR_TEXT_MUTED, g_font_normal);
    }

    // Version info
    char versionInfo[64];
    snprintf(versionInfo, sizeof(versionInfo), "Version %s", APP_VERSION);
    DrawText_(hdc, versionInfo, padding + 20, contentY + cardH - 30, COLOR_TEXT_MUTED, g_font_small);

    // Footer
    int footerY = height - 30;
    DrawText_(hdc, APP_AUTHOR, padding, footerY, COLOR_TEXT_MUTED, g_font_small);
}

// ============================================================================
// SCI-FI TOGGLE SWITCH - Spaceship Style
// ============================================================================
void DrawSciFiToggle(HDC hdc, int x, int y, int w, int h, bool enabled, bool hover) {
    // Outer frame with glow effect
    COLORREF borderColor = enabled ? COLOR_NEON_GREEN : COLOR_NEON_RED;
    COLORREF bgColor = enabled ? RGB(6, 78, 59) : RGB(127, 29, 29);
    COLORREF innerBg = enabled ? RGB(6, 95, 70) : RGB(153, 27, 27);

    if (hover) {
        borderColor = enabled ? RGB(134, 239, 172) : RGB(252, 165, 165);
    }

    // Draw outer glow
    if (hover) {
        HPEN glowPen = CreatePen(PS_SOLID, 3, borderColor);
        HPEN oldPen = (HPEN)SelectObject(hdc, glowPen);
        HBRUSH nullBrush = (HBRUSH)GetStockObject(NULL_BRUSH);
        HBRUSH oldBrush = (HBRUSH)SelectObject(hdc, nullBrush);
        RoundRect(hdc, x - 2, y - 2, x + w + 2, y + h + 2, 20, 20);
        SelectObject(hdc, oldPen);
        SelectObject(hdc, oldBrush);
        DeleteObject(glowPen);
    }

    // Draw main toggle body with gradient effect
    HBRUSH bgBrush = CreateSolidBrush(bgColor);
    HPEN borderPen = CreatePen(PS_SOLID, 3, borderColor);
    HPEN oldPen = (HPEN)SelectObject(hdc, borderPen);
    HBRUSH oldBrush = (HBRUSH)SelectObject(hdc, bgBrush);
    RoundRect(hdc, x, y, x + w, y + h, 18, 18);

    // Draw inner panel
    HBRUSH innerBrush = CreateSolidBrush(innerBg);
    SelectObject(hdc, innerBrush);
    HPEN innerPen = CreatePen(PS_SOLID, 1, borderColor);
    SelectObject(hdc, innerPen);
    RoundRect(hdc, x + 4, y + 4, x + w - 4, y + h - 4, 14, 14);

    // Draw toggle indicator (slider)
    int sliderW = w / 2 - 6;
    int sliderH = h - 12;
    int sliderX = enabled ? (x + w - sliderW - 8) : (x + 8);
    int sliderY = y + 6;

    HBRUSH sliderBrush = CreateSolidBrush(enabled ? COLOR_NEON_GREEN : COLOR_NEON_RED);
    SelectObject(hdc, sliderBrush);
    HPEN sliderPen = CreatePen(PS_SOLID, 2, enabled ? RGB(187, 247, 208) : RGB(254, 202, 202));
    SelectObject(hdc, sliderPen);
    RoundRect(hdc, sliderX, sliderY, sliderX + sliderW, sliderY + sliderH, 10, 10);

    // Draw ON/OFF text
    SetBkMode(hdc, TRANSPARENT);
    HFONT oldFont = (HFONT)SelectObject(hdc, g_font_small);
    SetTextColor(hdc, enabled ? COLOR_NEON_GREEN : COLOR_NEON_RED);

    const char* stateText = enabled ? "ON" : "OFF";
    int textX = enabled ? x + 12 : x + w - 30;
    int textY = y + (h - 14) / 2;
    TextOutA(hdc, textX, textY, stateText, (int)strlen(stateText));

    SelectObject(hdc, oldFont);
    SelectObject(hdc, oldPen);
    SelectObject(hdc, oldBrush);
    DeleteObject(bgBrush);
    DeleteObject(innerBrush);
    DeleteObject(sliderBrush);
    DeleteObject(borderPen);
    DeleteObject(innerPen);
    DeleteObject(sliderPen);
}

// ============================================================================
// MODELS SCREEN - Sci-Fi Model Selection Panel
// ============================================================================
void DrawModelsScreen(HDC hdc, RECT* rect) {
    int width = rect->right - rect->left;
    int height = rect->bottom - rect->top;

    // Draw space background
    DrawSpaceBackground(hdc, rect);
    DrawStarField(hdc, width, height);

    int padding = 20;
    int headerH = 60;

    // Header with title
    Draw3DPanel(hdc, padding, padding, width - padding * 2, headerH, true, true, COLOR_NEON_PURPLE);
    DrawText_(hdc, GetLanguage() == LANG_TH ? "🤖 เลือกโมเดล AI" : "🤖 AI MODELS", padding + 20, padding + 18, COLOR_TEXT_BRIGHT, g_font_large);

    // Back button
    int btnBackW = 100, btnBackH = 35;
    int btnBackX = width - padding - btnBackW - 15;
    int btnBackY = padding + (headerH - btnBackH) / 2;
    bool backHover = (g_hover_button == ID_BTN_BACK);
    DrawSpaceButton(hdc, btnBackX, btnBackY, btnBackW, btnBackH,
                    GetLanguage() == LANG_TH ? "กลับ" : "BACK", COLOR_NEON_CYAN, backHover, true);

    // Info text
    int infoY = padding + headerH + 15;
    DrawText_(hdc, GetLanguage() == LANG_TH ?
              "⚡ เปิด/ปิดสวิตช์เพื่อเลือกโมเดลที่ต้องการรับงาน" :
              "⚡ Toggle switches to select models for work",
              padding + 10, infoY, COLOR_TEXT_SECONDARY, g_font_small);

    // Stats bar
    int statsY = infoY + 25;
    int activeCount = 0;
    int installedCount = 0;
    for (int i = 0; i < g_model_count; i++) {
        if (g_models[i].is_installed) {
            installedCount++;
            if (g_models[i].is_enabled) activeCount++;
        }
    }

    char statsText[128];
    snprintf(statsText, sizeof(statsText), GetLanguage() == LANG_TH ?
             "🤖 ติดตั้ง: %d  |  ⚡ เปิดใช้งาน: %d" :
             "🤖 Installed: %d  |  ⚡ Active: %d", installedCount, activeCount);
    DrawText_(hdc, statsText, padding + 10, statsY, COLOR_NEON_GREEN, g_font_normal);

    // Model cards area
    int cardsY = statsY + 35;
    int cardsH = height - cardsY - padding - 30;
    int cardW = width - padding * 2;
    int cardH = 70;
    int cardSpacing = 10;
    int visibleCards = cardsH / (cardH + cardSpacing);

    // Draw model cards
    for (int i = 0; i < g_model_count && i < visibleCards; i++) {
        int idx = i + g_model_scroll_offset;
        if (idx >= g_model_count) break;

        ModelInfo* model = &g_models[idx];
        int cardY = cardsY + i * (cardH + cardSpacing);
        bool isHover = (g_model_hover == idx);

        // Card background
        COLORREF cardBorder = isHover ? COLOR_NEON_PURPLE : COLOR_PANEL_LIGHT;
        Draw3DPanel(hdc, padding, cardY, cardW, cardH, false, isHover, cardBorder);

        // Model icon
        const char* icon = model->is_installed ? "🤖" : "📦";
        DrawText_(hdc, icon, padding + 15, cardY + (cardH - 24) / 2, COLOR_TEXT_BRIGHT, g_font_large);

        // Model name
        DrawText_(hdc, model->name, padding + 50, cardY + 12, COLOR_TEXT_BRIGHT, g_font_normal);

        // Model details
        char details[128];
        float vramGB = model->vram_required_mb / 1024.0f;
        float sizeGB = model->size_mb / 1024.0f;
        snprintf(details, sizeof(details), "💾 %.1fGB  |  🎮 VRAM: %.1fGB  |  📂 %s",
                 sizeGB, vramGB, model->category);
        DrawText_(hdc, details, padding + 50, cardY + 38, COLOR_TEXT_SECONDARY, g_font_small);

        // Right side - Toggle or Download button
        int toggleX = width - padding - 90;
        int toggleY = cardY + (cardH - 32) / 2;

        if (model->is_installed) {
            // Draw Sci-Fi Toggle Switch
            bool toggleHover = isHover;
            DrawSciFiToggle(hdc, toggleX, toggleY, 70, 32, model->is_enabled, toggleHover);

            // Indicator lights (3 dots below toggle)
            int lightY = toggleY + 36;
            int lightSpacing = 12;
            int lightX = toggleX + 20;
            for (int j = 0; j < 3; j++) {
                COLORREF lightColor = model->is_enabled ? COLOR_NEON_GREEN : RGB(74, 74, 74);
                HBRUSH lightBrush = CreateSolidBrush(lightColor);
                HBRUSH oldBrush = (HBRUSH)SelectObject(hdc, lightBrush);
                Ellipse(hdc, lightX + j * lightSpacing, lightY, lightX + j * lightSpacing + 8, lightY + 8);
                SelectObject(hdc, oldBrush);
                DeleteObject(lightBrush);
            }
        } else if (model->is_downloading) {
            // Download progress
            char progText[32];
            snprintf(progText, sizeof(progText), "%d%%", model->download_progress);
            DrawText_(hdc, progText, toggleX + 20, toggleY + 8, COLOR_NEON_BLUE, g_font_normal);
        } else {
            // Download button
            bool dlHover = isHover;
            DrawSpaceButton(hdc, toggleX, toggleY, 70, 32, "⬇️ DL", COLOR_NEON_BLUE, dlHover, false);
        }
    }

    // Scroll indicator
    if (g_model_count > visibleCards) {
        char scrollText[32];
        snprintf(scrollText, sizeof(scrollText), "%d/%d", g_model_scroll_offset + 1, g_model_count);
        DrawText_(hdc, scrollText, width - padding - 60, height - padding - 20, COLOR_TEXT_MUTED, g_font_small);
    }

    // Footer
    int footerY = height - 25;
    DrawText_(hdc, APP_AUTHOR, padding, footerY, COLOR_TEXT_MUTED, g_font_small);
}

// ============================================================================
// NETWORK GRAPH
// ============================================================================
void DrawNetworkGraph(HDC hdc, int x, int y, int w, int h) {
    Draw3DPanel(hdc, x, y, w, h, false, false, 0);

    int padding = 5;
    int titleH = 16;
    int labelW = 30;
    int legendH = 16;
    int graphX = x + padding + labelW;
    int graphY = y + padding + titleH;
    int graphW = w - padding * 2 - labelW - 2;
    int graphH = h - padding * 2 - titleH - legendH - 2;

    // Draw title
    DrawText_(hdc, "NET", x + padding, y + 2, COLOR_TEXT_SECONDARY, g_font_small);

    // Find max value for auto-scaling
    float maxVal = 1.0f;
    for (int i = 0; i < g_net_history_count; i++) {
        if (g_net_up_history[i] > maxVal) maxVal = g_net_up_history[i];
        if (g_net_down_history[i] > maxVal) maxVal = g_net_down_history[i];
    }

    // Round up to nice values for the scale
    float scaleMax;
    const char* unit;
    if (maxVal < 10) {
        scaleMax = 10.0f;
        unit = "KB";
    } else if (maxVal < 100) {
        scaleMax = ((int)(maxVal / 10) + 1) * 10.0f;
        unit = "KB";
    } else if (maxVal < 1024) {
        scaleMax = ((int)(maxVal / 100) + 1) * 100.0f;
        unit = "KB";
    } else if (maxVal < 10240) {
        scaleMax = ((int)(maxVal / 1024) + 1) * 1024.0f;
        unit = "MB";
    } else {
        scaleMax = ((int)(maxVal / 10240) + 1) * 10240.0f;
        unit = "MB";
    }

    // Draw only 2 grid lines (top, bottom)
    for (int i = 0; i <= 2; i++) {
        int lineY = graphY + (graphH * i / 2);
        HPEN gridPen = CreatePen(PS_DOT, 1, RGB(40, 50, 70));
        SelectObject(hdc, gridPen);
        MoveToEx(hdc, graphX, lineY, NULL);
        LineTo(hdc, graphX + graphW, lineY);
        DeleteObject(gridPen);

        // Draw scale label (compact)
        float scaleVal = scaleMax * (2 - i) / 2.0f;
        char label[16];
        if (scaleMax >= 1024) {
            snprintf(label, sizeof(label), "%.0f", scaleVal / 1024.0f);
        } else {
            snprintf(label, sizeof(label), "%.0f", scaleVal);
        }
        SetTextColor(hdc, COLOR_TEXT_MUTED);
        SetBkMode(hdc, TRANSPARENT);
        SelectObject(hdc, g_font_small);
        wchar_t wlabel[16];
        MultiByteToWideChar(CP_UTF8, 0, label, -1, wlabel, 16);
        TextOutW(hdc, x + 2, lineY - 5, wlabel, (int)wcslen(wlabel));
    }

    if (g_net_history_count > 1) {
        int startIdx = (g_net_history_index - g_net_history_count + NET_HISTORY_SIZE) % NET_HISTORY_SIZE;

        // Draw upload line with glow (green)
        for (int glow = 2; glow > 0; glow--) {
            HPEN glowPen = CreatePen(PS_SOLID, 2 + glow * 2, RGB(0, 80 - glow * 20, 40 - glow * 10));
            SelectObject(hdc, glowPen);

            for (int i = 0; i < g_net_history_count - 1; i++) {
                int idx1 = (startIdx + i) % NET_HISTORY_SIZE;
                int idx2 = (startIdx + i + 1) % NET_HISTORY_SIZE;

                int y1 = graphY + graphH - (int)(g_net_up_history[idx1] / scaleMax * graphH);
                int y2 = graphY + graphH - (int)(g_net_up_history[idx2] / scaleMax * graphH);
                y1 = max(graphY, min(graphY + graphH, y1));
                y2 = max(graphY, min(graphY + graphH, y2));

                int x1 = graphX + (i * graphW / (NET_HISTORY_SIZE - 1));
                int x2 = graphX + ((i + 1) * graphW / (NET_HISTORY_SIZE - 1));

                MoveToEx(hdc, x1, y1, NULL);
                LineTo(hdc, x2, y2);
            }
            DeleteObject(glowPen);
        }

        HPEN upPen = CreatePen(PS_SOLID, 2, COLOR_NEON_GREEN);
        SelectObject(hdc, upPen);

        for (int i = 0; i < g_net_history_count - 1; i++) {
            int idx1 = (startIdx + i) % NET_HISTORY_SIZE;
            int idx2 = (startIdx + i + 1) % NET_HISTORY_SIZE;

            int y1 = graphY + graphH - (int)(g_net_up_history[idx1] / scaleMax * graphH);
            int y2 = graphY + graphH - (int)(g_net_up_history[idx2] / scaleMax * graphH);
            y1 = max(graphY, min(graphY + graphH, y1));
            y2 = max(graphY, min(graphY + graphH, y2));

            int x1 = graphX + (i * graphW / (NET_HISTORY_SIZE - 1));
            int x2 = graphX + ((i + 1) * graphW / (NET_HISTORY_SIZE - 1));

            MoveToEx(hdc, x1, y1, NULL);
            LineTo(hdc, x2, y2);
        }
        DeleteObject(upPen);

        // Draw download line with glow (cyan)
        for (int glow = 2; glow > 0; glow--) {
            HPEN glowPen = CreatePen(PS_SOLID, 2 + glow * 2, RGB(0, 60 - glow * 15, 80 - glow * 20));
            SelectObject(hdc, glowPen);

            for (int i = 0; i < g_net_history_count - 1; i++) {
                int idx1 = (startIdx + i) % NET_HISTORY_SIZE;
                int idx2 = (startIdx + i + 1) % NET_HISTORY_SIZE;

                int y1 = graphY + graphH - (int)(g_net_down_history[idx1] / scaleMax * graphH);
                int y2 = graphY + graphH - (int)(g_net_down_history[idx2] / scaleMax * graphH);
                y1 = max(graphY, min(graphY + graphH, y1));
                y2 = max(graphY, min(graphY + graphH, y2));

                int x1 = graphX + (i * graphW / (NET_HISTORY_SIZE - 1));
                int x2 = graphX + ((i + 1) * graphW / (NET_HISTORY_SIZE - 1));

                MoveToEx(hdc, x1, y1, NULL);
                LineTo(hdc, x2, y2);
            }
            DeleteObject(glowPen);
        }

        HPEN downPen = CreatePen(PS_SOLID, 2, COLOR_NEON_CYAN);
        SelectObject(hdc, downPen);

        for (int i = 0; i < g_net_history_count - 1; i++) {
            int idx1 = (startIdx + i) % NET_HISTORY_SIZE;
            int idx2 = (startIdx + i + 1) % NET_HISTORY_SIZE;

            int y1 = graphY + graphH - (int)(g_net_down_history[idx1] / scaleMax * graphH);
            int y2 = graphY + graphH - (int)(g_net_down_history[idx2] / scaleMax * graphH);
            y1 = max(graphY, min(graphY + graphH, y1));
            y2 = max(graphY, min(graphY + graphH, y2));

            int x1 = graphX + (i * graphW / (NET_HISTORY_SIZE - 1));
            int x2 = graphX + ((i + 1) * graphW / (NET_HISTORY_SIZE - 1));

            MoveToEx(hdc, x1, y1, NULL);
            LineTo(hdc, x2, y2);
        }
        DeleteObject(downPen);
    }

    // Legend with current speeds (compact)
    char speedBuf[16];
    int legendY = y + h - 14;

    // Upload - green square + speed
    HBRUSH upBrush = CreateSolidBrush(COLOR_NEON_GREEN);
    RECT upRect = {x + 5, legendY, x + 11, legendY + 6};
    FillRect(hdc, &upRect, upBrush);
    DeleteObject(upBrush);

    System_FormatSpeed(g_system.upload_speed, speedBuf, sizeof(speedBuf));
    DrawText_(hdc, speedBuf, x + 14, legendY - 2, COLOR_NEON_GREEN, g_font_small);

    // Download - cyan square + speed
    HBRUSH downBrush = CreateSolidBrush(COLOR_NEON_CYAN);
    RECT downRect = {x + w/2, legendY, x + w/2 + 6, legendY + 6};
    FillRect(hdc, &downRect, downBrush);
    DeleteObject(downBrush);

    System_FormatSpeed(g_system.download_speed, speedBuf, sizeof(speedBuf));
    DrawText_(hdc, speedBuf, x + w/2 + 9, legendY - 2, COLOR_NEON_CYAN, g_font_small);
}

// ============================================================================
// UPDATE & HIT TESTING
// ============================================================================
void UpdateStats(void) {
    GPU_GetAllInfo(&g_gpu_manager);
    System_GetInfo(&g_system);

    // Update network history
    g_net_up_history[g_net_history_index] = g_system.upload_speed;
    g_net_down_history[g_net_history_index] = g_system.download_speed;
    g_net_history_index = (g_net_history_index + 1) % NET_HISTORY_SIZE;
    if (g_net_history_count < NET_HISTORY_SIZE) {
        g_net_history_count++;
    }

    // Record electricity usage every minute
    uint64_t now = (uint64_t)time(NULL);
    if (g_last_elec_record_time == 0) {
        g_last_elec_record_time = now;
    } else if (now - g_last_elec_record_time >= 60) {  // Every minute
        for (int i = 0; i < g_gpu_manager.gpu_count; i++) {
            if (g_gpu_manager.gpus[i].power_usage > 0) {
                Electricity_RecordUsage(i, (float)g_gpu_manager.gpus[i].power_usage, 1.0f / 60.0f);
            }
        }
        g_last_elec_record_time = now;

        // Update totals
        g_electricity_today_cost = Electricity_GetTodayCost();
        g_electricity_month_cost = Electricity_GetMonthCost();
    }
}

int HitTestLogin(int x, int y, RECT* rect) {
    int width = rect->right - rect->left;
    int height = rect->bottom - rect->top;
    int splitX = width / 2;

    // New layout: Glass card on right side
    int cardW = 320;
    int cardH = 340;
    int cardX = splitX + (splitX - cardW) / 2;
    int cardY = (height - cardH) / 2;

    // Input positions
    int inputX = cardX + 25;
    int inputY = cardY + 95;
    int inputW = cardW - 50;
    int inputH = 42;
    int passY = inputY + inputH + 35;

    // Login button
    int btnW = inputW;
    int btnH = 48;
    int btnX = inputX;
    int btnY = cardY + cardH - btnH - 30;

    if (x >= btnX && x <= btnX + btnW && y >= btnY && y <= btnY + btnH) {
        return ID_BTN_LOGIN;
    }

    // Checkboxes
    int checkY = passY + inputH + 20;
    if (x >= inputX && x <= inputX + 200 && y >= checkY && y <= checkY + 18) {
        return ID_CHECK_REMEMBER;
    }
    if (x >= inputX && x <= inputX + 200 && y >= checkY + 28 && y <= checkY + 46) {
        return ID_CHECK_AUTOSTART;
    }

    // Language toggle (pill style) - new positions
    int langX = width - 100;
    int langY = 20;
    int langW = 80;
    int langH = 32;

    // EN side (left half of pill)
    if (x >= langX && x <= langX + langW/2 && y >= langY && y <= langY + langH) {
        return ID_BTN_LANG_EN;
    }
    // TH side (right half of pill)
    if (x >= langX + langW/2 && x <= langX + langW && y >= langY && y <= langY + langH) {
        return ID_BTN_LANG_TH;
    }

    return 0;
}

int HitTestDashboard(int x, int y, RECT* rect) {
    int width = rect->right - rect->left;
    int padding = 12;

    // Models button
    if (x >= width - padding - 175 && x <= width - padding - 135 && y >= 13 && y <= 37) {
        return ID_BTN_MODELS;
    }

    // Settings button
    if (x >= width - padding - 130 && x <= width - padding - 95 && y >= 13 && y <= 37) {
        return ID_BTN_SETTINGS;
    }

    int btnW = 38, btnH = 24;

    if (x >= width - padding - btnW * 2 - 10 && x <= width - padding - btnW - 15 && y >= 13 && y <= 13 + btnH) {
        return ID_BTN_LANG_EN;
    }
    if (x >= width - padding - btnW - 5 && x <= width - padding && y >= 13 && y <= 13 + btnH) {
        return ID_BTN_LANG_TH;
    }

    int tabsY = 58;
    int tabW = min(130, (width - padding * 2 - 10) / max(1, g_gpu_manager.gpu_count));
    int tabH = 28;

    for (int i = 0; i < g_gpu_manager.gpu_count; i++) {
        int tabX = padding + i * (tabW + 5);
        if (x >= tabX && x <= tabX + tabW && y >= tabsY && y <= tabsY + tabH) {
            return ID_BTN_GPU_BASE + i;
        }
    }

    int leftW = (width - padding * 3) / 2;
    int rightX = padding * 2 + leftW;
    int rightW = leftW;
    int contentY = tabsY + 40;
    int statusH = 70;
    int btnLogoutW = 80, btnLogoutH = 30;
    int btnLogoutX = rightX + rightW - btnLogoutW - 12;
    int btnLogoutY = contentY + (statusH - btnLogoutH) / 2;

    if (x >= btnLogoutX && x <= btnLogoutX + btnLogoutW && y >= btnLogoutY && y <= btnLogoutY + btnLogoutH) {
        return ID_BTN_LOGOUT;
    }

    int ctrlY = contentY + 145 + 8 + 48;
    int toggleX = padding + leftW - 60;
    if (x >= toggleX && x <= toggleX + 50 && y >= ctrlY && y <= ctrlY + 18) {
        return ID_CHECK_AUTO_FAN;
    }

    return 0;
}

int HitTestSlider(int x, int y, RECT* rect, int* sliderType, int* newValue) {
    int width = rect->right - rect->left;
    int padding = 12;
    int leftW = (width - padding * 3) / 2;
    int tabsY = 58;
    int contentY = tabsY + 40;
    int ctrlY = contentY + 145 + 8;

    int sliderY = ctrlY + 30 + 14;
    int sliderX = padding + 15;
    int sliderW = leftW - 30;

    if (y >= sliderY && y <= sliderY + 24 && x >= sliderX && x <= sliderX + sliderW) {
        *sliderType = ID_SLIDER_PERF;
        *newValue = 50 + (x - sliderX) * 50 / sliderW;
        *newValue = max(50, min(100, *newValue));
        return 1;
    }

    if (!g_auto_fan) {
        sliderY = ctrlY + 78 + 22;
        sliderW = leftW - 80;

        if (y >= sliderY && y <= sliderY + 24 && x >= sliderX && x <= sliderX + sliderW) {
            *sliderType = ID_SLIDER_FAN;
            *newValue = 20 + (x - sliderX) * 80 / sliderW;
            *newValue = max(20, min(100, *newValue));
            return 1;
        }
    }

    return 0;
}

int HitTestSettings(int x, int y, RECT* rect) {
    int width = rect->right - rect->left;
    int padding = 20;
    int headerH = 60;

    // Back button
    int btnBackW = 100, btnBackH = 35;
    int btnBackX = width - padding - btnBackW - 15;
    int btnBackY = padding + (headerH - btnBackH) / 2;
    if (x >= btnBackX && x <= btnBackX + btnBackW && y >= btnBackY && y <= btnBackY + btnBackH) {
        return ID_BTN_BACK;
    }

    int contentY = padding + headerH + 15;

    // Language buttons
    int langBtnW = 100, langBtnH = 40;
    int langBtnY = contentY + 55;

    if (x >= padding + 20 && x <= padding + 20 + langBtnW && y >= langBtnY && y <= langBtnY + langBtnH) {
        return ID_BTN_LANG_EN;
    }
    if (x >= padding + 130 && x <= padding + 130 + langBtnW && y >= langBtnY && y <= langBtnY + langBtnH) {
        return ID_BTN_LANG_TH;
    }

    // Auto-start checkbox
    int checkY = langBtnY + langBtnH + 25;
    if (x >= padding + 20 && x <= padding + 20 + 200 && y >= checkY && y <= checkY + 22) {
        return ID_CHECK_AUTOSTART;
    }

    return 0;
}

int HitTestModels(int x, int y, RECT* rect) {
    int width = rect->right - rect->left;
    int height = rect->bottom - rect->top;
    int padding = 20;
    int headerH = 60;

    // Back button
    int btnBackW = 100, btnBackH = 35;
    int btnBackX = width - padding - btnBackW - 15;
    int btnBackY = padding + (headerH - btnBackH) / 2;
    if (x >= btnBackX && x <= btnBackX + btnBackW && y >= btnBackY && y <= btnBackY + btnBackH) {
        return ID_BTN_BACK;
    }

    // Model cards area
    int infoY = padding + headerH + 15;
    int statsY = infoY + 25;
    int cardsY = statsY + 35;
    int cardH = 70;
    int cardSpacing = 10;
    int cardsH = height - cardsY - padding - 30;
    int visibleCards = cardsH / (cardH + cardSpacing);

    // Check which model card is hovered
    g_model_hover = -1;
    for (int i = 0; i < g_model_count && i < visibleCards; i++) {
        int idx = i + g_model_scroll_offset;
        if (idx >= g_model_count) break;

        int cardY = cardsY + i * (cardH + cardSpacing);
        int cardW = width - padding * 2;

        if (x >= padding && x <= padding + cardW && y >= cardY && y <= cardY + cardH) {
            g_model_hover = idx;

            // Check if clicking on toggle area (right side)
            int toggleX = width - padding - 90;
            if (x >= toggleX && x <= toggleX + 70) {
                return ID_MODEL_TOGGLE_BASE + idx;
            }
            break;
        }
    }

    return 0;
}

// ============================================================================
// WINDOW PROCEDURE
// ============================================================================
LRESULT CALLBACK WndProc(HWND hwnd, UINT msg, WPARAM wParam, LPARAM lParam) {
    switch (msg) {
        case WM_CREATE:
            CreateFonts();
            CreateLoginControls(hwnd);
            SetTimer(hwnd, ID_TIMER_UPDATE, UPDATE_INTERVAL_GPU, NULL);
            SetTimer(hwnd, ID_TIMER_ANIMATION, 40, NULL);
            return 0;

        case WM_TIMER:
            if (wParam == ID_TIMER_UPDATE) {
                UpdateStats();
                InvalidateRect(hwnd, NULL, FALSE);
            } else if (wParam == ID_TIMER_ANIMATION) {
                g_anim_tick++;
                RECT rect;
                GetClientRect(hwnd, &rect);
                UpdateStars(rect.right, rect.bottom);
                InvalidateRect(hwnd, NULL, FALSE);
            }
            return 0;

        case WM_COMMAND:
            if (HIWORD(wParam) == EN_SETFOCUS || HIWORD(wParam) == EN_KILLFOCUS) {
                InvalidateRect(hwnd, NULL, FALSE);
            }
            return 0;

        case WM_KEYDOWN:
            if (wParam == VK_RETURN && g_ui_mode == UI_MODE_LOGIN) {
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
            HBITMAP oldBitmap = (HBITMAP)SelectObject(memDC, memBitmap);

            if (g_ui_mode == UI_MODE_LOGIN) {
                DrawLoginScreen(memDC, &rect);
            } else if (g_ui_mode == UI_MODE_DASHBOARD) {
                DrawDashboard(memDC, &rect);
            } else if (g_ui_mode == UI_MODE_SETTINGS) {
                DrawSettingsScreen(memDC, &rect);
            } else if (g_ui_mode == UI_MODE_MODELS) {
                DrawModelsScreen(memDC, &rect);
            }

            BitBlt(hdc, 0, 0, rect.right, rect.bottom, memDC, 0, 0, SRCCOPY);

            SelectObject(memDC, oldBitmap);
            DeleteObject(memBitmap);
            DeleteDC(memDC);

            EndPaint(hwnd, &ps);
            return 0;
        }

        case WM_MOUSEMOVE: {
            RECT rect;
            GetClientRect(hwnd, &rect);
            int mx = GET_X_LPARAM(lParam);
            int my = GET_Y_LPARAM(lParam);
            int newHover;

            if (g_ui_mode == UI_MODE_LOGIN) {
                newHover = HitTestLogin(mx, my, &rect);
            } else if (g_ui_mode == UI_MODE_SETTINGS) {
                newHover = HitTestSettings(mx, my, &rect);
            } else if (g_ui_mode == UI_MODE_MODELS) {
                newHover = HitTestModels(mx, my, &rect);
            } else {
                newHover = HitTestDashboard(mx, my, &rect);

                if (g_dragging_perf || g_dragging_fan) {
                    int sliderType, newValue;
                    if (HitTestSlider(mx, my, &rect, &sliderType, &newValue)) {
                        if (sliderType == ID_SLIDER_PERF) {
                            g_perf_slider_value = newValue;
                        } else if (sliderType == ID_SLIDER_FAN) {
                            g_fan_slider_value = newValue;
                        }
                    }
                }
            }

            if (newHover != g_hover_button) {
                g_hover_button = newHover;
                InvalidateRect(hwnd, NULL, FALSE);
            }
            return 0;
        }

        case WM_LBUTTONDOWN: {
            RECT rect;
            GetClientRect(hwnd, &rect);
            int mx = GET_X_LPARAM(lParam);
            int my = GET_Y_LPARAM(lParam);

            if (g_ui_mode == UI_MODE_LOGIN) {
                int btn = HitTestLogin(mx, my, &rect);
                switch (btn) {
                    case ID_BTN_LOGIN:
                        DoLogin();
                        break;
                    case ID_CHECK_REMEMBER:
                        g_remember_password = !g_remember_password;
                        g_app_settings->remember_password = g_remember_password;
                        Settings_Save(g_app_settings);
                        if (!g_remember_password) {
                            Credentials_Clear();
                        }
                        LOG_INFO(LOG_CAT_SETTINGS, "Remember password: %s", g_remember_password ? "enabled" : "disabled");
                        break;
                    case ID_CHECK_AUTOSTART:
                        if (AutoStart_IsEnabled()) {
                            AutoStart_Disable();
                        } else {
                            AutoStart_Enable();
                        }
                        break;
                    case ID_BTN_LANG_EN:
                        SetLanguage(LANG_EN);
                        g_app_settings->language = LANG_EN;
                        Settings_Save(g_app_settings);
                        break;
                    case ID_BTN_LANG_TH:
                        SetLanguage(LANG_TH);
                        g_app_settings->language = LANG_TH;
                        Settings_Save(g_app_settings);
                        break;
                }
                InvalidateRect(hwnd, NULL, FALSE);
            } else if (g_ui_mode == UI_MODE_SETTINGS) {
                int btn = HitTestSettings(mx, my, &rect);
                switch (btn) {
                    case ID_BTN_BACK:
                        g_ui_mode = UI_MODE_DASHBOARD;
                        break;
                    case ID_BTN_LANG_EN:
                        SetLanguage(LANG_EN);
                        g_app_settings->language = LANG_EN;
                        Settings_Save(g_app_settings);
                        break;
                    case ID_BTN_LANG_TH:
                        SetLanguage(LANG_TH);
                        g_app_settings->language = LANG_TH;
                        Settings_Save(g_app_settings);
                        break;
                    case ID_CHECK_AUTOSTART:
                        g_app_settings->auto_start_enabled = !g_app_settings->auto_start_enabled;
                        if (g_app_settings->auto_start_enabled) {
                            AutoStart_Enable();
                        } else {
                            AutoStart_Disable();
                        }
                        Settings_Save(g_app_settings);
                        break;
                }
                InvalidateRect(hwnd, NULL, FALSE);
            } else if (g_ui_mode == UI_MODE_MODELS) {
                int btn = HitTestModels(mx, my, &rect);
                switch (btn) {
                    case ID_BTN_BACK:
                        g_ui_mode = UI_MODE_DASHBOARD;
                        break;
                    default:
                        // Handle model toggle clicks
                        if (btn >= ID_MODEL_TOGGLE_BASE && btn < ID_MODEL_TOGGLE_BASE + MAX_MODELS) {
                            int modelIdx = btn - ID_MODEL_TOGGLE_BASE;
                            if (modelIdx < g_model_count && g_models[modelIdx].is_installed) {
                                g_models[modelIdx].is_enabled = !g_models[modelIdx].is_enabled;
                                LOG_INFO(LOG_CAT_SETTINGS, "Model %s: %s",
                                         g_models[modelIdx].name,
                                         g_models[modelIdx].is_enabled ? "ENABLED" : "DISABLED");
                            }
                        }
                        break;
                }
                InvalidateRect(hwnd, NULL, FALSE);
            } else {
                int sliderType, newValue;
                if (HitTestSlider(mx, my, &rect, &sliderType, &newValue)) {
                    if (sliderType == ID_SLIDER_PERF) {
                        g_dragging_perf = true;
                        g_perf_slider_value = newValue;
                        SetCapture(hwnd);
                    } else if (sliderType == ID_SLIDER_FAN) {
                        g_dragging_fan = true;
                        g_fan_slider_value = newValue;
                        SetCapture(hwnd);
                    }
                    InvalidateRect(hwnd, NULL, FALSE);
                    return 0;
                }

                int btn = HitTestDashboard(mx, my, &rect);
                switch (btn) {
                    case ID_BTN_LANG_EN:
                        SetLanguage(LANG_EN);
                        g_app_settings->language = LANG_EN;
                        Settings_Save(g_app_settings);
                        break;
                    case ID_BTN_LANG_TH:
                        SetLanguage(LANG_TH);
                        g_app_settings->language = LANG_TH;
                        Settings_Save(g_app_settings);
                        break;
                    case ID_BTN_LOGOUT:
                        LOG_INFO(LOG_CAT_AUTH, "User logged out");
                        Worker_Stop(&g_worker);
                        g_ui_mode = UI_MODE_LOGIN;
                        ShowLoginControls();
                        break;
                    case ID_BTN_MODELS:
                        g_ui_mode = UI_MODE_MODELS;
                        break;
                    case ID_BTN_SETTINGS:
                        g_ui_mode = UI_MODE_SETTINGS;
                        break;
                    case ID_CHECK_AUTO_FAN:
                        g_auto_fan = !g_auto_fan;
                        break;
                    default:
                        if (btn >= ID_BTN_GPU_BASE && btn < ID_BTN_GPU_BASE + MAX_GPUS) {
                            g_selected_gpu = btn - ID_BTN_GPU_BASE;
                            LOG_INFO(LOG_CAT_GPU, "Selected GPU %d", g_selected_gpu);
                        }
                        break;
                }
                InvalidateRect(hwnd, NULL, FALSE);
            }
            return 0;
        }

        case WM_LBUTTONUP:
            if (g_dragging_perf) {
                g_dragging_perf = false;
                ReleaseCapture();
                GPU_SetPerformanceLimit(g_selected_gpu, g_perf_slider_value);
                LOG_INFO(LOG_CAT_GPU, "GPU %d performance limit set to %d%%", g_selected_gpu, g_perf_slider_value);
            }
            if (g_dragging_fan) {
                g_dragging_fan = false;
                ReleaseCapture();
                if (!g_auto_fan) {
                    GPU_SetFanSpeed(g_selected_gpu, g_fan_slider_value);
                    LOG_INFO(LOG_CAT_GPU, "GPU %d fan speed set to %d%%", g_selected_gpu, g_fan_slider_value);
                }
            }
            InvalidateRect(hwnd, NULL, FALSE);
            return 0;

        case WM_GETMINMAXINFO: {
            MINMAXINFO* mmi = (MINMAXINFO*)lParam;
            mmi->ptMinTrackSize.x = WINDOW_MIN_WIDTH;
            mmi->ptMinTrackSize.y = WINDOW_MIN_HEIGHT;
            return 0;
        }

        case WM_SIZE:
            RepositionLoginControls();
            InvalidateRect(hwnd, NULL, FALSE);
            return 0;

        case WM_DESTROY:
            KillTimer(hwnd, ID_TIMER_UPDATE);
            KillTimer(hwnd, ID_TIMER_ANIMATION);
            PostQuitMessage(0);
            return 0;

        case WM_CTLCOLOREDIT: {
            HDC hdcEdit = (HDC)wParam;
            SetTextColor(hdcEdit, COLOR_TEXT_PRIMARY);
            SetBkColor(hdcEdit, COLOR_SPACE_BLACK);
            static HBRUSH hBrushEdit = NULL;
            if (!hBrushEdit) hBrushEdit = CreateSolidBrush(COLOR_SPACE_BLACK);
            return (LRESULT)hBrushEdit;
        }
    }

    return DefWindowProcW(hwnd, msg, wParam, lParam);
}

// ============================================================================
// ENTRY POINT
// ============================================================================
int WINAPI wWinMain(HINSTANCE hInstance, HINSTANCE hPrevInstance, LPWSTR lpCmdLine, int nCmdShow) {
    // Check command line for --minimized
    if (lpCmdLine && wcsstr(lpCmdLine, L"--minimized")) {
        g_start_minimized = true;
    }

    INITCOMMONCONTROLSEX icex;
    icex.dwSize = sizeof(icex);
    icex.dwICC = ICC_WIN95_CLASSES | ICC_STANDARD_CLASSES;
    InitCommonControlsEx(&icex);

    App_Init();

    WNDCLASSEXW wc = {0};
    wc.cbSize = sizeof(wc);
    wc.style = CS_HREDRAW | CS_VREDRAW;
    wc.lpfnWndProc = WndProc;
    wc.hInstance = hInstance;
    wc.hCursor = LoadCursor(NULL, IDC_ARROW);
    wc.hbrBackground = CreateSolidBrush(COLOR_SPACE_BLACK);
    wc.lpszClassName = L"GPUShareClient";
    wc.hIcon = LoadIcon(NULL, IDI_APPLICATION);
    wc.hIconSm = LoadIcon(NULL, IDI_APPLICATION);

    if (!RegisterClassExW(&wc)) {
        MessageBoxW(NULL, L"Failed to register window class", L"Error", MB_ICONERROR);
        return 1;
    }

    int screenW = GetSystemMetrics(SM_CXSCREEN);
    int screenH = GetSystemMetrics(SM_CYSCREEN);
    int winX = (screenW - WINDOW_WIDTH) / 2;
    int winY = (screenH - WINDOW_HEIGHT) / 2;

    // Use saved window position if available
    if (g_app_settings->window_x >= 0 && g_app_settings->window_y >= 0) {
        winX = g_app_settings->window_x;
        winY = g_app_settings->window_y;
    }

    g_hwnd = CreateWindowExW(
        WS_EX_LAYERED,
        L"GPUShareClient",
        L"GPU Share - Command Center",
        WS_OVERLAPPEDWINDOW,
        winX, winY, WINDOW_WIDTH, WINDOW_HEIGHT,
        NULL, NULL, hInstance, NULL
    );

    if (!g_hwnd) {
        MessageBoxW(NULL, L"Failed to create window", L"Error", MB_ICONERROR);
        return 1;
    }

    SetLayeredWindowAttributes(g_hwnd, 0, 255, LWA_ALPHA);

    ShowWindow(g_hwnd, g_start_minimized ? SW_MINIMIZE : nCmdShow);
    UpdateWindow(g_hwnd);

    MSG msg;
    while (GetMessage(&msg, NULL, 0, 0)) {
        if (msg.message == WM_KEYDOWN && msg.wParam == VK_RETURN) {
            if (g_ui_mode == UI_MODE_LOGIN) {
                DoLogin();
                continue;
            }
        }

        if (!IsDialogMessage(g_hwnd, &msg)) {
            TranslateMessage(&msg);
            DispatchMessage(&msg);
        }
    }

    // Save window position
    RECT windowRect;
    if (GetWindowRect(g_hwnd, &windowRect)) {
        g_app_settings->window_x = windowRect.left;
        g_app_settings->window_y = windowRect.top;
        Settings_Save(g_app_settings);
    }

    App_Cleanup();
    return (int)msg.wParam;
}
