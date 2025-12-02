@echo off
echo ============================================
echo   GPU Share Client - Build Script
echo   Version 1.0.0
echo ============================================
echo.

:: Check for Visual Studio
where cl >nul 2>nul
if %ERRORLEVEL% neq 0 (
    echo [ERROR] Visual Studio compiler (cl.exe) not found!
    echo.
    echo Please run this script from "Developer Command Prompt for VS"
    echo or install Visual Studio Build Tools.
    echo.
    echo Download: https://visualstudio.microsoft.com/downloads/
    pause
    exit /b 1
)

:: Create output directories
if not exist "build" mkdir build
if not exist "release" mkdir release

echo [1/3] Compiling source files...

:: Compile with MSVC
cl /nologo /O2 /W3 /MD ^
    /DUNICODE /D_UNICODE /DWIN32_LEAN_AND_MEAN ^
    /I"include" ^
    /Fe"build\GPUShareClient.exe" ^
    src\main.c src\gpu_monitor.c src\system_monitor.c src\api_client.c ^
    /link ^
    user32.lib gdi32.lib shell32.lib comctl32.lib ^
    psapi.lib iphlpapi.lib winhttp.lib ^
    /SUBSYSTEM:WINDOWS ^
    /MANIFEST:EMBED

if %ERRORLEVEL% neq 0 (
    echo.
    echo [ERROR] Compilation failed!
    pause
    exit /b 1
)

:: Clean up object files
del /q *.obj 2>nul

echo [2/3] Creating release package...

:: Copy to release folder
copy /y "build\GPUShareClient.exe" "release\GPUShareClient-1.0.0-windows.exe" >nul

echo [3/3] Generating checksums...

:: Generate SHA256 checksum (using PowerShell)
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
