"""
GPU Share Client - GPU Monitoring Module
Monitors GPU temperature, power, memory, and fan speed
"""
import logging
from dataclasses import dataclass
from typing import Optional, List
import threading
import time

logger = logging.getLogger(__name__)

try:
    import pynvml
    PYNVML_AVAILABLE = True
except ImportError:
    PYNVML_AVAILABLE = False
    logger.warning("pynvml not available, GPU monitoring will be limited")

try:
    import GPUtil
    GPUTIL_AVAILABLE = True
except ImportError:
    GPUTIL_AVAILABLE = False


@dataclass
class GPUInfo:
    """GPU Information Container"""
    index: int
    name: str
    uuid: str
    memory_total: int  # MB
    memory_used: int   # MB
    memory_free: int   # MB
    memory_percent: float
    temperature: int   # Celsius
    fan_speed: int     # Percent
    power_draw: float  # Watts
    power_limit: float # Watts
    gpu_util: int      # Percent
    memory_util: int   # Percent
    clock_graphics: int  # MHz
    clock_memory: int    # MHz
    driver_version: str
    cuda_version: str


class GPUMonitor:
    """GPU Monitoring and Control"""

    def __init__(self):
        self.initialized = False
        self.device_count = 0
        self.handles = []
        self._lock = threading.Lock()
        self._initialize()

    def _initialize(self):
        """Initialize NVML"""
        if not PYNVML_AVAILABLE:
            logger.error("pynvml not available")
            return

        try:
            pynvml.nvmlInit()
            self.device_count = pynvml.nvmlDeviceGetCount()

            for i in range(self.device_count):
                handle = pynvml.nvmlDeviceGetHandleByIndex(i)
                self.handles.append(handle)

            self.initialized = True
            logger.info(f"GPU Monitor initialized with {self.device_count} GPU(s)")
        except pynvml.NVMLError as e:
            logger.error(f"Failed to initialize NVML: {e}")

    def shutdown(self):
        """Shutdown NVML"""
        if self.initialized:
            try:
                pynvml.nvmlShutdown()
                self.initialized = False
                logger.info("GPU Monitor shutdown")
            except pynvml.NVMLError as e:
                logger.error(f"Failed to shutdown NVML: {e}")

    def get_gpu_count(self) -> int:
        """Get number of GPUs"""
        return self.device_count

    def get_gpu_info(self, gpu_index: int = 0) -> Optional[GPUInfo]:
        """Get information for a specific GPU"""
        if not self.initialized or gpu_index >= self.device_count:
            return None

        with self._lock:
            try:
                handle = self.handles[gpu_index]

                # Basic info
                name = pynvml.nvmlDeviceGetName(handle)
                if isinstance(name, bytes):
                    name = name.decode('utf-8')
                uuid = pynvml.nvmlDeviceGetUUID(handle)
                if isinstance(uuid, bytes):
                    uuid = uuid.decode('utf-8')

                # Memory info
                mem_info = pynvml.nvmlDeviceGetMemoryInfo(handle)
                memory_total = mem_info.total // (1024 * 1024)
                memory_used = mem_info.used // (1024 * 1024)
                memory_free = mem_info.free // (1024 * 1024)
                memory_percent = (mem_info.used / mem_info.total) * 100

                # Temperature
                try:
                    temperature = pynvml.nvmlDeviceGetTemperature(handle, pynvml.NVML_TEMPERATURE_GPU)
                except:
                    temperature = 0

                # Fan speed
                try:
                    fan_speed = pynvml.nvmlDeviceGetFanSpeed(handle)
                except:
                    fan_speed = 0

                # Power
                try:
                    power_draw = pynvml.nvmlDeviceGetPowerUsage(handle) / 1000  # mW to W
                except:
                    power_draw = 0

                try:
                    power_limit = pynvml.nvmlDeviceGetPowerManagementLimit(handle) / 1000
                except:
                    power_limit = 0

                # Utilization
                try:
                    util = pynvml.nvmlDeviceGetUtilizationRates(handle)
                    gpu_util = util.gpu
                    memory_util = util.memory
                except:
                    gpu_util = 0
                    memory_util = 0

                # Clocks
                try:
                    clock_graphics = pynvml.nvmlDeviceGetClockInfo(handle, pynvml.NVML_CLOCK_GRAPHICS)
                except:
                    clock_graphics = 0

                try:
                    clock_memory = pynvml.nvmlDeviceGetClockInfo(handle, pynvml.NVML_CLOCK_MEM)
                except:
                    clock_memory = 0

                # Driver info
                try:
                    driver_version = pynvml.nvmlSystemGetDriverVersion()
                    if isinstance(driver_version, bytes):
                        driver_version = driver_version.decode('utf-8')
                except:
                    driver_version = "Unknown"

                try:
                    cuda_version = pynvml.nvmlSystemGetCudaDriverVersion_v2()
                    cuda_version = f"{cuda_version // 1000}.{(cuda_version % 1000) // 10}"
                except:
                    cuda_version = "Unknown"

                return GPUInfo(
                    index=gpu_index,
                    name=name,
                    uuid=uuid,
                    memory_total=memory_total,
                    memory_used=memory_used,
                    memory_free=memory_free,
                    memory_percent=memory_percent,
                    temperature=temperature,
                    fan_speed=fan_speed,
                    power_draw=power_draw,
                    power_limit=power_limit,
                    gpu_util=gpu_util,
                    memory_util=memory_util,
                    clock_graphics=clock_graphics,
                    clock_memory=clock_memory,
                    driver_version=driver_version,
                    cuda_version=cuda_version,
                )

            except pynvml.NVMLError as e:
                logger.error(f"Failed to get GPU info: {e}")
                return None

    def get_all_gpus(self) -> List[GPUInfo]:
        """Get information for all GPUs"""
        gpus = []
        for i in range(self.device_count):
            info = self.get_gpu_info(i)
            if info:
                gpus.append(info)
        return gpus

    def set_fan_speed(self, gpu_index: int, speed: int) -> bool:
        """
        Set fan speed for a GPU
        speed: 0-100 percent (0 = auto)
        """
        if not self.initialized or gpu_index >= self.device_count:
            return False

        try:
            handle = self.handles[gpu_index]

            if speed == 0:
                # Reset to auto
                pynvml.nvmlDeviceSetDefaultFanSpeed_v2(handle, 0)
            else:
                # Set manual speed
                speed = max(30, min(100, speed))  # Clamp between 30-100
                pynvml.nvmlDeviceSetFanSpeed_v2(handle, 0, speed)

            logger.info(f"Set GPU {gpu_index} fan speed to {speed}%")
            return True
        except pynvml.NVMLError as e:
            logger.error(f"Failed to set fan speed: {e}")
            return False

    def set_power_limit(self, gpu_index: int, limit_watts: int) -> bool:
        """Set power limit for a GPU in watts"""
        if not self.initialized or gpu_index >= self.device_count:
            return False

        try:
            handle = self.handles[gpu_index]

            # Get min/max limits
            min_limit = pynvml.nvmlDeviceGetPowerManagementLimitConstraints(handle)[0] // 1000
            max_limit = pynvml.nvmlDeviceGetPowerManagementLimitConstraints(handle)[1] // 1000

            limit_watts = max(min_limit, min(max_limit, limit_watts))

            pynvml.nvmlDeviceSetPowerManagementLimit(handle, limit_watts * 1000)
            logger.info(f"Set GPU {gpu_index} power limit to {limit_watts}W")
            return True
        except pynvml.NVMLError as e:
            logger.error(f"Failed to set power limit: {e}")
            return False


# Singleton instance
_gpu_monitor: Optional[GPUMonitor] = None


def get_gpu_monitor() -> GPUMonitor:
    """Get GPU Monitor singleton"""
    global _gpu_monitor
    if _gpu_monitor is None:
        _gpu_monitor = GPUMonitor()
    return _gpu_monitor
