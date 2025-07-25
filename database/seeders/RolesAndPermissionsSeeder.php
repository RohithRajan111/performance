<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Project; // <-- Make sure this is imported at the top
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Permissions
        $permissions = [
            'log working hours', 'apply for leave', 'assign tasks', 'view team progress',
            'assign projects', 'view all projects progress', 'view all working hours',
            'manage leave applications', 'manage employees', 'manage roles', 'view leaves'
        ];
        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Create Roles and Assign Permissions
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $employeeRole->givePermissionTo(['log working hours', 'apply for leave']);

        $teamLeadRole = Role::firstOrCreate(['name' => 'team-lead']);
        $teamLeadRole->givePermissionTo([
            'assign tasks', 'view team progress', 'log working hours', 'apply for leave', 'view leaves',
        ]);
        $teamLeadRole->revokePermissionTo('manage leave applications');

        $pmRole = Role::firstOrCreate(['name' => 'project-manager']);
        $pmRole->givePermissionTo(['assign projects', 'view all projects progress']);

        $hrRole = Role::firstOrCreate(['name' => 'hr']);
        $hrRole->givePermissionTo([
            'view all working hours', 'manage leave applications', 'manage employees',
            'manage roles', 'apply for leave', 'view leaves',
        ]);

        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->givePermissionTo(Permission::all());

        // Create Users with Designations (Order matters for parent_id)

        // 1. Admin
        $adminUser = User::factory()->create(['name' => 'Admin User', 'email' => 'admin@example.com', 'designation' => 'System Administrator']);
        $adminUser->assignRole($adminRole);

        // 2. HR
        $hrUser = User::factory()->create(['name' => 'HR Manager', 'email' => 'hr@example.com', 'designation' => 'Human Resources', 'parent_id' => $adminUser->id]);
        $hrUser->assignRole($hrRole);

        // 3. Project Manager
        $pmUser = User::factory()->create(['name' => 'PM User', 'email' => 'pm@example.com', 'designation' => 'Project Manager', 'parent_id' => $adminUser->id]);
        $pmUser->assignRole($pmRole);

        // 4. Team Lead
        $leadUser = User::factory()->create(['name' => 'Team Lead User', 'email' => 'lead@example.com', 'designation' => 'Lead Developer', 'parent_id' => $pmUser->id]);
        $leadUser->assignRole($teamLeadRole);

        // 5. Employees
        $employeeUser = User::factory()->create(['name' => 'Employee User', 'email' => 'employee@example.com', 'designation' => 'Software Engineer', 'parent_id' => $leadUser->id]);
        $employeeUser->assignRole($employeeRole);
        $employeeUser2 = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'designation' => 'Frontend Developer', 'parent_id' => $leadUser->id]);
        $employeeUser2->assignRole($employeeRole);

        // Create Team
        $devTeam = Team::firstOrCreate(['name' => 'Core Development Team', 'team_lead_id' => $leadUser->id]);
        $devTeam->members()->sync([$leadUser->id, $employeeUser->id, $employeeUser2->id]);

        // --- THIS IS THE MOST IMPORTANT BLOCK ---

        // Create a Project
        $projectAlpha = Project::factory()->create([
            'name' => 'Project Alpha',
            'project_manager_id' => $pmUser->id,
            'team_id' => $devTeam->id,
            'status' => 'in-progress',
            'end_date' => now()->addMonths(3),
        ]);

        // **CRUCIAL**: Attach members to the project.
        // This is the step that adds rows to your `project_user` pivot table.
        // Without this, the Team Lead is NOT a member of any project.
        $projectAlpha->members()->attach([
            $pmUser->id,       // The PM is a member
            $leadUser->id,     // **This makes the Team Lead a member**
            $employeeUser->id, // The Employee is a member
        ]);
    }
}