"""
GPU Share Client - API Client
Handles communication with the GPU Share Platform API
"""
import logging
import json
import time
from typing import Optional, Dict, Any, Callable
import threading
import queue

logger = logging.getLogger(__name__)

try:
    import requests
    REQUESTS_AVAILABLE = True
except ImportError:
    REQUESTS_AVAILABLE = False
    logger.error("requests not available")

try:
    import websocket
    WEBSOCKET_AVAILABLE = True
except ImportError:
    WEBSOCKET_AVAILABLE = False
    logger.warning("websocket-client not available, real-time updates disabled")


class APIClient:
    """API Client for GPU Share Platform"""

    def __init__(self, base_url: str, token: str = ""):
        self.base_url = base_url.rstrip('/')
        self.token = token
        self.node_id = ""
        self.session = requests.Session() if REQUESTS_AVAILABLE else None
        self._update_headers()

    def _update_headers(self):
        """Update session headers with auth token"""
        if self.session and self.token:
            self.session.headers.update({
                'Authorization': f'Bearer {self.token}',
                'Accept': 'application/json',
                'Content-Type': 'application/json',
            })

    def set_token(self, token: str):
        """Set API token"""
        self.token = token
        self._update_headers()

    def _request(self, method: str, endpoint: str, data: Dict = None, timeout: int = 30) -> Dict:
        """Make API request"""
        if not self.session:
            return {'success': False, 'error': 'requests not available'}

        url = f"{self.base_url}/{endpoint.lstrip('/')}"

        try:
            if method == 'GET':
                response = self.session.get(url, params=data, timeout=timeout)
            elif method == 'POST':
                response = self.session.post(url, json=data, timeout=timeout)
            elif method == 'PUT':
                response = self.session.put(url, json=data, timeout=timeout)
            elif method == 'DELETE':
                response = self.session.delete(url, timeout=timeout)
            else:
                return {'success': False, 'error': f'Unknown method: {method}'}

            response.raise_for_status()
            return response.json()

        except requests.exceptions.Timeout:
            logger.error(f"Request timeout: {url}")
            return {'success': False, 'error': 'Request timeout'}
        except requests.exceptions.ConnectionError:
            logger.error(f"Connection error: {url}")
            return {'success': False, 'error': 'Connection error'}
        except requests.exceptions.HTTPError as e:
            logger.error(f"HTTP error: {e}")
            try:
                return response.json()
            except:
                return {'success': False, 'error': str(e)}
        except Exception as e:
            logger.error(f"Request error: {e}")
            return {'success': False, 'error': str(e)}

    # Authentication
    def login(self, email: str, password: str) -> Dict:
        """Login and get API token"""
        result = self._request('POST', '/auth/login', {
            'email': email,
            'password': password,
        })

        if result.get('success') and result.get('token'):
            self.set_token(result['token'])

        return result

    def get_profile(self) -> Dict:
        """Get current user profile"""
        return self._request('GET', '/user/profile')

    # Node Registration
    def register_node(self, gpu_info: Dict, system_info: Dict) -> Dict:
        """Register GPU node"""
        result = self._request('POST', '/node/register', {
            'gpu_info': gpu_info,
            'system_info': system_info,
        })

        if result.get('success') and result.get('node_id'):
            self.node_id = result['node_id']

        return result

    def update_node_status(self, status: str, gpu_stats: Dict = None, system_stats: Dict = None) -> Dict:
        """Update node status"""
        data = {'status': status}
        if gpu_stats:
            data['gpu_stats'] = gpu_stats
        if system_stats:
            data['system_stats'] = system_stats

        return self._request('POST', f'/node/{self.node_id}/status', data)

    def heartbeat(self, gpu_stats: Dict = None, system_stats: Dict = None) -> Dict:
        """Send heartbeat to keep node alive"""
        data = {}
        if gpu_stats:
            data['gpu_stats'] = gpu_stats
        if system_stats:
            data['system_stats'] = system_stats

        return self._request('POST', f'/node/{self.node_id}/heartbeat', data)

    # Jobs
    def get_pending_job(self) -> Dict:
        """Get next pending job for this node"""
        return self._request('GET', f'/node/{self.node_id}/job')

    def accept_job(self, job_id: str) -> Dict:
        """Accept a job"""
        return self._request('POST', f'/job/{job_id}/accept', {
            'node_id': self.node_id,
        })

    def update_job_progress(self, job_id: str, progress: int, status: str = 'processing') -> Dict:
        """Update job progress"""
        return self._request('POST', f'/job/{job_id}/progress', {
            'progress': progress,
            'status': status,
        })

    def complete_job(self, job_id: str, result_url: str, metadata: Dict = None) -> Dict:
        """Complete a job"""
        data = {
            'result_url': result_url,
            'status': 'completed',
        }
        if metadata:
            data['metadata'] = metadata

        return self._request('POST', f'/job/{job_id}/complete', data)

    def fail_job(self, job_id: str, error: str) -> Dict:
        """Report job failure"""
        return self._request('POST', f'/job/{job_id}/fail', {
            'error': error,
        })

    # Earnings
    def get_earnings_summary(self) -> Dict:
        """Get earnings summary"""
        return self._request('GET', '/user/earnings')

    def get_node_stats(self) -> Dict:
        """Get node statistics"""
        return self._request('GET', f'/node/{self.node_id}/stats')


