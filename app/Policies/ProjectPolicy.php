<?php

namespace App\Policies;

use App\Models\Project;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

class ProjectPolicy
{
    use HandlesAuthorization;

    /**
     * This method runs before any other method in the policy.
     * If it returns true, the user is automatically authorized.
     * This is the perfect place for an admin/super-user check.
     */
    public function before(User $user, string $ability): ?bool
    {
        // Users with 'manage employees' permission can do anything related to projects.
        if ($user->can('manage employees')) {
            return true;
        }

        return null; // Return null to let the other policy methods run.
    }

    /**
     * Determine whether the user can view any projects (the index page).
     */
    public function viewAny(User $user): bool
    {
        // Any logged-in user can visit the project list page.
        // The controller will correctly scope the list of projects they see.
        return true;
    }

    /**
     * Determine whether the user can view a specific project's details.
     * This is the corrected logic.
     */
    public function view(User $user, Project $project): bool
    {
        // Rule 1: The assigned Project Manager can view the project.
        if ($user->id === $project->project_manager_id) {
            return true;
        }

        // We must check if the project has a team before accessing its properties.
        if ($project->team) {
            // Rule 2: The Team Lead of the assigned team can view the project.
            if ($user->id === $project->team->team_lead_id) {
                return true;
            }
            // Rule 3: Any member of the assigned team can view the project.
            if ($project->team->members->contains($user->id)) {
                return true;
            }
        }

        // If none of the specific rules match, deny access.
        return false;
    }

    /**
     * Determine whether the user can create projects.
     */
    public function create(User $user): bool
    {
        // Only users who can assign projects should be able to create them.
        return $user->can('assign projects');
    }

    /**
     * Determine whether the user can update the project.
     */
    public function update(User $user, Project $project): bool
    {
        // For now, only the Project Manager can update.
        // The `before` method already allows Admins.
        return $user->id === $project->project_manager_id;
    }

    // You can add other policy methods like delete(), restore(), etc. later.
}
