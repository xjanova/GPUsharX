@echo off
echo ============================================
echo   GPU Share Client - MinGW Build Script
echo   Version 1.0.0
echo ============================================
echo.

:: Check for GCC
where gcc >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] GCC compiler not found!
    echo.
    echo Please install MinGW-w64 or add it to PATH.
    echo Download: https://www.mingw-w64.org/
    echo.
    echo Or install via MSYS2:
    echo   pacman -S mingw-w64-x86_64-gcc
    pause
    exit /b 1
)

:: Create output directories
if not exist "build" mkdir build
if not exist "release" mkdir release

echo [1/3] Compiling source files...

:: Compile with GCC
gcc -o build/GPUShareClient.exe ^
    -O2 -Wall -Wextra ^
    -DUNICODE -D_UNICODE -DWIN32_LEAN_AND_MEAN ^
    -I include ^
    -mwindows ^
    src/main.c src/gpu_monitor.c src/system_monitor.c src/api_client.c ^
    -luser32 -lgdi32 -lshell32 -lcomctl32 ^
    -lpsapi -liphlpapi -lwinhttp

if %ERRORLEVEL% neq 0 (
    echo.
    echo [ERROR] Compilation failed!
    pause
    exit /b 1
)

echo [2/3] Creating release package...

:: Copy to release folder
copy /y "build\GPUShareClient.exe" "release\GPUShareClient-1.0.0-windows.exe" >nul

echo [3/3] Generating checksums...

:: Generate SHA256 checksum
powershell -Command "(Get-FileHash 'release\GPUShareClient-1.0.0-windows.exe' -Algorithm SHA256).Hash" > "release\checksum.txt"

echo.
echo ============================================
echo   Build Complete!
echo ============================================
echo.
echo Output: release\GPUShareClient-1.0.0-windows.exe
echo.

:: Show file size
for %%A in ("release\GPUShareClient-1.0.0-windows.exe") do (
    echo Size: %%~zA bytes
)

echo.
pause
