/**
 * GPU Share Client - GPU Monitoring Module
 * Uses NVML (NVIDIA Management Library) for GPU info
 * Supports multiple GPUs with individual controls
 */

#include "../include/common.h"
#include "../include/types.h"
#include "../include/config.h"

// NVML Types (minimal definitions)
typedef void* nvmlDevice_t;
typedef int nvmlReturn_t;
typedef struct { unsigned long long total; unsigned long long free; unsigned long long used; } nvmlMemory_t;
typedef struct { unsigned int gpu; unsigned int memory; } nvmlUtilization_t;

#define NVML_SUCCESS 0
#define NVML_TEMPERATURE_GPU 0

// NVML Function pointers
typedef nvmlReturn_t (*nvmlInit_t)(void);
typedef nvmlReturn_t (*nvmlShutdown_t)(void);
typedef nvmlReturn_t (*nvmlDeviceGetCount_t)(unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetHandleByIndex_t)(unsigned int, nvmlDevice_t*);
typedef nvmlReturn_t (*nvmlDeviceGetName_t)(nvmlDevice_t, char*, unsigned int);
typedef nvmlReturn_t (*nvmlDeviceGetUUID_t)(nvmlDevice_t, char*, unsigned int);
typedef nvmlReturn_t (*nvmlDeviceGetTemperature_t)(nvmlDevice_t, int, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetPowerUsage_t)(nvmlDevice_t, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetEnforcedPowerLimit_t)(nvmlDevice_t, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceGetPowerManagementLimit_t)(nvmlDevice_t, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceSetPowerManagementLimit_t)(nvmlDevice_t, unsigned int);
typedef nvmlReturn_t (*nvmlDeviceGetMemoryInfo_t)(nvmlDevice_t, nvmlMemory_t*);
typedef nvmlReturn_t (*nvmlDeviceGetFanSpeed_t)(nvmlDevice_t, unsigned int*);
typedef nvmlReturn_t (*nvmlDeviceSetFanSpeed_v2_t)(nvmlDevice_t, unsigned int, unsigned int);
typedef nvmlReturn_t (*nvmlDeviceGetUtilizationRates_t)(nvmlDevice_t, nvmlUtilization_t*);

// Global NVML state
static HMODULE nvml_lib = NULL;
static bool nvml_initialized = false;
static unsigned int gpu_device_count = 0;
static nvmlDevice_t gpu_handles[MAX_GPUS] = {NULL};

// Function pointers
static nvmlInit_t pNvmlInit = NULL;
static nvmlShutdown_t pNvmlShutdown = NULL;
static nvmlDeviceGetCount_t pNvmlDeviceGetCount = NULL;
static nvmlDeviceGetHandleByIndex_t pNvmlDeviceGetHandleByIndex = NULL;
static nvmlDeviceGetName_t pNvmlDeviceGetName = NULL;
static nvmlDeviceGetUUID_t pNvmlDeviceGetUUID = NULL;
static nvmlDeviceGetTemperature_t pNvmlDeviceGetTemperature = NULL;
static nvmlDeviceGetPowerUsage_t pNvmlDeviceGetPowerUsage = NULL;
static nvmlDeviceGetEnforcedPowerLimit_t pNvmlDeviceGetEnforcedPowerLimit = NULL;
static nvmlDeviceGetPowerManagementLimit_t pNvmlDeviceGetPowerManagementLimit = NULL;
static nvmlDeviceSetPowerManagementLimit_t pNvmlDeviceSetPowerManagementLimit = NULL;
static nvmlDeviceGetMemoryInfo_t pNvmlDeviceGetMemoryInfo = NULL;
static nvmlDeviceGetFanSpeed_t pNvmlDeviceGetFanSpeed = NULL;
static nvmlDeviceSetFanSpeed_v2_t pNvmlDeviceSetFanSpeed = NULL;
static nvmlDeviceGetUtilizationRates_t pNvmlDeviceGetUtilizationRates = NULL;

