#!/usr/bin/env python3
"""
GPU Share Client - Build Script
Creates Windows/Linux executables using PyInstaller
"""
import os
import sys
import shutil
import hashlib
import json
import subprocess
from pathlib import Path
from datetime import datetime

# Build configuration
APP_NAME = "GPUShareClient"
VERSION = "1.0.0"
AUTHOR = "Xman Studio Thailand"
DESCRIPTION = "GPU Share Client - Share your GPU power and earn rewards"

# Paths
ROOT_DIR = Path(__file__).parent
SRC_DIR = ROOT_DIR / "src"
DIST_DIR = ROOT_DIR / "dist"
BUILD_DIR = ROOT_DIR / "build"
RELEASES_DIR = ROOT_DIR / "releases"


def clean_build():
    """Clean previous build artifacts"""
    print("Cleaning previous build...")
    for dir_path in [DIST_DIR, BUILD_DIR]:
        if dir_path.exists():
            shutil.rmtree(dir_path)
    print("Done!")


def check_requirements():
    """Check if required packages are installed"""
    print("Checking requirements...")

    try:
        import PyInstaller
        print(f"  PyInstaller: {PyInstaller.__version__}")
    except ImportError:
        print("  PyInstaller not found. Installing...")
        subprocess.run([sys.executable, "-m", "pip", "install", "pyinstaller"], check=True)

    # Check other requirements
    requirements = ["customtkinter", "pynvml", "psutil", "websocket-client", "requests", "pyyaml", "pillow"]
    for req in requirements:
        try:
            __import__(req.replace("-", "_"))
            print(f"  {req}: OK")
        except ImportError:
            print(f"  {req}: Missing - Installing...")
            subprocess.run([sys.executable, "-m", "pip", "install", req], check=True)

    print("All requirements satisfied!")


def get_platform():
    """Get current platform"""
    if sys.platform == "win32":
        return "windows"
    elif sys.platform == "linux":
        return "linux"
    elif sys.platform == "darwin":
        return "mac"
    return "unknown"


def build_executable():
    """Build the executable using PyInstaller"""
    platform = get_platform()
    print(f"\nBuilding for platform: {platform}")

    # PyInstaller options
    main_script = str(ROOT_DIR / "main.py")

    # Icon path (create a simple icon if needed)
    icon_option = []
    icon_path = ROOT_DIR / "assets" / "icon.ico"
    if icon_path.exists():
        icon_option = ["--icon", str(icon_path)]

    # Build command
    cmd = [
        sys.executable, "-m", "PyInstaller",
        "--name", APP_NAME,
        "--onefile",
        "--windowed" if platform == "windows" else "--console",
        "--clean",
        "--noconfirm",
        # Add hidden imports for CustomTkinter
        "--hidden-import", "customtkinter",
        "--hidden-import", "PIL._tkinter_finder",
        "--hidden-import", "pynvml",
        "--hidden-import", "psutil",
        # Collect data files
        "--collect-data", "customtkinter",
        # Add metadata
        f"--add-data={SRC_DIR}{os.pathsep}src",
    ] + icon_option + [main_script]

    print("Running PyInstaller...")
    print(" ".join(cmd))

    result = subprocess.run(cmd, cwd=str(ROOT_DIR))

    if result.returncode != 0:
        print("Build failed!")
        return None

    print("Build completed successfully!")

    # Find the built executable
    if platform == "windows":
        exe_name = f"{APP_NAME}.exe"
    else:
        exe_name = APP_NAME

    exe_path = DIST_DIR / exe_name

    if not exe_path.exists():
        print(f"Executable not found at {exe_path}")
        return None

    return exe_path


def calculate_checksum(file_path):
    """Calculate SHA256 checksum of a file"""
    sha256 = hashlib.sha256()
    with open(file_path, "rb") as f:
        for chunk in iter(lambda: f.read(8192), b""):
            sha256.update(chunk)
    return sha256.hexdigest()


