#!/usr/bin/env python3
"""
GPU Share Client - Main Entry Point
Xman Studio Thailand
"""
import sys
import logging
from pathlib import Path

# Add src to path
sys.path.insert(0, str(Path(__file__).parent))

from src.config import LOG_FILE, LOG_FORMAT, APP_NAME, APP_VERSION

# Setup logging
logging.basicConfig(
    level=logging.INFO,
    format=LOG_FORMAT,
    handlers=[
        logging.FileHandler(LOG_FILE, encoding='utf-8'),
        logging.StreamHandler(sys.stdout),
    ]
)

logger = logging.getLogger(__name__)


def main():
    """Main entry point"""
    logger.info(f"Starting {APP_NAME} v{APP_VERSION}")

    try:
        from src.gui import run_app
        run_app()
    except ImportError as e:
        logger.error(f"Failed to import GUI: {e}")
        print("\nMissing dependencies. Please install requirements:")
        print("  pip install -r requirements.txt")
        sys.exit(1)
    except Exception as e:
        logger.exception(f"Application error: {e}")
        sys.exit(1)


if __name__ == "__main__":
    main()
