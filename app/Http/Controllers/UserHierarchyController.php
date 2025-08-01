<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class UserHierarchyController extends Controller
{
    public function index()
    {
        $currentUser = Auth::user();

        // --- 1. Determine the scope of users to show ---
        $usersToShow = new Collection;

        if ($currentUser->hasRole(['admin', 'hr'])) {
            // Admins and HR still see the full company hierarchy.
            $usersToShow = User::all();
        } else {
            // --- NEW "LOCAL CONTEXT" LOGIC ---

            // Start with the current user.
            $userIdsToShow = [$currentUser->id];

            // 1. Get the Manager's ID
            if ($currentUser->parent_id) {
                $userIdsToShow[] = $currentUser->parent_id;
            }

            // 2. Get Peer IDs (others who report to the same manager)
            if ($currentUser->parent_id) {
                $peerIds = User::where('parent_id', $currentUser->parent_id)
                    ->pluck('id') // Pluck just the IDs
                    ->all();
                $userIdsToShow = array_merge($userIdsToShow, $peerIds);
            }

            // 3. Get Direct Report IDs (people who report to the current user)
            $reportIds = User::where('parent_id', $currentUser->id)
                ->pluck('id')
                ->all();
            $userIdsToShow = array_merge($userIdsToShow, $reportIds);

            // 4. Remove duplicates and get the final user models.
            $uniqueIds = array_unique($userIdsToShow);
            $usersToShow = User::whereIn('id', $uniqueIds)->get();

            // --- END OF NEW LOGIC ---
        }

        // --- 2. Format the user data for the chart ---
        // This part remains the same, as it correctly formats any collection of users.
        $reportingNodes = $this->formatUsersForBalkanGraph($usersToShow)->toArray();

        return Inertia::render('Hierarchy/CompanyHierarchy', [
            'reportingNodes' => $reportingNodes,
            'leaveFlowNodes' => [], // Leave flow can be handled separately
        ]);
    }

    /**
     * Formats users for the BalkanGraph library and handles profile images.
     */
    private function formatUsersForBalkanGraph(Collection $users)
    {
        return $users->map(function ($user) {
            $title = $user->designation ?? 'Employee';
            $tags = ['employee-node'];

            // Tag the logged-in user to highlight them (optional but cool)
            if ($user->id === Auth::id()) {
                $tags[] = 'is-logged-in-user';
            }

            // A simple way to tag the top person in the current view
            if (is_null($user->parent_id)) {
                $tags[] = 'ceo';
            }

            // Check for a real image, otherwise use a placeholder
            $imageUrl = $user->image
                ? Storage::url($user->image)
                : 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&background=random&color=fff';

            return [
                'id' => $user->id,
                'pid' => $user->parent_id,
                'name' => $user->name,
                'title' => $title,
                'image' => $imageUrl,
                'tags' => $tags,
            ];
        });
    }
}
