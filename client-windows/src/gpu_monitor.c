/**
 * GPU Share Client - GPU Monitoring Module
 * Uses NVML (NVIDIA Management Library) for GPU info
 */

#include <windows.h>
#include <stdio.h>
#include <string.h>
#include "../include/types.h"
#include "../include/config.h"

// NVML Types (minimal definitions)
typedef void* nvmlDevice_t;
typedef int nvmlReturn_t;
typedef struct { unsigned long long total; unsigned long long free; unsigned long long used; } nvmlMemory_t;
typedef struct { unsigned int gpu; unsigned int memory; } nvmlUtilization_t;

#define NVML_SUCCESS 0

// NVML Function pointers
typedef nvmlReturn_t (*nvmlInit_t)(void);
typedef nvmlReturn_t (*nvmlShutdown_t)(void);
typedef nvmlReturn_t (*nvmlDeviceGetCount_t)(unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetHandleByIndex_t)(unsigned int, nvmlDevice_t*);
typedef nvmlReturn_t (*nvmlDeviceGetName_t)(nvmlDevice_t, char*, unsigned int);
typedef nvmlReturn_t (*nvmlDeviceGetTemperature_t)(nvmlDevice_t, int, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetPowerUsage_t)(nvmlDevice_t, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetEnforcedPowerLimit_t)(nvmlDevice_t, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetMemoryInfo_t)(nvmlDevice_t, nvmlMemory_t*);
typedef nvmlReturn_t (*nvmlDeviceGetFanSpeed_t)(nvmlDevice_t, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetUtilizationRates_t)(nvmlDevice_t, nvmlUtilization_t*);

// Global NVML state
static HMODULE nvml_lib = NULL;
static nvmlDevice_t gpu_handle = NULL;
static bool nvml_initialized = false;

// Function pointers
static nvmlInit_t pNvmlInit = NULL;
static nvmlShutdown_t pNvmlShutdown = NULL;
static nvmlDeviceGetCount_t pNvmlDeviceGetCount = NULL;
static nvmlDeviceGetHandleByIndex_t pNvmlDeviceGetHandleByIndex = NULL;
static nvmlDeviceGetName_t pNvmlDeviceGetName = NULL;
static nvmlDeviceGetTemperature_t pNvmlDeviceGetTemperature = NULL;
static nvmlDeviceGetPowerUsage_t pNvmlDeviceGetPowerUsage = NULL;
static nvmlDeviceGetEnforcedPowerLimit_t pNvmlDeviceGetEnforcedPowerLimit = NULL;
static nvmlDeviceGetMemoryInfo_t pNvmlDeviceGetMemoryInfo = NULL;
static nvmlDeviceGetFanSpeed_t pNvmlDeviceGetFanSpeed = NULL;
static nvmlDeviceGetUtilizationRates_t pNvmlDeviceGetUtilizationRates = NULL;

// Initialize NVML
bool GPU_Init(void) {
    if (nvml_initialized) return true;

    // Try to load NVML from NVIDIA driver location
    nvml_lib = LoadLibraryA("nvml.dll");
    if (!nvml_lib) {
        // Try alternate location
        nvml_lib = LoadLibraryA("C:\\Windows\\System32\\nvml.dll");
    }
    if (!nvml_lib) {
        nvml_lib = LoadLibraryA("C:\\Program Files\\NVIDIA Corporation\\NVSMI\\nvml.dll");
    }

    if (!nvml_lib) {
        return false;
    }

    // Load functions
    pNvmlInit = (nvmlInit_t)GetProcAddress(nvml_lib, "nvmlInit_v2");
    if (!pNvmlInit) pNvmlInit = (nvmlInit_t)GetProcAddress(nvml_lib, "nvmlInit");

    pNvmlShutdown = (nvmlShutdown_t)GetProcAddress(nvml_lib, "nvmlShutdown");
    pNvmlDeviceGetCount = (nvmlDeviceGetCount_t)GetProcAddress(nvml_lib, "nvmlDeviceGetCount_v2");
    pNvmlDeviceGetHandleByIndex = (nvmlDeviceGetHandleByIndex_t)GetProcAddress(nvml_lib, "nvmlDeviceGetHandleByIndex_v2");
    pNvmlDeviceGetName = (nvmlDeviceGetName_t)GetProcAddress(nvml_lib, "nvmlDeviceGetName");
    pNvmlDeviceGetTemperature = (nvmlDeviceGetTemperature_t)GetProcAddress(nvml_lib, "nvmlDeviceGetTemperature");
    pNvmlDeviceGetPowerUsage = (nvmlDeviceGetPowerUsage_t)GetProcAddress(nvml_lib, "nvmlDeviceGetPowerUsage");
    pNvmlDeviceGetEnforcedPowerLimit = (nvmlDeviceGetEnforcedPowerLimit_t)GetProcAddress(nvml_lib, "nvmlDeviceGetEnforcedPowerLimit");
    pNvmlDeviceGetMemoryInfo = (nvmlDeviceGetMemoryInfo_t)GetProcAddress(nvml_lib, "nvmlDeviceGetMemoryInfo");
    pNvmlDeviceGetFanSpeed = (nvmlDeviceGetFanSpeed_t)GetProcAddress(nvml_lib, "nvmlDeviceGetFanSpeed");
    pNvmlDeviceGetUtilizationRates = (nvmlDeviceGetUtilizationRates_t)GetProcAddress(nvml_lib, "nvmlDeviceGetUtilizationRates");

    if (!pNvmlInit) {
        FreeLibrary(nvml_lib);
        nvml_lib = NULL;
        return false;
    }

    // Initialize NVML
    if (pNvmlInit() != NVML_SUCCESS) {
        FreeLibrary(nvml_lib);
        nvml_lib = NULL;
        return false;
    }

    // Get first GPU
    unsigned int device_count = 0;
    if (pNvmlDeviceGetCount && pNvmlDeviceGetCount(&device_count) == NVML_SUCCESS && device_count > 0) {
        if (pNvmlDeviceGetHandleByIndex) {
            pNvmlDeviceGetHandleByIndex(0, &gpu_handle);
        }
    }

    nvml_initialized = true;
    return true;
}

