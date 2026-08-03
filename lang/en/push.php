<?php

declare(strict_types=1);

/**
 * Translation keys untuk Web Push Notifications — English.
 */
return [

    'months' => [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ],

    'chat' => [
        'reply_failed' => 'Your chat reply failed to process. Try again later.',
    ],

    'budget' => [
        'created_title' => 'Budget ready',
        'created_body' => 'Your :month budget is ready. Check it out!',
        'failed_title' => 'Budget generation failed',
        'failed_body' => 'Your :month budget failed to generate. Try again from the Budgeting page.',
        'over_title' => 'Budget exceeded',
        'over_body' => 'Your ":group" budget has been exceeded this month.',
    ],

    'loan' => [
        'day_before_title' => 'Due tomorrow',
        'day_before_body' => ':subject (:type) is due tomorrow — :amount remaining.',
        'due_title' => 'Due today',
        'due_body' => ':subject (:type) is due today — :amount remaining.',
        'upcoming_title' => 'Due soon',
        'upcoming_body' => ':subject (:type) is due in :days days — :amount remaining.',
        'overdue_title' => 'Past due',
        'overdue_body' => ':subject (:type) is :days days past due — :amount remaining.',
        'type_debt' => 'debt',
        'type_receivable' => 'receivable',
    ],
];
