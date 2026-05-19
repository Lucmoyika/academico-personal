<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [
            'courses.view',
            'courses.edit',
            'courses.delete',
            'enrollments.view',
            'enrollments.edit',
            'enrollments.delete',
            'attendance.view',
            'attendance.edit',
            'evaluation.edit',
            'evaluation.view',
            'reports.view',
            'calendars.view',
            'hr.view',
            'hr.manage',
            'student.edit',
            'comments.edit',
            'leads.manage',
        ];

        foreach ($permissions as $permissionName) {
            Permission::firstOrCreate(['name' => $permissionName]);
        }

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        $secretaryRole = Role::firstOrCreate(['name' => 'secretary']);
        $secretaryRole->syncPermissions([
            'calendars.view',
            'evaluation.view',
            'attendance.view',
            'attendance.edit',
            'enrollments.view',
            'enrollments.edit',
            'courses.view',
            'leads.manage',
        ]);

        Role::firstOrCreate(['name' => 'viewer']);
    }
}
