/**
 * GPU Share Client - Settings Manager Implementation
 * Auto-start, Remember password, Electricity rates
 * Copyright (c) 2024 Xman Studio Thailand
 */

#include "../include/settings.h"
#include "../include/logger.h"
#include <windows.h>
#include <shlobj.h>
#include <stdio.h>
#include <string.h>
#include <time.h>

// ============================================================================
// PRIVATE DATA
// ============================================================================
static AppSettings g_settings = {0};
static bool g_initialized = false;
static char g_app_data_path[MAX_PATH] = {0};
static char g_log_path[MAX_PATH] = {0};

// Simple XOR key for basic credential obfuscation (not secure encryption!)
static const char XOR_KEY[] = "GPU$h@r3_X7m4n_2024!Th41l@nd";

// Registry key for auto-start
#define AUTOSTART_REG_KEY   "SOFTWARE\\Microsoft\\Windows\\CurrentVersion\\Run"
#define AUTOSTART_VALUE_NAME "GPUShareClient"

// Settings file version
#define SETTINGS_VERSION    1

// ============================================================================
// PATH HELPERS
// ============================================================================
bool GetAppDataPath(char* path, size_t size) {
    if (!path || size == 0) return false;

    char appdata[MAX_PATH];
    if (SUCCEEDED(SHGetFolderPathA(NULL, CSIDL_LOCAL_APPDATA, NULL, 0, appdata))) {
        snprintf(path, size, "%s\\GPUShare", appdata);

        // Create directory if not exists
        CreateDirectoryA(path, NULL);
        return true;
    }
    return false;
}

bool GetLogPath(char* path, size_t size) {
    if (!path || size == 0) return false;

    char appdata[MAX_PATH];
    if (GetAppDataPath(appdata, sizeof(appdata))) {
        snprintf(path, size, "%s\\logs", appdata);
        CreateDirectoryA(path, NULL);
        return true;
    }
    return false;
}

// ============================================================================
// ENCRYPTION HELPERS (Simple XOR - NOT secure, just basic obfuscation)
// ============================================================================
static void XorEncrypt(const char* input, char* output, size_t len) {
    size_t key_len = strlen(XOR_KEY);
    for (size_t i = 0; i < len; i++) {
        output[i] = input[i] ^ XOR_KEY[i % key_len];
    }
}

static void XorDecrypt(const char* input, char* output, size_t len) {
    // XOR is symmetric
    XorEncrypt(input, output, len);
}

// ============================================================================
// SETTINGS MANAGEMENT
// ============================================================================
void Settings_SetDefaults(AppSettings* settings) {
    if (!settings) return;

    memset(settings, 0, sizeof(AppSettings));

    settings->version = SETTINGS_VERSION;

    // Auto-start defaults
    settings->auto_start_enabled = false;
    settings->start_minimized = false;
    settings->minimize_to_tray = true;

    // Remember login
    settings->remember_password = false;

    // Language (0 = EN, 1 = TH)
    settings->language = 1;  // Default Thai

    // GPU defaults
    settings->default_performance_limit = 100;
    settings->default_temp_limit = 85;
    settings->default_auto_fan = true;
    settings->default_fan_speed = 60;

    // Electricity rates (Thai 2024)
    Electricity_GetDefaultRates(&settings->electricity_rates);
    settings->electricity_cost_per_kwh_override = 0;
    settings->use_custom_rate = false;

    // Logging
    settings->logging_enabled = true;
    settings->log_retention_days = 30;

    // Window (use defaults)
    settings->window_x = -1;
    settings->window_y = -1;
    settings->window_width = 0;
    settings->window_height = 0;
}

bool Settings_Init(void) {
    if (g_initialized) return true;

    // Get app data path
    if (!GetAppDataPath(g_app_data_path, sizeof(g_app_data_path))) {
        return false;
    }

    // Get log path
    GetLogPath(g_log_path, sizeof(g_log_path));

    // Load or create default settings
    if (!Settings_Load(&g_settings)) {
        Settings_SetDefaults(&g_settings);
        Settings_Save(&g_settings);
    }

    g_initialized = true;
    return true;
}

