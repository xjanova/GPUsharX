<?php

namespace App\Services;

use App\Models\User;
use App\Models\GenerationJob;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GoogleDriveService
{
    protected $client = null;
    protected bool $isAvailable = false;

    public function __construct()
    {
        // Check if Google API Client is installed
        if (!class_exists('\Google\Client')) {
            Log::warning('Google API Client not installed. Run: composer require google/apiclient');
            return;
        }

        try {
            $this->client = new \Google\Client();
            $this->client->setClientId(config('services.google.client_id'));
            $this->client->setClientSecret(config('services.google.client_secret'));
            $this->client->setRedirectUri(config('services.google.redirect_drive'));
            $this->client->addScope(\Google\Service\Drive::DRIVE_FILE);
            $this->client->setAccessType('offline');
            $this->client->setPrompt('consent');
            $this->isAvailable = true;
        } catch (\Exception $e) {
            Log::error('Failed to initialize Google Client', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Check if Google Drive service is available
     */
    public function isAvailable(): bool
    {
        return $this->isAvailable && $this->client !== null;
    }

    /**
     * Get authorization URL
     */
    public function getAuthUrl(): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }
        return $this->client->createAuthUrl();
    }

    /**
     * Handle OAuth callback
     */
    public function handleCallback(string $code, User $user): bool
    {
        if (!$this->isAvailable()) {
            return false;
        }

        try {
            $token = $this->client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                Log::error('Google OAuth error', $token);
                return false;
            }

            // Get user info
            $this->client->setAccessToken($token);
            $oauth2 = new \Google\Service\Oauth2($this->client);
            $userInfo = $oauth2->userinfo->get();

            // Save tokens to user
            $user->update([
                'google_id' => $userInfo->id,
                'google_email' => $userInfo->email,
                'google_access_token' => $token['access_token'],
                'google_refresh_token' => $token['refresh_token'] ?? $user->google_refresh_token,
                'google_token_expires_at' => now()->addSeconds($token['expires_in'] ?? 3600),
            ]);

            // Create folder in Google Drive
            $this->createAppFolder($user);

            return true;
        } catch (\Exception $e) {
            Log::error('Google Drive connection failed', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Create app folder in Google Drive
     */
    public function createAppFolder(User $user): ?string
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $this->setUserToken($user);
            $service = new \Google\Service\Drive($this->client);

            // Check if folder already exists
            if ($user->google_drive_folder_id) {
                try {
                    $service->files->get($user->google_drive_folder_id);
                    return $user->google_drive_folder_id;
                } catch (\Exception $e) {
                    // Folder doesn't exist, create new
                }
            }

            // Create new folder
            $folderMetadata = new \Google\Service\Drive\DriveFile([
                'name' => 'GPU Share Platform',
                'mimeType' => 'application/vnd.google-apps.folder',
            ]);

            $folder = $service->files->create($folderMetadata, [
                'fields' => 'id',
            ]);

            $user->update(['google_drive_folder_id' => $folder->id]);

            return $folder->id;
        } catch (\Exception $e) {
            Log::error('Failed to create Google Drive folder', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Upload file to Google Drive
     */
    public function uploadFile(User $user, string $localPath, string $filename, string $mimeType = 'image/png'): ?array
    {
        if (!$this->isAvailable()) {
            return null;
        }

        try {
            $this->setUserToken($user);
            $service = new \Google\Service\Drive($this->client);

            // Ensure folder exists
            $folderId = $user->google_drive_folder_id ?? $this->createAppFolder($user);

            if (!$folderId) {
                throw new \Exception('Unable to create Google Drive folder');
            }

            // Create file metadata
            $fileMetadata = new \Google\Service\Drive\DriveFile([
                'name' => $filename,
                'parents' => [$folderId],
            ]);

            // Get file content
            $content = file_get_contents($localPath);

            // Upload file
            $file = $service->files->create($fileMetadata, [
                'data' => $content,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id, webViewLink, webContentLink',
            ]);

            return [
                'id' => $file->id,
                'view_link' => $file->webViewLink,
                'download_link' => $file->webContentLink,
            ];
        } catch (\Exception $e) {
            Log::error('Failed to upload to Google Drive', [
                'user_id' => $user->id,
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Upload generation result to Google Drive
     */
    public function uploadGenerationResult(GenerationJob $job): ?array
    {
        $user = $job->user;

        if (!$user || !$user->google_drive_connected) {
            return null;
        }

        if (!$job->result_url) {
            return null;
        }

        try {
            // Download from result URL or get from storage
            $localPath = null;

            if (filter_var($job->result_url, FILTER_VALIDATE_URL)) {
                // External URL - download temp
                $tempPath = storage_path('app/temp/' . $job->job_id . '.png');
                file_put_contents($tempPath, file_get_contents($job->result_url));
                $localPath = $tempPath;
            } else {
                // Local storage
                $localPath = Storage::disk('public')->path($job->result_url);
            }

            if (!$localPath || !file_exists($localPath)) {
                return null;
            }

            // Determine filename and mime type
            $extension = $job->type === 'video' ? 'mp4' : 'png';
            $mimeType = $job->type === 'video' ? 'video/mp4' : 'image/png';
            $filename = "GPU_Share_{$job->job_id}.{$extension}";

            // Upload to Google Drive
            $result = $this->uploadFile($user, $localPath, $filename, $mimeType);

            // Clean up temp file
            if (isset($tempPath) && file_exists($tempPath)) {
                unlink($tempPath);
            }

            // Update job with Google Drive info
            if ($result) {
                $job->update([
                    'result_metadata' => array_merge($job->result_metadata ?? [], [
                        'google_drive' => $result,
                        'uploaded_at' => now()->toIso8601String(),
                    ]),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error('Failed to upload generation to Google Drive', [
                'job_id' => $job->job_id,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }

    /**
     * Set user's access token to client
     */
    protected function setUserToken(User $user): void
    {
        $this->client->setAccessToken($user->google_access_token);

        // Refresh if expired
        if ($this->client->isAccessTokenExpired() && $user->google_refresh_token) {
            $newToken = $this->client->fetchAccessTokenWithRefreshToken($user->google_refresh_token);

            $user->update([
                'google_access_token' => $newToken['access_token'],
                'google_token_expires_at' => now()->addSeconds($newToken['expires_in'] ?? 3600),
            ]);
        }
    }

    /**
     * Disconnect Google Drive
     */
    public function disconnect(User $user): bool
    {
        try {
            // Revoke token if possible
            if ($user->google_access_token) {
                $this->client->revokeToken($user->google_access_token);
            }
        } catch (\Exception $e) {
            // Ignore revoke errors
        }

        $user->update([
            'google_id' => null,
            'google_email' => null,
            'google_access_token' => null,
            'google_refresh_token' => null,
            'google_token_expires_at' => null,
            'google_drive_folder_id' => null,
        ]);

        return true;
    }
}
