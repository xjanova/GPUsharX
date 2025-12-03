<?php

namespace App\Http\Controllers;

use App\Models\CreditTransaction;
use App\Models\ManualPayment;
use App\Models\SiteSetting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;
use Stripe\Webhook;

class PaymentController extends Controller
{
    /**
     * Get the active payment gateway
     */
    protected function getActiveGateway(): string
    {
        return SiteSetting::get('payment_gateway', 'stripe');
    }

    /**
     * Initialize Stripe with API key from settings
     */
    protected function initStripe(): void
    {
        $secretKey = SiteSetting::get('stripe_secret') ?: config('stripe.secret');
        Stripe::setApiKey($secretKey);
    }

    /**
     * หน้าซื้อเครดิต
     */
    public function index()
    {
        $packages = config('stripe.packages');
        $gateway = $this->getActiveGateway();

        return view('credits.buy', compact('packages', 'gateway'));
    }

    /**
     * สร้าง Checkout Session ตาม Gateway ที่เลือก
     */
    public function createCheckoutSession(Request $request)
    {
        $request->validate([
            'package' => 'required|string|in:starter,basic,pro,enterprise',
        ]);

        $gateway = $this->getActiveGateway();

        switch ($gateway) {
            case 'stripe':
                return $this->createStripeCheckout($request);
            case 'omise':
                return $this->createOmiseCheckout($request);
            case 'gbprimepay':
                return $this->createGBPrimePayCheckout($request);
            case 'manual':
                return $this->createManualPayment($request);
            default:
                return response()->json(['error' => 'Payment gateway not configured'], 400);
        }
    }

