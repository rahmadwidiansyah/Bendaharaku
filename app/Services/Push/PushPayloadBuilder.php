<?php

namespace App\Services\Push;

use App\Models\BudgetGroup;
use App\Models\User;
use App\Support\MoneyFormatter;
use Illuminate\Support\Carbon;

/**
 * PushPayloadBuilder — membangun array payload notifikasi yang sudah dilokalisasi.
 *
 * Hanya menyusun data (title/body/url/tag) — TIDAK mengirim.
 * Bahasa mengikuti user->locale (id/en) via lang/push.php.
 */
class PushPayloadBuilder
{
    public static function chatReplyReady(User $user, string $preview): array
    {
        return [
            'title' => $user->botDisplayName,
            'body' => mb_substr(trim($preview), 0, (int) config('bendaharaku.push.chat_preview_length', 60)),
            'url' => '/chat',
            'tag' => 'chat-reply',
            'data' => ['kind' => 'chat'],
        ];
    }

    public static function chatReplyFailed(User $user): array
    {
        return [
            'title' => $user->botDisplayName,
            'body' => self::t('push.chat.reply_failed', $user),
            'url' => '/chat',
            'tag' => 'chat-reply',
            'data' => ['kind' => 'chat'],
        ];
    }

    public static function budgetCreated(User $user, int $month, int $year): array
    {
        return [
            'title' => self::t('push.budget.created_title', $user),
            'body' => self::t('push.budget.created_body', $user, ['month' => self::monthName($month, $user)]),
            'url' => '/budgeting',
            'tag' => 'budget-created',
            'data' => ['kind' => 'budget', 'year' => $year, 'month' => $month],
        ];
    }

    public static function budgetGenerationFailed(User $user, int $month, int $year): array
    {
        return [
            'title' => self::t('push.budget.failed_title', $user),
            'body' => self::t('push.budget.failed_body', $user, ['month' => self::monthName($month, $user)]),
            'url' => '/budgeting',
            'tag' => 'budget-failed',
            'data' => ['kind' => 'budget', 'year' => $year, 'month' => $month],
        ];
    }

    public static function budgetOver(User $user, BudgetGroup $group): array
    {
        return [
            'title' => self::t('push.budget.over_title', $user),
            'body' => self::t('push.budget.over_body', $user, ['group' => $group->expenseGroups->first()?->group_name ?? $group->generated_by]),
            'url' => '/budgeting',
            'tag' => 'budget-over',
            'data' => ['kind' => 'budget'],
        ];
    }

    public static function loanReminder(
        User $user,
        string $loanType,
        string $reminderType,
        string $subject,
        float $balance,
        int $daysUntilDue = 0,
    ): array {
        $typeName = self::t('push.loan.type_'.$loanType, $user);

        $titleKey = $messageKey = null;
        $replace = [
            'subject' => $subject,
            'type' => $typeName,
            'amount' => MoneyFormatter::rupiah(abs($balance)),
        ];

        switch ($reminderType) {
            case 'day_before':
                $titleKey = 'push.loan.day_before_title';
                $messageKey = 'push.loan.day_before_body';
                break;

            case 'due_date':
                $titleKey = 'push.loan.due_title';
                $messageKey = 'push.loan.due_body';
                break;

            case 'overdue':
                $replace['days'] = abs($daysUntilDue);
                $titleKey = 'push.loan.overdue_title';
                $messageKey = 'push.loan.overdue_body';
                break;

            default:
                $replace['days'] = max(0, $daysUntilDue);
                $titleKey = 'push.loan.upcoming_title';
                $messageKey = 'push.loan.upcoming_body';
                break;
        }

        return [
            'title' => self::t($titleKey, $user),
            'body' => self::t($messageKey, $user, $replace),
            'url' => $loanType === 'debt' ? '/loans/hutang' : '/loans/piutang',
            'tag' => 'loan-'.$loanType,
            'data' => ['kind' => 'loan', 'loan_type' => $loanType],
        ];
    }

    private static function t(string $key, User $user, array $replace = []): string
    {
        return __($key, $replace, $user->locale ?? 'id');
    }

    private static function monthName(int $month, User $user): string
    {
        $months = __('push.months', [], $user->locale ?? 'id');
        $index = max(0, min(11, $month - 1));

        return is_array($months) ? $months[$index] : Carbon::createFromDate(null, $month, 1)->format('F');
    }
}
