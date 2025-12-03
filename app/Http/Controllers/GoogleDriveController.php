<?php

namespace App\Http\Controllers;

use App\Services\GoogleDriveService;
use Illuminate\Http\Request;

class GoogleDriveController extends Controller
{
    public function __construct(
        protected GoogleDriveService $googleDriveService
    ) {}

    /**
     * Show Google Drive settings page
     */
    public function settings()
    {
        $isAvailable = $this->googleDriveService->isAvailable();
        return view('settings.google-drive', compact('isAvailable'));
    }

    /**
     * Redirect to Google OAuth
     */
    public function connect()
    {
        if (!$this->googleDriveService->isAvailable()) {
            return redirect()->route('settings.google-drive')
                ->with('error', 'Google Drive ยังไม่พร้อมใช้งาน กรุณาติดตั้ง Google API Client (composer require google/apiclient)');
        }

        $authUrl = $this->googleDriveService->getAuthUrl();

        if (!$authUrl) {
            return redirect()->route('settings.google-drive')
                ->with('error', 'ไม่สามารถเชื่อมต่อ Google ได้ กรุณาตรวจสอบการตั้งค่า');
        }

        return redirect($authUrl);
    }

    /**
     * Handle OAuth callback
     */
    public function callback(Request $request)
    {
        if ($request->has('error')) {
            return redirect()->route('settings.google-drive')
                ->with('error', 'การเชื่อมต่อ Google ถูกยกเลิก');
        }

        $code = $request->get('code');
        $user = auth()->user();

        $success = $this->googleDriveService->handleCallback($code, $user);

        if ($success) {
            return redirect()->route('settings.google-drive')
                ->with('success', 'เชื่อมต่อ Google Drive สำเร็จ! ผลงานจะถูกบันทึกอัตโนมัติ');
        }

        return redirect()->route('settings.google-drive')
            ->with('error', 'ไม่สามารถเชื่อมต่อ Google Drive ได้ กรุณาลองใหม่');
    }

    /**
     * Disconnect Google Drive
     */
    public function disconnect()
    {
        $user = auth()->user();
        $this->googleDriveService->disconnect($user);

        return redirect()->route('settings.google-drive')
            ->with('success', 'ยกเลิกการเชื่อมต่อ Google Drive แล้ว');
    }

    /**
     * Test upload to Google Drive
     */
    public function testUpload()
    {
        $user = auth()->user();

        if (!$user->google_drive_connected) {
            return back()->with('error', 'กรุณาเชื่อมต่อ Google Drive ก่อน');
        }

        // Create a test image
        $testImagePath = storage_path('app/test-upload.png');

        // Create simple test image
        $img = imagecreatetruecolor(200, 200);
        $bgColor = imagecolorallocate($img, 168, 85, 247); // Purple
        $textColor = imagecolorallocate($img, 255, 255, 255);
        imagefill($img, 0, 0, $bgColor);
        imagestring($img, 5, 50, 90, 'GPU Share', $textColor);
        imagepng($img, $testImagePath);
        imagedestroy($img);

        $result = $this->googleDriveService->uploadFile(
            $user,
            $testImagePath,
            'test-upload-' . time() . '.png',
            'image/png'
        );

        // Clean up
        if (file_exists($testImagePath)) {
            unlink($testImagePath);
        }

        if ($result) {
            return back()->with('success', 'อัพโหลดทดสอบสำเร็จ! ไฟล์ถูกบันทึกใน Google Drive แล้ว');
        }

        return back()->with('error', 'ไม่สามารถอัพโหลดไฟล์ได้ กรุณาลองเชื่อมต่อใหม่');
    }
}
