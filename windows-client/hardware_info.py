"""
Hardware Information Collector - Anti-cheat compatible
"""
import hashlib
import platform
import subprocess
import json
from typing import Dict, Optional

try:
    import wmi
    import GPUtil
    import psutil
    WMI_AVAILABLE = True
except ImportError:
    WMI_AVAILABLE = False


class HardwareInfo:
    def __init__(self):
        self.wmi_client = wmi.WMI() if WMI_AVAILABLE else None

    def get_cpu_id(self) -> str:
        """Get CPU identifier"""
        if not self.wmi_client:
            return "unknown"
        try:
            cpu = self.wmi_client.Win32_Processor()[0]
            return cpu.ProcessorId.strip()
        except Exception:
            return "unknown"

    def get_motherboard_serial(self) -> str:
        """Get motherboard serial number"""
        if not self.wmi_client:
            return "unknown"
        try:
            board = self.wmi_client.Win32_BaseBoard()[0]
            return board.SerialNumber.strip()
        except Exception:
            return "unknown"

    def get_disk_serial(self) -> str:
        """Get primary disk serial number"""
        if not self.wmi_client:
            return "unknown"
        try:
            disks = self.wmi_client.Win32_DiskDrive()
            if disks:
                return disks[0].SerialNumber.strip() if disks[0].SerialNumber else "unknown"
        except Exception:
            pass
        return "unknown"

    def get_gpu_info(self) -> Dict:
        """Get GPU information"""
        try:
            gpus = GPUtil.getGPUs()
            if gpus:
                gpu = gpus[0]  # Primary GPU
                return {
                    'name': gpu.name,
                    'uuid': gpu.uuid,
                    'memory_total': int(gpu.memoryTotal),  # MB
                    'driver': gpu.driver,
                }
        except Exception:
            pass

        # Fallback to WMI
        if self.wmi_client:
            try:
                gpus = self.wmi_client.Win32_VideoController()
                if gpus:
                    gpu = gpus[0]
                    vram_bytes = gpu.AdapterRAM if gpu.AdapterRAM else 0
                    vram_mb = vram_bytes // (1024 * 1024) if vram_bytes > 0 else 4096
                    return {
                        'name': gpu.Name,
                        'uuid': gpu.DeviceID,
                        'memory_total': vram_mb,
                        'driver': gpu.DriverVersion,
                    }
            except Exception:
                pass

        return {
            'name': 'Unknown GPU',
            'uuid': 'unknown',
            'memory_total': 4096,
            'driver': 'unknown',
        }

    def get_gpu_metrics(self) -> Dict:
        """Get current GPU metrics (temperature, usage, etc.)"""
        try:
            gpus = GPUtil.getGPUs()
            if gpus:
                gpu = gpus[0]
                return {
                    'temperature': gpu.temperature,
                    'gpu_usage': int(gpu.load * 100),
                    'memory_usage': int(gpu.memoryUtil * 100),
                    'memory_used': int(gpu.memoryUsed),
                    'memory_free': int(gpu.memoryFree),
                }
        except Exception:
            pass

        return {
            'temperature': 0,
            'gpu_usage': 0,
            'memory_usage': 0,
            'memory_used': 0,
            'memory_free': 0,
        }

    def generate_machine_id(self) -> str:
        """Generate unique machine fingerprint for anti-cheat"""
        components = [
            self.get_cpu_id(),
            self.get_motherboard_serial(),
            self.get_disk_serial(),
            self.get_gpu_info().get('uuid', 'unknown'),
        ]

        combined = '|'.join(components)
        return hashlib.sha256(combined.encode()).hexdigest()

    def get_system_info(self) -> Dict:
        """Get complete system information"""
        gpu_info = self.get_gpu_info()
        gpu_metrics = self.get_gpu_metrics()

        return {
            'machine_id': self.generate_machine_id(),
            'platform': platform.system(),
            'platform_version': platform.version(),
            'processor': platform.processor(),
            'cpu_cores': psutil.cpu_count() if psutil else 0,
            'ram_total': psutil.virtual_memory().total // (1024 * 1024) if psutil else 0,
            'gpu_model': gpu_info['name'],
            'gpu_vram_mb': gpu_info['memory_total'],
            'gpu_uuid': gpu_info['uuid'],
            'gpu_driver': gpu_info['driver'],
            'gpu_metrics': gpu_metrics,
        }

    def get_registration_data(self) -> Dict:
        """Get data needed for node registration"""
        system_info = self.get_system_info()
        return {
            'machine_id': system_info['machine_id'],
            'hostname': platform.node(),
            'gpu_model': system_info['gpu_model'],
            'gpu_vram_mb': system_info['gpu_vram_mb'],
            'gpu_driver': system_info['gpu_driver'],
            'os_info': f"{system_info['platform']} {system_info['platform_version']}",
            'gpu_specs': {
                'uuid': system_info['gpu_uuid'],
                'driver': system_info['gpu_driver'],
                'platform': system_info['platform'],
            },
        }


if __name__ == '__main__':
    hw = HardwareInfo()
    print(json.dumps(hw.get_system_info(), indent=2))
