"""
GPU Share Client - Job Worker
Handles job processing, status updates, and failure handling
"""
import logging
import time
import threading
from typing import Optional, Dict, Callable
from enum import Enum
from dataclasses import dataclass

logger = logging.getLogger(__name__)


class JobStatus(Enum):
    """Job Status Enum"""
    IDLE = "idle"
    RECEIVING = "receiving"
    PROCESSING = "processing"
    UPLOADING = "uploading"
    COMPLETED = "completed"
    FAILED = "failed"


@dataclass
class JobInfo:
    """Job Information"""
    job_id: str
    job_type: str
    model_id: str
    prompt: str
    params: Dict
    status: JobStatus
    progress: int
    started_at: float
    error: Optional[str] = None


class JobWorker:
    """Job Worker - Handles job processing"""

    def __init__(self, api_client, gpu_monitor, system_monitor):
        self.api_client = api_client
        self.gpu_monitor = gpu_monitor
        self.system_monitor = system_monitor

        self._running = False
        self._current_job: Optional[JobInfo] = None
        self._status = JobStatus.IDLE
        self._thread: Optional[threading.Thread] = None

        # Callbacks
        self._on_status_change: Optional[Callable] = None
        self._on_job_received: Optional[Callable] = None
        self._on_job_completed: Optional[Callable] = None
        self._on_job_failed: Optional[Callable] = None
        self._on_earnings_update: Optional[Callable] = None

        # Stats
        self.jobs_completed = 0
        self.jobs_failed = 0
        self.total_earned = 0.0
        self.consecutive_failures = 0

        # Limits
        self.max_consecutive_failures = 5
        self.temp_limit = 85  # Celsius
        self.gpu_usage_limit = 90  # Percent

    def set_callbacks(
        self,
        on_status_change: Callable = None,
        on_job_received: Callable = None,
        on_job_completed: Callable = None,
        on_job_failed: Callable = None,
        on_earnings_update: Callable = None,
    ):
        """Set event callbacks"""
        self._on_status_change = on_status_change
        self._on_job_received = on_job_received
        self._on_job_completed = on_job_completed
        self._on_job_failed = on_job_failed
        self._on_earnings_update = on_earnings_update

    def set_limits(self, temp_limit: int = 85, gpu_usage_limit: int = 90):
        """Set operating limits"""
        self.temp_limit = temp_limit
        self.gpu_usage_limit = gpu_usage_limit

    @property
    def status(self) -> JobStatus:
        return self._status

    @property
    def current_job(self) -> Optional[JobInfo]:
        return self._current_job

    def _set_status(self, status: JobStatus):
        """Set status and trigger callback"""
        old_status = self._status
        self._status = status
        if self._on_status_change and old_status != status:
            self._on_status_change(status)

    def start(self):
        """Start job worker"""
        if self._running:
            return

        self._running = True
        self._thread = threading.Thread(target=self._worker_loop)
        self._thread.daemon = True
        self._thread.start()
        logger.info("Job worker started")

    def stop(self):
        """Stop job worker"""
        self._running = False
        if self._thread:
            self._thread.join(timeout=5)
        self._set_status(JobStatus.IDLE)
        logger.info("Job worker stopped")

    def _worker_loop(self):
        """Main worker loop"""
        while self._running:
            try:
                # Check safety limits
                if not self._check_safety():
                    time.sleep(10)
                    continue

                # Check for consecutive failures
                if self.consecutive_failures >= self.max_consecutive_failures:
                    logger.warning("Too many consecutive failures, pausing...")
                    self._set_status(JobStatus.IDLE)
                    time.sleep(60)  # Pause for 1 minute
                    self.consecutive_failures = 0
                    continue

                # If idle, request new job
                if self._status == JobStatus.IDLE:
                    self._request_job()

                # Process current job
                if self._current_job and self._status == JobStatus.PROCESSING:
                    self._process_job()

                time.sleep(2)  # Poll interval

            except Exception as e:
                logger.error(f"Worker loop error: {e}")
                time.sleep(5)

    def _check_safety(self) -> bool:
        """Check if it's safe to continue working"""
        gpu_info = self.gpu_monitor.get_gpu_info(0)
        if not gpu_info:
            return True

        # Check temperature
        if gpu_info.temperature > self.temp_limit:
            logger.warning(f"GPU temperature too high: {gpu_info.temperature}°C")
            return False

        # Check GPU utilization if we're not processing
        if self._status == JobStatus.IDLE and gpu_info.gpu_util > self.gpu_usage_limit:
            logger.warning(f"GPU usage too high: {gpu_info.gpu_util}%")
            return False

        return True

    def _request_job(self):
        """Request a new job from the API"""
        try:
            result = self.api_client.get_pending_job()

            if result.get('success') and result.get('job'):
                job_data = result['job']
                self._current_job = JobInfo(
                    job_id=job_data['job_id'],
                    job_type=job_data['type'],
                    model_id=job_data.get('model_id', ''),
                    prompt=job_data.get('prompt', ''),
                    params=job_data.get('params', {}),
                    status=JobStatus.RECEIVING,
                    progress=0,
                    started_at=time.time(),
                )

                self._set_status(JobStatus.RECEIVING)

                if self._on_job_received:
                    self._on_job_received(self._current_job)

                # Accept the job
                accept_result = self.api_client.accept_job(self._current_job.job_id)
                if accept_result.get('success'):
                    self._set_status(JobStatus.PROCESSING)
                else:
                    self._fail_current_job("Failed to accept job")

        except Exception as e:
            logger.error(f"Failed to request job: {e}")

    def _process_job(self):
        """Process the current job"""
        if not self._current_job:
            return

        job = self._current_job

        try:
            # Simulate processing (in real implementation, this would call the AI model)
            # For now, we just update progress
            for progress in range(job.progress, 101, 10):
                if not self._running:
                    break

                job.progress = progress
                self.api_client.update_job_progress(job.job_id, progress)

                # Check safety during processing
                if not self._check_safety():
                    self._fail_current_job("GPU overheating or overloaded")
                    return

                time.sleep(1)  # Simulate work

            # Complete the job
            self._complete_current_job()

        except Exception as e:
            logger.error(f"Job processing error: {e}")
            self._fail_current_job(str(e))

    def _complete_current_job(self):
        """Mark current job as completed"""
        if not self._current_job:
            return

        job = self._current_job

        try:
            self._set_status(JobStatus.UPLOADING)

            # In real implementation, upload result here
            result_url = f"/results/{job.job_id}"

            processing_time = time.time() - job.started_at

            result = self.api_client.complete_job(
                job.job_id,
                result_url,
                metadata={
                    'processing_time_ms': int(processing_time * 1000),
                    'node_id': self.api_client.node_id,
                }
            )

            if result.get('success'):
                self.jobs_completed += 1
                self.consecutive_failures = 0

                if result.get('earned'):
                    self.total_earned += result['earned']
                    if self._on_earnings_update:
                        self._on_earnings_update(result['earned'], self.total_earned)

                if self._on_job_completed:
                    self._on_job_completed(job)

                logger.info(f"Job {job.job_id} completed successfully")
            else:
                self._fail_current_job("Failed to submit result")
                return

        except Exception as e:
            self._fail_current_job(str(e))
            return

        self._current_job = None
        self._set_status(JobStatus.IDLE)

    def _fail_current_job(self, error: str):
        """Mark current job as failed"""
        if not self._current_job:
            return

        job = self._current_job
        job.status = JobStatus.FAILED
        job.error = error

        try:
            self.api_client.fail_job(job.job_id, error)
        except Exception as e:
            logger.error(f"Failed to report job failure: {e}")

        self.jobs_failed += 1
        self.consecutive_failures += 1

        if self._on_job_failed:
            self._on_job_failed(job, error)

        logger.error(f"Job {job.job_id} failed: {error}")

        # Provide suggestions for common failures
        if self.consecutive_failures >= 3:
            suggestions = self._get_failure_suggestions()
            logger.warning(f"Suggestions: {suggestions}")

        self._current_job = None
        self._set_status(JobStatus.FAILED)

        # Short delay before returning to idle
        time.sleep(3)
        self._set_status(JobStatus.IDLE)

    def _get_failure_suggestions(self) -> str:
        """Get suggestions for resolving failures"""
        suggestions = []

        # Check GPU temperature
        gpu_info = self.gpu_monitor.get_gpu_info(0)
        if gpu_info and gpu_info.temperature > 80:
            suggestions.append("GPU is running hot. Consider improving cooling or reducing fan speed.")

        # Check system stats
        system_info = self.system_monitor.get_system_info()
        if system_info:
            if system_info.ram_percent > 90:
                suggestions.append("System RAM is nearly full. Close other applications.")

            if system_info.net_speed_down < 1:
                suggestions.append("Network connection may be slow. Check internet connection.")

        # General suggestions
        suggestions.append("If using overclocking, try resetting to default clocks.")
        suggestions.append("Ensure GPU drivers are up to date.")

        return " | ".join(suggestions)

    def get_stats(self) -> Dict:
        """Get worker statistics"""
        return {
            'status': self._status.value,
            'jobs_completed': self.jobs_completed,
            'jobs_failed': self.jobs_failed,
            'total_earned': self.total_earned,
            'consecutive_failures': self.consecutive_failures,
            'current_job': self._current_job.job_id if self._current_job else None,
        }