void Settings_Cleanup(void) {
    if (!g_initialized) return;

    Settings_Save(&g_settings);
    g_initialized = false;
}

bool Settings_Load(AppSettings* settings) {
    if (!settings) return false;

    char filepath[MAX_PATH];
    snprintf(filepath, sizeof(filepath), "%s\\%s", g_app_data_path, SETTINGS_FILENAME);

    FILE* file = fopen(filepath, "rb");
    if (!file) {
        return false;
    }

    size_t read = fread(settings, sizeof(AppSettings), 1, file);
    fclose(file);

    if (read != 1 || settings->version != SETTINGS_VERSION) {
        // Version mismatch, use defaults but keep some settings
        bool auto_start = settings->auto_start_enabled;
        bool remember = settings->remember_password;
        int lang = settings->language;

        Settings_SetDefaults(settings);

        settings->auto_start_enabled = auto_start;
        settings->remember_password = remember;
        settings->language = lang;
    }

    return true;
}

bool Settings_Save(const AppSettings* settings) {
    if (!settings) return false;

    char filepath[MAX_PATH];
    snprintf(filepath, sizeof(filepath), "%s\\%s", g_app_data_path, SETTINGS_FILENAME);

    FILE* file = fopen(filepath, "wb");
    if (!file) {
        return false;
    }

    size_t written = fwrite(settings, sizeof(AppSettings), 1, file);
    fclose(file);

    return written == 1;
}

AppSettings* Settings_Get(void) {
    if (!g_initialized) {
        Settings_Init();
    }
    return &g_settings;
}

// ============================================================================
// AUTO-START MANAGEMENT (Windows Registry)
// ============================================================================
bool AutoStart_Enable(void) {
    HKEY hKey;
    LONG result = RegOpenKeyExA(HKEY_CURRENT_USER, AUTOSTART_REG_KEY, 0, KEY_SET_VALUE, &hKey);

    if (result != ERROR_SUCCESS) {
        LOG_ERROR(LOG_CAT_SETTINGS, "Failed to open registry key for auto-start");
        return false;
    }

    // Get executable path
    char exePath[MAX_PATH];
    GetModuleFileNameA(NULL, exePath, MAX_PATH);

    // Add quotes and minimized flag
    char value[MAX_PATH + 32];
    snprintf(value, sizeof(value), "\"%s\" --minimized", exePath);

    result = RegSetValueExA(hKey, AUTOSTART_VALUE_NAME, 0, REG_SZ,
                            (BYTE*)value, (DWORD)(strlen(value) + 1));

    RegCloseKey(hKey);

    if (result == ERROR_SUCCESS) {
        LOG_INFO(LOG_CAT_SETTINGS, "Auto-start enabled");
        g_settings.auto_start_enabled = true;
        Settings_Save(&g_settings);
        return true;
    }

    LOG_ERROR(LOG_CAT_SETTINGS, "Failed to set auto-start registry value");
    return false;
}

bool AutoStart_Disable(void) {
    HKEY hKey;
    LONG result = RegOpenKeyExA(HKEY_CURRENT_USER, AUTOSTART_REG_KEY, 0, KEY_SET_VALUE, &hKey);

    if (result != ERROR_SUCCESS) {
        return false;
    }

    result = RegDeleteValueA(hKey, AUTOSTART_VALUE_NAME);
    RegCloseKey(hKey);

    if (result == ERROR_SUCCESS || result == ERROR_FILE_NOT_FOUND) {
        LOG_INFO(LOG_CAT_SETTINGS, "Auto-start disabled");
        g_settings.auto_start_enabled = false;
        Settings_Save(&g_settings);
        return true;
    }

    return false;
}

