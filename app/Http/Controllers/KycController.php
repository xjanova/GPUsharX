<?php

namespace App\Http\Controllers;

use App\Models\KycVerification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class KycController extends Controller
{
    /**
     * Show KYC verification page
     */
    public function index()
    {
        $user = Auth::user();
        $kyc = $user->kycVerification;
        $kycHistory = $user->kycVerifications()->latest()->get();

        return view('kyc.index', compact('user', 'kyc', 'kycHistory'));
    }

    /**
     * Show KYC submission form
     */
    public function create()
    {
        $user = Auth::user();

        // If already approved, redirect
        if ($user->isKycApproved()) {
            return redirect()->route('kyc.index')
                ->with('info', 'บัญชีของคุณได้รับการยืนยันแล้ว');
        }

        // If pending, show status
        if ($user->isKycPending()) {
            return redirect()->route('kyc.index')
                ->with('info', 'คำขอยืนยันตัวตนของคุณอยู่ระหว่างการตรวจสอบ');
        }

        $kyc = $user->kycVerification;
        $banks = KycVerification::THAI_BANKS;
        $provinces = KycVerification::THAI_PROVINCES;
        $idTypes = KycVerification::getIdTypes();

        return view('kyc.create', compact('user', 'kyc', 'banks', 'provinces', 'idTypes'));
    }

    /**
     * Store KYC verification request
     */
    public function store(Request $request)
    {
        $user = Auth::user();

        // Validate
        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'date_of_birth' => 'required|date|before:-18 years',
            'nationality' => 'required|string|max:50',
            'id_type' => ['required', Rule::in(array_keys(KycVerification::getIdTypes()))],
            'id_number' => 'required|string|max:50',
            'phone_number' => 'required|string|regex:/^[0-9]{9,10}$/',
            'address' => 'required|string|max:500',
            'province' => 'required|string',
            'district' => 'required|string|max:100',
            'postal_code' => 'required|string|regex:/^[0-9]{5}$/',
            'id_front_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'id_back_image' => 'nullable|image|mimes:jpeg,png,jpg|max:5120',
            'selfie_image' => 'required|image|mimes:jpeg,png,jpg|max:5120',
            'bank_name' => ['required', Rule::in(array_keys(KycVerification::THAI_BANKS))],
            'bank_account_number' => 'required|string|regex:/^[0-9]{10,15}$/',
            'bank_account_name' => 'required|string|max:100',
        ], [
            'date_of_birth.before' => 'คุณต้องมีอายุ 18 ปีขึ้นไป',
            'phone_number.regex' => 'หมายเลขโทรศัพท์ไม่ถูกต้อง',
            'postal_code.regex' => 'รหัสไปรษณีย์ไม่ถูกต้อง',
            'id_front_image.max' => 'ไฟล์รูปภาพต้องไม่เกิน 5MB',
            'selfie_image.max' => 'ไฟล์รูปภาพต้องไม่เกิน 5MB',
            'bank_account_number.regex' => 'หมายเลขบัญชีไม่ถูกต้อง',
        ]);

        // Upload images
        $idFrontPath = $request->file('id_front_image')
            ->store("kyc/{$user->id}", 'private');

        $idBackPath = null;
        if ($request->hasFile('id_back_image')) {
            $idBackPath = $request->file('id_back_image')
                ->store("kyc/{$user->id}", 'private');
        }

        $selfiePath = $request->file('selfie_image')
            ->store("kyc/{$user->id}", 'private');

        // Check if resubmitting
        $existingKyc = $user->kycVerification;
        $submissionCount = $existingKyc ? $existingKyc->submission_count + 1 : 1;

        // Delete old images if resubmitting
        if ($existingKyc) {
            Storage::disk('private')->delete([
                $existingKyc->id_front_image,
                $existingKyc->id_back_image,
                $existingKyc->selfie_image,
            ]);
            $existingKyc->delete();
        }

        // Create KYC record
        KycVerification::create([
            'user_id' => $user->id,
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'date_of_birth' => $validated['date_of_birth'],
            'nationality' => $validated['nationality'],
            'id_type' => $validated['id_type'],
            'id_number' => $validated['id_number'],
            'phone_number' => $validated['phone_number'],
            'address' => $validated['address'],
            'province' => $validated['province'],
            'district' => $validated['district'],
            'postal_code' => $validated['postal_code'],
            'id_front_image' => $idFrontPath,
            'id_back_image' => $idBackPath,
            'selfie_image' => $selfiePath,
            'bank_name' => $validated['bank_name'],
            'bank_account_number' => $validated['bank_account_number'],
            'bank_account_name' => $validated['bank_account_name'],
            'status' => KycVerification::STATUS_PENDING,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'submission_count' => $submissionCount,
        ]);

        // Update user KYC status
        $user->update(['kyc_status' => 'pending']);

        return redirect()->route('kyc.index')
            ->with('success', 'ส่งคำขอยืนยันตัวตนเรียบร้อยแล้ว กรุณารอการตรวจสอบ 1-3 วันทำการ');
    }

    /**
     * View KYC document image
     */
    public function viewDocument($type)
    {
        $user = Auth::user();
        $kyc = $user->kycVerification;

        if (!$kyc) {
            abort(404);
        }

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
