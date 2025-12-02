"""
GPU Share Client - System Monitoring Module
Monitors CPU, RAM, Network bandwidth, and Power consumption
"""
import logging
import platform
import time
from dataclasses import dataclass
from typing import Optional, Tuple
import threading

logger = logging.getLogger(__name__)

try:
    import psutil
    PSUTIL_AVAILABLE = True
except ImportError:
    PSUTIL_AVAILABLE = False
    logger.warning("psutil not available, system monitoring will be limited")


@dataclass
class SystemInfo:
    """System Information Container"""
    # CPU
    cpu_percent: float
    cpu_freq_current: float
    cpu_freq_max: float
    cpu_temp: Optional[float]
    cpu_cores: int
    cpu_threads: int

    # Memory
    ram_total: int      # MB
    ram_used: int       # MB
    ram_percent: float

    # Disk
    disk_total: int     # GB
    disk_used: int      # GB
    disk_free: int      # GB
    disk_percent: float

    # Network
    net_bytes_sent: int
    net_bytes_recv: int
    net_speed_up: float    # MB/s
    net_speed_down: float  # MB/s

    # System
    platform: str
    hostname: str
    uptime: int  # seconds


@dataclass
class NetworkSpeed:
    """Network Speed Measurement"""
    upload: float    # MB/s
    download: float  # MB/s
    total_sent: int  # bytes
    total_recv: int  # bytes


class SystemMonitor:
    """System Monitoring"""

    def __init__(self):
        self._last_net_io = None
        self._last_net_time = None
        self._lock = threading.Lock()
        self._initialize()

    def _initialize(self):
        """Initialize system monitoring"""
        if PSUTIL_AVAILABLE:
            # Get initial network counters
            self._last_net_io = psutil.net_io_counters()
            self._last_net_time = time.time()
            logger.info("System Monitor initialized")

    def get_system_info(self) -> Optional[SystemInfo]:
        """Get current system information"""
        if not PSUTIL_AVAILABLE:
            return None

        with self._lock:
            try:
                # CPU
                cpu_percent = psutil.cpu_percent(interval=0.1)
                cpu_freq = psutil.cpu_freq()
                cpu_freq_current = cpu_freq.current if cpu_freq else 0
                cpu_freq_max = cpu_freq.max if cpu_freq else 0

                # CPU Temperature
                cpu_temp = None
                try:
                    temps = psutil.sensors_temperatures()
                    if temps:
                        for name, entries in temps.items():
                            if entries:
                                cpu_temp = entries[0].current
                                break
                except:
                    pass

                cpu_cores = psutil.cpu_count(logical=False) or 0
                cpu_threads = psutil.cpu_count(logical=True) or 0

                # Memory
                mem = psutil.virtual_memory()
                ram_total = mem.total // (1024 * 1024)
                ram_used = mem.used // (1024 * 1024)
                ram_percent = mem.percent

                # Disk
                disk = psutil.disk_usage('/')
                disk_total = disk.total // (1024 * 1024 * 1024)
                disk_used = disk.used // (1024 * 1024 * 1024)
                disk_free = disk.free // (1024 * 1024 * 1024)
                disk_percent = disk.percent

                # Network speed
                net_speed = self.get_network_speed()

                # System info
                hostname = platform.node()
                system_platform = f"{platform.system()} {platform.release()}"
                uptime = int(time.time() - psutil.boot_time())

                return SystemInfo(
                    cpu_percent=cpu_percent,
                    cpu_freq_current=cpu_freq_current,
                    cpu_freq_max=cpu_freq_max,
                    cpu_temp=cpu_temp,
                    cpu_cores=cpu_cores,
                    cpu_threads=cpu_threads,
                    ram_total=ram_total,
                    ram_used=ram_used,
                    ram_percent=ram_percent,
                    disk_total=disk_total,
                    disk_used=disk_used,
                    disk_free=disk_free,
                    disk_percent=disk_percent,
                    net_bytes_sent=net_speed.total_sent,
                    net_bytes_recv=net_speed.total_recv,
                    net_speed_up=net_speed.upload,
                    net_speed_down=net_speed.download,
                    platform=system_platform,
                    hostname=hostname,
                    uptime=uptime,
                )

            except Exception as e:
                logger.error(f"Failed to get system info: {e}")
                return None

    def get_network_speed(self) -> NetworkSpeed:
        """Get current network speed"""
        if not PSUTIL_AVAILABLE:
            return NetworkSpeed(0, 0, 0, 0)

        try:
            current_io = psutil.net_io_counters()
            current_time = time.time()

            if self._last_net_io is None:
                self._last_net_io = current_io
                self._last_net_time = current_time
                return NetworkSpeed(0, 0, current_io.bytes_sent, current_io.bytes_recv)

            time_diff = current_time - self._last_net_time
            if time_diff == 0:
                time_diff = 1

            bytes_sent_diff = current_io.bytes_sent - self._last_net_io.bytes_sent
            bytes_recv_diff = current_io.bytes_recv - self._last_net_io.bytes_recv

            upload_speed = (bytes_sent_diff / time_diff) / (1024 * 1024)  # MB/s
            download_speed = (bytes_recv_diff / time_diff) / (1024 * 1024)  # MB/s

            self._last_net_io = current_io
            self._last_net_time = current_time

            return NetworkSpeed(
                upload=round(upload_speed, 2),
                download=round(download_speed, 2),
                total_sent=current_io.bytes_sent,
                total_recv=current_io.bytes_recv,
            )

        except Exception as e:
            logger.error(f"Failed to get network speed: {e}")
            return NetworkSpeed(0, 0, 0, 0)

    def get_cpu_temperature(self) -> Optional[float]:
        """Get CPU temperature"""
        if not PSUTIL_AVAILABLE:
            return None

        try:
            temps = psutil.sensors_temperatures()
            if temps:
                # Try common sensor names
                for name in ['coretemp', 'cpu_thermal', 'k10temp', 'acpitz']:
                    if name in temps and temps[name]:
                        return temps[name][0].current
                # Fallback to first available
                for name, entries in temps.items():
                    if entries:
                        return entries[0].current
        except:
            pass

        return None

    def estimate_power_consumption(self, gpu_power: float) -> Tuple[float, float]:
        """
        Estimate total system power consumption and cost
        Returns: (total_watts, cost_per_hour in THB)
        """
        # Base system power (rough estimate)
        base_power = 100  # W for CPU, motherboard, RAM, etc.

        # CPU power (rough estimate based on usage)
        if PSUTIL_AVAILABLE:
            cpu_percent = psutil.cpu_percent(interval=0)
            # Assume 65W TDP CPU
            cpu_power = 65 * (cpu_percent / 100)
        else:
            cpu_power = 30

        total_power = base_power + cpu_power + gpu_power

        # Calculate cost (Thailand electricity rate ~4 THB/kWh)
        electricity_rate = 4.0  # THB per kWh
        cost_per_hour = (total_power / 1000) * electricity_rate

        return total_power, cost_per_hour


# Singleton instance
_system_monitor: Optional[SystemMonitor] = None


def get_system_monitor() -> SystemMonitor:
    """Get System Monitor singleton"""
    global _system_monitor
    if _system_monitor is None:
        _system_monitor = SystemMonitor()
    return _system_monitor
