<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\KycVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class KycController extends Controller
{
    /**
     * List all KYC verifications
     */
    public function index(Request $request)
    {
        $status = $request->get('status', 'pending');

        $query = KycVerification::with(['user', 'reviewer'])
            ->orderBy('created_at', 'desc');

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $verifications = $query->paginate(20);

        $counts = [
            'all' => KycVerification::count(),
            'pending' => KycVerification::where('status', 'pending')->count(),
            'reviewing' => KycVerification::where('status', 'reviewing')->count(),
            'approved' => KycVerification::where('status', 'approved')->count(),
            'rejected' => KycVerification::where('status', 'rejected')->count(),
        ];

        return view('admin.kyc.index', compact('verifications', 'status', 'counts'));
    }

    /**
     * Show KYC verification detail
     */
    public function show(KycVerification $kyc)
    {
        $kyc->load(['user', 'reviewer']);

        // Start reviewing if pending
        if ($kyc->isPending()) {
            $kyc->startReview(Auth::user());
        }

        return view('admin.kyc.show', compact('kyc'));
    }

    /**
     * Approve KYC verification
     */
    public function approve(KycVerification $kyc)
    {
        if (!$kyc->isPending() && !$kyc->isReviewing()) {
            return back()->with('error', 'ไม่สามารถอนุมัติได้ เนื่องจากสถานะไม่ถูกต้อง');
        }

        $kyc->approve(Auth::user());

        return redirect()->route('admin.kyc.index')
            ->with('success', "อนุมัติ KYC สำหรับ {$kyc->full_name} เรียบร้อยแล้ว");
    }

    /**
     * Reject KYC verification
     */
    public function reject(Request $request, KycVerification $kyc)
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        if (!$kyc->isPending() && !$kyc->isReviewing()) {
            return back()->with('error', 'ไม่สามารถปฏิเสธได้ เนื่องจากสถานะไม่ถูกต้อง');
        }

        $kyc->reject(Auth::user(), $request->rejection_reason);

        return redirect()->route('admin.kyc.index')
            ->with('success', "ปฏิเสธ KYC สำหรับ {$kyc->full_name} เรียบร้อยแล้ว");
    }

    /**
     * View KYC document image (admin)
     */
    public function viewDocument(KycVerification $kyc, $type)
    {
        $path = match($type) {
            'id_front' => $kyc->id_front_image,
            'id_back' => $kyc->id_back_image,
            'selfie' => $kyc->selfie_image,
            default => abort(404),
        };

        if (!$path || !Storage::disk('private')->exists($path)) {
            abort(404);
        }

        return response()->file(Storage::disk('private')->path($path));
    }
}
