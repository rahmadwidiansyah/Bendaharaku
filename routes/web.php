<?php

use App\Http\Controllers\AiAnalyticsController;
use App\Http\Controllers\AnalyticsController;
use App\Http\Controllers\Api\V1\BudgetController;
use App\Http\Controllers\BudgetingController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ChatBotProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EvidenceController;
use App\Http\Controllers\EvidenceDebugController;
use App\Http\Controllers\EvidenceReviewController;
use App\Http\Controllers\GoogleController;
use App\Http\Controllers\LoanController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PushNotificationController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\Settings\AiSettingsController;
use App\Http\Controllers\Settings\PrivacyLogController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\WebChatController;
use App\Support\ActivityLogger;
use App\Support\SettingsChangeLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Google Auth
Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::middleware(['auth'])->group(function () {
    // Dashboard & Main Analytics
    Route::get('/dashboard', [DashboardController::class, 'index'])->middleware('verified')->name('dashboard');
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

    // Budgeting
    Route::get('/budgeting', function (Request $request) {
        $user = $request->user();

        $categories = $user->categories()
            ->whereHas('type', fn ($q) => $q->where('name', 'Expense'))
            ->with('type')
            ->orderBy('category_name')
            ->get(['id', 'type_id', 'category_name', 'icon', 'custom_name', 'custom_icon']);

        $expenseGroups = config('bendaharaku.budget.expense_groups');

        return Inertia::render('Budgeting/Index', [
            'categories' => $categories,
            'expenseGroups' => $expenseGroups,
            'botName' => $user->bot_display_name,
            'botAvatar' => $user->bot_avatar_url,
        ]);
    })->middleware('verified')->name('budgeting.index');

    Route::get('/budgeting/create', [BudgetingController::class, 'create'])
        ->middleware('verified')
        ->name('budgeting.create');
    Route::post('/budgeting/create', [BudgetingController::class, 'store'])
        ->middleware('verified')
        ->name('budgeting.store');

    // Settings
    Route::get('/settings', function (Request $request) {
        return Inertia::render('Settings/Index');
    })->name('settings.index');

    // Simpan preferensi locale user ke DB agar chat (Telegram & Web) bisa baca
    Route::patch('/settings/locale', function (Request $request) {
        $validated = $request->validate(['locale' => ['required', 'string', 'in:id,en,auto']]);
        // 'auto' = null di DB (ikuti platform/default)
        $user = $request->user();
        $oldLocale = $user->locale;
        $newLocale = $validated['locale'] === 'auto' ? null : $validated['locale'];
        $user->update(['locale' => $newLocale]);

        // Log change
        SettingsChangeLogger::logChange(
            $user,
            'locale',
            'settings.account.preferences',
            $oldLocale,
            $newLocale
        );
        ActivityLogger::forUser($user, 'preferences', 'updated', 'Bahasa diubah', 'Locale: '.($oldLocale ?? 'auto').' → '.($newLocale ?? 'auto'), ['old' => $oldLocale, 'new' => $newLocale]);

        return response()->json(['success' => true]);
    })->name('settings.locale.update');

    // ── SETTINGS PAGES - NEW HIERARCHY ────────────────────────────────
    // Account
    Route::prefix('settings/account')->name('settings.account.')->group(function () {
        Route::get('/profile', function (Request $request) {
            $user = $request->user();

            return Inertia::render('Settings/Account/Profile', [
                'user' => $user,
                'status' => session('status'),
            ]);
        })->name('profile');

        // Full profile update (name, email, whatsapp, telegram, avatar) via ProfileController
        Route::match(['post', 'patch'], '/profile', [ProfileController::class, 'update'])->name('profile.update');

        Route::get('/security', function (Request $request) {
            $user = $request->user();
            $currentSessionId = $request->session()->getId();

            // Fetch all sessions for this user from the sessions table (database driver)
            $dbSessions = DB::table('sessions')
                ->where('user_id', $user->id)
                ->orderByDesc('last_activity')
                ->get();

            $sessions = $dbSessions->map(function ($s) use ($currentSessionId) {
                return [
                    'id' => $s->id,
                    'ip' => $s->ip_address,
                    'user_agent' => $s->user_agent,
                    'last_activity' => Carbon::createFromTimestamp($s->last_activity)->toDateTimeString(),
                    'is_current' => $s->id === $currentSessionId,
                ];
            })->values()->all();

            // If no DB sessions found (e.g. driver mismatch), fall back to current-request info
            if (empty($sessions)) {
                $sessions = [[
                    'id' => $currentSessionId,
                    'ip' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity' => now()->toDateTimeString(),
                    'is_current' => true,
                ]];
            }

            $currentSession = collect($sessions)->firstWhere('is_current', true) ?? $sessions[0];
            $otherSessions = collect($sessions)->where('is_current', false)->values()->all();

            return Inertia::render('Settings/Account/Security', [
                'currentSession' => $currentSession,
                'otherSessions' => $otherSessions,
            ]);
        })->name('security');

        Route::get('/preferences', function (Request $request) {
            $user = $request->user();

            return Inertia::render('Settings/Account/Preferences', [
                'userTimezone' => $user->timezone ?? 'Asia/Jakarta',
                'userDateFormat' => $user->date_format ?? 'DD/MM/YYYY',
                'userLanguage' => $user->locale ?? 'id',
            ]);
        })->name('preferences');
        Route::patch('/preferences', function (Request $request) {
            $validated = $request->validate([
                'timezone' => ['required', 'string', 'timezone'],
                'date_format' => ['required', 'string', 'in:DD/MM/YYYY,MM/DD/YYYY,YYYY-MM-DD'],
                'language' => ['required', 'string', 'in:id,en'],
            ]);

            $user = $request->user();
            $user->update([
                'timezone' => $validated['timezone'],
                'date_format' => $validated['date_format'],
                'locale' => $validated['language'],
            ]);

            SettingsChangeLogger::logChange(
                $user,
                'preferences',
                'settings.account.preferences',
                ['timezone' => $user->getOriginal('timezone'), 'date_format' => $user->getOriginal('date_format'), 'locale' => $user->getOriginal('locale')],
                $validated
            );
            ActivityLogger::forUser($user, 'preferences', 'updated', 'Preferensi diperbarui', 'Timezone/format bahasa diubah', ['validated' => $validated]);

            return response()->json(['success' => true, 'message' => 'Preferensi berhasil diperbarui.']);
        })->name('preferences.update');
    });

    // Application
    Route::prefix('settings/application')->name('settings.application.')->group(function () {
        Route::get('/appearance', function (Request $request) {
            $user = $request->user();

            return Inertia::render('Settings/Application/Appearance', [
                'userAccentColor' => $user->accent_color ?? 'teal',
                'userTheme' => $user->theme ?? 'dark',
                'categoryIconColored' => $user->category_icon_colored ?? true,
            ]);
        })->name('appearance');
        Route::patch('/appearance', function (Request $request) {
            $validated = $request->validate([
                'theme' => ['required', 'string', 'in:light,dark,system'],
                'accent_color' => ['required', 'string', 'regex:/^(teal|blue|indigo|pink|cyan|rose|custom:#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3}))$/'],
                'category_icon_colored' => ['boolean'],
            ]);

            $user = $request->user();
            $user->update([
                'theme' => $validated['theme'],
                'accent_color' => $validated['accent_color'],
                'category_icon_colored' => $validated['category_icon_colored'] ?? $user->category_icon_colored,
            ]);

            SettingsChangeLogger::logChange(
                $user,
                'appearance',
                'settings.application.appearance',
                ['accent_color' => $user->getOriginal('accent_color')],
                $validated
            );
            ActivityLogger::forUser($user, 'appearance', 'updated', 'Warna/tema diubah', 'Warna aksen/tema diperbarui ke '.$validated['accent_color'].' / '.$validated['theme'], $validated);

            return response()->json(['success' => true, 'message' => 'Tampilan berhasil diperbarui.']);
        })->name('appearance.update');

        Route::get('/notifications', function (Request $request) {
            $user = $request->user();

            return Inertia::render('Settings/Application/Notifications', [
                'emailNotifications' => (bool) $user->email_notifications,
                'pushNotifications' => (bool) $user->push_notifications,
                'vapidPublicKey' => config('services.webpush.vapid_public_key'),
            ]);
        })->name('notifications');
        Route::patch('/notifications', function (Request $request) {
            $validated = $request->validate([
                'email_notifications' => ['required', 'boolean'],
                'push_notifications' => ['required', 'boolean'],
            ]);

            $user = $request->user();
            $user->update([
                'email_notifications' => $validated['email_notifications'],
                'push_notifications' => $validated['push_notifications'],
            ]);

            SettingsChangeLogger::logChange(
                $user,
                'notifications',
                'settings.application.notifications',
                [],
                $validated
            );
            ActivityLogger::forUser($user, 'notifications', 'updated', 'Pengaturan notifikasi diubah', null, $validated);

            return response()->json(['success' => true, 'message' => 'Notifikasi berhasil diperbarui.']);
        })->name('notifications.update');
    });

    // Finance
    Route::prefix('settings/finance')->name('settings.finance.')->group(function () {
        Route::get('/logic', function () {
            return Inertia::render('Settings/Finance/Defaults');
        })->name('logic');
        Route::patch('/logic', function (Request $request) {
            $validated = $request->validate([
                'allow_negative_balance' => ['required', 'boolean'],
            ]);

            $user = $request->user();
            $old = $user->allow_negative_balance;
            $user->update($validated);

            SettingsChangeLogger::logChange(
                $user,
                'finance_defaults',
                'settings.finance.logic',
                ['allow_negative_balance' => $old],
                $validated
            );
            ActivityLogger::forUser($user, 'finance', 'updated', 'Logika transaksi diubah', ($validated['allow_negative_balance'] ? 'Izinkan saldo minus' : 'Tolak saldo minus'), $validated);

            return response()->json(['success' => true, 'message' => 'Logika transaksi diperbarui.']);
        })->name('logic.update');

        Route::get('/budget', fn () => Inertia::render('Settings/Finance/Budget'))->name('budget');
    });

    // Privacy & Data
    Route::prefix('settings/privacy')->name('settings.privacy.')->group(function () {
        Route::get('/settings', fn () => Inertia::render('Settings/Privacy/Settings'))->name('settings');
        Route::patch('/settings', function (Request $request) {
            $validated = $request->validate([
                'data_collection' => ['required', 'boolean'],
                'analytics' => ['required', 'boolean'],
            ]);

            $user = $request->user();
            // Store privacy preferences

            SettingsChangeLogger::logChange(
                $user,
                'privacy',
                'settings.privacy.settings',
                [],
                $validated
            );
            ActivityLogger::forUser($user, 'privacy', 'updated', 'Pengaturan privasi diubah', null, $validated);

            return response()->json(['success' => true, 'message' => 'Privasi berhasil diperbarui.']);
        })->name('settings.update');

        Route::get('/data', fn () => Inertia::render('Settings/Privacy/Data'))->name('data');
        Route::get('/danger', fn () => Inertia::render('Settings/Privacy/Danger'))->name('danger');
        Route::get('/logs', [PrivacyLogController::class, 'index'])->name('logs');
    });

    // API helpers used by frontend Settings (cache clear, account delete, account export)
    Route::post('/api/cache/clear', function (Request $request) {
        // In many hosted/dev environments cache clearing from web can cause DB or permission errors.
        // Return a clear, actionable response instead of attempting operations that may fail.
        return response()->json([
            'success' => false,
            'message' => 'Cache clearing via web disabled. Run "php artisan cache:clear" on the server or change the cache driver to a non-database store.',
        ], 501);
    })->name('api.cache.clear');

    Route::post('/api/account/delete', function (Request $request) {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        try {
            // Invalidate session and logout first
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            // Attempt deletion (soft-delete if model supports it)
            $user->delete();
        } catch (Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Account deleted']);
    })->name('api.account.delete');

    Route::post('/api/account/export', function (Request $request) {
        $user = $request->user();
        if (! $user) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 401);
        }

        // Collect user data for export - keep limited to non-sensitive fields
        $export = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'created_at' => $user->created_at?->toDateTimeString(),
            ],
            'wallets' => DB::table('wallets')->where('user_id', $user->id)->get(),
            'categories' => DB::table('categories')->where('user_id', $user->id)->get(),
            'transactions' => DB::table('transactions')->where('user_id', $user->id)->limit(1000)->get(),
        ];

        $json = json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);

        return response($json, 200, [
            'Content-Type' => 'application/json',
            'Content-Disposition' => 'attachment; filename="bendaharaku-export.json"',
        ]);
    })->name('api.account.export');

    // System
    Route::prefix('settings/system')->name('settings.system.')->group(function () {
        Route::get('/about', function () {
            $composerJson = json_decode(file_get_contents(base_path('composer.json')), true);
            $appVersion = $composerJson['version']
                ?? config('app.version')
                ?? '1.0.0';

            return Inertia::render('Settings/System/About', [
                'appVersion' => $appVersion,
            ]);
        })->name('about');
    });

    // AI Settings - Restructured Hierarchy
    Route::prefix('settings/ai')->name('settings.ai.')->group(function () {
        Route::get('/models', [AiSettingsController::class, 'index'])->name('models');
        Route::get('/bot', function (Request $request) {
            $user = $request->user();

            return Inertia::render('Settings/AI/Bot', [
                'botName' => $user->bot_name ?? 'Ken-Chan',
                'botAvatar' => $user->bot_avatar
                    ? asset('storage/'.$user->bot_avatar)
                    : null,
            ]);
        })->name('bot');
        Route::get('/memory', fn () => Inertia::render('Settings/AI/Memory'))->name('memory');
        Route::get('/memory/manage', [AiSettingsController::class, 'memories'])->name('memory.manage');
        Route::get('/memory/{id}', [AiSettingsController::class, 'memoryLogs'])->name('memory.detail');
        Route::get('/integrations', fn () => Inertia::render('Settings/AI/Integration'))->name('integrations');

        // Legacy endpoints (for backward compatibility)
        Route::get('/', fn () => redirect()->route('settings.ai.models'))->name('index');
        Route::patch('/models', [AiSettingsController::class, 'store'])->name('store');
        Route::post('/test', [AiSettingsController::class, 'testConnection'])->name('test');
        Route::get('/integration', fn () => Inertia::render('Settings/AI/Integration'))->name('integration');

        // API untuk Dashboard (not in settings, but for reference)
        Route::get('/api/dashboard', [AiAnalyticsController::class, 'dashboard'])->name('api.dashboard');
        Route::get('/api/feedback', [AiAnalyticsController::class, 'feedback'])->name('api.feedback');
    });

    // Alias route untuk AiAnalytics.vue full-page (/settings/ai/analytics)
    Route::prefix('api/ai/analytics')->name('api.ai.analytics.')->group(function () {
        Route::get('/dashboard', [AiAnalyticsController::class, 'dashboard'])->name('dashboard');
        Route::get('/feedback', [AiAnalyticsController::class, 'feedback'])->name('feedback');
    });

    // Recent settings changes (for Settings Home widget)
    Route::get('/settings/recent-changes', function (Request $request) {
        $userId = $request->user()->id;
        $rows = DB::table('user_settings_changes')
            ->where('user_id', $userId)
            ->orderByDesc('changed_at')
            ->limit(10)
            ->get();

        return response()->json($rows);
    })->name('settings.recent_changes');

    // ── Chat ──────────────────────────────────────────────────────
    Route::prefix('chat')->name('chat.')->group(function () {
        Route::get('/', [WebChatController::class, 'index'])->name('index');
        Route::post('/message', [WebChatController::class, 'sendMessage'])->name('message');
        Route::get('/message/{id}/status', [WebChatController::class, 'messageStatus'])->name('message.status');
        Route::get('/history', [WebChatController::class, 'history'])->name('history');
        Route::get('/commands', [WebChatController::class, 'commands'])->name('commands');
        Route::get('/wallets', [WebChatController::class, 'wallets'])->name('wallets');
        Route::get('/transaction/{id}/status', [WebChatController::class, 'transactionStatus'])->name('transaction.status');
        Route::patch('/transaction/{id}/confirm', [WebChatController::class, 'confirmTransaction'])->name('transaction.confirm');
        Route::patch('/transaction/{id}/wallet', [WebChatController::class, 'assignWallet'])->name('transaction.assign-wallet');
        Route::delete('/transaction/{id}/cancel', [WebChatController::class, 'cancelTransaction'])->name('transaction.cancel');
        // Draft-specific route: status check berdasarkan draft ID
        Route::get('/draft/{id}/status', [WebChatController::class, 'draftStatus'])->name('draft.status');

        // ── Evidence (OCR Upload & Review) ────────────────────────
        // Upload gambar bukti transaksi, trigger OCR pipeline
        Route::post('/evidence', [EvidenceController::class, 'store'])->name('evidence.store');
        // Web Share Target (PWA) — share foto dari Galeri / app Bank
        Route::post('/evidence/share', [EvidenceController::class, 'share'])->name('evidence.share');
        Route::get('/evidence/share', fn () => redirect()->route('chat.index'))->name('evidence.share.get');
        // Serve private image untuk Chat (foto harus tampil, bukan text [Evidence])
        Route::get('/evidence/{uuid}/image', [EvidenceController::class, 'image'])->name('evidence.image');
        // Retry grouping LLM jika gagal (server LLM down) — chat turun seperti kirim lagi
        Route::post('/evidence/{uuid}/retry', [EvidenceController::class, 'retry'])->name('evidence.retry');

        // Review & edit draft hasil OCR
        Route::get('/evidence/{uuid}/draft', [EvidenceReviewController::class, 'show'])->name('evidence.draft.show');
        Route::patch('/evidence/{uuid}/draft', [EvidenceReviewController::class, 'update'])->name('evidence.draft.update');

        // Commit draft menjadi transaksi nyata
        Route::post('/evidence/{uuid}/commit', [EvidenceReviewController::class, 'commit'])->name('evidence.commit');

        // Debug & monitoring pipeline
        Route::get('/evidence/{uuid}/timeline', [EvidenceDebugController::class, 'timeline'])->name('evidence.timeline');
        Route::get('/evidence/stats', [EvidenceDebugController::class, 'stats'])->name('evidence.stats');
        Route::get('/evidence/health', [EvidenceDebugController::class, 'health'])->name('evidence.health');
    });

    // ── Chat Bot Profile settings ─────────────────────────────────
    Route::prefix('settings/chat')->name('settings.chat.')->group(function () {
        Route::get('/bot-profile', [ChatBotProfileController::class, 'show'])->name('bot-profile');
        Route::patch('/bot-profile', [ChatBotProfileController::class, 'update'])->name('bot-profile.update');
        Route::delete('/bot-avatar', [ChatBotProfileController::class, 'destroyAvatar'])->name('bot-avatar.destroy');
    });

    // ── Global search ─────────────────────────────────────────────
    Route::get('/search', [SearchController::class, 'index'])->name('search.page');
    Route::get('/api/search', [SearchController::class, 'search'])->name('search.global');

    // ── Budgeting API (session auth, sama seperti Chat) ─────────────
    Route::prefix('api/v1/budget')->name('budget.')->group(function () {
        Route::get('/settings', [BudgetController::class, 'settings'])->name('settings');
        Route::post('/settings', [BudgetController::class, 'updateSettings'])->name('settings.update');
        Route::post('/generate', [BudgetController::class, 'generate'])->name('generate');
        Route::get('/generate/status', [BudgetController::class, 'generationStatus'])->name('generate.status');
        Route::get('/{year}/{month}', [BudgetController::class, 'show'])->name('show');
        Route::put('/{budgetGroup}', [BudgetController::class, 'update'])->name('update');
    });

    // ── Redirects for backward compatibility ───────────────────────
    Route::get('/settings/chat/bot-profile', fn () => redirect('/settings/ai/bot', 301));

    // ── Web Push Notifications ──────────────────────────────────────
    Route::post('/notifications/subscribe', [PushNotificationController::class, 'subscribe'])->name('notifications.subscribe');
    Route::post('/notifications/unsubscribe', [PushNotificationController::class, 'unsubscribe'])->name('notifications.unsubscribe');
    Route::post('/notifications/presence', [PushNotificationController::class, 'presence'])->name('notifications.presence');

    // Resources CRUD
    Route::patch('wallets/{wallet}/set-pin', [WalletController::class, 'setPin'])->name('wallets.set-pin');
    Route::resource('wallets', WalletController::class);
    Route::resource('categories', CategoryController::class);
    Route::patch('transactions/{transaction}/confirm', [TransactionController::class, 'confirm'])->name('transactions.confirm');
    Route::resource('transactions', TransactionController::class);
    Route::get('/loans/{type}', [LoanController::class, 'index'])->name('loans.index');
});

// PWA — Digital Asset Links for TWA (env-based SHA256, fallback to file)
Route::get('/.well-known/assetlinks.json', function () {
    $envSha = env('ANDROID_ASSETLINKS_SHA256') ?: config('services.twa.sha256');
    $path = public_path('.well-known/assetlinks.json');

    if ($envSha && $envSha !== 'REPLACE_WITH_SHA256_FROM_KEYSTORE') {
        return response()->json([
            [
                'relation' => ['delegate_permission/common.handle_all_urls'],
                'target' => [
                    'namespace' => 'android_app',
                    'package_name' => config('services.twa.package', 'id.bendaharaku.twa'),
                    'sha256_cert_fingerprints' => [$envSha],
                ],
            ],
        ]);
    }

    if (! file_exists($path)) {
        return response()->json([], 404);
    }

    return response()->file($path, ['Content-Type' => 'application/json']);
});

// Health check
Route::get('/health', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'laravel',
        'time' => now()->toIso8601String(),
    ]);
});

Route::get('/ready', function () {
    return response()->json([
        'status' => 'ok',
        'service' => 'laravel',
        'time' => now()->toIso8601String(),
    ]);
});

require __DIR__.'/auth.php';