bool AutoStart_IsEnabled(void) {
    HKEY hKey;
    LONG result = RegOpenKeyExA(HKEY_CURRENT_USER, AUTOSTART_REG_KEY, 0, KEY_READ, &hKey);

    if (result != ERROR_SUCCESS) {
        return false;
    }

    char value[MAX_PATH];
    DWORD size = sizeof(value);
    result = RegQueryValueExA(hKey, AUTOSTART_VALUE_NAME, NULL, NULL, (BYTE*)value, &size);
    RegCloseKey(hKey);

    return result == ERROR_SUCCESS;
}

// ============================================================================
// CREDENTIALS MANAGEMENT
// ============================================================================
bool Credentials_Save(const char* email, const char* password, bool remember) {
    if (!email || !password) return false;

    SavedCredentials creds = {0};
    strncpy(creds.email, email, sizeof(creds.email) - 1);

    // Encrypt password
    char encrypted[256] = {0};
    size_t pass_len = strlen(password);
    XorEncrypt(password, encrypted, pass_len);
    memcpy(creds.password, encrypted, pass_len);

    creds.last_login = (uint64_t)time(NULL);

    char filepath[MAX_PATH];
    snprintf(filepath, sizeof(filepath), "%s\\%s", g_app_data_path, CREDENTIALS_FILENAME);

    FILE* file = fopen(filepath, "wb");
    if (!file) {
        LOG_ERROR(LOG_CAT_AUTH, "Failed to save credentials");
        return false;
    }

    fwrite(&creds, sizeof(SavedCredentials), 1, file);
    fclose(file);

    g_settings.remember_password = remember;
    Settings_Save(&g_settings);

    LOG_INFO(LOG_CAT_AUTH, "Credentials saved for: %s", email);
    return true;
}

bool Credentials_Load(char* email, size_t email_size, char* password, size_t password_size) {
    if (!email || !password) return false;

    char filepath[MAX_PATH];
    snprintf(filepath, sizeof(filepath), "%s\\%s", g_app_data_path, CREDENTIALS_FILENAME);

    FILE* file = fopen(filepath, "rb");
    if (!file) {
        return false;
    }

    SavedCredentials creds = {0};
    size_t read = fread(&creds, sizeof(SavedCredentials), 1, file);
    fclose(file);

    if (read != 1) {
        return false;
    }

    strncpy(email, creds.email, email_size - 1);

    // Decrypt password
    char decrypted[256] = {0};
    XorDecrypt(creds.password, decrypted, strlen(creds.password));
    strncpy(password, decrypted, password_size - 1);

    // Clear sensitive data from memory
    memset(&creds, 0, sizeof(creds));
    memset(decrypted, 0, sizeof(decrypted));

    LOG_INFO(LOG_CAT_AUTH, "Credentials loaded for: %s", email);
    return true;
}

bool Credentials_Clear(void) {
    char filepath[MAX_PATH];
    snprintf(filepath, sizeof(filepath), "%s\\%s", g_app_data_path, CREDENTIALS_FILENAME);

    if (DeleteFileA(filepath) || GetLastError() == ERROR_FILE_NOT_FOUND) {
        g_settings.remember_password = false;
        Settings_Save(&g_settings);
        LOG_INFO(LOG_CAT_AUTH, "Credentials cleared");
        return true;
    }

    return false;
}

bool Credentials_HasSaved(void) {
    char filepath[MAX_PATH];
    snprintf(filepath, sizeof(filepath), "%s\\%s", g_app_data_path, CREDENTIALS_FILENAME);

    DWORD attrs = GetFileAttributesA(filepath);
    return attrs != INVALID_FILE_ATTRIBUTES;
}

