<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app('cache')->forget('spatie.permission.cache');
        app('cache')->forget('spatie.rolePermissions');
        \Illuminate\Support\Facades\Cache::forget('spatie.permission.cache');

        // Define all permissions
        $permissions = [
            // Dashboard
            'admin.dashboard.view',
            // User management
            'users.view',
            'users.update_status',
            'users.view_financial',
            // Transaction monitoring
            'transactions.view_metadata',
            'transactions.view_sensitive',
            // Support tools
            'support.manage',
            // AI operations
            'ai.providers.view',
            'ai.providers.update',
            'ai.secrets.manage',
            'ai.logs.view',
            // Audit & admin
            'audit_logs.view',
            'admin.roles.manage',
            'system.settings.update',
        ];

        // Create permissions
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission, 'guard_name' => 'web']);
        }

        // Create roles
        $ownerRole = Role::firstOrCreate(['name' => 'owner', 'guard_name' => 'web']);
        $adminRole = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $supportRole = Role::firstOrCreate(['name' => 'support', 'guard_name' => 'web']);

        // Owner: all permissions
        $ownerRole->syncPermissions($permissions);

        // Admin: most permissions except secrets, roles, critical settings
        $adminPermissions = [
            'admin.dashboard.view',
            'users.view',
            'users.update_status',
            'users.view_financial',
            'transactions.view_metadata',
            'transactions.view_sensitive',
            'support.manage',
            'ai.providers.view',
            'ai.providers.update',
            'ai.logs.view',
            'audit_logs.view',
        ];
        $adminRole->syncPermissions($adminPermissions);

        // Support: limited permissions for support tasks
        $supportPermissions = [
            'admin.dashboard.view',
            'users.view',
            'users.update_status',
            'transactions.view_metadata',
            'support.manage',
            'ai.logs.view',
        ];
        $supportRole->syncPermissions($supportPermissions);
    }
}
