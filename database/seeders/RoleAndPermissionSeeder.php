<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create permissions
        $permissions = [
            // Project permissions
            'create projects',
            'edit projects',
            'delete projects',
            'view projects',
            'manage project settings',
            
            // Task permissions
            'create tasks',
            'edit tasks',
            'delete tasks',
            'view tasks',
            'assign tasks',
            
            // User management permissions
            'manage users',
            'invite users',
            'assign roles',
            
            // Comment permissions
            'create comments',
            'edit own comments',
            'delete own comments',
            'delete any comments',
            
            // System permissions
            'view analytics',
            'manage system settings',
        ];

        foreach ($permissions as $permission) {
            Permission::create(['name' => $permission]);
        }

        // Create roles and assign permissions
        
        // Admin role - has all permissions
        $adminRole = Role::create(['name' => 'Admin']);
        $adminRole->givePermissionTo(Permission::all());
        
        // Project Manager role - can manage projects and tasks
        $pmRole = Role::create(['name' => 'Project Manager']);
        $pmRole->givePermissionTo([
            'create projects',
            'edit projects',
            'view projects',
            'manage project settings',
            'create tasks',
            'edit tasks',
            'view tasks',
            'assign tasks',
            'create comments',
            'edit own comments',
            'delete own comments',
            'invite users',
            'view analytics',
        ]);
        
        // Developer role - can work with tasks and comments
        $devRole = Role::create(['name' => 'Developer']);
        $devRole->givePermissionTo([
            'view projects',
            'view tasks',
            'edit tasks', // can edit assigned tasks
            'create comments',
            'edit own comments',
            'delete own comments',
        ]);
    }
}
