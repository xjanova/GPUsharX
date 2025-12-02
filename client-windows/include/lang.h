/**
 * GPU Share Client - Localization Header
 * Thai and English language support
 */

#ifndef LANG_H
#define LANG_H

typedef enum {
    LANG_EN = 0,
    LANG_TH = 1
} Language;

// String IDs
typedef enum {
    STR_APP_TITLE = 0,
    STR_DASHBOARD,
    STR_GPU_STATUS,
    STR_SYSTEM_STATUS,
    STR_EARNINGS,
    STR_SETTINGS,

    // GPU Info
    STR_TEMPERATURE,
    STR_POWER_USAGE,
    STR_MEMORY_USAGE,
    STR_FAN_SPEED,
    STR_GPU_LOAD,
    STR_GPU_NAME,

    // System Info
    STR_CPU_USAGE,
    STR_RAM_USAGE,
    STR_NETWORK,
    STR_UPLOAD,
    STR_DOWNLOAD,

    // Job Status
    STR_STATUS,
    STR_IDLE,
    STR_RECEIVING,
    STR_PROCESSING,
    STR_UPLOADING,
    STR_COMPLETED,
    STR_FAILED,
    STR_WAITING_JOB,

    // Earnings
    STR_TODAY_EARNINGS,
    STR_TOTAL_EARNINGS,
    STR_PENDING_EARNINGS,
    STR_JOBS_COMPLETED,
    STR_JOBS_FAILED,
    STR_UPTIME,

    // Buttons
    STR_START,
    STR_STOP,
    STR_LOGIN,
    STR_LOGOUT,
    STR_SAVE,
    STR_CANCEL,

    // Settings
    STR_API_KEY,
    STR_SERVER_URL,
    STR_TEMP_LIMIT,
    STR_POWER_LIMIT,
    STR_AUTO_START,
    STR_MINIMIZE_TRAY,
    STR_LANGUAGE,

    // Messages
    STR_CONNECTING,
    STR_CONNECTED,
    STR_DISCONNECTED,
    STR_ERROR_CONNECTION,
    STR_ERROR_GPU,
    STR_CHECK_INTERNET,
    STR_CHECK_GPU,
    STR_NO_OVERCLOCK,

    // Misc
    STR_VERSION,
    STR_ABOUT,
    STR_EXIT,
    STR_THB,
    STR_HOURS,
    STR_MINUTES,

    STR_COUNT  // Total string count
} StringID;

// Language strings
static const char* strings_en[STR_COUNT] = {
    "GPU Share Client",
    "Dashboard",
    "GPU Status",
    "System Status",
    "Earnings",
    "Settings",

    "Temperature",
    "Power Usage",
    "Memory Usage",
    "Fan Speed",
    "GPU Load",
    "GPU Name",

    "CPU Usage",
    "RAM Usage",
    "Network",
    "Upload",
    "Download",

    "Status",
    "Idle",
    "Receiving Job",
    "Processing",
    "Uploading",
    "Completed",
    "Failed",
    "Waiting for jobs...",

    "Today's Earnings",
    "Total Earnings",
    "Pending Earnings",
    "Jobs Completed",
    "Jobs Failed",
    "Uptime",

    "Start",
    "Stop",
    "Login",
    "Logout",
    "Save",
    "Cancel",

    "API Key",
    "Server URL",
    "Temperature Limit",
    "Power Limit",
    "Auto Start",
    "Minimize to Tray",
    "Language",

    "Connecting...",
    "Connected",
    "Disconnected",
    "Connection Error",
    "GPU Error",
    "Check your internet connection",
    "Check your GPU health",
    "Avoid overclocking",

    "Version",
    "About",
    "Exit",
    "THB",
    "hours",
    "minutes"
};

static const char* strings_th[STR_COUNT] = {
    "GPU Share Client",
    "แดชบอร์ด",
    "สถานะ GPU",
    "สถานะระบบ",
    "รายได้",
    "ตั้งค่า",

    "อุณหภูมิ",
    "การใช้พลังงาน",
    "การใช้หน่วยความจำ",
    "ความเร็วพัดลม",
    "โหลด GPU",
    "ชื่อ GPU",

    "การใช้ CPU",
    "การใช้ RAM",
    "เครือข่าย",
    "อัปโหลด",
    "ดาวน์โหลด",

    "สถานะ",
    "ว่าง",
    "กำลังรับงาน",
    "กำลังประมวลผล",
    "กำลังอัปโหลด",
    "เสร็จสิ้น",
    "ล้มเหลว",
    "รอรับงาน...",

    "รายได้วันนี้",
    "รายได้ทั้งหมด",
    "รายได้รอโอน",
    "งานสำเร็จ",
    "งานล้มเหลว",
    "เวลาทำงาน",

    "เริ่ม",
    "หยุด",
    "เข้าสู่ระบบ",
    "ออกจากระบบ",
    "บันทึก",
    "ยกเลิก",

    "API Key",
    "URL เซิร์ฟเวอร์",
    "จำกัดอุณหภูมิ",
    "จำกัดพลังงาน",
    "เริ่มอัตโนมัติ",
    "ย่อลง System Tray",
    "ภาษา",

    "กำลังเชื่อมต่อ...",
    "เชื่อมต่อแล้ว",
    "ไม่ได้เชื่อมต่อ",
    "เชื่อมต่อผิดพลาด",
    "GPU ผิดพลาด",
    "ตรวจสอบการเชื่อมต่ออินเทอร์เน็ต",
    "ตรวจสอบสภาพ GPU",
    "หลีกเลี่ยงการ Overclock",

    "เวอร์ชัน",
    "เกี่ยวกับ",
    "ออก",
    "บาท",
    "ชั่วโมง",
    "นาที"
};

// Current language
static Language current_lang = LANG_EN;

// Get localized string
static inline const char* L(StringID id) {
    if (id >= STR_COUNT) return "";
    return (current_lang == LANG_TH) ? strings_th[id] : strings_en[id];
}

// Set language
static inline void SetLanguage(Language lang) {
    current_lang = lang;
}

// Get current language
static inline Language GetLanguage(void) {
    return current_lang;
}

#endif // LANG_H