class WebSocketClient:
    """WebSocket client for real-time updates"""

    def __init__(self, ws_url: str, token: str = ""):
        self.ws_url = ws_url
        self.token = token
        self.ws = None
        self.connected = False
        self._callbacks: Dict[str, Callable] = {}
        self._message_queue = queue.Queue()
        self._thread = None
        self._running = False

    def set_callback(self, event: str, callback: Callable):
        """Set callback for event"""
        self._callbacks[event] = callback

    def connect(self):
        """Connect to WebSocket server"""
        if not WEBSOCKET_AVAILABLE:
            logger.error("websocket-client not available")
            return False

        try:
            self.ws = websocket.WebSocketApp(
                self.ws_url,
                header={'Authorization': f'Bearer {self.token}'},
                on_message=self._on_message,
                on_error=self._on_error,
                on_close=self._on_close,
                on_open=self._on_open,
            )

            self._running = True
            self._thread = threading.Thread(target=self._run)
            self._thread.daemon = True
            self._thread.start()

            return True
        except Exception as e:
            logger.error(f"Failed to connect WebSocket: {e}")
            return False

    def _run(self):
        """Run WebSocket connection"""
        while self._running:
            try:
                self.ws.run_forever()
            except Exception as e:
                logger.error(f"WebSocket error: {e}")

            if self._running:
                time.sleep(5)  # Reconnect delay

    def _on_open(self, ws):
        """WebSocket opened"""
        self.connected = True
        logger.info("WebSocket connected")
        if 'connected' in self._callbacks:
            self._callbacks['connected']()

    def _on_message(self, ws, message):
        """Handle incoming message"""
        try:
            data = json.loads(message)
            event = data.get('event', 'message')

            if event in self._callbacks:
                self._callbacks[event](data)
            elif 'message' in self._callbacks:
                self._callbacks['message'](data)

        except json.JSONDecodeError:
            logger.error(f"Invalid JSON message: {message}")

    def _on_error(self, ws, error):
        """Handle WebSocket error"""
        logger.error(f"WebSocket error: {error}")
        if 'error' in self._callbacks:
            self._callbacks['error'](error)

    def _on_close(self, ws, close_status_code, close_msg):
        """Handle WebSocket close"""
        self.connected = False
        logger.info(f"WebSocket closed: {close_status_code} {close_msg}")
        if 'disconnected' in self._callbacks:
            self._callbacks['disconnected']()

    def send(self, event: str, data: Dict = None):
        """Send message"""
        if self.ws and self.connected:
            message = json.dumps({'event': event, 'data': data or {}})
            self.ws.send(message)

    def disconnect(self):
        """Disconnect WebSocket"""
        self._running = False
        if self.ws:
            self.ws.close()


# Singleton instances
_api_client: Optional[APIClient] = None
_ws_client: Optional[WebSocketClient] = None


def get_api_client(base_url: str = None, token: str = None) -> APIClient:
    """Get API Client singleton"""
    global _api_client
    if _api_client is None:
        from .config import API_BASE_URL
        _api_client = APIClient(base_url or API_BASE_URL, token or "")
    elif token:
        _api_client.set_token(token)
    return _api_client


def get_ws_client(ws_url: str = None, token: str = None) -> WebSocketClient:
    """Get WebSocket Client singleton"""
    global _ws_client
    if _ws_client is None:
        from .config import WS_URL
        _ws_client = WebSocketClient(ws_url or WS_URL, token or "")
    return _ws_client
