<?php

namespace Database\Seeders;

use App\Models\Team;
use App\Models\User;
use App\Models\Project;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        // Get roles to assign
        $adminRole = Role::where('name', 'admin')->first();
        $hrRole = Role::where('name', 'hr')->first();
        $pmRole = Role::where('name', 'project-manager')->first();
        $teamLeadRole = Role::where('name', 'team-lead')->first();
        $employeeRole = Role::where('name', 'employee')->first();

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
        $employeeUser1 = User::factory()->create(['name' => 'Employee User', 'email' => 'employee@example.com', 'designation' => 'Software Engineer', 'parent_id' => $leadUser->id]);
        $employeeUser1->assignRole($employeeRole);
        
        $employeeUser2 = User::factory()->create(['name' => 'Jane Doe', 'email' => 'jane@example.com', 'designation' => 'Frontend Developer', 'parent_id' => $leadUser->id]);
        $employeeUser2->assignRole($employeeRole);

        // Create Team
        $devTeam = Team::firstOrCreate(['name' => 'Core Development Team', 'team_lead_id' => $leadUser->id]);
        $devTeam->members()->sync([$leadUser->id, $employeeUser1->id, $employeeUser2->id]);

        // Create a Project and attach members
        $projectAlpha = Project::factory()->create([
            'name' => 'Project Alpha',
            'project_manager_id' => $pmUser->id,
            'team_id' => $devTeam->id,
            'status' => 'in-progress',
        ]);
        $projectAlpha->members()->attach([$pmUser->id, $leadUser->id, $employeeUser1->id]);
    }
}