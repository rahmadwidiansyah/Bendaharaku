<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AdminAuditLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AdminTestDataSeeder extends Seeder
{
    public function run(): void
    {
        // Create test users with different roles
        $owner = User::firstOrCreate(
            ['email' => 'owner@bendaharaku.test'],
            [
                'name' => 'Test Owner',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $owner->assignRole('owner');

        $admin = User::firstOrCreate(
            ['email' => 'admin@bendaharaku.test'],
            [
                'name' => 'Test Admin',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $admin->assignRole('admin');

        $support = User::firstOrCreate(
            ['email' => 'support@bendaharaku.test'],
            [
                'name' => 'Test Support',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
            ]
        );
        $support->assignRole('support');

        // Create regular test users
        for ($i = 1; $i <= 10; $i++) {
            User::firstOrCreate(
                ['email' => "user{$i}@test.com"],
                [
                    'name' => "Test User {$i}",
                    'password' => Hash::make('password'),
                    'email_verified_at' => $i % 3 === 0 ? null : now()->subDays(rand(1, 30)),
                    'status' => $i % 5 === 0 ? 'suspended' : 'active',
                ]
            );
        }

        // Create test transaction logs
        $users = User::whereDoesntHave('roles')->limit(5)->get();
        foreach ($users as $user) {
            for ($i = 0; $i < rand(3, 10); $i++) {
                DB::table('transaction_logs')->insert([
                    'user_id' => $user->id,
                    'type' => ['income', 'expense', 'transfer'][rand(0, 2)],
                    'amount' => rand(10000, 500000),
                    'description' => 'Test transaction ' . ($i + 1),
                    'created_at' => now()->subDays(rand(0, 30)),
                    'updated_at' => now()->subDays(rand(0, 30)),
                ]);
            }
        }

        // Create test audit logs
        $adminUsers = [$owner, $admin, $support];
        $actions = [
            'user_status_changed',
            'support_search',
            'support_view_user',
            'support_access_logged',
        ];

        foreach ($adminUsers as $adminUser) {
            for ($i = 0; $i < rand(5, 15); $i++) {
                $action = $actions[array_rand($actions)];
                $targetUser = User::whereDoesntHave('roles')->inRandomOrder()->first();

                AdminAuditLog::create([
                    'admin_id' => $adminUser->id,
                    'action' => $action,
                    'target_type' => 'User',
                    'target_id' => $targetUser?->id,
                    'old_values' => $action === 'user_status_changed' ? ['status' => 'active'] : null,
                    'new_values' => $action === 'user_status_changed' ? ['status' => 'suspended'] : null,
                    'metadata' => [
                        'ip' => '127.0.0.1',
                        'user_agent' => 'Mozilla/5.0 Test',
                        'reason' => $action === 'support_access_logged' ? 'User reported issue' : null,
                    ],
                    'success' => rand(0, 10) > 1, // 90% success rate
                    'created_at' => now()->subDays(rand(0, 30))->subHours(rand(0, 23)),
                ]);
            }
        }

        // Create test activity logs (for AI logs simulation) - only if table exists
        if (DB::getSchemaBuilder()->hasTable('activity_logs')) {
            foreach ($users as $user) {
                for ($i = 0; $i < rand(2, 5); $i++) {
                    DB::table('activity_logs')->insert([
                        'log_name' => 'ai',
                        'description' => 'AI transaction parsing',
                        'subject_type' => 'User',
                        'subject_id' => $user->id,
                        'causer_type' => 'User',
                        'causer_id' => $user->id,
                        'properties' => json_encode([
                            'provider' => ['openai', 'anthropic', 'google'][rand(0, 2)],
                            'model' => 'gpt-4',
                            'tokens' => rand(100, 1000),
                        ]),
                        'created_at' => now()->subDays(rand(0, 30)),
                        'updated_at' => now()->subDays(rand(0, 30)),
                    ]);
                }
            }
            $this->command->info('✓ Created test AI activity logs');
        } else {
            $this->command->warn('⊘ Skipped AI logs (activity_logs table not found)');
        }

        $this->command->info('✓ Created test admin users: owner@bendaharaku.test, admin@bendaharaku.test, support@bendaharaku.test');
        $this->command->info('✓ Created 10 regular test users');
        $this->command->info('✓ Created test transactions');
        $this->command->info('✓ Created test audit logs');
        $this->command->info('Password for all test users: password');
    }
}
