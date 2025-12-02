"""
GPU Share Client - Configuration
"""
import os
from pathlib import Path

# Application Info
APP_NAME = "GPU Share Client"
APP_VERSION = "1.0.0"
APP_AUTHOR = "Xman Studio Thailand"

# Paths
BASE_DIR = Path(__file__).parent.parent
CONFIG_DIR = Path.home() / ".gpushare"
CONFIG_FILE = CONFIG_DIR / "config.yaml"
LOG_FILE = CONFIG_DIR / "client.log"

# Create config directory if not exists
CONFIG_DIR.mkdir(parents=True, exist_ok=True)

# API Configuration
API_BASE_URL = os.getenv("GPU_SHARE_API_URL", "http://localhost/gpu-sharing-platform/public/api")
WS_URL = os.getenv("GPU_SHARE_WS_URL", "ws://localhost:8080")

# Default Settings
DEFAULT_CONFIG = {
    "api_url": API_BASE_URL,
    "api_token": "",
    "node_id": "",
    "auto_start": False,
    "start_minimized": False,
    "gpu_usage_limit": 90,  # Percent
    "temp_limit": 85,  # Celsius
    "power_limit": 100,  # Percent of TDP
    "fan_speed_auto": True,
    "fan_speed_min": 30,  # Percent
    "fan_speed_max": 100,  # Percent
    "bandwidth_limit": 0,  # 0 = unlimited (MB/s)
    "work_hours_enabled": False,
    "work_hours_start": "00:00",
    "work_hours_end": "23:59",
}

# Status Colors (RGB)
STATUS_COLORS = {
    "idle": "#6B7280",      # Gray
    "receiving": "#3B82F6", # Blue
    "processing": "#10B981", # Green
    "uploading": "#8B5CF6", # Purple
    "error": "#EF4444",     # Red
    "offline": "#374151",   # Dark Gray
}

# Job Types
JOB_TYPES = {
    "image": "Image Generation",
    "video": "Video Generation",
    "3d_render": "3D Rendering",
    "training": "Model Training",
}

# Refresh Intervals (seconds)
REFRESH_GPU_STATS = 1
REFRESH_SYSTEM_STATS = 5
REFRESH_JOB_STATUS = 2
HEARTBEAT_INTERVAL = 30

# Retry Configuration
MAX_RETRIES = 3
RETRY_DELAY = 5  # seconds

# Log Configuration
LOG_FORMAT = "%(asctime)s - %(name)s - %(levelname)s - %(message)s"
LOG_MAX_SIZE = 10 * 1024 * 1024  # 10 MB
LOG_BACKUP_COUNT = 5
