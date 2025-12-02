"""
GPU Sharing Platform - Windows Client Configuration
"""
import os
from dotenv import load_dotenv

load_dotenv()

# API Configuration
API_BASE_URL = os.getenv('API_BASE_URL', 'http://localhost:8000/api')
API_TIMEOUT = 30

# Heartbeat interval in seconds
HEARTBEAT_INTERVAL = 30

# Work polling interval in seconds
WORK_POLL_INTERVAL = 5

# Benchmark settings
BENCHMARK_ITERATIONS = 1000
BENCHMARK_MATRIX_SIZE = 1024

# Anti-cheat
VERIFICATION_TIMEOUT = 60

# Logging
LOG_FILE = 'gpu_client.log'
LOG_LEVEL = 'INFO'

# Client version
CLIENT_VERSION = '1.0.0'
