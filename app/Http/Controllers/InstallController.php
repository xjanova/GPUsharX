<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use App\Models\User;

class InstallController extends Controller
{
    protected $steps = [
        1 => 'welcome',
        2 => 'requirements',
        3 => 'database',
        4 => 'admin',
        5 => 'settings',
        6 => 'complete',
    ];

    public function index()
    {
        // Check if already installed
        if ($this->isInstalled()) {
            return redirect('/');
        }

        return redirect()->route('install.step', 1);
    }

    public function step($step)
    {
        if ($this->isInstalled() && $step < 6) {
            return redirect('/');
        }

        $step = (int) $step;

        if (!isset($this->steps[$step])) {
            return redirect()->route('install.step', 1);
        }

        $data = [
            'step' => $step,
            'stepName' => $this->steps[$step],
            'totalSteps' => count($this->steps),
        ];

        // Add step-specific data
        if ($step === 2) {
            $data['requirements'] = $this->checkRequirements();
        }

        return view('install.step-' . $step, $data);
    }

    public function processStep(Request $request, $step)
    {
        $step = (int) $step;

        switch ($step) {
            case 1:
                // Welcome - just proceed
                return redirect()->route('install.step', 2);

            case 2:
                // Requirements check
                $requirements = $this->checkRequirements();
                if (in_array(false, array_column($requirements, 'passed'))) {
                    return back()->with('error', 'กรุณาแก้ไขข้อกำหนดที่ยังไม่ผ่านก่อนดำเนินการต่อ');
                }
                return redirect()->route('install.step', 3);

            case 3:
                // Database configuration
                return $this->configureDatabase($request);

            case 4:
                // Admin account
                return $this->createAdmin($request);

            case 5:
                // Platform settings
                return $this->savePlatformSettings($request);

            default:
                return redirect()->route('install.step', 1);
        }
    }

    protected function checkRequirements(): array
    {
        return [
            [
                'name' => 'PHP Version',
                'required' => '>= 8.2',
                'current' => PHP_VERSION,
                'passed' => version_compare(PHP_VERSION, '8.2.0', '>='),
            ],
            [
                'name' => 'PDO Extension',
                'required' => 'Enabled',
                'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('pdo'),
            ],
            [
                'name' => 'PDO MySQL',
                'required' => 'Enabled',
                'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('pdo_mysql'),
            ],
            [
                'name' => 'OpenSSL Extension',
                'required' => 'Enabled',
                'current' => extension_loaded('openssl') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('openssl'),
            ],
            [
                'name' => 'Mbstring Extension',
                'required' => 'Enabled',
                'current' => extension_loaded('mbstring') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('mbstring'),
            ],
            [
                'name' => 'Tokenizer Extension',
                'required' => 'Enabled',
                'current' => extension_loaded('tokenizer') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('tokenizer'),
            ],
            [
                'name' => 'JSON Extension',
                'required' => 'Enabled',
                'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('json'),
            ],
            [
                'name' => 'cURL Extension',
                'required' => 'Enabled',
                'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('curl'),
            ],
            [
                'name' => 'GD Extension',
                'required' => 'Enabled',
                'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('gd'),
            ],
            [
                'name' => 'Fileinfo Extension',
                'required' => 'Enabled',
                'current' => extension_loaded('fileinfo') ? 'Enabled' : 'Disabled',
                'passed' => extension_loaded('fileinfo'),
            ],
            [
                'name' => 'Storage Writable',
                'required' => 'Writable',
                'current' => is_writable(storage_path()) ? 'Writable' : 'Not Writable',
                'passed' => is_writable(storage_path()),
            ],
            [
                'name' => 'Bootstrap Cache Writable',
                'required' => 'Writable',
                'current' => is_writable(base_path('bootstrap/cache')) ? 'Writable' : 'Not Writable',
                'passed' => is_writable(base_path('bootstrap/cache')),
            ],
            [
                'name' => '.env File Writable',
                'required' => 'Writable',
                'current' => is_writable(base_path('.env')) ? 'Writable' : 'Not Writable',
                'passed' => is_writable(base_path('.env')),
            ],
        ];
    }

