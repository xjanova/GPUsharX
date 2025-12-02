@echo off
echo Building GPU Sharing Platform Client...
echo.

REM Check Python
python --version >nul 2>&1
if errorlevel 1 (
    echo Python not found. Please install Python 3.10 or higher.
    pause
    exit /b 1
)

REM Create virtual environment
if not exist "venv" (
    echo Creating virtual environment...
    python -m venv venv
)

REM Activate and install dependencies
echo Installing dependencies...
call venv\Scripts\activate.bat
pip install -r requirements.txt
pip install pyinstaller

REM Build executable
echo Building executable...
pyinstaller --onefile --windowed --name "GPUShareClient" --icon=icon.ico main.py

echo.
echo Build complete! Executable is in dist/GPUShareClient.exe
pause