def create_release(exe_path):
    """Create a release package"""
    platform = get_platform()

    # Create releases directory
    RELEASES_DIR.mkdir(exist_ok=True)

    # Create versioned filename
    if platform == "windows":
        release_name = f"{APP_NAME}-{VERSION}-windows.exe"
    else:
        release_name = f"{APP_NAME}-{VERSION}-{platform}"

    release_path = RELEASES_DIR / release_name

    # Copy executable
    print(f"\nCreating release: {release_name}")
    shutil.copy2(exe_path, release_path)

    # Calculate checksum
    checksum = calculate_checksum(release_path)
    file_size = release_path.stat().st_size

    # Create release info
    release_info = {
        "app_name": APP_NAME,
        "version": VERSION,
        "platform": platform,
        "filename": release_name,
        "file_size": file_size,
        "checksum_sha256": checksum,
        "build_date": datetime.now().isoformat(),
        "min_gpu_memory": "4GB",
        "min_ram": "8GB",
        "release_notes": f"GPU Share Client v{VERSION}\n- Initial release\n- GPU monitoring and fan control\n- Job processing with status indicators\n- Real-time earnings tracking",
    }

    # Save release info
    info_path = RELEASES_DIR / f"{APP_NAME}-{VERSION}-{platform}.json"
    with open(info_path, "w") as f:
        json.dump(release_info, f, indent=2)

    print(f"Release created: {release_path}")
    print(f"  Size: {file_size / 1024 / 1024:.2f} MB")
    print(f"  SHA256: {checksum}")
    print(f"  Info: {info_path}")

    return release_info


def create_source_zip():
    """Create source code zip for download"""
    print("\nCreating source code package...")

    RELEASES_DIR.mkdir(exist_ok=True)

    source_name = f"{APP_NAME}-{VERSION}-source"
    source_dir = RELEASES_DIR / source_name

    # Create temp directory
    if source_dir.exists():
        shutil.rmtree(source_dir)
    source_dir.mkdir()

    # Copy source files
    files_to_copy = [
        "main.py",
        "requirements.txt",
        "README.md",
    ]

    for f in files_to_copy:
        src = ROOT_DIR / f
        if src.exists():
            shutil.copy2(src, source_dir / f)

    # Copy src directory
    if SRC_DIR.exists():
        shutil.copytree(SRC_DIR, source_dir / "src")

    # Create zip
    zip_path = RELEASES_DIR / f"{source_name}.zip"
    shutil.make_archive(str(RELEASES_DIR / source_name), "zip", RELEASES_DIR, source_name)

    # Clean up temp directory
    shutil.rmtree(source_dir)

    # Calculate checksum
    checksum = calculate_checksum(zip_path)
    file_size = zip_path.stat().st_size

    # Create release info for source
    release_info = {
        "app_name": APP_NAME,
        "version": VERSION,
        "platform": "source",
        "filename": f"{source_name}.zip",
        "file_size": file_size,
        "checksum_sha256": checksum,
        "build_date": datetime.now().isoformat(),
        "release_notes": f"GPU Share Client v{VERSION} Source Code",
    }

    info_path = RELEASES_DIR / f"{source_name}.json"
    with open(info_path, "w") as f:
        json.dump(release_info, f, indent=2)

    print(f"Source package created: {zip_path}")
    print(f"  Size: {file_size / 1024:.2f} KB")
    print(f"  SHA256: {checksum}")

    return release_info


def main():
    """Main build process"""
    print("=" * 60)
    print(f"  {APP_NAME} Build Script v{VERSION}")
    print(f"  {AUTHOR}")
    print("=" * 60)

    # Check arguments
    if len(sys.argv) > 1:
        if sys.argv[1] == "--clean":
            clean_build()
            return
        elif sys.argv[1] == "--source-only":
            create_source_zip()
            return

    # Full build process
    clean_build()
    check_requirements()

    exe_path = build_executable()

    if exe_path:
        release_info = create_release(exe_path)
        source_info = create_source_zip()

        print("\n" + "=" * 60)
        print("  BUILD COMPLETE!")
        print("=" * 60)
        print(f"\nRelease files in: {RELEASES_DIR}")
        print("\nTo upload to server, copy the release files to:")
        print("  storage/app/public/client-releases/")
    else:
        print("\nBuild failed. Check the error messages above.")
        sys.exit(1)


if __name__ == "__main__":
    main()
