<?php

declare(strict_types=1);

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\AiParseLog;
use App\Models\ChatMessage;
use App\Models\TransactionLog;
use App\Models\UserActivityLog;
use App\Models\UserAiMemoryLog;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class PrivacyLogController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        // Ensure log strings follow the user's language preference (not just app default)
        if (! empty($user->locale) && in_array($user->locale, ['id', 'en'], true)) {
            app()->setLocale($user->locale);
        }
        $filter = $request->input('filter', 'all');
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 20;

        // Build filtered items for pagination (honor current filter)
        $items = $this->buildItems($user, $filter);

        // Build unfiltered (all) items with same window/limits for stable stats — pill angka tidak berubah saat ganti filter
        $allItems = $filter === 'all' ? $items : $this->buildItems($user, 'all');

        // Sort filtered items by created_at desc
        $sorted = $items->sortByDesc(function ($item) {
            $ts = $item['created_at'];
            if ($ts instanceof Carbon) {
                return $ts->timestamp;
            }
            if (is_string($ts)) {
                return Carbon::parse($ts)->timestamp;
            }

            return 0;
        })->values();

        $total = $sorted->count();
        $totalPages = (int) ceil($total / $perPage);
        $offset = ($page - 1) * $perPage;
        $paginated = $sorted->slice($offset, $perPage)->values();

        // Stats dari window yang sama (limit 150+100 tiap sumber) agar konsisten dengan list; tidak pakai hitungan tanpa limit yang bikin angka melonjak
        $stats = [
            'all' => $allItems->count(),
            'settings' => $allItems->whereIn('type', ['settings', 'appearance', 'preferences', 'notifications', 'privacy', 'finance'])->count(),
            'memory' => $allItems->where('type', 'memory')->count(),
            'transaction' => $allItems->where('type', 'transaction')->count(),
            'ai' => $allItems->where('type', 'ai')->count(),
            'chat' => $allItems->where('type', 'chat')->count(),
            'wallet' => $allItems->where('type', 'wallet')->count(),
            'category' => $allItems->where('type', 'category')->count(),
        ];

        return Inertia::render('Settings/Privacy/Logs', [
            'logs' => $paginated,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'total_pages' => $totalPages,
                'has_more' => $page < $totalPages,
            ],
            'filters' => [
                'filter' => $filter,
            ],
            'stats' => $stats,
        ]);
    }

    private function userLocale($user): string
    {
        $loc = $user->locale ?? app()->getLocale();

        return $loc === 'en' ? 'en' : 'id';
    }

    private function settingsTitle(?string $page, ?string $key): string
    {
        // fallback is locale-aware; page slug itself stays as key (e.g. Application > Appearance)
        $isEn = false; // page label is technical, keep same; but fallback text needs locale
        // we determine locale via request? use app locale for fallback
        $locale = app()->getLocale() === 'en' ? 'en' : 'id';
        if ($page) {
            $label = str_replace(['settings.', '.'], ['', ' > '], $page);

            return ucwords(str_replace(['_', '-'], ' ', $label));
        }
        if ($key) {
            return ucwords(str_replace(['_', '-'], ' ', $key));
        }

        return $locale === 'en' ? 'Settings Changed' : 'Perubahan Pengaturan';
    }

    private function settingsDescription(object $row): string
    {
        // Use current app locale so EN users see English description; row itself is stored language-agnostic
        $isEn = app()->getLocale() === 'en';
        $old = $row->old_value;
        $new = $row->new_value;
        if ($old !== null && $new !== null) {
            return $isEn
                ? "Changed from \"{$this->trimValue($old)}\" to \"{$this->trimValue($new)}\""
                : "Diubah dari \"{$this->trimValue($old)}\" ke \"{$this->trimValue($new)}\"";
        }
        if ($new !== null) {
            return $isEn
                ? "New value: \"{$this->trimValue($new)}\""
                : "Nilai baru: \"{$this->trimValue($new)}\"";
        }

        return $isEn ? 'Settings updated' : 'Pengaturan diperbarui';
    }

    private function trimValue(?string $v): string
    {
        if ($v === null) {
            return '-';
        }
        $decoded = json_decode($v, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return json_encode($decoded, JSON_UNESCAPED_UNICODE);
        }

        return mb_strimwidth($v, 0, 80, '...');
    }

    private function memoryTitle(string $action, ?string $keyword): string
    {
        $isEn = app()->getLocale() === 'en';
        $kw = $keyword ? "\"{$keyword}\"" : 'memory';
        if ($isEn) {
            return match ($action) {
                'CREATED' => "New memory {$kw}",
                'REWARDED' => "Memory reinforced {$kw}",
                'REBUILT' => "Memory rebuilt {$kw}",
                'PRUNED' => "Memory removed {$kw}",
                default => "Memory {$action} {$kw}",
            };
        }

        return match ($action) {
            'CREATED' => "Memory baru {$kw}",
            'REWARDED' => "Memory diperkuat {$kw}",
            'REBUILT' => "Memory rebuilt {$kw}",
            'PRUNED' => "Memory dihapus {$kw}",
            default => "Memory {$action} {$kw}",
        };
    }

    private function buildItems($user, string $filter)
    {
        $items = collect();

        // 0. Unified activity logs (limit 150, same window as legacy)
        $activityQuery = UserActivityLog::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(150);
        if ($filter !== 'all') {
            $typeMap = [
                'settings' => ['settings', 'appearance', 'preferences', 'notifications', 'privacy', 'finance'],
                'wallet' => ['wallet'],
                'category' => ['category'],
                'transaction' => ['transaction'],
                'memory' => ['memory'],
                'ai' => ['ai'],
                'chat' => ['chat'],
            ];
            $allowed = $typeMap[$filter] ?? [$filter];
            $activityQuery->whereIn('type', $allowed);
        }
        foreach ($activityQuery->get() as $act) {
            $items->push([
                'id' => 'activity-'.$act->id,
                'type' => $act->type,
                'type_label' => $this->activityTypeLabel($act->type),
                'title' => $act->title,
                'description' => $act->description ?? '-',
                'created_at' => $act->created_at,
                'created_at_human' => $act->created_at?->diffForHumans() ?? '-',
                'created_at_raw' => $act->created_at?->toIso8601String(),
                'icon' => $this->activityIcon($act->type),
                'color' => $this->activityColor($act->type, $act->action),
                'metadata' => $act->metadata,
            ]);
        }

        // 1. Settings changes (legacy)
        if (in_array($filter, ['all', 'settings'], true)) {
            $rows = DB::table('user_settings_changes')->where('user_id', $user->id)->orderByDesc('changed_at')->limit(100)->get();
            $isEnSettings = app()->getLocale() === 'en';
            foreach ($rows as $r) {
                $items->push([
                    'id' => 'settings-'.$r->id,
                    'type' => 'settings',
                    'type_label' => $this->activityTypeLabel('settings'),
                    'title' => $this->settingsTitle($r->setting_page, $r->setting_key),
                    'description' => $this->settingsDescription($r),
                    'created_at' => $r->changed_at,
                    'created_at_human' => Carbon::parse($r->changed_at)->diffForHumans(),
                    'created_at_raw' => Carbon::parse($r->changed_at)->toIso8601String(),
                    'icon' => 'Settings',
                    'color' => 'blue',
                    'metadata' => ['setting_key' => $r->setting_key, 'setting_page' => $r->setting_page, 'old_value' => $r->old_value, 'new_value' => $r->new_value],
                ]);
            }
        }

        // 2. Memory logs
        if (in_array($filter, ['all', 'memory'], true)) {
            $logs = UserAiMemoryLog::where('user_id', $user->id)->orderByDesc('created_at')->limit(100)->get();
            foreach ($logs as $log) {
                $items->push([
                    'id' => 'memory-'.$log->id,
                    'type' => 'memory',
                    'type_label' => $this->activityTypeLabel('memory'),
                    'title' => $this->memoryTitle($log->action, $log->memory_keyword),
                    'description' => $log->reason ?? $log->action,
                    'created_at' => $log->created_at,
                    'created_at_human' => $log->created_at?->diffForHumans() ?? '-',
                    'created_at_raw' => $log->created_at?->toIso8601String(),
                    'icon' => 'Database',
                    'color' => match ($log->action) {
                        'CREATED' => 'green', 'REWARDED' => 'violet', 'REBUILT' => 'blue', 'PRUNED' => 'red', default => 'gray'
                    },
                    'metadata' => ['action' => $log->action, 'memory_keyword' => $log->memory_keyword, 'raw_subject' => $log->raw_subject, 'source' => $log->source, 'old_weight' => $log->old_weight, 'new_weight' => $log->new_weight, 'transaction_id' => $log->transaction_id],
                ]);
            }
        }

        // 3. Transaction logs
        if (in_array($filter, ['all', 'transaction'], true)) {
            $isEnTrx = app()->getLocale() === 'en';
            $trxLogs = TransactionLog::where('user_id', $user->id)->with(['category:id,category_name', 'sourceWallet:id,name', 'destinationWallet:id,name'])->orderByDesc('created_at')->limit(100)->get();
            foreach ($trxLogs as $trx) {
                $isDeleted = $trx->trashed();
                $fallbackCat = $isEnTrx ? 'Transaction' : 'Transaksi';
                $items->push([
                    'id' => 'trx-'.$trx->id,
                    'type' => 'transaction',
                    'type_label' => $isDeleted ? ($isEnTrx ? 'Transaction (deleted)' : 'Transaksi (dihapus)') : $this->activityTypeLabel('transaction'),
                    'title' => ($trx->subject && $trx->subject !== '-') ? $trx->subject : ($trx->category?->category_name ?? $fallbackCat),
                    'description' => $this->transactionDescription($trx),
                    'created_at' => $trx->created_at,
                    'created_at_human' => $trx->created_at?->diffForHumans() ?? '-',
                    'created_at_raw' => $trx->created_at?->toIso8601String(),
                    'icon' => 'Wallet',
                    'color' => $isDeleted ? 'red' : 'emerald',
                    'metadata' => ['amount' => (float) $trx->amount, 'category' => $trx->category?->category_name, 'source_wallet' => $trx->sourceWallet?->name, 'destination_wallet' => $trx->destinationWallet?->name, 'date' => $trx->date?->toDateString(), 'reference' => $trx->reference_number, 'is_cleared' => $trx->is_cleared],
                ]);
            }
        }

        // 4. AI parse logs
        if (in_array($filter, ['all', 'ai'], true)) {
            $aiLogs = AiParseLog::where('user_id', $user->id)->orderByDesc('created_at')->limit(100)->get();
            foreach ($aiLogs as $log) {
                $status = strtolower((string) ($log->status ?? ($log->is_success ? 'executed' : 'failed')));
                $items->push([
                    'id' => 'ai-'.$log->id,
                    'type' => 'ai',
                    'type_label' => 'AI Parse',
                    'title' => mb_strimwidth($log->input_text ?? '-', 0, 60, '...'),
                    'description' => 'Provider: '.strtoupper($log->provider ?? '-').' | Status: '.$status,
                    'created_at' => $log->created_at,
                    'created_at_human' => $log->created_at?->diffForHumans() ?? '-',
                    'created_at_raw' => $log->created_at?->toIso8601String(),
                    'icon' => 'Zap',
                    'color' => $log->is_success ? 'indigo' : 'red',
                    'metadata' => ['provider' => $log->provider, 'status' => $status, 'confidence' => $log->final_confidence, 'error' => $log->error_message],
                ]);
            }
        }

        // 5. Chat messages
        if (in_array($filter, ['all', 'chat'], true)) {
            $isEnChat = app()->getLocale() === 'en';
            $chatRows = ChatMessage::whereHas('conversation', fn ($q) => $q->where('user_id', $user->id))->where('role', 'user')->orderByDesc('created_at')->limit(50)->get();
            foreach ($chatRows as $msg) {
                $preview = $msg->textPreview() ?: ($msg->raw_text ?? '-');
                $items->push([
                    'id' => 'chat-'.$msg->id,
                    'type' => 'chat',
                    'type_label' => $this->activityTypeLabel('chat'),
                    'title' => mb_strimwidth($preview, 0, 60, '...'),
                    'description' => $isEnChat ? 'Chat message sent' : 'Pesan chat dikirim',
                    'created_at' => $msg->created_at,
                    'created_at_human' => $msg->created_at?->diffForHumans() ?? '-',
                    'created_at_raw' => $msg->created_at?->toIso8601String(),
                    'icon' => 'MessageCircle',
                    'color' => 'cyan',
                    'metadata' => ['conversation_id' => $msg->conversation_id, 'status' => $msg->status ?? null],
                ]);
            }
        }

        return $items;
    }

    private function transactionDescription(TransactionLog $trx): string
    {
        $cat = $trx->category?->category_name ?? '-';
        $amt = number_format((float) $trx->amount, 0, ',', '.');
        $date = $trx->date?->format('d M Y') ?? '-';

        return "Rp {$amt} • {$cat} • {$date}";
    }

    private function activityTypeLabel(string $type): string
    {
        $isEn = app()->getLocale() === 'en';
        if ($isEn) {
            return match ($type) {
                'transaction' => 'Transaction',
                'wallet' => 'Wallet',
                'category' => 'Category',
                'settings' => 'Settings',
                'appearance' => 'Appearance',
                'preferences' => 'Preferences',
                'memory' => 'AI Memory',
                'ai' => 'AI',
                'chat' => 'Chat',
                'privacy' => 'Privacy',
                'finance' => 'Finance',
                default => ucfirst($type),
            };
        }

        return match ($type) {
            'transaction' => 'Transaksi',
            'wallet' => 'Dompet',
            'category' => 'Kategori',
            'settings' => 'Pengaturan',
            'appearance' => 'Tampilan',
            'preferences' => 'Preferensi',
            'memory' => 'Memory AI',
            'ai' => 'AI',
            'chat' => 'Chat',
            'privacy' => 'Privasi',
            'finance' => 'Keuangan',
            default => ucfirst($type),
        };
    }

    private function activityIcon(string $type): string
    {
        return match ($type) {
            'transaction' => 'Wallet',
            'wallet' => 'Wallet',
            'category' => 'Tag',
            'memory' => 'Database',
            'ai' => 'Zap',
            'chat' => 'MessageCircle',
            default => 'Settings',
        };
    }

    private function activityColor(string $type, string $action): string
    {
        if (str_contains($action, 'delete') || $action === 'PRUNED') {
            return 'red';
        }

        return match ($type) {
            'transaction' => 'emerald',
            'wallet' => 'teal',
            'category' => 'amber',
            'memory' => str_contains($action, 'CREATED') ? 'green' : 'violet',
            'ai' => 'indigo',
            'chat' => 'cyan',
            'settings', 'appearance', 'preferences' => 'blue',
            default => 'gray',
        };
    }
}
