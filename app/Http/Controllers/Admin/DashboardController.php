<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $stats = [
            'total_users' => User::count(),
            'active_users' => User::where('email_verified_at', '!=', null)->count(),
            'total_transactions' => Transaction::count(),
            'transactions_7d' => Transaction::where('created_at', '>=', now()->subDays(7))->count(),
            'transactions_30d' => Transaction::where('created_at', '>=', now()->subDays(30))->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'admin_user' => $request->user(),
        ]);
    }
}
