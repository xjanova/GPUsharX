<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ClientVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class AdminClientController extends Controller
{
    public function index()
    {
        $versions = ClientVersion::orderBy('created_at', 'desc')->get();

        $stats = [
            'total_versions' => $versions->count(),
            'total_downloads' => $versions->sum('download_count'),
            'platforms' => $versions->groupBy('platform')->map->count(),
        ];

        return view('admin.client-versions', compact('versions', 'stats'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'version' => 'required|string|max:20',
            'platform' => 'required|in:windows,linux,mac,source',
            'file' => 'required|file|max:512000', // 500MB max
            'release_notes' => 'nullable|string',
            'min_gpu_memory' => 'nullable|string|max:20',
            'min_ram' => 'nullable|string|max:20',
            'is_latest' => 'boolean',
        ]);

        $file = $request->file('file');

        // Generate filename
        $extension = $file->getClientOriginalExtension();
        $filename = "GPUShareClient-{$request->version}-{$request->platform}";
        if ($extension) {
            $filename .= ".{$extension}";
        }

        // Store file
        $path = $file->storeAs('client-releases', $filename, 'public');

        // Calculate checksum
        $fullPath = Storage::disk('public')->path($path);
        $checksum = hash_file('sha256', $fullPath);

        // Create version record
        $version = ClientVersion::create([
            'version' => $request->version,
            'platform' => $request->platform,
            'filename' => $filename,
            'file_path' => $path,
            'file_size' => $file->getSize(),
            'checksum_sha256' => $checksum,
            'release_notes' => $request->release_notes,
            'min_gpu_memory' => $request->min_gpu_memory ?? '4GB',
            'min_ram' => $request->min_ram ?? '8GB',
            'is_active' => true,
        ]);

        // Set as latest if requested
        if ($request->boolean('is_latest')) {
            ClientVersion::setAsLatest($version);
        }

        return response()->json([
            'success' => true,
            'message' => 'เวอร์ชันถูกอัปโหลดเรียบร้อย',
            'version' => $version,
        ]);
    }

    public function update(Request $request, ClientVersion $version)
    {
        $request->validate([
            'release_notes' => 'nullable|string',
            'min_gpu_memory' => 'nullable|string|max:20',
            'min_ram' => 'nullable|string|max:20',
            'is_active' => 'boolean',
            'is_latest' => 'boolean',
        ]);

        $version->update([
            'release_notes' => $request->release_notes,
            'min_gpu_memory' => $request->min_gpu_memory ?? $version->min_gpu_memory,
            'min_ram' => $request->min_ram ?? $version->min_ram,
            'is_active' => $request->boolean('is_active', $version->is_active),
        ]);

        if ($request->boolean('is_latest')) {
            ClientVersion::setAsLatest($version);
        }

        return response()->json([
            'success' => true,
            'message' => 'อัปเดตเวอร์ชันเรียบร้อย',
        ]);
    }

    public function destroy(ClientVersion $version)
    {
        // Delete file
        if (Storage::disk('public')->exists($version->file_path)) {
            Storage::disk('public')->delete($version->file_path);
        }

        $version->delete();

        return response()->json([
            'success' => true,
            'message' => 'ลบเวอร์ชันเรียบร้อย',
        ]);
    }

    public function setLatest(ClientVersion $version)
    {
        ClientVersion::setAsLatest($version);

        return response()->json([
            'success' => true,
            'message' => "ตั้ง v{$version->version} เป็นเวอร์ชันล่าสุดแล้ว",
        ]);
    }

    public function toggle(ClientVersion $version)
    {
        $version->update(['is_active' => !$version->is_active]);

        $status = $version->is_active ? 'เปิดใช้งาน' : 'ปิดใช้งาน';

        return response()->json([
            'success' => true,
            'message' => "{$status}เวอร์ชัน v{$version->version} แล้ว",
            'is_active' => $version->is_active,
        ]);
    }
}