    /**
     * Stripe Checkout
     */
    protected function createStripeCheckout(Request $request)
    {
        $packageKey = $request->package;
        $package = config("stripe.packages.{$packageKey}");

        if (!$package) {
            return response()->json(['error' => 'Invalid package'], 400);
        }

        $user = Auth::user();

        try {
            $this->initStripe();

            $session = StripeSession::create([
                'payment_method_types' => ['card', 'promptpay'],
                'line_items' => [[
                    'price_data' => [
                        'currency' => config('stripe.currency', 'thb'),
                        'product_data' => [
                            'name' => $package['name'] . ' - ' . $package['credits'] . ' Credits',
                            'description' => $package['description'],
                        ],
                        'unit_amount' => $package['price'],
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => route('payment.success') . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('payment.cancel'),
                'customer_email' => $user->email,
                'metadata' => [
                    'user_id' => $user->id,
                    'package' => $packageKey,
                    'credits' => $package['credits'],
                ],
                'locale' => 'th',
            ]);

            return response()->json([
                'id' => $session->id,
                'url' => $session->url,
            ]);

        } catch (\Exception $e) {
            Log::error('Stripe checkout error', [
                'error' => $e->getMessage(),
                'user_id' => $user->id,
            ]);

            return response()->json([
                'error' => 'ไม่สามารถสร้างการชำระเงินได้: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Omise Checkout (placeholder - needs omise-php package)
     */
    protected function createOmiseCheckout(Request $request)
    {
        $packageKey = $request->package;
        $package = config("stripe.packages.{$packageKey}");

        if (!$package) {
            return response()->json(['error' => 'Invalid package'], 400);
        }

        // For now, return manual payment view with QR
        return response()->json([
            'gateway' => 'omise',
            'redirect' => route('payment.manual', [
                'package' => $packageKey,
                'gateway' => 'omise'
            ]),
        ]);
    }

    /**
     * GB Prime Pay Checkout (placeholder)
     */
    protected function createGBPrimePayCheckout(Request $request)
    {
        $packageKey = $request->package;
        $package = config("stripe.packages.{$packageKey}");

        if (!$package) {
            return response()->json(['error' => 'Invalid package'], 400);
        }

        return response()->json([
            'gateway' => 'gbprimepay',
            'redirect' => route('payment.manual', [
                'package' => $packageKey,
                'gateway' => 'gbprimepay'
            ]),
        ]);
    }

    /**
     * Manual Payment (PromptPay/Bank Transfer)
     */
    protected function createManualPayment(Request $request)
    {
        $packageKey = $request->package;
        $package = config("stripe.packages.{$packageKey}");

        if (!$package) {
            return response()->json(['error' => 'Invalid package'], 400);
        }

        return response()->json([
            'gateway' => 'manual',
            'redirect' => route('payment.manual', ['package' => $packageKey]),
        ]);
    }

    /**
     * Manual Payment Page (PromptPay QR / Bank Transfer)
     */
    public function manualPayment(Request $request, $package)
    {
        $packageData = config("stripe.packages.{$package}");

        if (!$packageData) {
            return redirect()->route('credits.buy')->with('error', 'แพ็คเกจไม่ถูกต้อง');
        }

        $promptpayEnabled = SiteSetting::get('promptpay_enabled');
        $promptpayId = SiteSetting::get('promptpay_id');
        $promptpayName = SiteSetting::get('promptpay_name');
        $bankEnabled = SiteSetting::get('bank_transfer_enabled');
        $bankAccounts = json_decode(SiteSetting::get('bank_accounts', '[]'), true);

        return view('credits.manual', compact(
            'package', 'packageData',
            'promptpayEnabled', 'promptpayId', 'promptpayName',
            'bankEnabled', 'bankAccounts'
        ));
    }

    /**
     * หน้า Success หลังชำระเงินสำเร็จ
     */
    public function success(Request $request)
    {
        $sessionId = $request->get('session_id');

        if (!$sessionId) {
            return redirect()->route('credits.buy')->with('error', 'ไม่พบข้อมูลการชำระเงิน');
        }

        try {
            $this->initStripe();
            $session = StripeSession::retrieve($sessionId);

            if ($session->payment_status === 'paid') {
                // ตรวจสอบว่า credit ถูกเพิ่มไปแล้วหรือยัง (ป้องกัน double credit)
                $existingTransaction = CreditTransaction::where('reference_type', 'stripe_session')
                    ->where('reference_id', $sessionId)
                    ->first();

                if (!$existingTransaction) {
                    // เพิ่ม credits
                    $userId = $session->metadata->user_id;
                    $credits = (int) $session->metadata->credits;
                    $package = $session->metadata->package;

                    $user = User::find($userId);
                    if ($user) {
                        $user->increment('credits', $credits);

                        // บันทึก transaction
                        CreditTransaction::create([
                            'user_id' => $user->id,
                            'type' => 'purchase',
                            'amount' => $credits,
                            'balance_after' => $user->credits,
                            'description' => "ซื้อแพ็คเกจ " . config("stripe.packages.{$package}.name", $package),
                            'reference_type' => 'stripe_session',
                            'reference_id' => $sessionId,
                        ]);

                        Log::info('Credits added via Stripe', [
                            'user_id' => $user->id,
                            'credits' => $credits,
                            'package' => $package,
                            'session_id' => $sessionId,
                        ]);
                    }
                }

                return view('credits.success', [
                    'credits' => $session->metadata->credits,
                    'package' => config("stripe.packages.{$session->metadata->package}"),
                ]);
            }

            return redirect()->route('credits.buy')->with('error', 'การชำระเงินยังไม่สำเร็จ');

        } catch (\Exception $e) {
            Log::error('Stripe success page error', [
                'error' => $e->getMessage(),
                'session_id' => $sessionId,
            ]);

            return redirect()->route('credits.buy')->with('error', 'เกิดข้อผิดพลาด กรุณาติดต่อฝ่ายสนับสนุน');
        }
    }

    /**
     * หน้า Cancel
     */
    public function cancel()
    {
        return redirect()->route('credits.buy')->with('info', 'การชำระเงินถูกยกเลิก');
    }

    /**
     * Stripe Webhook Handler
     */
    public function webhook(Request $request)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = SiteSetting::get('stripe_webhook_secret') ?: config('stripe.webhook_secret');

        if (!$webhookSecret) {
            Log::warning('Stripe webhook secret not configured');
            return response('Webhook secret not configured', 400);
        }

        try {
            $this->initStripe();
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\UnexpectedValueException $e) {
            Log::error('Stripe webhook invalid payload', ['error' => $e->getMessage()]);
            return response('Invalid payload', 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::error('Stripe webhook invalid signature', ['error' => $e->getMessage()]);
            return response('Invalid signature', 400);
        }

        // Handle the event
        switch ($event->type) {
            case 'checkout.session.completed':
                $session = $event->data->object;
                $this->handleCheckoutCompleted($session);
                break;

            case 'payment_intent.payment_failed':
                $paymentIntent = $event->data->object;
                Log::warning('Payment failed', [
                    'payment_intent' => $paymentIntent->id,
                    'error' => $paymentIntent->last_payment_error->message ?? 'Unknown error',
                ]);
                break;

            default:
                Log::info('Unhandled Stripe event', ['type' => $event->type]);
        }

        return response('OK', 200);
    }

    /**
     * Handle successful checkout
     */
    protected function handleCheckoutCompleted($session)
    {
        if ($session->payment_status !== 'paid') {
            return;
        }

        $sessionId = $session->id;

        // ตรวจสอบว่า credit ถูกเพิ่มไปแล้วหรือยัง
        $existingTransaction = CreditTransaction::where('reference_type', 'stripe_session')
            ->where('reference_id', $sessionId)
            ->first();

        if ($existingTransaction) {
            Log::info('Credits already added for session', ['session_id' => $sessionId]);
            return;
        }

        $userId = $session->metadata->user_id ?? null;
        $credits = (int) ($session->metadata->credits ?? 0);
        $package = $session->metadata->package ?? 'unknown';

        if (!$userId || !$credits) {
            Log::error('Invalid session metadata', [
                'session_id' => $sessionId,
                'metadata' => $session->metadata,
            ]);
            return;
        }

        $user = User::find($userId);
        if (!$user) {
            Log::error('User not found for credit addition', [
                'user_id' => $userId,
                'session_id' => $sessionId,
            ]);
            return;
        }

        $user->increment('credits', $credits);

        CreditTransaction::create([
            'user_id' => $user->id,
            'type' => 'purchase',
            'amount' => $credits,
            'balance_after' => $user->credits,
            'description' => "ซื้อแพ็คเกจ " . config("stripe.packages.{$package}.name", $package) . " (Webhook)",
            'reference_type' => 'stripe_session',
            'reference_id' => $sessionId,
        ]);

        Log::info('Credits added via Stripe webhook', [
            'user_id' => $user->id,
            'credits' => $credits,
            'package' => $package,
            'session_id' => $sessionId,
        ]);
    }

    /**
     * ประวัติการซื้อเครดิต
     */
    public function history()
    {
        $transactions = CreditTransaction::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('credits.history', compact('transactions'));
    }

    /**
     * แจ้งชำระเงินแบบ Manual (PromptPay/Bank Transfer)
     */
    public function confirmManualPayment(Request $request)
    {
        $request->validate([
            'package' => 'required|string|in:starter,basic,pro,enterprise',
            'transfer_date' => 'required|date',
            'transfer_time' => 'required',
            'amount' => 'required|numeric|min:1',
            'slip' => 'required|image|max:5120', // Max 5MB
            'note' => 'nullable|string|max:500',
        ]);

        $package = config("stripe.packages.{$request->package}");

        if (!$package) {
            return back()->with('error', 'แพ็คเกจไม่ถูกต้อง');
        }

        $user = Auth::user();

        // Check for existing pending payment for this package
        $existingPending = ManualPayment::where('user_id', $user->id)
            ->where('package', $request->package)
            ->where('status', 'pending')
            ->first();

        if ($existingPending) {
            return back()->with('error', 'คุณมีรายการรอตรวจสอบสำหรับแพ็คเกจนี้อยู่แล้ว กรุณารอการตรวจสอบ');
        }

        // Store the slip
        $slipPath = $request->file('slip')->store('payment-slips', 'public');

        // Create manual payment record
        $payment = ManualPayment::create([
            'user_id' => $user->id,
            'package' => $request->package,
            'amount' => $request->amount,
            'credits' => $package['credits'],
            'transfer_date' => $request->transfer_date,
            'transfer_time' => $request->transfer_time,
            'slip_path' => $slipPath,
            'note' => $request->note,
            'status' => 'pending',
        ]);

        Log::info('Manual payment submitted', [
            'user_id' => $user->id,
            'payment_id' => $payment->id,
            'package' => $request->package,
            'amount' => $request->amount,
        ]);

        return redirect()->route('payment.pending')->with('success', 'แจ้งชำระเงินเรียบร้อยแล้ว กรุณารอการตรวจสอบ');
    }

    /**
     * หน้ารายการรอตรวจสอบ
     */
    public function pendingPayments()
    {
        $payments = ManualPayment::where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('credits.pending', compact('payments'));
    }
}
