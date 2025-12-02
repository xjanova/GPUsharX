# GPU Share Client

Share your GPU power and earn rewards with GPU Share Platform.

## Features

- **GPU Monitoring**: Real-time temperature, power, memory, and utilization monitoring
- **Fan Control**: Adjust GPU fan speed for optimal cooling
- **System Monitoring**: CPU, RAM, and network bandwidth tracking
- **Power Estimation**: Track power consumption and electricity cost
- **Job Processing**: Automatic job receiving and processing
- **Status Indicators**: Visual status lights for job state
- **Earnings Tracking**: Real-time earnings display

## Requirements

### Hardware
- NVIDIA GPU (GTX 1060 6GB or higher recommended)
- 8GB RAM minimum
- Stable internet connection (10+ Mbps)

### Software
- Windows 10/11 or Ubuntu 20.04+
- Python 3.10 or higher
- NVIDIA drivers with CUDA support

## Installation

### From Source

1. Clone or download the repository

2. Install Python dependencies:
```bash
cd client
pip install -r requirements.txt
```

3. Run the client:
```bash
python main.py
```

### Windows Installer

Download the Windows installer from the platform website.

## Usage

1. **Login**: Enter your GPU Share Platform credentials
2. **Configure**: Set temperature limits and fan speed preferences
3. **Start Mining**: Click "Start Mining" to begin receiving jobs
4. **Monitor**: Watch your GPU stats and earnings in real-time

## Status Indicators

| Color | Status | Description |
|-------|--------|-------------|
| Gray | Idle | Waiting for jobs |
| Blue | Receiving | Downloading job data |
| Green | Processing | GPU is working |
| Purple | Uploading | Uploading results |
| Red | Error | Job failed |

## Troubleshooting

### Jobs Keep Failing

1. **Check Internet Connection**: Ensure stable connection
2. **Check GPU Temperature**: Keep below 85°C
3. **Disable Overclocking**: Reset GPU to default clocks
4. **Update Drivers**: Install latest NVIDIA drivers

### High Temperature

1. Improve case airflow
2. Increase fan speed in settings
3. Clean dust from GPU heatsink
4. Lower temperature limit in settings

### Client Won't Start

1. Ensure Python 3.10+ is installed
2. Install all requirements: `pip install -r requirements.txt`
3. Check the log file: `~/.gpushare/client.log`

## Configuration

Config file location: `~/.gpushare/config.yaml`

```yaml
api_url: "http://your-server.com/api"
api_token: "your-api-token"
auto_start: false
gpu_usage_limit: 90
temp_limit: 85
fan_speed_auto: true
```

## API Endpoints

The client communicates with these API endpoints:

- `POST /api/auth/login` - Authentication
- `POST /api/node/register` - Register GPU node
- `POST /api/node/{id}/heartbeat` - Keep-alive signal
- `GET /api/node/{id}/job` - Get pending job
- `POST /api/job/{id}/complete` - Submit job result

## Development

### Project Structure

```
client/
├── main.py              # Entry point
├── requirements.txt     # Python dependencies
├── README.md           # This file
└── src/
    ├── __init__.py
    ├── config.py        # Configuration
    ├── gpu_monitor.py   # GPU monitoring (NVML)
    ├── system_monitor.py # System monitoring (psutil)
    ├── api_client.py    # API communication
    ├── job_worker.py    # Job processing
    └── gui.py           # GUI (CustomTkinter)
```

### Building Executable

Using PyInstaller:

```bash
pip install pyinstaller
pyinstaller --onefile --windowed --name GPUShareClient main.py
```

## License

Copyright (c) 2024 Xman Studio Thailand. All rights reserved.

## Support

- Website: https://gpu-share.xman.studio
- Email: support@xman.studio
- Discord: https://discord.gg/gpushare
