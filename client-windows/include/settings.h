/**
 * GPU Share Client - Settings Manager
 * Auto-start, Remember password, Electricity rates
 * Copyright (c) 2024 Xman Studio Thailand
 */

#ifndef SETTINGS_H
#define SETTINGS_H

#include "common.h"

// Settings file path (in AppData)
#define SETTINGS_FILENAME       "gpu_share_settings.dat"
#define CREDENTIALS_FILENAME    "gpu_share_cred.dat"

// Thai Electricity Rates (สำหรับบ้านที่อยู่อาศัย ประเภท 1)
// อัตราค่าไฟฟ้าประเภทที่ 1 บ้านอยู่อาศัย
typedef struct {
    float rate_0_150;       // หน่วยที่ 1-150: 3.2484 บาท/หน่วย
    float rate_151_400;     // หน่วยที่ 151-400: 4.2218 บาท/หน่วย
    float rate_401_plus;    // หน่วยที่ 401 ขึ้นไป: 4.4217 บาท/หน่วย
    float ft_rate;          // ค่า Ft (ปรับตามช่วงเวลา)
    float vat_percent;      // VAT 7%
    float service_charge;   // ค่าบริการรายเดือน (38.22 บาท)
} ThaiElectricityRates;

// Default Thai electricity rates (2024)
#define DEFAULT_RATE_0_150      3.2484f
#define DEFAULT_RATE_151_400    4.2218f
#define DEFAULT_RATE_401_PLUS   4.4217f
#define DEFAULT_FT_RATE         0.0f        // Ft อาจเป็นบวกหรือลบ
#define DEFAULT_VAT_PERCENT     7.0f
#define DEFAULT_SERVICE_CHARGE  38.22f

// App Settings structure
typedef struct {
    // Version for compatibility
    uint32_t version;

    // Auto-start settings
    bool auto_start_enabled;
    bool start_minimized;
    bool minimize_to_tray;

    // Remember login
    bool remember_password;

    // Language
    int language;   // 0 = EN, 1 = TH

    // GPU Defaults
    int default_performance_limit;  // 50-100%
    int default_temp_limit;         // 70-95C
    bool default_auto_fan;
    int default_fan_speed;          // 20-100%

    // Electricity settings
    ThaiElectricityRates electricity_rates;
    float electricity_cost_per_kwh_override;  // ถ้า > 0 ใช้ค่านี้แทน
    bool use_custom_rate;

    // Logging
    bool logging_enabled;
    int log_retention_days;         // จำนวนวันที่เก็บ log

    // Window position
    int window_x;
    int window_y;
    int window_width;
    int window_height;

} AppSettings;

// Saved credentials (encrypted with simple XOR for basic protection)
typedef struct {
    char email[256];
    char password[256];     // Encrypted
    char auth_token[512];   // For auto-login
    uint64_t last_login;
} SavedCredentials;

// Electricity usage tracking
typedef struct {
    uint64_t timestamp;         // Unix timestamp
    int gpu_index;
    float power_watts;          // Average power during interval
    float duration_hours;       // Duration in hours
    float kwh_used;             // kWh consumed
    float cost_thb;             // Cost in Thai Baht
} ElectricityRecord;

// Daily electricity summary
typedef struct {
    int year;
    int month;
    int day;
    float total_kwh;
    float total_cost_thb;
    float avg_power_watts;
    float hours_active;
    int gpu_count;
} DailyElectricitySummary;

// Monthly summary
typedef struct {
    int year;
    int month;
    float total_kwh;
    float total_cost_thb;
    float earnings_thb;
    float net_profit_thb;       // earnings - electricity cost
    int days_active;
} MonthlyElectricitySummary;

// ============================================================================
// FUNCTION DECLARATIONS
// ============================================================================

// Settings management
bool Settings_Init(void);
void Settings_Cleanup(void);
bool Settings_Load(AppSettings* settings);
bool Settings_Save(const AppSettings* settings);
void Settings_SetDefaults(AppSettings* settings);
AppSettings* Settings_Get(void);

// Auto-start management (Windows Registry)
bool AutoStart_Enable(void);
bool AutoStart_Disable(void);
bool AutoStart_IsEnabled(void);

// Credentials management
bool Credentials_Save(const char* email, const char* password, bool remember);
bool Credentials_Load(char* email, size_t email_size, char* password, size_t password_size);
bool Credentials_Clear(void);
bool Credentials_HasSaved(void);

// Electricity calculation
float Electricity_CalculateCost(float kwh, const ThaiElectricityRates* rates);
float Electricity_CalculateCostSimple(float kwh, float rate_per_kwh);
float Electricity_WattsToKWH(float watts, float hours);
float Electricity_EstimateMonthlyCost(float avg_watts, float hours_per_day);
void Electricity_GetDefaultRates(ThaiElectricityRates* rates);

// Electricity tracking
bool Electricity_RecordUsage(int gpu_index, float power_watts, float duration_hours);
bool Electricity_GetDailySummary(int year, int month, int day, DailyElectricitySummary* summary);
bool Electricity_GetMonthlySummary(int year, int month, MonthlyElectricitySummary* summary);
float Electricity_GetTodayCost(void);
float Electricity_GetMonthCost(void);

// Path helpers
bool GetAppDataPath(char* path, size_t size);
bool GetLogPath(char* path, size_t size);

#endif // SETTINGS_H