// ============================================================================
// ELECTRICITY CALCULATION (Thai Rates)
// ============================================================================
void Electricity_GetDefaultRates(ThaiElectricityRates* rates) {
    if (!rates) return;

    rates->rate_0_150 = DEFAULT_RATE_0_150;
    rates->rate_151_400 = DEFAULT_RATE_151_400;
    rates->rate_401_plus = DEFAULT_RATE_401_PLUS;
    rates->ft_rate = DEFAULT_FT_RATE;
    rates->vat_percent = DEFAULT_VAT_PERCENT;
    rates->service_charge = DEFAULT_SERVICE_CHARGE;
}

float Electricity_CalculateCost(float kwh, const ThaiElectricityRates* rates) {
    if (!rates || kwh <= 0) return 0;

    float cost = 0;

    // คำนวณค่าไฟแบบขั้นบันได
    if (kwh <= 150) {
        cost = kwh * rates->rate_0_150;
    } else if (kwh <= 400) {
        cost = 150 * rates->rate_0_150;
        cost += (kwh - 150) * rates->rate_151_400;
    } else {
        cost = 150 * rates->rate_0_150;
        cost += 250 * rates->rate_151_400;  // 151-400 = 250 units
        cost += (kwh - 400) * rates->rate_401_plus;
    }

    // เพิ่มค่า Ft
    cost += kwh * rates->ft_rate;

    // เพิ่มค่าบริการ
    cost += rates->service_charge;

    // เพิ่ม VAT
    cost *= (1 + rates->vat_percent / 100);

    return cost;
}

float Electricity_CalculateCostSimple(float kwh, float rate_per_kwh) {
    if (rate_per_kwh <= 0 || kwh <= 0) return 0;
    return kwh * rate_per_kwh;
}

float Electricity_WattsToKWH(float watts, float hours) {
    return (watts * hours) / 1000.0f;
}

float Electricity_EstimateMonthlyCost(float avg_watts, float hours_per_day) {
    // คำนวณค่าไฟต่อเดือน (30 วัน)
    float kwh_per_month = Electricity_WattsToKWH(avg_watts, hours_per_day * 30);

    AppSettings* settings = Settings_Get();
    if (settings->use_custom_rate && settings->electricity_cost_per_kwh_override > 0) {
        return Electricity_CalculateCostSimple(kwh_per_month, settings->electricity_cost_per_kwh_override);
    }

    return Electricity_CalculateCost(kwh_per_month, &settings->electricity_rates);
}

// ============================================================================
// ELECTRICITY TRACKING
// ============================================================================
static char g_electricity_log_path[MAX_PATH] = {0};

static void GetElectricityLogPath(int year, int month, char* path, size_t size) {
    char base_path[MAX_PATH];
    GetAppDataPath(base_path, sizeof(base_path));
    snprintf(path, size, "%s\\electricity_%04d%02d.dat", base_path, year, month);
}

bool Electricity_RecordUsage(int gpu_index, float power_watts, float duration_hours) {
    if (power_watts <= 0 || duration_hours <= 0) return false;

    ElectricityRecord record = {0};
    record.timestamp = (uint64_t)time(NULL);
    record.gpu_index = gpu_index;
    record.power_watts = power_watts;
    record.duration_hours = duration_hours;
    record.kwh_used = Electricity_WattsToKWH(power_watts, duration_hours);

    AppSettings* settings = Settings_Get();
    if (settings->use_custom_rate && settings->electricity_cost_per_kwh_override > 0) {
        record.cost_thb = Electricity_CalculateCostSimple(record.kwh_used, settings->electricity_cost_per_kwh_override);
    } else {
        // ใช้ค่าเฉลี่ยประมาณ 4 บาท/หน่วยสำหรับการบันทึกแบบ realtime
        record.cost_thb = record.kwh_used * 4.0f;
    }

    // Get current date
    time_t now = time(NULL);
    struct tm* tm_info = localtime(&now);

    char filepath[MAX_PATH];
    GetElectricityLogPath(tm_info->tm_year + 1900, tm_info->tm_mon + 1, filepath, sizeof(filepath));

    FILE* file = fopen(filepath, "ab");
    if (!file) {
        return false;
    }

    fwrite(&record, sizeof(ElectricityRecord), 1, file);
    fclose(file);

    return true;
}