    protected function configureDatabase(Request $request)
    {
        // Increase PHP execution time for migrations
        set_time_limit(300);

        $validator = Validator::make($request->all(), [
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ]);
            }
            return back()->withErrors($validator)->withInput();
        }

        // Test connection
        try {
            \Log::info('Install: Starting database configuration');

            $pdo = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port}",
                $request->db_username,
                $request->db_password ?? ''
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            \Log::info('Install: PDO connection successful');

            // Create database if not exists
            $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$request->db_database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            \Log::info('Install: Database created/verified');

            // Configure database connection in memory FIRST (before writing to .env)
            // This prevents the dev server from restarting mid-migration
            config([
                'database.default' => 'mysql',
                'database.connections.mysql.host' => $request->db_host,
                'database.connections.mysql.port' => $request->db_port,
                'database.connections.mysql.database' => $request->db_database,
                'database.connections.mysql.username' => $request->db_username,
                'database.connections.mysql.password' => $request->db_password ?? '',
            ]);

            // Set session/cache to file to avoid DB dependency
            config(['session.driver' => 'file']);
            config(['cache.default' => 'file']);

            DB::purge('mysql');
            DB::reconnect('mysql');

            \Log::info('Install: Starting migrations');

            // Run fresh migrations (drop all tables and recreate)
            Artisan::call('migrate:fresh', ['--force' => true]);

            \Log::info('Install: Migrations completed, starting seeders');

            // Run seeders
            Artisan::call('db:seed', ['--force' => true]);

            \Log::info('Install: Seeders completed successfully');

            // NOW write to .env file AFTER migrations complete
            // This ensures the dev server restart (if any) happens after DB is ready
            $this->updateEnvFile([
                'DB_CONNECTION' => 'mysql',
                'DB_HOST' => $request->db_host,
                'DB_PORT' => $request->db_port,
                'DB_DATABASE' => $request->db_database,
                'DB_USERNAME' => $request->db_username,
                'DB_PASSWORD' => $request->db_password ?? '',
                'SESSION_DRIVER' => 'file',
                'CACHE_STORE' => 'file',
            ]);

            \Log::info('Install: .env file updated');

            // Clear config cache
            Artisan::call('config:clear');
            Artisan::call('cache:clear');

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'เชื่อมต่อฐานข้อมูลและสร้างตารางเรียบร้อยแล้ว',
                    'redirect' => route('install.step', 4),
                ]);
            }

            return redirect()->route('install.step', 4)->with('success', 'เชื่อมต่อฐานข้อมูลและสร้างตารางเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            \Log::error('Install error: ' . $e->getMessage());
            \Log::error('Install stack trace: ' . $e->getTraceAsString());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . $e->getMessage(),
                ]);
            }
            return back()->with('error', 'ไม่สามารถเชื่อมต่อฐานข้อมูลได้: ' . $e->getMessage())->withInput();
        }
    }

    protected function createAdmin(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ]);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            // Delete existing admin from seeder if exists
            User::where('email', 'admin@gpushare.com')->delete();

            // Create new admin
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]);

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'สร้างบัญชีผู้ดูแลระบบเรียบร้อยแล้ว',
                    'redirect' => route('install.step', 5),
                ]);
            }

            return redirect()->route('install.step', 5)->with('success', 'สร้างบัญชีผู้ดูแลระบบเรียบร้อยแล้ว');

        } catch (\Exception $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถสร้างบัญชีได้: ' . $e->getMessage(),
                ]);
            }
            return back()->with('error', 'ไม่สามารถสร้างบัญชีได้: ' . $e->getMessage())->withInput();
        }
    }

    protected function savePlatformSettings(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'app_name' => 'required|string|max:255',
            'app_url' => 'required|url',
            'timezone' => 'required|string',
        ]);

        if ($validator->fails()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $validator->errors()->first(),
                ]);
            }
            return back()->withErrors($validator)->withInput();
        }

        try {
            \Log::info('Install Step 5: Saving platform settings');

            // Create installed marker FIRST (before .env changes)
            File::put(storage_path('installed'), now()->toDateTimeString());

            \Log::info('Install Step 5: Created installed marker');

            // Clear all caches BEFORE writing to .env
            Artisan::call('config:clear');
            Artisan::call('cache:clear');
            Artisan::call('view:clear');

            \Log::info('Install Step 5: Cleared caches');

            // Update .env LAST (to prevent server restart issues)
            $this->updateEnvFile([
                'APP_NAME' => '"' . $request->app_name . '"',
                'APP_URL' => $request->app_url,
                'APP_TIMEZONE' => $request->timezone,
                'APP_INSTALLED' => 'true',
            ]);

            \Log::info('Install Step 5: Updated .env file');

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'ติดตั้งระบบเรียบร้อยแล้ว!',
                    'redirect' => route('install.step', 6),
                ]);
            }

            return redirect()->route('install.step', 6)->with('success', 'ติดตั้งระบบเรียบร้อยแล้ว!');

        } catch (\Exception $e) {
            \Log::error('Install Step 5 error: ' . $e->getMessage());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'ไม่สามารถบันทึกการตั้งค่าได้: ' . $e->getMessage(),
                ]);
            }
            return back()->with('error', 'ไม่สามารถบันทึกการตั้งค่าได้: ' . $e->getMessage())->withInput();
        }
    }

    protected function updateEnvFile(array $data): void
    {
        $envPath = base_path('.env');
        $envContent = File::get($envPath);

        foreach ($data as $key => $value) {
            $pattern = "/^{$key}=.*/m";
            $replacement = "{$key}={$value}";

            if (preg_match($pattern, $envContent)) {
                $envContent = preg_replace($pattern, $replacement, $envContent);
            } else {
                $envContent .= "\n{$replacement}";
            }
        }

        File::put($envPath, $envContent);
    }

    protected function isInstalled(): bool
    {
        return File::exists(storage_path('installed'));
    }

    public function uninstall()
    {
        // For development only - remove in production
        if (app()->environment('local')) {
            File::delete(storage_path('installed'));
            return redirect()->route('install.index');
        }
        abort(404);
    }

    public function testDatabase(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'db_host' => 'required|string',
            'db_port' => 'required|numeric',
            'db_database' => 'required|string',
            'db_username' => 'required|string',
            'db_password' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => $validator->errors()->first(),
            ]);
        }

        try {
            $pdo = new \PDO(
                "mysql:host={$request->db_host};port={$request->db_port}",
                $request->db_username,
                $request->db_password ?? ''
            );
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Try to select the database or check if it exists
            $stmt = $pdo->query("SHOW DATABASES LIKE '{$request->db_database}'");
            $dbExists = $stmt->rowCount() > 0;

            return response()->json([
                'success' => true,
                'message' => $dbExists
                    ? "เชื่อมต่อสำเร็จ! ฐานข้อมูล '{$request->db_database}' พร้อมใช้งาน"
                    : "เชื่อมต่อสำเร็จ! ระบบจะสร้างฐานข้อมูล '{$request->db_database}' ให้อัตโนมัติ",
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ]);
        }
    }
}
