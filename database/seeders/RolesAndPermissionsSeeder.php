<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Create roles
        $owner = Role::firstOrCreate(
            ['name' => 'owner'],
            ['guard_name' => 'web']
        );
        $admin = Role::firstOrCreate(
            ['name' => 'admin'],
            ['guard_name' => 'web']
        );
        $support = Role::firstOrCreate(
            ['name' => 'support'],
            ['guard_name' => 'web']
        );

        // Create permissions
        $permissions = [
            'admin.dashboard.view',
            'users.view',
            'users.update_status',
            'users.view_financial',
            'transactions.view_metadata',
            'transactions.view_sensitive',
            'support.manage',
            'ai.providers.view',
            'ai.providers.update',
            'ai.secrets.manage',
            'ai.logs.view',
            'audit_logs.view',
            'admin.roles.manage',
            'system.settings.update',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(
                ['name' => $permission],
                ['guard_name' => 'web']
            );
        }

        // Assign permissions to roles
        // Owner: all permissions
        $owner->syncPermissions(Permission::all());

        // Admin: everything except roles & secrets
        $admin->syncPermissions(
            Permission::whereNotIn('name', [
                'admin.roles.manage',
                'ai.secrets.manage',
                'system.settings.update',
            ])->get()
        );

        // Support: limited permissions
        $support->syncPermissions([
            'users.view',
            'users.update_status',
            'transactions.view_metadata',
            'support.manage',
            'ai.logs.view',
        ]);

        $this->command->info('✓ Roles and permissions seeded successfully');
    }
}
