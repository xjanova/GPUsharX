# GPU Share Client - Windows

Professional GPU monitoring and sharing client for Windows.

## Features

- **Real-time GPU Monitoring**: Temperature, power usage, memory, fan speed
- **System Monitoring**: CPU, RAM, network bandwidth
- **Job Processing**: Receive and process jobs with status indicators
- **Earnings Tracking**: Real-time earnings display
- **Bilingual Support**: English and Thai languages
- **Modern UI**: Dark theme dashboard design

## Requirements

- Windows 10/11 (64-bit)
- NVIDIA GPU with driver installed
- 8GB RAM minimum
- Internet connection

## Building from Source

### Option 1: Visual Studio (Recommended)

1. Install Visual Studio 2019+ with "Desktop development with C++"
2. Open "Developer Command Prompt for VS"
3. Navigate to this directory
4. Run: `build.bat`

### Option 2: MinGW-w64

1. Install MinGW-w64 (MSYS2 recommended)
2. Add MinGW bin to PATH
3. Open Command Prompt
4. Navigate to this directory
5. Run: `build-mingw.bat`

### Option 3: CMake

```bash
mkdir build
cd build
cmake .. -G "Visual Studio 17 2022"
cmake --build . --config Release
```

## Project Structure

```
client-windows/
├── include/
│   ├── config.h      # App configuration
│   ├── lang.h        # Localization strings
│   └── types.h       # Type definitions
├── src/
│   ├── main.c        # Main application & GUI
│   ├── gpu_monitor.c # NVML GPU monitoring
│   ├── system_monitor.c # CPU/RAM/Network
│   └── api_client.c  # HTTP API client
├── build.bat         # MSVC build script
├── build-mingw.bat   # MinGW build script
└── CMakeLists.txt    # CMake configuration
```

## Status Indicators

| Color | Status | Description |
|-------|--------|-------------|
| Gray | Idle | Waiting for jobs |
| Blue (blink) | Receiving | Downloading job data |
| Green (blink) | Processing | GPU is working |
| Purple (blink) | Uploading | Sending results |
| Red | Error | Job failed |

## Language Support

Click **EN** or **TH** buttons in the top-right corner to switch languages.

- **EN**: English
- **TH**: ภาษาไทย

## License

Copyright (c) 2024 Xman Studio Thailand. All rights reserved.