// Initialize NVML and detect all GPUs
bool GPU_Init(void) {
    if (nvml_initialized) return true;

    // Try to load NVML from various locations
    const char* nvml_paths[] = {
        "nvml.dll",
        "C:\\Windows\\System32\\nvml.dll",
        "C:\\Program Files\\NVIDIA Corporation\\NVSMI\\nvml.dll",
        NULL
    };

    for (int i = 0; nvml_paths[i] != NULL; i++) {
        nvml_lib = LoadLibraryA(nvml_paths[i]);
        if (nvml_lib) break;
    }

    if (!nvml_lib) {
        return false;
    }

    // Load functions
    pNvmlInit = (nvmlInit_t)GetProcAddress(nvml_lib, "nvmlInit_v2");
    if (!pNvmlInit) pNvmlInit = (nvmlInit_t)GetProcAddress(nvml_lib, "nvmlInit");

    pNvmlShutdown = (nvmlShutdown_t)GetProcAddress(nvml_lib, "nvmlShutdown");
    pNvmlDeviceGetCount = (nvmlDeviceGetCount_t)GetProcAddress(nvml_lib, "nvmlDeviceGetCount_v2");
    if (!pNvmlDeviceGetCount) pNvmlDeviceGetCount = (nvmlDeviceGetCount_t)GetProcAddress(nvml_lib, "nvmlDeviceGetCount");

    pNvmlDeviceGetHandleByIndex = (nvmlDeviceGetHandleByIndex_t)GetProcAddress(nvml_lib, "nvmlDeviceGetHandleByIndex_v2");
    if (!pNvmlDeviceGetHandleByIndex) pNvmlDeviceGetHandleByIndex = (nvmlDeviceGetHandleByIndex_t)GetProcAddress(nvml_lib, "nvmlDeviceGetHandleByIndex");

    pNvmlDeviceGetName = (nvmlDeviceGetName_t)GetProcAddress(nvml_lib, "nvmlDeviceGetName");
    pNvmlDeviceGetUUID = (nvmlDeviceGetUUID_t)GetProcAddress(nvml_lib, "nvmlDeviceGetUUID");
    pNvmlDeviceGetTemperature = (nvmlDeviceGetTemperature_t)GetProcAddress(nvml_lib, "nvmlDeviceGetTemperature");
    pNvmlDeviceGetPowerUsage = (nvmlDeviceGetPowerUsage_t)GetProcAddress(nvml_lib, "nvmlDeviceGetPowerUsage");
    pNvmlDeviceGetEnforcedPowerLimit = (nvmlDeviceGetEnforcedPowerLimit_t)GetProcAddress(nvml_lib, "nvmlDeviceGetEnforcedPowerLimit");
    pNvmlDeviceGetPowerManagementLimit = (nvmlDeviceGetPowerManagementLimit_t)GetProcAddress(nvml_lib, "nvmlDeviceGetPowerManagementLimit");
    pNvmlDeviceSetPowerManagementLimit = (nvmlDeviceSetPowerManagementLimit_t)GetProcAddress(nvml_lib, "nvmlDeviceSetPowerManagementLimit");
    pNvmlDeviceGetMemoryInfo = (nvmlDeviceGetMemoryInfo_t)GetProcAddress(nvml_lib, "nvmlDeviceGetMemoryInfo");
    pNvmlDeviceGetFanSpeed = (nvmlDeviceGetFanSpeed_t)GetProcAddress(nvml_lib, "nvmlDeviceGetFanSpeed");
    pNvmlDeviceSetFanSpeed = (nvmlDeviceSetFanSpeed_v2_t)GetProcAddress(nvml_lib, "nvmlDeviceSetFanSpeed_v2");
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

    // Get GPU count and handles
    gpu_device_count = 0;
    if (pNvmlDeviceGetCount && pNvmlDeviceGetCount(&gpu_device_count) == NVML_SUCCESS) {
        if (gpu_device_count > MAX_GPUS) {
            gpu_device_count = MAX_GPUS;
        }

        for (unsigned int i = 0; i < gpu_device_count; i++) {
            if (pNvmlDeviceGetHandleByIndex) {
                pNvmlDeviceGetHandleByIndex(i, &gpu_handles[i]);
            }
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
    gpu_device_count = 0;
    for (int i = 0; i < MAX_GPUS; i++) {
        gpu_handles[i] = NULL;
    }
}

// Get total GPU count
int GPU_GetCount(void) {
    return (int)gpu_device_count;
}

// Get GPU Information for all GPUs
bool GPU_GetAllInfo(GPUManager* manager) {
    if (!manager) return false;

    memset(manager, 0, sizeof(GPUManager));

    if (!nvml_initialized || gpu_device_count == 0) {
        // Return demo data for testing without GPU
        manager->gpu_count = 2;  // Demo: 2 GPUs

        // Demo GPU 0
        manager->gpus[0].index = 0;
        strcpy(manager->gpus[0].name, "NVIDIA GeForce RTX 3080 (Demo)");
        strcpy(manager->gpus[0].uuid, "GPU-DEMO-0000-0000-0000");
        manager->gpus[0].temperature = 45;
        manager->gpus[0].power_usage = 120;
        manager->gpus[0].power_limit = 320;
        manager->gpus[0].memory_used = 2048;
        manager->gpus[0].memory_total = 10240;
        manager->gpus[0].fan_speed = 35;
        manager->gpus[0].gpu_load = 15;
        manager->gpus[0].memory_load = 20;
        manager->gpus[0].is_available = false;
        manager->gpus[0].is_enabled = true;
        manager->gpus[0].performance_limit = 100;
        manager->gpus[0].auto_fan = true;

        // Demo GPU 1
        manager->gpus[1].index = 1;
        strcpy(manager->gpus[1].name, "NVIDIA GeForce RTX 3070 (Demo)");
        strcpy(manager->gpus[1].uuid, "GPU-DEMO-0000-0000-0001");
        manager->gpus[1].temperature = 52;
        manager->gpus[1].power_usage = 180;
        manager->gpus[1].power_limit = 220;
        manager->gpus[1].memory_used = 4096;
        manager->gpus[1].memory_total = 8192;
        manager->gpus[1].fan_speed = 55;
        manager->gpus[1].gpu_load = 75;
        manager->gpus[1].memory_load = 50;
        manager->gpus[1].is_available = false;
        manager->gpus[1].is_enabled = true;
        manager->gpus[1].performance_limit = 80;
        manager->gpus[1].auto_fan = false;
        manager->gpus[1].manual_fan_speed = 60;

        manager->enabled_count = 2;
        return true;
    }

    manager->gpu_count = (int)gpu_device_count;
    manager->enabled_count = 0;

    for (int i = 0; i < (int)gpu_device_count; i++) {
        GPUInfo* gpu = &manager->gpus[i];
        nvmlDevice_t handle = gpu_handles[i];

        gpu->index = i;
        gpu->is_available = true;

        // Get GPU name
        if (pNvmlDeviceGetName && handle) {
            pNvmlDeviceGetName(handle, gpu->name, sizeof(gpu->name));
        }

        // Get GPU UUID
        if (pNvmlDeviceGetUUID && handle) {
            pNvmlDeviceGetUUID(handle, gpu->uuid, sizeof(gpu->uuid));
        }

        // Get temperature
        if (pNvmlDeviceGetTemperature && handle) {
            unsigned int temp;
            if (pNvmlDeviceGetTemperature(handle, NVML_TEMPERATURE_GPU, &temp) == NVML_SUCCESS) {
                gpu->temperature = (int)temp;

                // Add to temperature history
                gpu->temp_history[gpu->temp_history_index] = gpu->temperature;
                gpu->temp_history_index = (gpu->temp_history_index + 1) % TEMP_HISTORY_SIZE;
                if (gpu->temp_history_count < TEMP_HISTORY_SIZE) {
                    gpu->temp_history_count++;
                }
            }
        }

        // Get power usage (mW to W)
        if (pNvmlDeviceGetPowerUsage && handle) {
            unsigned int power;
            if (pNvmlDeviceGetPowerUsage(handle, &power) == NVML_SUCCESS) {
                gpu->power_usage = (int)(power / 1000);
            }
        }

        // Get power limit
        if (pNvmlDeviceGetEnforcedPowerLimit && handle) {
            unsigned int limit;
            if (pNvmlDeviceGetEnforcedPowerLimit(handle, &limit) == NVML_SUCCESS) {
                gpu->power_limit = (int)(limit / 1000);
            }
        }

        // Get memory info
        if (pNvmlDeviceGetMemoryInfo && handle) {
            nvmlMemory_t mem;
            if (pNvmlDeviceGetMemoryInfo(handle, &mem) == NVML_SUCCESS) {
                gpu->memory_used = (int)(mem.used / (1024 * 1024));
                gpu->memory_total = (int)(mem.total / (1024 * 1024));
            }
        }

        // Get fan speed
        if (pNvmlDeviceGetFanSpeed && handle) {
            unsigned int fan;
            if (pNvmlDeviceGetFanSpeed(handle, &fan) == NVML_SUCCESS) {
                gpu->fan_speed = (int)fan;
            }
        }

        // Get utilization
        if (pNvmlDeviceGetUtilizationRates && handle) {
            nvmlUtilization_t util;
            if (pNvmlDeviceGetUtilizationRates(handle, &util) == NVML_SUCCESS) {
                gpu->gpu_load = (int)util.gpu;
                gpu->memory_load = (int)util.memory;
            }
        }

        // Default settings if not set
        if (gpu->performance_limit == 0) {
            gpu->performance_limit = 100;
        }
        if (!gpu->is_enabled) {
            gpu->is_enabled = true;  // Enable by default
        }
        gpu->auto_fan = true;  // Auto fan by default

        if (gpu->is_enabled) {
            manager->enabled_count++;
        }
    }

    return true;
}

// Get single GPU info (for backward compatibility)
bool GPU_GetInfo(GPUInfo* info) {
    if (!info) return false;

    GPUManager manager;
    if (GPU_GetAllInfo(&manager) && manager.gpu_count > 0) {
        *info = manager.gpus[0];
        return true;
    }
    return false;
}

// Set GPU performance limit (power limit percentage)
bool GPU_SetPerformanceLimit(int gpu_index, int limit_percent) {
    if (gpu_index < 0 || gpu_index >= (int)gpu_device_count) return false;
    if (limit_percent < 50 || limit_percent > 100) return false;

    nvmlDevice_t handle = gpu_handles[gpu_index];
    if (!handle || !pNvmlDeviceSetPowerManagementLimit) return false;

    // Get current power limit to calculate new limit
    unsigned int current_limit = 0;
    if (pNvmlDeviceGetPowerManagementLimit) {
        pNvmlDeviceGetPowerManagementLimit(handle, &current_limit);
    }

    if (current_limit == 0) return false;

    // Calculate new limit based on percentage
    unsigned int new_limit = (current_limit * limit_percent) / 100;

    return pNvmlDeviceSetPowerManagementLimit(handle, new_limit) == NVML_SUCCESS;
}

// Set GPU fan speed
bool GPU_SetFanSpeed(int gpu_index, int speed_percent) {
    if (gpu_index < 0 || gpu_index >= (int)gpu_device_count) return false;
    if (speed_percent < 0 || speed_percent > 100) return false;

    nvmlDevice_t handle = gpu_handles[gpu_index];
    if (!handle || !pNvmlDeviceSetFanSpeed) return false;

    // Fan index 0, set target speed
    return pNvmlDeviceSetFanSpeed(handle, 0, (unsigned int)speed_percent) == NVML_SUCCESS;
}

// Check if GPU is available
bool GPU_IsAvailable(void) {
    return nvml_initialized && gpu_device_count > 0;
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

// Get fan color based on value
COLORREF GPU_GetFanColor(int speed) {
    if (speed < 40) return COLOR_SUCCESS;
    if (speed < 70) return COLOR_WARNING;
    return COLOR_DANGER;
}