float Electricity_GetTodayCost(void) {
    time_t now = time(NULL);
    struct tm* tm_info = localtime(&now);

    DailyElectricitySummary summary = {0};
    if (Electricity_GetDailySummary(tm_info->tm_year + 1900, tm_info->tm_mon + 1, tm_info->tm_mday, &summary)) {
        return summary.total_cost_thb;
    }
    return 0;
}

float Electricity_GetMonthCost(void) {
    time_t now = time(NULL);
    struct tm* tm_info = localtime(&now);

    MonthlyElectricitySummary summary = {0};
    if (Electricity_GetMonthlySummary(tm_info->tm_year + 1900, tm_info->tm_mon + 1, &summary)) {
        return summary.total_cost_thb;
    }
    return 0;
}

bool Electricity_GetDailySummary(int year, int month, int day, DailyElectricitySummary* summary) {
    if (!summary) return false;

    memset(summary, 0, sizeof(DailyElectricitySummary));
    summary->year = year;
    summary->month = month;
    summary->day = day;

    char filepath[MAX_PATH];
    GetElectricityLogPath(year, month, filepath, sizeof(filepath));

    FILE* file = fopen(filepath, "rb");
    if (!file) return false;

    // Get start and end timestamps for the day
    struct tm start_tm = {0};
    start_tm.tm_year = year - 1900;
    start_tm.tm_mon = month - 1;
    start_tm.tm_mday = day;
    time_t start_time = mktime(&start_tm);
    time_t end_time = start_time + 86400;  // +24 hours

    ElectricityRecord record;
    float total_power = 0;
    int count = 0;

    while (fread(&record, sizeof(ElectricityRecord), 1, file) == 1) {
        if (record.timestamp >= (uint64_t)start_time && record.timestamp < (uint64_t)end_time) {
            summary->total_kwh += record.kwh_used;
            summary->total_cost_thb += record.cost_thb;
            summary->hours_active += record.duration_hours;
            total_power += record.power_watts * record.duration_hours;
            count++;
        }
    }

    fclose(file);

    if (summary->hours_active > 0) {
        summary->avg_power_watts = total_power / summary->hours_active;
    }

    return count > 0;
}

bool Electricity_GetMonthlySummary(int year, int month, MonthlyElectricitySummary* summary) {
    if (!summary) return false;

    memset(summary, 0, sizeof(MonthlyElectricitySummary));
    summary->year = year;
    summary->month = month;

    char filepath[MAX_PATH];
    GetElectricityLogPath(year, month, filepath, sizeof(filepath));

    FILE* file = fopen(filepath, "rb");
    if (!file) return false;

    ElectricityRecord record;
    int days_with_data[32] = {0};

    while (fread(&record, sizeof(ElectricityRecord), 1, file) == 1) {
        summary->total_kwh += record.kwh_used;

        // Track unique days
        time_t ts = (time_t)record.timestamp;
        struct tm* tm_info = localtime(&ts);
        if (tm_info->tm_mday >= 1 && tm_info->tm_mday <= 31) {
            days_with_data[tm_info->tm_mday] = 1;
        }
    }

    fclose(file);

    // Count active days
    for (int i = 1; i <= 31; i++) {
        if (days_with_data[i]) summary->days_active++;
    }

    // Calculate total cost using tiered rates
    AppSettings* settings = Settings_Get();
    if (settings->use_custom_rate && settings->electricity_cost_per_kwh_override > 0) {
        summary->total_cost_thb = Electricity_CalculateCostSimple(summary->total_kwh, settings->electricity_cost_per_kwh_override);
    } else {
        summary->total_cost_thb = Electricity_CalculateCost(summary->total_kwh, &settings->electricity_rates);
    }

    return true;
}
