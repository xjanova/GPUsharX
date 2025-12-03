@echo off
echo ========================================
echo GPU Share - AI Engine Setup
echo ========================================
echo.

REM Check for Python
python --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python 3.10+ from https://www.python.org/
    pause
    exit /b 1
)

echo Python found. Installing dependencies...
echo.

REM Upgrade pip
python -m pip install --upgrade pip

REM Install PyTorch with CUDA 12.1 support
echo.
echo Installing PyTorch with CUDA support...
python -m pip install torch torchvision --index-url https://download.pytorch.org/whl/cu121

REM Install Diffusers and related packages
echo.
echo Installing Diffusers and AI packages...
python -m pip install diffusers transformers accelerate safetensors

REM Install other dependencies
echo.
echo Installing additional dependencies...
python -m pip install pillow huggingface_hub numpy requests

REM Install for image/video processing
python -m pip install opencv-python imageio-ffmpeg

echo.
echo ========================================
echo Installation Complete!
echo ========================================
echo.
echo To verify installation, run:
echo   python ai_engine.py --status
echo.
echo To install a model:
echo   python ai_engine.py --generate "a beautiful sunset" --model sd-1.5
echo.
pause