// Shutdown NVML
void GPU_Shutdown(void) {
    if (nvml_initialized && pNvmlShutdown) {
        pNvmlShutdown();
    }
    if (nvml_lib) {
        FreeLibrary(nvml_lib);
        nvml_lib = NULL;
    }
    nvml_initialized = false;
    gpu_handle = NULL;
}

// Get GPU Information
bool GPU_GetInfo(GPUInfo* info) {
    if (!info) return false;

    memset(info, 0, sizeof(GPUInfo));

    if (!nvml_initialized || !gpu_handle) {
        // Return demo data for testing without GPU
        strcpy(info->name, "NVIDIA GeForce RTX 3080 (Demo)");
        info->temperature = 45;
        info->power_usage = 120;
        info->power_limit = 320;
        info->memory_used = 2048;
        info->memory_total = 10240;
        info->fan_speed = 35;
        info->gpu_load = 15;
        info->memory_load = 20;
        info->is_available = false;
        return true;
    }

    info->is_available = true;

    // Get GPU name
    if (pNvmlDeviceGetName) {
        pNvmlDeviceGetName(gpu_handle, info->name, sizeof(info->name));
    }

    // Get temperature
    if (pNvmlDeviceGetTemperature) {
        unsigned int temp;
        if (pNvmlDeviceGetTemperature(gpu_handle, 0, &temp) == NVML_SUCCESS) {
            info->temperature = (int)temp;
        }
    }

    // Get power usage (mW to W)
    if (pNvmlDeviceGetPowerUsage) {
        unsigned int power;
        if (pNvmlDeviceGetPowerUsage(gpu_handle, &power) == NVML_SUCCESS) {
            info->power_usage = (int)(power / 1000);
        }
    }

    // Get power limit
    if (pNvmlDeviceGetEnforcedPowerLimit) {
        unsigned int limit;
        if (pNvmlDeviceGetEnforcedPowerLimit(gpu_handle, &limit) == NVML_SUCCESS) {
            info->power_limit = (int)(limit / 1000);
        }
    }

    // Get memory info
    if (pNvmlDeviceGetMemoryInfo) {
        nvmlMemory_t mem;
        if (pNvmlDeviceGetMemoryInfo(gpu_handle, &mem) == NVML_SUCCESS) {
            info->memory_used = (int)(mem.used / (1024 * 1024));
            info->memory_total = (int)(mem.total / (1024 * 1024));
        }
    }

    // Get fan speed
    if (pNvmlDeviceGetFanSpeed) {
        unsigned int fan;
        if (pNvmlDeviceGetFanSpeed(gpu_handle, &fan) == NVML_SUCCESS) {
            info->fan_speed = (int)fan;
        }
    }

    // Get utilization
    if (pNvmlDeviceGetUtilizationRates) {
        nvmlUtilization_t util;
        if (pNvmlDeviceGetUtilizationRates(gpu_handle, &util) == NVML_SUCCESS) {
            info->gpu_load = (int)util.gpu;
            info->memory_load = (int)util.memory;
        }
    }

    return true;
}

// Check if GPU is available
bool GPU_IsAvailable(void) {
    return nvml_initialized && gpu_handle != NULL;
}

// Get temperature color based on value
COLORREF GPU_GetTempColor(int temp) {
    if (temp < 50) return COLOR_SUCCESS;
    if (temp < 70) return COLOR_WARNING;
    return COLOR_DANGER;
}

// Get load color based on value
COLORREF GPU_GetLoadColor(int load) {
    if (load < 30) return COLOR_TEXT_MUTED;
    if (load < 70) return COLOR_SUCCESS;
    if (load < 90) return COLOR_WARNING;
    return COLOR_DANGER;
}
